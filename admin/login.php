<?php
require_once __DIR__ . '/includes/auth.php';

if ($logged = current_admin()) {
    redirect($logged['role'] === 'admin' ? 'index.php' : 'scan.php');
}

$error = null;
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $oldUsername = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare(
        'SELECT id, username, password_hash, role, is_active FROM admins WHERE username = ? OR email = ? LIMIT 1'
    );
    $stmt->execute([$oldUsername, $oldUsername]);
    $admin = $stmt->fetch();

    if ($admin && (int) $admin['is_active'] === 1 && password_verify($password, $admin['password_hash'])) {
        login_admin((int) $admin['id']);
        flash('success', 'Selamat datang di Panel Admin, ' . $admin['username'] . '!');
        redirect($admin['role'] === 'admin' ? 'index.php' : 'scan.php');
    }

    $error = 'Username/email atau password admin tidak valid, atau akun nonaktif.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Login - Panel Admin Barasuara</title>

    <!-- Theme Init -->
    <script>
      (() => {
        'use strict';
        const root = document.documentElement;
        if (root.getAttribute('data-lte-color-mode') === 'off') return;

        const STORAGE_KEY = 'lte-theme';
        let stored = null;
        try { stored = localStorage.getItem(STORAGE_KEY); } catch {}
        const authored = root.getAttribute('data-bs-theme');
        let resolved = 'dark';
        if (stored === 'dark' || stored === 'light') {
          resolved = stored;
        } else if (authored === 'dark' || authored === 'light') {
          resolved = authored;
        } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
          resolved = 'light';
        }
        root.setAttribute('data-bs-theme', resolved);
        root.style.colorScheme = resolved;
        if (resolved !== authored) {
          root.setAttribute('data-lte-theme-resolved', '');
        }
      })();
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

    <!-- AdminLTE Required CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="dist/css/admin-custom.css">
</head>

<body class="login-page bg-body-secondary d-flex align-items-center justify-content-center min-vh-100">
    <main class="login-box" style="width: 420px; max-width: 92%;">
        <div class="card card-outline card-primary shadow-lg">
            <div class="card-header text-center py-3">
                <a href="../index.php" class="link-body-emphasis text-decoration-none">
                    <img src="dist/assets/img/AdminLTELogo.png" alt="Logo" class="brand-image mb-2" style="max-height: 48px;">
                    <h1 class="mb-0 fs-3 fw-bold"><b>BARASUARA</b> <span class="text-primary">ADMIN</span></h1>
                </a>
                <small class="text-secondary d-block mt-1">Management System &amp; Gate Scanner</small>
            </div>
            <div class="card-body login-card-body p-4">
                <p class="login-box-msg text-secondary text-center mb-3">
                    Masuk untuk mengelola basis data tiket, konser, media, dan gate scanner.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <span class="small"><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php require __DIR__ . '/../includes/flash.php'; ?>

                <form method="post">
                    <?= csrf_field() ?>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input
                                id="username"
                                type="text"
                                name="username"
                                class="form-control"
                                placeholder="Username atau Email"
                                value="<?= e($oldUsername) ?>"
                                required
                                autocomplete="username"
                            >
                            <label for="username">Username atau Email Admin</label>
                        </div>
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Password"
                                required
                                autocomplete="current-password"
                            >
                            <label for="password">Password</label>
                        </div>
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
                            </button>
                        </div>
                    </div>
                </form>

                <div class="notice-demo mt-3">
                    <strong class="d-block mb-1 text-primary"><i class="bi bi-info-circle me-1"></i> Akun Demo Default:</strong>
                    <div class="small mb-1">&bull; Full Admin: <code>superadmin</code> / <code>admin123</code></div>
                    <div class="small">&bull; Gate Scanner: <code>gatekeeper1</code> / <code>admin123</code></div>
                </div>

                <div class="text-center mt-3 pt-3 border-top">
                    <a href="../index.php" class="text-decoration-none small text-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Website Publik Barasuara
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>
</html>
