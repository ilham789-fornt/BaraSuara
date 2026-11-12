<?php
/**
 * READ: detail pesanan milik user — instruksi pembayaran QRIS (jika pending)
 * atau daftar e-ticket dengan QR code (jika lunas).
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

$user = require_login();
expire_stale_orders();

$code = (string) ($_GET['code'] ?? '');
$stmt = db()->prepare(
    'SELECT o.*, TIMESTAMPDIFF(SECOND, NOW(), o.created_at + INTERVAL ' . ORDER_HOLD_MINUTES . ' MINUTE) AS seconds_left
     FROM orders o WHERE o.order_code = ? AND o.user_id = ?'
);
$stmt->execute([$code, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Pesanan tidak ditemukan.');
    redirect('my-orders.php');
}

$ticketStmt = db()->prepare(
    'SELECT ot.*, tt.nama_gelombang, tt.price, t.kota, t.venue, t.tanggal_konser, t.nama_tur
     FROM order_tickets ot
     JOIN ticket_tiers tt ON tt.id = ot.tier_id
     JOIN tours t ON t.id = tt.tour_id
     WHERE ot.order_id = ? ORDER BY ot.id'
);
$ticketStmt->execute([$order['id']]);
$tickets = $ticketStmt->fetchAll();
$first = $tickets[0];

[$statusLabel, $statusClass] = payment_status_badge($order['payment_status']);
$isPending = $order['payment_status'] === 'pending';
$isPaid = $order['payment_status'] === 'paid';

// Payload QR simulasi (pada produksi diganti string QRIS dinamis dari payment gateway).
$qrisPayload = 'QRIS-SIMULASI|BARASUARA|' . $order['order_code'] . '|' . (int) $order['total_amount'];

$pageTitle = 'Pesanan ' . $order['order_code'];
$activePage = 'my-orders';
$extraScripts = '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>'
    . '<script src="lib/order-status.js" defer></script>';
require __DIR__ . '/includes/layout_header.php';
?>

<main style="padding-bottom: 60px;">
    <section class="section fade-in" aria-labelledby="status-heading">
        <div class="status-wrapper">
            <a href="my-orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Tiket Saya</a>

            <div class="order-panel">
                <div class="status-head">
                    <div>
                        <p class="muted-label">Kode Pesanan</p>
                        <h2 id="status-heading" class="order-code"><?= e($order['order_code']) ?></h2>
                    </div>
                    <span class="badge badge-lg <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                </div>

                <dl class="order-detail-grid">
                    <div><dt>Konser</dt><dd><?= e($first['nama_tur']) ?> &mdash; <?= e($first['kota']) ?></dd></div>
                    <div><dt>Tanggal</dt><dd><?= e(tanggal_id($first['tanggal_konser'])) ?></dd></div>
                    <div><dt>Venue</dt><dd><?= e($first['venue']) ?></dd></div>
                    <div><dt>Gelombang</dt><dd><?= e($first['nama_gelombang']) ?> (<?= rupiah($first['price']) ?>)</dd></div>
                    <div><dt>Jumlah Tiket</dt><dd><?= (int) $order['total_tickets'] ?> tiket</dd></div>
                    <div><dt>Total Bayar</dt><dd class="text-accent"><?= rupiah($order['total_amount']) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($order['email']) ?></dd></div>
                    <div><dt>WhatsApp</dt><dd><?= e($order['no_whatsapp']) ?></dd></div>
                    <div><dt>Dipesan</dt><dd><?= e(tanggal_id($order['created_at'], true)) ?></dd></div>
                    <?php if ($isPaid && $order['paid_at']): ?>
                        <div><dt>Dibayar</dt><dd><?= e(tanggal_id($order['paid_at'], true)) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <?php if ($isPending): ?>
                <!-- Pembayaran QRIS -->
                <div class="order-panel qris-panel">
                    <h3 class="panel-title"><i class="fas fa-qrcode"></i> Bayar dengan QRIS</h3>
                    <div class="qris-box">
                        <div class="qris-code" id="qris-code" data-payload="<?= e($qrisPayload) ?>"
                            aria-label="Kode QRIS pembayaran"></div>
                        <div class="qris-info">
                            <p class="muted-label">Total Pembayaran</p>
                            <p class="qris-amount"><?= rupiah($order['total_amount']) ?></p>
                            <p class="muted-label">Selesaikan dalam</p>
                            <p class="countdown" id="countdown" data-seconds="<?= max(0, (int) $order['seconds_left']) ?>">--:--</p>
                            <ol class="qris-steps">
                                <li>Buka aplikasi m-banking atau e-wallet Anda.</li>
                                <li>Pilih menu <strong>Scan QR / QRIS</strong>.</li>
                                <li>Pindai kode di samping dan pastikan nominal sesuai.</li>
                                <li>Konfirmasi pembayaran, lalu klik tombol di bawah.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="notice-demo">
                        <i class="fas fa-flask"></i>
                        Mode demo: QR di atas adalah simulasi. Klik <strong>"Saya Sudah Bayar"</strong> untuk
                        mensimulasikan pembayaran berhasil.
                    </div>

                    <div class="status-actions">
                        <form action="order-action.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="code" value="<?= e($order['order_code']) ?>">
                            <input type="hidden" name="action" value="pay">
                            <button type="submit" class="btn"><i class="fas fa-check"></i> Saya Sudah Bayar</button>
                        </form>
                        <form action="order-action.php" method="post"
                            onsubmit="return confirm('Batalkan pesanan ini? Kuota tiket akan dilepas.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="code" value="<?= e($order['order_code']) ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-outline"><i class="fas fa-times"></i> Batalkan Pesanan</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($isPaid): ?>
                <!-- E-ticket -->
                <div class="ticket-list-header">
                    <h3>E-Ticket (<?= count($tickets) ?>)</h3>
                    <button type="button" class="btn-sm btn-outline" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak / Simpan PDF</button>
                </div>
                <div class="eticket-list">
                    <?php foreach ($tickets as $i => $ticket): ?>
                        <article class="eticket">
                            <div class="eticket-main">
                                <p class="eticket-brand">BARASUARA &middot; <?= e($ticket['nama_tur']) ?></p>
                                <h4><?= e($ticket['kota']) ?></h4>
                                <p class="eticket-meta"><i class="fas fa-calendar-alt"></i>
                                    <?= e(tanggal_id($ticket['tanggal_konser'])) ?></p>
                                <p class="eticket-meta"><i class="fas fa-map-marker-alt"></i> <?= e($ticket['venue']) ?></p>
                                <div class="eticket-holder">
                                    <span class="muted-label">Pemegang Tiket</span>
                                    <strong><?= e($ticket['holder_name']) ?></strong>
                                </div>
                                <span class="badge badge-info"><?= e($ticket['nama_gelombang']) ?> &middot; Tiket #<?= $i + 1 ?></span>
                                <?php if ($ticket['is_checked_in']): ?>
                                    <span class="badge badge-muted">Sudah Check-in</span>
                                <?php endif; ?>
                            </div>
                            <div class="eticket-stub">
                                <div class="ticket-qr" data-payload="<?= e($ticket['ticket_code']) ?>"
                                    aria-label="QR code tiket <?= e($ticket['ticket_code']) ?>"></div>
                                <code><?= e($ticket['ticket_code']) ?></code>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="summary-note text-center">Tunjukkan QR code dan identitas sesuai nama pemegang tiket di pintu masuk.</p>
            <?php else: ?>
                <div class="order-panel text-center">
                    <p style="color: #aaa; margin: 0 0 15px;">Pesanan ini <?= e(strtolower($statusLabel)) ?> dan kuota tiket
                        telah dilepas.</p>
                    <a href="tour.php" class="btn"><i class="fas fa-ticket-alt"></i> Pesan Ulang</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

