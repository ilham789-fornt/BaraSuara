<?php
require_once __DIR__ . '/includes/bootstrap.php';

$redirectTo = safe_redirect_target($_GET['redirect'] ?? $_POST['redirect'] ?? '', 'tour.php');

if (current_user()) {
    redirect($redirectTo);
}

$errors = [];
$old = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $old['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($old['full_name']) < 3 || mb_strlen($old['full_name']) > 150) {
        $errors['full_name'] = 'Nama lengkap minimal 3 dan maksimal 150 karakter.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak cocok.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $check->execute([$old['email']]);
        if ($check->fetchColumn()) {
            $errors['email'] = 'Email sudah terdaftar. Silakan masuk.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'non_member')"
        );
        $stmt->execute([$old['full_name'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]);

        login_user((int) db()->lastInsertId());
        flash('success', 'Akun berhasil dibuat. Selamat datang, ' . $old['full_name'] . '!');
        redirect($redirectTo);
    }
}

$pageTitle = 'Daftar Akun';
$pageDescription = 'Daftar akun fans Barasuara untuk memesan tiket konser dan menghubungi manajemen.';
$activePage = 'register';
require __DIR__ . '/includes/layout_header.php';
?>

<main>
    <section class="section fade-in" aria-labelledby="register-heading">
        <div class="auth-card">
            <div class="text-center">
                <h2 id="register-heading">Daftar Akun</h2>
                <p class="auth-subtitle">Buat akun untuk memesan tiket konser dan menghubungi Barasuara.</p>
            </div>

            <form method="post" class="contact-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">

                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" required maxlength="150"
                        autocomplete="name" value="<?= e($old['full_name']) ?>" placeholder="Nama sesuai identitas">
                    <?php if (isset($errors['full_name'])): ?><small class="field-error"><?= e($errors['full_name']) ?></small><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required maxlength="250" autocomplete="email"
                        value="<?= e($old['email']) ?>" placeholder="email@domain.com">
                    <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8"
                        autocomplete="new-password" placeholder="Minimal 8 karakter">
                    <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Konfirmasi Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8"
                        autocomplete="new-password" placeholder="Ulangi password">
                    <?php if (isset($errors['password_confirm'])): ?><small class="field-error"><?= e($errors['password_confirm']) ?></small><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-block">Daftar <i class="fas fa-user-plus"></i></button>
            </form>

            <p class="auth-switch">Sudah punya akun?
                <a href="login.php?redirect=<?= e(urlencode($redirectTo)) ?>">Masuk di sini</a>
            </p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

