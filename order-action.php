<?php
/**
 * UPDATE / "DELETE" (soft): aksi user terhadap pesanan pending miliknya.
 *  - action=pay    : simulasi pembayaran QRIS berhasil -> payment_status = 'paid'
 *  - action=cancel : batalkan pesanan -> payment_status = 'failed' (kuota otomatis kembali)
 *
 * Pesanan tidak dihapus permanen agar riwayat transaksi tetap tersimpan.
 * Pada produksi, aksi 'pay' digantikan oleh webhook/callback dari payment gateway QRIS.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my-orders.php');
}

$user = require_login();
verify_csrf();
expire_stale_orders();

$code = (string) ($_POST['code'] ?? '');
$action = (string) ($_POST['action'] ?? '');
$back = 'order-status.php?code=' . urlencode($code);

if ($action === 'pay') {
    $stmt = db()->prepare(
        "UPDATE orders SET payment_status = 'paid', paid_at = NOW()
         WHERE order_code = ? AND user_id = ? AND payment_status = 'pending'"
    );
    $stmt->execute([$code, $user['id']]);
    $stmt->rowCount() > 0
        ? flash('success', 'Pembayaran berhasil! E-ticket Anda sudah terbit.')
        : flash('error', 'Pesanan tidak dapat dibayar (mungkin sudah kedaluwarsa atau dibatalkan).');
} elseif ($action === 'cancel') {
    $stmt = db()->prepare(
        "UPDATE orders SET payment_status = 'failed'
         WHERE order_code = ? AND user_id = ? AND payment_status = 'pending'"
    );
    $stmt->execute([$code, $user['id']]);
    $stmt->rowCount() > 0
        ? flash('success', 'Pesanan dibatalkan.')
        : flash('error', 'Pesanan tidak dapat dibatalkan.');
} else {
    flash('error', 'Aksi tidak dikenal.');
}

redirect($back);

