<?php
require_once __DIR__ . '/includes/bootstrap.php';

$redirectTo = safe_redirect_target($_GET['redirect'] ?? $_POST['redirect'] ?? '', 'tour.php');

if (current_user()) {
    redirect($redirectTo);
}

$error = null;
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $oldEmail = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    // 1. Cek terlebih dahulu di tabel `admins` (bisa login via email atau username admin)
    $adminStmt = db()->prepare(
        'SELECT id, username, email, password_hash, role, is_active
         FROM admins WHERE (LOWER(email) = ? OR LOWER(username) = ?) AND is_active = 1 LIMIT 1'
    );
    $adminStmt->execute([$oldEmail, $oldEmail]);
    $admin = $adminStmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
        flash('success', 'Selamat datang di Panel Admin, ' . $admin['username'] . '!');
        redirect($admin['role'] === 'admin' ? 'admin/index.php' : 'admin/scan.php');
    }

    // 2. Cek di tabel `users` (member / non_member)
    $stmt = db()->prepare('SELECT id, full_name, password_hash FROM users WHERE email = ?');
    $stmt->execute([$oldEmail]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        login_user((int) $user['id']);
        flash('success', 'Selamat datang kembali, ' . $user['full_name'] . '!');
        redirect($redirectTo);
    }

    $error = 'Email/username atau password salah.';
}

$pageTitle = 'Masuk';
$pageDescription = 'Masuk ke akun fans Barasuara.';
$activePage = 'login';
require __DIR__ . '/includes/layout_header.php';
?>

<main>
    <section class="section fade-in" aria-labelledby="login-heading">
        <div class="auth-card">
            <div class="text-center">
                <h2 id="login-heading">Masuk</h2>
                <p class="auth-subtitle">Masuk untuk memesan tiket konser, menghubungi Barasuara, atau akses Panel Admin.</p>
            </div>

            <?php if ($error): ?>
                <div class="flash flash-error" role="alert"><i class="fas fa-exclamation-triangle"></i>
                    <span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">

                <div class="form-group">
                    <label for="email">Email atau Username</label>
                    <input type="text" id="email" name="email" required autocomplete="username"
                        value="<?= e($oldEmail) ?>" placeholder="email@domain.com">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        placeholder="Password akun">
                </div>

                <button type="submit" class="btn btn-block">Masuk <i class="fas fa-sign-in-alt"></i></button>
            </form>

            <p class="auth-switch">Belum punya akun?
                <a href="register.php?redirect=<?= e(urlencode($redirectTo)) ?>">Daftar sekarang</a>
            </p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
