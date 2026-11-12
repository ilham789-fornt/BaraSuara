<?php
/**
 * Gate Scanner Panitia (dapat diakses oleh role `admin` maupun `gate_scanner`).
 * Memvalidasi kode e-ticket penonton dan meng-update `order_tickets.is_checked_in`.
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin', 'gate_scanner']);
$pdo = db();

$searchCode = strtoupper(trim((string) ($_GET['code'] ?? $_POST['ticket_code'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

    if ($action === 'checkin' && $ticketId > 0) {
        $stmt = $pdo->prepare(
            "UPDATE order_tickets ot
             JOIN orders o ON o.id = ot.order_id
             SET ot.is_checked_in = 1, ot.checked_in_at = NOW(), ot.scanned_by = ?
             WHERE ot.id = ? AND o.payment_status = 'paid' AND ot.is_checked_in = 0"
        );
        $stmt->execute([(int) $admin['id'], $ticketId]);
        $stmt->rowCount() > 0
            ? flash('success', 'Check-In berhasil! Penonton diizinkan masuk ke venue.')
            : flash('error', 'Tiket sudah pernah check-in atau pesanan belum lunas.');
        redirect('scan.php?code=' . urlencode($searchCode));
    } elseif ($action === 'reset' && $ticketId > 0) {
        $pdo->prepare('UPDATE order_tickets SET is_checked_in = 0, checked_in_at = NULL, scanned_by = NULL WHERE id = ?')
            ->execute([$ticketId]);
        flash('info', 'Status check-in tiket berhasil di-reset.');
        redirect('scan.php?code=' . urlencode($searchCode));
    }
}

$foundTicket = null;
if ($searchCode !== '') {
    $stmt = $pdo->prepare(
        'SELECT ot.*, o.order_code, o.payment_status, o.email, o.no_whatsapp,
                tt.nama_gelombang, t.kota, t.venue, t.tanggal_konser, t.nama_tur,
                a.username AS scanner_name
         FROM order_tickets ot
         JOIN orders o ON o.id = ot.order_id
         JOIN ticket_tiers tt ON tt.id = ot.tier_id
         JOIN tours t ON t.id = tt.tour_id
         LEFT JOIN admins a ON a.id = ot.scanned_by
         WHERE ot.ticket_code = ? OR o.order_code = ?
         LIMIT 1'
    );
    $stmt->execute([$searchCode, $searchCode]);
    $foundTicket = $stmt->fetch() ?: null;
}

$recentScans = $pdo->query(
    'SELECT ot.ticket_code, ot.holder_name, ot.checked_in_at, t.kota, a.username AS scanner_name
     FROM order_tickets ot
     JOIN ticket_tiers tt ON tt.id = ot.tier_id
     JOIN tours t ON t.id = tt.tour_id
     LEFT JOIN admins a ON a.id = ot.scanned_by
     WHERE ot.is_checked_in = 1
     ORDER BY ot.checked_in_at DESC
     LIMIT 8'
)->fetchAll();

$adminTitle = 'Gate Scanner E-Ticket';
$adminNav = 'scan';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2><i class="fas fa-qrcode"></i> Gate Scanner &mdash; Validasi E-Ticket</h2>
            <p>Masukkan atau pindai kode QR E-Ticket penonton untuk verifikasi pintu masuk venue.</p>
        </div>
    </div>

    <div class="admin-two-col">
        <div class="order-panel">
            <h3 class="panel-title">Pindai / Cari Kode Tiket</h3>
            <form method="get" action="scan.php" class="contact-form">
                <div class="form-group">
                    <label for="code">Kode E-Ticket atau Kode Order</label>
                    <input type="text" id="code" name="code" required autofocus
                        placeholder="Cth: TKT-JKT-01-XXXXXXXXXX" value="<?= e($searchCode) ?>">
                </div>
                <button type="submit" class="btn"><i class="fas fa-search"></i> Verifikasi Tiket</button>
            </form>

            <?php if ($searchCode !== '' && !$foundTicket): ?>
                <div class="flash flash-error" style="margin-top: 20px;">
                    <i class="fas fa-times-circle"></i>
                    <span>Kode tiket <strong><?= e($searchCode) ?></strong> tidak ditemukan di basis data!</span>
                </div>
            <?php elseif ($foundTicket):
                [$pLabel, $pClass] = payment_status_badge($foundTicket['payment_status']);
                ?>
                <div class="scanner-result" style="margin-top: 22px;">
                    <div class="status-head" style="margin-bottom: 12px;">
                        <code><?= e($foundTicket['ticket_code']) ?></code>
                        <span class="badge <?= e($pClass) ?>">Order: <?= e($pLabel) ?></span>
                    </div>

                    <dl class="order-detail-grid">
                        <div><dt>Pemegang Tiket</dt><dd><strong><?= e($foundTicket['holder_name']) ?></strong></dd></div>
                        <div><dt>Konser</dt><dd><?= e($foundTicket['kota']) ?> &mdash; <?= e($foundTicket['venue']) ?></dd></div>
                        <div><dt>Gelombang</dt><dd><?= e($foundTicket['nama_gelombang']) ?></dd></div>
                        <div><dt>Status Pintu Masuk</dt>
                            <dd>
                                <?php if ($foundTicket['is_checked_in']): ?>
                                    <span class="badge badge-warning">SUDAH CHECK-IN (<?= e(tanggal_id($foundTicket['checked_in_at'], true)) ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-success">BELUM CHECK-IN (SIAP MASUK)</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>

                    <div class="status-actions">
                        <?php if ($foundTicket['payment_status'] === 'paid' && !$foundTicket['is_checked_in']): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="checkin">
                                <input type="hidden" name="ticket_id" value="<?= (int) $foundTicket['id'] ?>">
                                <input type="hidden" name="ticket_code" value="<?= e($foundTicket['ticket_code']) ?>">
                                <button type="submit" class="btn"><i class="fas fa-check-circle"></i> Konfirmasi Check-In Sekarang</button>
                            </form>
                        <?php elseif ($foundTicket['is_checked_in']): ?>
                            <form method="post" onsubmit="return confirm('Reset status check-in tiket ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reset">
                                <input type="hidden" name="ticket_id" value="<?= (int) $foundTicket['id'] ?>">
                                <input type="hidden" name="ticket_code" value="<?= e($foundTicket['ticket_code']) ?>">
                                <button type="submit" class="btn-sm btn-outline"><i class="fas fa-undo"></i> Reset Check-In</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-history"></i> Riwayat Check-In Terbaru</h3>
            <?php if (!$recentScans): ?>
                <p class="muted-label">Belum ada penonton yang melakukan check-in.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kode Tiket</th>
                                <th>Penonton &amp; Kota</th>
                                <th>Waktu &amp; Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentScans as $sc): ?>
                                <tr>
                                    <td><a href="scan.php?code=<?= e(urlencode($sc['ticket_code'])) ?>"><code><?= e($sc['ticket_code']) ?></code></a></td>
                                    <td><strong><?= e($sc['holder_name']) ?></strong><br><small class="muted-label"><?= e($sc['kota']) ?></small></td>
                                    <td><small class="muted-label"><?= e(date('d/m/y H:i', strtotime($sc['checked_in_at']))) ?><br>oleh <?= e($sc['scanner_name'] ?? '-') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
