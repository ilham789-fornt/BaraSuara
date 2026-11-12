<?php
/**
 * Logika domain ticketing: status gelombang, kuota tersisa, dan masa tahan pesanan.
 *
 * Aturan utama:
 *  - User TIDAK memilih gelombang. Gelombang aktif ditentukan otomatis dari jadwal
 *    (waktu_mulai <= sekarang <= waktu_berakhir), is_active = 1, dan kuota masih ada.
 *  - Kuota terpakai = tiket dari pesanan 'paid' + 'pending' yang belum lewat masa tahan.
 *  - Pesanan 'pending' yang lewat ORDER_HOLD_MINUTES otomatis menjadi 'expired'
 *    sehingga kuotanya kembali tersedia.
 */

const ORDER_HOLD_MINUTES = 15;
const PAYMENT_METHOD = 'QRIS';

/** Tandai pesanan pending yang melewati batas waktu pembayaran sebagai expired. */
function expire_stale_orders(): void
{
    db()->exec(
        "UPDATE orders SET payment_status = 'expired'
         WHERE payment_status = 'pending'
           AND created_at < (NOW() - INTERVAL " . ORDER_HOLD_MINUTES . " MINUTE)"
    );
}

/** Subquery jumlah tiket terpakai per tier (paid + pending yang masih berlaku). */
function sold_count_sql(string $tierAlias = 't'): string
{
    return "(SELECT COUNT(*) FROM order_tickets ot
             JOIN orders o ON o.id = ot.order_id
             WHERE ot.tier_id = {$tierAlias}.id
               AND o.payment_status IN ('pending', 'paid'))";
}

/**
 * Hitung status satu gelombang tiket.
 * Return: active | upcoming | ended | sold_out | inactive
 */
function tier_status(array $tier, ?int $now = null): string
{
    $now ??= time();
    if (!(int) $tier['is_active']) {
        return 'inactive';
    }
    if ($now < strtotime($tier['waktu_mulai'])) {
        return 'upcoming';
    }
    if ($now > strtotime($tier['waktu_berakhir'])) {
        return 'ended';
    }
    if ((int) $tier['remaining'] <= 0) {
        return 'sold_out';
    }
    return 'active';
}

/** Ambil semua gelombang untuk satu tur beserta sisa kuota & status. */
function get_tour_tiers(int $tourId): array
{
    $stmt = db()->prepare(
        'SELECT t.*, ' . sold_count_sql('t') . ' AS sold
         FROM ticket_tiers t
         WHERE t.tour_id = ?
         ORDER BY t.waktu_mulai ASC'
    );
    $stmt->execute([$tourId]);

    $tiers = [];
    foreach ($stmt->fetchAll() as $tier) {
        $tier['remaining'] = max(0, (int) $tier['kuota'] - (int) $tier['sold']);
        $tier['status'] = tier_status($tier);
        $tiers[] = $tier;
    }
    return $tiers;
}

/** Gelombang yang sedang dijual untuk tur, atau null. */
function find_active_tier(array $tiers): ?array
{
    foreach ($tiers as $tier) {
        if ($tier['status'] === 'active') {
            return $tier;
        }
    }
    return null;
}

/** Gelombang berikutnya yang akan dibuka (untuk info "Segera Dibuka"). */
function find_upcoming_tier(array $tiers): ?array
{
    foreach ($tiers as $tier) {
        if ($tier['status'] === 'upcoming') {
            return $tier;
        }
    }
    return null;
}

/**
 * Ringkasan status penjualan sebuah tur untuk ditampilkan di kartu tur.
 * Return: ['state' => on_sale|coming_soon|sold_out|closed|finished, 'tier' => ?array]
 */
function tour_sale_state(array $tour, array $tiers): array
{
    if (strtotime($tour['tanggal_konser'] . ' 23:59:59') < time()) {
        return ['state' => 'finished', 'tier' => null];
    }
    if (!(int) $tour['status_event']) {
        return ['state' => 'sold_out', 'tier' => null];
    }
    if ($active = find_active_tier($tiers)) {
        return ['state' => 'on_sale', 'tier' => $active];
    }
    if ($upcoming = find_upcoming_tier($tiers)) {
        return ['state' => 'coming_soon', 'tier' => $upcoming];
    }
    foreach ($tiers as $tier) {
        if ($tier['status'] === 'sold_out') {
            return ['state' => 'sold_out', 'tier' => null];
        }
    }
    return ['state' => 'closed', 'tier' => null];
}

/** Jumlah tiket yang sudah dipegang user di satu gelombang (paid + pending). */
function user_tickets_in_tier(int $userId, int $tierId): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM order_tickets ot
         JOIN orders o ON o.id = ot.order_id
         WHERE o.user_id = ? AND ot.tier_id = ?
           AND o.payment_status IN ('pending', 'paid')"
    );
    $stmt->execute([$userId, $tierId]);
    return (int) $stmt->fetchColumn();
}

/** Ambil data tur berdasarkan ID. */
function find_tour(int $tourId): ?array
{
    $stmt = db()->prepare('SELECT * FROM tours WHERE id = ?');
    $stmt->execute([$tourId]);
    return $stmt->fetch() ?: null;
}

/** Label & kelas CSS untuk status pembayaran pesanan. */
function payment_status_badge(string $status): array
{
    return match ($status) {
        'paid'    => ['Lunas', 'badge-success'],
        'pending' => ['Menunggu Pembayaran', 'badge-warning'],
        'expired' => ['Kedaluwarsa', 'badge-muted'],
        'failed'  => ['Dibatalkan', 'badge-danger'],
        default   => [ucfirst($status), 'badge-muted'],
    };
}

/** Label untuk status gelombang tiket. */
function tier_status_label(array $tier): array
{
    return match ($tier['status']) {
        'active'   => ['Sedang Berlangsung', 'badge-success'],
        'upcoming' => ['Mulai ' . tanggal_id($tier['waktu_mulai']), 'badge-info'],
        'ended'    => ['Berakhir', 'badge-muted'],
        'sold_out' => ['Habis', 'badge-danger'],
        default    => ['Tidak Tersedia', 'badge-muted'],
    };
}

/** Generate kode unik, contoh: BRS-261108-7F3A9C */
function generate_code(string $prefix, int $bytes = 3): string
{
    return $prefix . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes($bytes)));
}

