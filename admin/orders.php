<?php
/**
 * CRUD Tabel `orders` dan `order_tickets` (INSERT, SELECT, UPDATE, DELETE).
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
expire_stale_orders();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'create_order':
                $userId = (int) $_POST['user_id'];
                $tierId = (int) $_POST['tier_id'];
                $qty = max(1, min(10, (int) $_POST['total_tickets']));
                $email = strtolower(trim((string) $_POST['email']));
                $wa = trim((string) $_POST['no_whatsapp']);
                $status = (string) $_POST['payment_status'];
                $holderBase = trim((string) $_POST['holder_name']);

                $tierStmt = $pdo->prepare('SELECT tt.*, t.tour_code FROM ticket_tiers tt JOIN tours t ON t.id = tt.tour_id WHERE tt.id = ?');
                $tierStmt->execute([$tierId]);
                $tierRow = $tierStmt->fetch();
                if (!$tierRow) {
                    throw new RuntimeException('Gelombang tiket tidak ditemukan.');
                }

                $pdo->beginTransaction();
                $orderCode = generate_code('BRS');
                $totalAmount = (float) $tierRow['price'] * $qty;
                $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;

                $insOrder = $pdo->prepare(
                    'INSERT INTO orders (order_code, user_id, total_amount, total_tickets, email, no_whatsapp, metode_pembayaran, payment_status, paid_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insOrder->execute([$orderCode, $userId, $totalAmount, $qty, $email, $wa, 'QRIS', $status, $paidAt]);
                $newOrderId = (int) $pdo->lastInsertId();

                $insTicket = $pdo->prepare(
                    'INSERT INTO order_tickets (order_id, tier_id, ticket_code, holder_name) VALUES (?, ?, ?, ?)'
                );
                for ($i = 1; $i <= $qty; $i++) {
                    $tCode = 'TKT-' . $tierRow['tour_code'] . '-' . strtoupper(bin2hex(random_bytes(5)));
                    $hName = $qty > 1 ? $holderBase . ' #' . $i : $holderBase;
                    $insTicket->execute([$newOrderId, $tierId, $tCode, $hName]);
                }
                $pdo->commit();
                flash('success', 'Pesanan manual ' . $orderCode . ' beserta ' . $qty . ' e-ticket berhasil dibuat.');
                break;

            case 'update_order':
                $status = (string) $_POST['payment_status'];
                $paidSql = $status === 'paid' ? ', paid_at = COALESCE(paid_at, NOW())' : '';
                $stmt = $pdo->prepare(
                    "UPDATE orders
                     SET email = ?, no_whatsapp = ?, payment_status = ?{$paidSql}
                     WHERE id = ?"
                );
                $stmt->execute([
                    strtolower(trim((string) $_POST['email'])),
                    trim((string) $_POST['no_whatsapp']),
                    $status,
                    (int) $_POST['id'],
                ]);
                flash('success', 'Data pesanan berhasil diperbarui.');
                break;

            case 'delete_order':
                $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
                $stmt->execute([(int) $_POST['id']]);
                flash('success', 'Pesanan beserta e-ticket terkait berhasil dihapus.');
                break;

            case 'create_ticket':
                $orderId = (int) $_POST['order_id'];
                $tierId = (int) $_POST['tier_id'];
                $holder = trim((string) $_POST['holder_name']);

                $tStmt = $pdo->prepare('SELECT t.tour_code, tt.price FROM ticket_tiers tt JOIN tours t ON t.id = tt.tour_id WHERE tt.id = ?');
                $tStmt->execute([$tierId]);
                $tRow = $tStmt->fetch();
                if (!$tRow) {
                    throw new RuntimeException('Tier tidak ditemukan.');
                }

                $tCode = 'TKT-' . $tRow['tour_code'] . '-' . strtoupper(bin2hex(random_bytes(5)));
                $pdo->prepare(
                    'INSERT INTO order_tickets (order_id, tier_id, ticket_code, holder_name) VALUES (?, ?, ?, ?)'
                )->execute([$orderId, $tierId, $tCode, $holder]);

                $pdo->prepare(
                    'UPDATE orders SET total_tickets = total_tickets + 1, total_amount = total_amount + ? WHERE id = ?'
                )->execute([(float) $tRow['price'], $orderId]);
                flash('success', 'E-Ticket baru (' . $tCode . ') berhasil ditambahkan.');
                break;

            case 'update_ticket':
                $checked = !empty($_POST['is_checked_in']) ? 1 : 0;
                $stmt = $pdo->prepare(
                    'UPDATE order_tickets
                     SET holder_name = ?,
                         is_checked_in = ?,
                         checked_in_at = CASE WHEN ? = 1 THEN COALESCE(checked_in_at, NOW()) ELSE NULL END,
                         scanned_by = CASE WHEN ? = 1 THEN COALESCE(scanned_by, ?) ELSE NULL END
                     WHERE id = ?'
                );
                $stmt->execute([
                    trim((string) $_POST['holder_name']),
                    $checked,
                    $checked,
                    $checked,
                    (int) $admin['id'],
                    (int) $_POST['id'],
                ]);
                flash('success', 'Data e-ticket berhasil diperbarui.');
                break;

            case 'delete_ticket':
                $stmt = $pdo->prepare('DELETE FROM order_tickets WHERE id = ?');
                $stmt->execute([(int) $_POST['id']]);
                flash('success', 'E-Ticket berhasil dihapus.');
                break;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Operasi gagal: ' . $e->getMessage());
    }

    redirect('orders.php');
}

$orders = $pdo->query(
    'SELECT o.*, u.full_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC'
)->fetchAll();

$tickets = $pdo->query(
    'SELECT ot.*, o.order_code, o.payment_status, tt.nama_gelombang, t.kota, a.username AS scanner_name
     FROM order_tickets ot
     JOIN orders o ON o.id = ot.order_id
     JOIN ticket_tiers tt ON tt.id = ot.tier_id
     JOIN tours t ON t.id = tt.tour_id
     LEFT JOIN admins a ON a.id = ot.scanned_by
     ORDER BY ot.id DESC'
)->fetchAll();

$usersList = $pdo->query('SELECT id, full_name, email FROM users ORDER BY full_name ASC')->fetchAll();
$tiersList = $pdo->query(
    'SELECT tt.id, tt.nama_gelombang, tt.price, t.kota
     FROM ticket_tiers tt JOIN tours t ON t.id = tt.tour_id
     ORDER BY t.tanggal_konser ASC'
)->fetchAll();

$editOrder = null;
if (isset($_GET['edit_order'])) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_order']]);
    $editOrder = $stmt->fetch() ?: null;
}

$editTicket = null;
if (isset($_GET['edit_ticket'])) {
    $stmt = $pdo->prepare('SELECT * FROM order_tickets WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_ticket']]);
    $editTicket = $stmt->fetch() ?: null;
}

$adminTitle = 'Kelola Pesanan & E-Ticket';
$adminNav = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2>Manajemen Pesanan &amp; E-Ticket</h2>
            <!-- <p>Operasi CRUD penuh untuk tabel <code>orders</code> dan <code>order_tickets</code>.</p> -->
        </div>
    </div>

    <!-- ==================== BAGIAN 1: TABEL ORDERS ==================== -->
    <div class="admin-two-col" style="margin-bottom: 35px;">
        <div class="order-panel">
            <?php if ($editOrder): ?>
                <h3 class="panel-title"><i class="fas fa-edit"></i> Edit Pesanan
                    <code><?= e($editOrder['order_code']) ?></code>
                </h3>
                <form method="post" class="contact-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_order">
                    <input type="hidden" name="id" value="<?= (int) $editOrder['id'] ?>">

                    <div class="form-group">
                        <label>Email Pengiriman</label>
                        <input type="email" name="email" required value="<?= e($editOrder['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp</label>
                        <input type="text" name="no_whatsapp" required value="<?= e($editOrder['no_whatsapp']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Status Pembayaran QRIS</label>
                        <select name="payment_status" required>
                            <?php foreach (['pending', 'paid', 'expired', 'failed'] as $st): ?>
                                <option value="<?= $st ?>" <?= $editOrder['payment_status'] === $st ? 'selected' : '' ?>>
                                    <?= strtoupper($st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="status-actions">
                        <button type="submit" class="btn-sm"><i class="fas fa-save"></i> Simpan Perubahan</button>
                        <a href="orders.php" class="btn-sm btn-outline">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <h3 class="panel-title"><i class="fas fa-plus-circle"></i> Buat Pesanan Manual
                </h3>
                <form method="post" class="contact-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create_order">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pilih Akun User</label>
                            <select name="user_id" required>
                                <?php foreach ($usersList as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pilih Gelombang Tiket</label>
                            <select name="tier_id" required>
                                <?php foreach ($tiersList as $tl): ?>
                                    <option value="<?= (int) $tl['id'] ?>">
                                        <?= e($tl['kota']) ?> - <?= e($tl['nama_gelombang']) ?> (<?= rupiah($tl['price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Jumlah Tiket</label>
                            <input type="number" name="total_tickets" min="1" max="4" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Status Pembayaran</label>
                            <select name="payment_status" required>
                                <option value="paid">PAID (Lunas)</option>
                                <option value="pending">PENDING</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="pembeli@domain.com">
                        </div>
                        <div class="form-group">
                            <label>No. WhatsApp</label>
                            <input type="text" name="no_whatsapp" required placeholder="081234567890">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nama Pemegang Tiket</label>
                        <input type="text" name="holder_name" required placeholder="Nama sesuai identitas">
                    </div>

                    <button type="submit" class="btn-sm"><i class="fas fa-plus"></i> Buat Pesanan &amp; Tiket</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-receipt"></i> Daftar Transaksi Pesanan</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode Order</th>
                            <th>Pemesan &amp; Kontak</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="5" class="muted-label">Belum ada data pesanan.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $o):
                            [$lbl, $cls] = payment_status_badge($o['payment_status']); ?>
                            <tr>
                                <td>
                                    <code><?= e($o['order_code']) ?></code><br>
                                    <small
                                        class="muted-label"><?= e(date('d/m/y H:i', strtotime($o['created_at']))) ?></small>
                                </td>
                                <td>
                                    <strong><?= e($o['full_name']) ?></strong><br>
                                    <small class="muted-label"><?= e($o['email']) ?> &middot;
                                        <?= e($o['no_whatsapp']) ?></small>
                                </td>
                                <td><?= rupiah($o['total_amount']) ?><br><small
                                        class="muted-label"><?= (int) $o['total_tickets'] ?> tiket</small></td>
                                <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
                                <td class="table-actions">
                                    <a href="orders.php?edit_order=<?= (int) $o['id'] ?>" class="btn-xs btn-outline"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post"
                                        onsubmit="return confirm('Hapus pesanan <?= e($o['order_code']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                        <button type="submit" class="btn-xs btn-danger" title="Hapus"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== BAGIAN 2: TABEL ORDER_TICKETS ==================== -->
    <div id="tickets-section" class="admin-two-col">
        <div class="order-panel">
            <?php if ($editTicket): ?>
                <h3 class="panel-title"><i class="fas fa-edit"></i> Edit E-Ticket
                    <code><?= e($editTicket['ticket_code']) ?></code>
                </h3>
                <form method="post" class="contact-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_ticket">
                    <input type="hidden" name="id" value="<?= (int) $editTicket['id'] ?>">

                    <div class="form-group">
                        <label>Nama Pemegang Tiket (Holder Name)</label>
                        <input type="text" name="holder_name" required value="<?= e($editTicket['holder_name']) ?>">
                    </div>
                    <label class="agree-check">
                        <input type="checkbox" name="is_checked_in" value="1" <?= $editTicket['is_checked_in'] ? 'checked' : '' ?>>
                        <span>Sudah Check-In di Gate Venue</span>
                    </label>
                    <div class="status-actions">
                        <button type="submit" class="btn-sm"><i class="fas fa-save"></i> Simpan E-Ticket</button>
                        <a href="orders.php#tickets-section" class="btn-sm btn-outline">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <h3 class="panel-title"><i class="fas fa-plus-circle"></i> Tambah E-Ticket ke Order
                </h3>
                <?php if (!$orders): ?>
                    <p class="muted-label">Buat minimal 1 pesanan terlebih dahulu untuk menambahkan e-ticket.</p>
                <?php else: ?>
                    <form method="post" class="contact-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_ticket">
                        <div class="form-group">
                            <label>Pilih Order</label>
                            <select name="order_id" required>
                                <?php foreach ($orders as $o): ?>
                                    <option value="<?= (int) $o['id'] ?>"><?= e($o['order_code']) ?> - <?= e($o['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pilih Gelombang Tiket</label>
                            <select name="tier_id" required>
                                <?php foreach ($tiersList as $tl): ?>
                                    <option value="<?= (int) $tl['id'] ?>"><?= e($tl['kota']) ?> - <?= e($tl['nama_gelombang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nama Pemegang Tiket</label>
                            <input type="text" name="holder_name" required placeholder="Nama lengkap penonton">
                        </div>
                        <button type="submit" class="btn-sm"><i class="fas fa-plus"></i> Tambah E-Ticket</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-ticket-alt"></i> Daftar E-Ticket Individu</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode Tiket</th>
                            <th>Pemegang &amp; Konser</th>
                            <th>Check-In</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$tickets): ?>
                            <tr>
                                <td colspan="4" class="muted-label">Belum ada e-ticket yang diterbitkan.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($tickets as $tk): ?>
                            <tr>
                                <td>
                                    <code><?= e($tk['ticket_code']) ?></code><br>
                                    <small class="muted-label">Order: <?= e($tk['order_code']) ?></small>
                                </td>
                                <td>
                                    <strong><?= e($tk['holder_name']) ?></strong><br>
                                    <small class="muted-label"><?= e($tk['kota']) ?> &middot;
                                        <?= e($tk['nama_gelombang']) ?></small>
                                </td>
                                <td>
                                    <?php if ($tk['is_checked_in']): ?>
                                        <span class="badge badge-success">Checked-In</span><br>
                                        <small class="muted-label">oleh <?= e($tk['scanner_name'] ?? 'admin') ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-muted">Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="orders.php?edit_ticket=<?= (int) $tk['id'] ?>#tickets-section"
                                        class="btn-xs btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post"
                                        onsubmit="return confirm('Hapus tiket <?= e($tk['ticket_code']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_ticket">
                                        <input type="hidden" name="id" value="<?= (int) $tk['id'] ?>">
                                        <button type="submit" class="btn-xs btn-danger" title="Hapus"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>