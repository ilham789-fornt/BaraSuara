<?php
/**
 * READ: daftar seluruh pesanan milik user yang sedang login.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

$user = require_login();
expire_stale_orders();

$stmt = db()->prepare(
    'SELECT o.order_code, o.total_amount, o.total_tickets, o.payment_status, o.created_at,
            t.kota, t.venue, t.tanggal_konser, tt.nama_gelombang
     FROM orders o
     JOIN order_tickets ot ON ot.id = (SELECT MIN(id) FROM order_tickets WHERE order_id = o.id)
     JOIN ticket_tiers tt ON tt.id = ot.tier_id
     JOIN tours t ON t.id = tt.tour_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC, o.id DESC'
);
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Tiket Saya';
$activePage = 'my-orders';
require __DIR__ . '/includes/layout_header.php';
?>

<main style="padding-bottom: 60px;">
    <section class="section fade-in" aria-labelledby="myorders-heading">
        <div class="status-wrapper">
            <div class="text-center" style="margin-bottom: 30px;">
                <h2 id="myorders-heading" style="font-size: 2rem;">Tiket Saya</h2>
                <p style="color: #aaa; margin-top: 0;">Halo, <?= e($user['full_name']) ?>. Berikut riwayat pesanan Anda.</p>
            </div>

            <?php if (!$orders): ?>
                <div class="order-panel text-center">
                    <p style="color: #aaa;">Anda belum memiliki pesanan tiket.</p>
                    <a href="tour.php" class="btn"><i class="fas fa-ticket-alt"></i> Lihat Jadwal Tour</a>
                </div>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $o):
                        [$label, $class] = payment_status_badge($o['payment_status']); ?>
                        <a class="order-row" href="order-status.php?code=<?= e(urlencode($o['order_code'])) ?>">
                            <div class="order-row-main">
                                <code><?= e($o['order_code']) ?></code>
                                <strong><?= e($o['kota']) ?> &middot; <?= e(tanggal_id($o['tanggal_konser'])) ?></strong>
                                <span class="muted-label"><?= (int) $o['total_tickets'] ?> tiket <?= e($o['nama_gelombang']) ?>
                                    &middot; dipesan <?= e(tanggal_id($o['created_at'], true)) ?></span>
                            </div>
                            <div class="order-row-side">
                                <span class="badge <?= e($class) ?>"><?= e($label) ?></span>
                                <span class="order-row-total"><?= rupiah($o['total_amount']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

