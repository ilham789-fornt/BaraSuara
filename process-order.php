<?php
/**
 * CREATE: memproses formulir pemesanan tiket.
 * Seluruh pengecekan kuota dilakukan di dalam transaksi dengan row lock (SELECT ... FOR UPDATE)
 * pada baris ticket_tiers agar tidak terjadi oversold saat banyak user memesan bersamaan.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tour.php');
}

$user = require_login();
verify_csrf();
expire_stale_orders();

$tourId = (int) ($_POST['tour_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 0);
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$whatsapp = preg_replace('/[\s\-]/', '', (string) ($_POST['no_whatsapp'] ?? ''));
$holders = array_values(array_map(
    static fn($name) => trim(preg_replace('/\s+/', ' ', (string) $name)),
    is_array($_POST['holders'] ?? null) ? $_POST['holders'] : []
));
$paymentMethod = (string) ($_POST['payment_method'] ?? '');

$backToForm = 'order.php?tour=' . $tourId;

$tour = $tourId > 0 ? find_tour($tourId) : null;
if (!$tour) {
    flash('error', 'Jadwal konser tidak ditemukan.');
    redirect('tour.php');
}

/* --- Validasi input --- */
$errors = [];
if ($quantity < 1) {
    $errors[] = 'Jumlah tiket minimal 1.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    $errors[] = 'Email tidak valid.';
}
if (!preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $whatsapp)) {
    $errors[] = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';
}
if (count($holders) !== $quantity) {
    $errors[] = 'Jumlah nama pemegang tiket harus sama dengan jumlah tiket.';
}
foreach ($holders as $i => $name) {
    if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
        $errors[] = 'Nama pemegang tiket #' . ($i + 1) . ' minimal 3 karakter.';
    }
}
if ($paymentMethod !== PAYMENT_METHOD) {
    $errors[] = 'Metode pembayaran tidak valid.';
}
if (empty($_POST['agree'])) {
    $errors[] = 'Anda harus menyetujui syarat & ketentuan.';
}

$failWith = static function (array $errors) use ($backToForm, $quantity, $email, $whatsapp, $holders): never {
    $_SESSION['order_errors'] = $errors;
    $_SESSION['order_old'] = [
        'quantity'    => $quantity,
        'email'       => $email,
        'no_whatsapp' => $whatsapp,
        'holders'     => $holders,
    ];
    redirect($backToForm);
};

if ($errors) {
    $failWith($errors);
}

/* --- Transaksi pembuatan pesanan --- */
$pdo = db();
try {
    $pdo->beginTransaction();

    // Kunci tour & tier untuk tur ini selama transaksi berlangsung.
    $tourLock = $pdo->prepare('SELECT * FROM tours WHERE id = ? FOR UPDATE');
    $tourLock->execute([$tourId]);
    $tour = $tourLock->fetch();

    $tierStmt = $pdo->prepare(
        'SELECT t.*, ' . sold_count_sql('t') . ' AS sold
         FROM ticket_tiers t WHERE t.tour_id = ? ORDER BY t.waktu_mulai ASC FOR UPDATE'
    );
    $tierStmt->execute([$tourId]);
    $tiers = [];
    foreach ($tierStmt->fetchAll() as $row) {
        $row['remaining'] = max(0, (int) $row['kuota'] - (int) $row['sold']);
        $row['status'] = tier_status($row);
        $tiers[] = $row;
    }

    $sale = tour_sale_state($tour, $tiers);
    if ($sale['state'] !== 'on_sale') {
        $pdo->rollBack();
        flash('error', 'Maaf, penjualan tiket untuk konser ini baru saja ditutup atau habis.');
        redirect('tour.php');
    }

    $tier = $sale['tier'];
    $alreadyHeld = user_tickets_in_tier((int) $user['id'], (int) $tier['id']);
    $allowed = (int) $tier['max_per_order'] - $alreadyHeld;

    if ($quantity > $allowed) {
        $pdo->rollBack();
        $failWith([$allowed > 0
            ? 'Anda hanya dapat memesan maksimal ' . $allowed . ' tiket lagi untuk gelombang ini.'
            : 'Anda sudah mencapai batas tiket untuk gelombang ini.']);
    }
    if ($quantity > (int) $tier['remaining']) {
        $pdo->rollBack();
        $failWith(['Sisa kuota tidak mencukupi. Tersisa ' . (int) $tier['remaining'] . ' tiket.']);
    }

    $orderCode = generate_code('BRS');
    $total = (float) $tier['price'] * $quantity;

    $orderStmt = $pdo->prepare(
        "INSERT INTO orders (order_code, user_id, total_amount, total_tickets, email, no_whatsapp, metode_pembayaran, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $orderStmt->execute([$orderCode, $user['id'], $total, $quantity, $email, $whatsapp, PAYMENT_METHOD]);
    $orderId = (int) $pdo->lastInsertId();

    $ticketStmt = $pdo->prepare(
        'INSERT INTO order_tickets (order_id, tier_id, ticket_code, holder_name) VALUES (?, ?, ?, ?)'
    );
    foreach ($holders as $holderName) {
        $ticketCode = 'TKT-' . $tour['tour_code'] . '-' . strtoupper(bin2hex(random_bytes(5)));
        $ticketStmt->execute([$orderId, $tier['id'], $ticketCode, $holderName]);
    }

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[process-order] ' . $ex->getMessage());
    $failWith(['Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.']);
}

flash('success', 'Pesanan ' . $orderCode . ' berhasil dibuat. Silakan selesaikan pembayaran via QRIS.');
redirect('order-status.php?code=' . urlencode($orderCode));

