<?php
/**
 * Header & Layout Template AdminLTE v4 untuk Panel Admin Barasuara.
 * Variabel pendukung: $adminTitle, $adminNav (index|tours|orders|scan|music|news|inquiries|users)
 */
$adminTitle = $adminTitle ?? 'Dashboard Admin';
$adminNav = $adminNav ?? 'index';
$adminUser = current_admin();
$isFullAdmin = $adminUser && $adminUser['role'] === 'admin';

// Pemetaan menu navigasi
$navItems = [
    'dashboard' => [
        'title' => 'DASHBOARD & RINGKASAN',
        'items' => [
            'index' => ['index.php', 'bi-speedometer2', 'Ringkasan Dashboard', ['admin']],
        ],
    ],
    'ticketing' => [
        'title' => 'TIKET & JADWAL TUR',
        'items' => [
            'tours'  => ['tours.php', 'bi-calendar2-event', 'Tour & Tier Tiket', ['admin']],
            'orders' => ['orders.php', 'bi-receipt-cutoff', 'Pesanan & E-Tiket', ['admin']],
            'scan'   => ['scan.php', 'bi-qr-code-scan', 'Gate Scanner', ['admin', 'gate_scanner']],
        ],
    ],
    'content' => [
        'title' => 'KONTEN & MEDIA',
        'items' => [
            'music'     => ['music.php', 'bi-disc', 'Album & Lagu', ['admin']],
            'news'      => ['news.php', 'bi-newspaper', 'Berita & Acara', ['admin']],
            'inquiries' => ['inquiries.php', 'bi-envelope-paper', 'Pesan Kontak Masuk', ['admin']],
        ],
    ],
    'management' => [
        'title' => 'PENGGUNA & SISTEM',
        'items' => [
            'users' => ['users.php', 'bi-people-fill', 'User & Administrator', ['admin']],
        ],
    ],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= e($adminTitle) ?> - Admin Barasuara</title>

    <!-- Theme Init (mencegah kedipan tema saat reload) -->
    <script>
      (() => {
        'use strict';
        const root = document.documentElement;
        if (root.getAttribute('data-lte-color-mode') === 'off') return;

        const STORAGE_KEY = 'lte-theme';
        let stored = null;
        try {
          stored = localStorage.getItem(STORAGE_KEY);
        } catch {}
        const authored = root.getAttribute('data-bs-theme');
        let resolved = 'dark'; // Default dark rock theme khas Barasuara
        if (stored === 'dark' || stored === 'light') {
          resolved = stored;
        } else if (authored === 'dark' || authored === 'light') {
          resolved = authored;
        } else if (globalThis.matchMedia('(prefers-color-scheme: light)').matches) {
          resolved = 'light';
        }
        root.setAttribute('data-bs-theme', resolved);
        root.style.colorScheme = resolved;
        if (resolved !== authored) {
          root.setAttribute('data-lte-theme-resolved', '');
        }
      })();
    </script>

    <!-- Meta Tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#c24b2f">

    <!-- Fonts: Source Sans 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous">

    <!-- Third Party Plugin: OverlayScrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous">

    <!-- Third Party Plugin: Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

    <!-- FontAwesome (mendukung ikon bawaan CRUD) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">

    <!-- AdminLTE v4 Required CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">

    <!-- Custom CSS Adapter Barasuara -->
    <link rel="stylesheet" href="dist/css/admin-custom.css">
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <!-- Header / Navbar Atas -->
        <nav class="app-header navbar navbar-expand bg-body shadow-sm">
            <div class="container-fluid">
                <!-- Tombol Toggle Sidebar & Quick Links -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                            <i class="bi bi-list fs-4"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="../index.php" target="_blank" rel="noopener" class="nav-link">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Website
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="scan.php" class="nav-link">
                            <i class="bi bi-qr-code-scan me-1"></i> Scanner Pintu
                        </a>
                    </li>
                </ul>

                <!-- Navbar Sisi Kanan -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Status 10 Tabel Basis Data -->
                    <li class="nav-item d-none d-lg-block me-2">
                        <span class="badge text-bg-success px-3 py-2">
                            <i class="bi bi-database-check me-1"></i> 10 Tabel Basis Data Aktif
                        </span>
                    </li>

                    <!-- Fullscreen Toggle -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="Toggle Fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                        </a>
                    </li>

                    <!-- Color Mode Switcher (Light / Dark / Auto) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="bd-theme" aria-label="Toggle color scheme" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sun-fill d-none" data-lte-theme-icon="light"></i>
                            <i class="bi bi-moon-fill" data-lte-theme-icon="dark"></i>
                            <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 8rem;">
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
                                    <i class="bi bi-sun-fill me-2"></i> Light
                                    <i class="bi bi-check-lg ms-auto d-none"></i>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="dark">
                                    <i class="bi bi-moon-fill me-2"></i> Dark
                                    <i class="bi bi-check-lg ms-auto d-none"></i>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="auto">
                                    <i class="bi bi-circle-half me-2"></i> Auto
                                    <i class="bi bi-check-lg ms-auto d-none"></i>
                                </button>
                            </li>
                        </ul>
                    </li>

                    <!-- User Menu Dropdown -->
                    <?php if ($adminUser): ?>
                        <li class="nav-item dropdown user-menu">
                            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                                <img src="dist/assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow-sm" alt="<?= e($adminUser['username']) ?>" style="width: 32px; height: 32px; object-fit: cover;">
                                <span class="d-none d-md-inline fw-semibold"><?= e($adminUser['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                                <!-- User Header -->
                                <li class="user-header text-bg-primary text-center p-3">
                                    <img src="dist/assets/img/user2-160x160.jpg" class="rounded-circle shadow mb-2" alt="<?= e($adminUser['username']) ?>" style="width: 70px; height: 70px; object-fit: cover;">
                                    <p class="mb-0 fw-bold fs-6">
                                        <?= e($adminUser['username']) ?>
                                    </p>
                                    <small class="badge bg-light text-dark mt-1">Role: <?= strtoupper(e($adminUser['role'])) ?></small>
                                    <div class="small opacity-75 mt-1"><?= e($adminUser['email'] ?? 'admin@barasuara.com') ?></div>
                                </li>
                                <!-- User Body -->
                                <li class="user-body p-3">
                                    <div class="small text-secondary mb-1">
                                        <i class="bi bi-clock-history me-1"></i> Login terakhir:
                                    </div>
                                    <div class="small fw-semibold">
                                        <?= !empty($adminUser['last_login']) ? date('d M Y, H:i', strtotime($adminUser['last_login'])) : 'Sesi saat ini' ?>
                                    </div>
                                </li>
                                <!-- User Footer -->
                                <li class="user-footer d-flex justify-content-between p-2 border-top">
                                    <a href="<?= $isFullAdmin ? 'users.php#admins-section' : 'scan.php' ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-person me-1"></i> Profil
                                    </a>
                                    <form action="logout.php" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-box-arrow-right me-1"></i> Keluar
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Sidebar Utama AdminLTE -->
        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <!-- Sidebar Brand -->
            <div class="sidebar-brand border-bottom border-secondary border-opacity-25">
                <a href="<?= $isFullAdmin ? 'index.php' : 'scan.php' ?>" class="brand-link">
                    <img src="dist/assets/img/AdminLTELogo.png" alt="Barasuara Logo" class="brand-image opacity-75 shadow-sm">
                    <span class="brand-text fw-bold">BARASUARA</span>
                    <span class="badge text-bg-danger ms-auto fs-7">LTE v4</span>
                </a>
            </div>

            <!-- Sidebar Search (Pencarian Menu) -->
            <div class="sidebar-search" role="search">
                <input
                    type="search"
                    id="sidebar-search-input"
                    class="form-control form-control-sm"
                    placeholder="Cari menu..."
                    autocomplete="off"
                    data-lte-toggle="sidebar-search"
                    data-lte-target="#navigation"
                >
            </div>

            <!-- Sidebar Navigasi -->
            <div class="sidebar-wrapper">
                <nav class="mt-2" aria-label="Main navigation">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">
                        <?php foreach ($navItems as $sectionKey => $section): ?>
                            <?php
                            // Cek apakah ada item yang diizinkan untuk role saat ini
                            $visibleItems = [];
                            foreach ($section['items'] as $itemKey => [$href, $icon, $label, $allowedRoles]) {
                                if ($adminUser && in_array($adminUser['role'], $allowedRoles, true)) {
                                    $visibleItems[$itemKey] = [$href, $icon, $label];
                                }
                            }
                            if (empty($visibleItems)) continue;
                            ?>
                            <li class="nav-header"><?= e($section['title']) ?></li>
                            <?php foreach ($visibleItems as $itemKey => [$href, $icon, $label]): ?>
                                <li class="nav-item">
                                    <a href="<?= e($href) ?>" class="nav-link<?= $adminNav === $itemKey ? ' active' : '' ?>">
                                        <i class="nav-icon bi <?= e($icon) ?>"></i>
                                        <p><?= e($label) ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        <li class="nav-header">TAUTAN SISTEM</li>
                        <li class="nav-item">
                            <a href="../index.php" target="_blank" rel="noopener" class="nav-link">
                                <i class="nav-icon bi bi-box-arrow-up-right text-info"></i>
                                <p>Halaman Depan Publik</p>
                            </a>
                        </li>
                        <?php if ($adminUser): ?>
                            <li class="nav-item">
                                <form action="logout.php" method="post" id="sidebar-logout-form" class="m-0 p-0">
                                    <?= csrf_field() ?>
                                    <a href="#" onclick="document.getElementById('sidebar-logout-form').submit(); return false;" class="nav-link text-danger">
                                        <i class="nav-icon bi bi-power"></i>
                                        <p>Keluar (Logout)</p>
                                    </a>
                                </form>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="app-main">
            <!-- Breadcrumbs & Page Header -->
            <div class="app-content-header py-3">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h1 class="mb-0 fs-3 fw-bold"><?= e($adminTitle) ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end mb-0">
                                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Admin</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= e($adminTitle) ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Konten Halaman -->
            <div class="app-content">
                <div class="container-fluid">
                    <!-- Flash Message Notifikasi -->
                    <?php require __DIR__ . '/../../includes/flash.php'; ?>
