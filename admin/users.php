<?php
/**
 * CRUD Tabel `users` dan `admins` (INSERT, SELECT, UPDATE, DELETE).
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'create_user':
                $sub = !empty($_POST['is_subscribed']) ? 1 : 0;
                $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role, is_subscribed, subscribed_at)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
                            trim((string) $_POST['full_name']),
                            strtolower(trim((string) $_POST['email'])),
                            password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
                            (string) $_POST['role'],
                            $sub,
                            $sub ? date('Y-m-d H:i:s') : null,
                        ]);
                flash('success', 'Akun user baru berhasil ditambahkan.');
                break;

            case 'update_user':
                $sub = !empty($_POST['is_subscribed']) ? 1 : 0;
                $pass = trim((string) ($_POST['password'] ?? ''));
                if ($pass !== '') {
                    $pdo->prepare(
                        'UPDATE users SET full_name = ?, email = ?, password_hash = ?, role = ?, is_subscribed = ? WHERE id = ?'
                    )->execute([
                                trim((string) $_POST['full_name']),
                                strtolower(trim((string) $_POST['email'])),
                                password_hash($pass, PASSWORD_DEFAULT),
                                (string) $_POST['role'],
                                $sub,
                                (int) $_POST['id'],
                            ]);
                } else {
                    $pdo->prepare(
                        'UPDATE users SET full_name = ?, email = ?, role = ?, is_subscribed = ? WHERE id = ?'
                    )->execute([
                                trim((string) $_POST['full_name']),
                                strtolower(trim((string) $_POST['email'])),
                                (string) $_POST['role'],
                                $sub,
                                (int) $_POST['id'],
                            ]);
                }
                flash('success', 'Data akun user berhasil diperbarui.');
                break;

            case 'delete_user':
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([(int) $_POST['id']]);
                flash('success', 'Akun user berhasil dihapus.');
                break;

            case 'create_admin':
                $pdo->prepare(
                    'INSERT INTO admins (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)'
                )->execute([
                            trim((string) $_POST['username']),
                            strtolower(trim((string) $_POST['email'])),
                            password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
                            (string) $_POST['role'],
                            !empty($_POST['is_active']) ? 1 : 0,
                        ]);
                flash('success', 'Akun panitia/admin baru berhasil ditambahkan.');
                break;

            case 'update_admin':
                $pass = trim((string) ($_POST['password'] ?? ''));
                $active = !empty($_POST['is_active']) ? 1 : 0;
                if ($pass !== '') {
                    $pdo->prepare(
                        'UPDATE admins SET username = ?, email = ?, password_hash = ?, role = ?, is_active = ? WHERE id = ?'
                    )->execute([
                                trim((string) $_POST['username']),
                                strtolower(trim((string) $_POST['email'])),
                                password_hash($pass, PASSWORD_DEFAULT),
                                (string) $_POST['role'],
                                $active,
                                (int) $_POST['id'],
                            ]);
                } else {
                    $pdo->prepare(
                        'UPDATE admins SET username = ?, email = ?, role = ?, is_active = ? WHERE id = ?'
                    )->execute([
                                trim((string) $_POST['username']),
                                strtolower(trim((string) $_POST['email'])),
                                (string) $_POST['role'],
                                $active,
                                (int) $_POST['id'],
                            ]);
                }
                flash('success', 'Data admin berhasil diperbarui.');
                break;

            case 'delete_admin':
                $targetId = (int) $_POST['id'];
                if ($targetId === (int) $admin['id']) {
                    throw new RuntimeException('Anda tidak dapat menghapus akun admin yang sedang digunakan login.');
                }
                $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$targetId]);
                flash('success', 'Akun admin berhasil dihapus.');
                break;
        }
    } catch (Throwable $e) {
        flash('error', 'Operasi gagal: ' . $e->getMessage());
    }

    redirect('users.php');
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC, id DESC')->fetchAll();
$admins = $pdo->query('SELECT * FROM admins ORDER BY id ASC')->fetchAll();

$editUser = null;
if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_user']]);
    $editUser = $stmt->fetch() ?: null;
}

$editAdmin = null;
if (isset($_GET['edit_admin'])) {
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_admin']]);
    $editAdmin = $stmt->fetch() ?: null;
}

$adminTitle = 'Kelola User & Admin';
$adminNav = 'users';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2>Manajemen Akun Pengguna &amp; Panitia Admin</h2>
            <!-- <p>Operasi CRUD penuh untuk tabel <code>users</code> dan <code>admins</code>.</p> -->
        </div>
    </div>

    <!-- ==================== BAGIAN 1: TABEL USERS ==================== -->
    <div class="admin-two-col" style="margin-bottom: 35px;">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editUser ? 'fa-edit' : 'fa-user-plus' ?>"></i>
                <?= $editUser ? 'Edit Akun User #' . (int) $editUser['id'] : 'Tambah Akun User Baru' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editUser ? 'update_user' : 'create_user' ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" required maxlength="150"
                        value="<?= e($editUser['full_name'] ?? '') ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required maxlength="250"
                            value="<?= e($editUser['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="member" <?= (($editUser['role'] ?? '') === 'member') ? 'selected' : '' ?>>member
                            </option>
                            <option value="non_member" <?= (($editUser['role'] ?? 'non_member') === 'non_member') ? 'selected' : '' ?>>non_member</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password <?= $editUser ? '<small>(Kosongkan jika tidak diubah)</small>' : '' ?></label>
                    <input type="password" name="password" <?= $editUser ? '' : 'required' ?> minlength="6"
                        placeholder="<?= $editUser ? 'Biarkan kosong jika tetap' : 'Minimal 6 karakter' ?>">
                </div>

                <label class="agree-check" style="margin: 4px 0;">
                    <input type="checkbox" name="is_subscribed" value="1" <?= !empty($editUser['is_subscribed']) ? 'checked' : '' ?>>
                    <span>Berlangganan Newsletter Barasuara</span>
                </label>

                <div class="status-actions">
                    <button type="submit" class="btn-sm"><i class="fas fa-save"></i>
                        <?= $editUser ? 'Simpan User' : 'Tambah User' ?></button>
                    <?php if ($editUser): ?>
                        <a href="users.php" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-users"></i> Daftar Pengguna</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nama &amp; Email</th>
                            <th>Role</th>
                            <th>Newsletter</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= e($u['full_name']) ?></strong><br>
                                    <small class="muted-label"><?= e($u['email']) ?></small>
                                </td>
                                <td><span
                                        class="badge <?= $u['role'] === 'member' ? 'badge-info' : 'badge-muted' ?>"><?= e($u['role']) ?></span>
                                </td>
                                <td><?= $u['is_subscribed'] ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-muted">Tidak</span>' ?>
                                </td>
                                <td class="table-actions">
                                    <a href="users.php?edit_user=<?= (int) $u['id'] ?>" class="btn-xs btn-outline"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Hapus user <?= e($u['full_name']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
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

    <!-- ==================== BAGIAN 2: TABEL ADMINS ==================== -->
    <div id="admins-section" class="admin-two-col">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editAdmin ? 'fa-edit' : 'fa-user-shield' ?>"></i>
                <?= $editAdmin ? 'Edit Akun Admin #' . (int) $editAdmin['id'] : 'Tambah Admin / Gate Scanner' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editAdmin ? 'update_admin' : 'create_admin' ?>">
                <?php if ($editAdmin): ?>
                    <input type="hidden" name="id" value="<?= (int) $editAdmin['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required maxlength="50"
                            value="<?= e($editAdmin['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Role Panitia</label>
                        <select name="role" required>
                            <option value="admin" <?= (($editAdmin['role'] ?? '') === 'admin') ? 'selected' : '' ?>>admin
                                (Akses Penuh)</option>
                            <option value="gate_scanner" <?= (($editAdmin['role'] ?? '') === 'gate_scanner') ? 'selected' : '' ?>>gate_scanner (Khusus Scan Tiket)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Admin</label>
                    <input type="email" name="email" required maxlength="100"
                        value="<?= e($editAdmin['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Password <?= $editAdmin ? '<small>(Kosongkan jika tidak diubah)</small>' : '' ?></label>
                    <input type="password" name="password" <?= $editAdmin ? '' : 'required' ?> minlength="6"
                        placeholder="<?= $editAdmin ? 'Biarkan kosong jika tetap' : 'Minimal 6 karakter' ?>">
                </div>

                <label class="agree-check" style="margin: 4px 0;">
                    <input type="checkbox" name="is_active" value="1" <?= (!isset($editAdmin) || !empty($editAdmin['is_active'])) ? 'checked' : '' ?>>
                    <span>Akun Admin Aktif</span>
                </label>

                <div class="status-actions">
                    <button type="submit" class="btn-sm"><i class="fas fa-save"></i>
                        <?= $editAdmin ? 'Simpan Admin' : 'Tambah Admin' ?></button>
                    <?php if ($editAdmin): ?>
                        <a href="users.php#admins-section" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-user-shield"></i> Daftar Panitia &amp; Admin</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username &amp; Email</th>
                            <th>Role</th>
                            <th>Login Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $ad): ?>
                            <tr>
                                <td>
                                    <strong><?= e($ad['username']) ?></strong><br>
                                    <small class="muted-label"><?= e($ad['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $ad['role'] === 'admin' ? 'badge-info' : 'badge-warning' ?>">
                                        <?= e($ad['role']) ?>
                                    </span>
                                </td>
                                <td><small
                                        class="muted-label"><?= $ad['last_login'] ? e(date('d/m/y H:i', strtotime($ad['last_login']))) : 'Belum pernah' ?></small>
                                </td>
                                <td class="table-actions">
                                    <a href="users.php?edit_admin=<?= (int) $ad['id'] ?>#admins-section"
                                        class="btn-xs btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ((int) $ad['id'] !== (int) $admin['id']): ?>
                                        <form method="post"
                                            onsubmit="return confirm('Hapus admin <?= e($ad['username']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                                            <button type="submit" class="btn-xs btn-danger" title="Hapus"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
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