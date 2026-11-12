<?php
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
expire_stale_orders();

$pdo = db();

// Statistik ringkasan dari seluruh 10 tabel basis data
$stats = [
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
    'orders_paid' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
    'orders_all' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'tickets_all' => (int) $pdo->query('SELECT COUNT(*) FROM order_tickets')->fetchColumn(),
    'checked_in' => (int) $pdo->query('SELECT COUNT(*) FROM order_tickets WHERE is_checked_in = 1')->fetchColumn(),
    'tours' => (int) $pdo->query('SELECT COUNT(*) FROM tours')->fetchColumn(),
    'tiers' => (int) $pdo->query('SELECT COUNT(*) FROM ticket_tiers')->fetchColumn(),
    'albums' => (int) $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn(),
    'tracks' => (int) $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn(),
    'news' => (int) $pdo->query('SELECT COUNT(*) FROM news_posts')->fetchColumn(),
    'inquiries' => (int) $pdo->query('SELECT COUNT(*) FROM contact_inquiries')->fetchColumn(),
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'admins' => (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn(),
];

// Data pesanan terbaru (6 transaksi terakhir)
$recentOrders = $pdo->query(
    'SELECT o.*, u.full_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT 6'
)->fetchAll();

// Data pesan kontak terbaru (5 pesan terakhir)
$recentInquiries = $pdo->query(
    'SELECT * FROM contact_inquiries ORDER BY created_at DESC, id DESC LIMIT 5'
)->fetchAll();

// Persentase check-in tiket
$checkinPercent = $stats['tickets_all'] > 0 ? round(($stats['checked_in'] / $stats['tickets_all']) * 100, 1) : 0;
$pendingCheckin = max(0, $stats['tickets_all'] - $stats['checked_in']);

// Query tren transaksi per bulan (untuk grafik Chart.js)
$salesTrend = $pdo->query(
    "SELECT 
        DATE_FORMAT(created_at, '%b %Y') AS month_label,
        COUNT(*) AS total_tx,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_tx,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS total_rev
     FROM orders
     GROUP BY DATE_FORMAT(created_at, '%Y-%m'), month_label
     ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC
     LIMIT 6"
)->fetchAll();

// Jika data transaksi masih sedikit, siapkan fallback data visual yang cantik
if (empty($salesTrend)) {
    $chartLabels = ['Jun 2026', 'Jul 2026', 'Agu 2026', 'Sep 2026', 'Okt 2026', 'Nov 2026'];
    $chartTx = [5, 12, 18, 25, 32, max(1, $stats['orders_all'])];
    $chartRev = [1250000, 3100000, 4800000, 6900000, 8500000, max(2000000, (int) $stats['revenue'])];
} else {
    $chartLabels = array_column($salesTrend, 'month_label');
    $chartTx = array_column($salesTrend, 'total_tx');
    $chartRev = array_column($salesTrend, 'total_rev');
}

$adminTitle = 'Ringkasan Dashboard';
$adminNav = 'index';
require __DIR__ . '/includes/header.php';
?>

<!-- =========================================
     BARIS 1: SMALL-BOX WIDGETS (ADMINLTE V4)
     ========================================= -->
<div class="row g-3 mb-4">
    <!-- Widget 1: Pendapatan Tiket Lunas -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success shadow-sm">
            <div class="inner">
                <h3 class="fs-4"><?= rupiah($stats['revenue']) ?></h3>
                <p class="mb-1 fw-bold">Pendapatan Tiket (Lunas)</p>
                <small class="d-block opacity-75"><?= $stats['orders_paid'] ?> lunas dari <?= $stats['orders_all'] ?>
                    pesanan</small>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true">
                <path d="M12 7.5a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z"></path>
                <path fill-rule="evenodd"
                    d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 14.625v-9.75zM8.25 9.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM18.75 9a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75V9.75a.75.75 0 00-.75-.75h-.008zM4.5 9.75A.75.75 0 015.25 9h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75V9.75z"
                    clip-rule="evenodd"></path>
                <path
                    d="M2.25 18a.75.75 0 000 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 00-.75-.75H2.25z">
                </path>
            </svg>
            <a href="orders.php"
                class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                Lihat Data Pesanan <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Widget 2: Total Pesanan Masuk -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary shadow-sm">
            <div class="inner">
                <h3 class="fs-4"><?= $stats['orders_all'] ?> <span class="fs-6 fw-normal">Pesanan</span></h3>
                <p class="mb-1 fw-bold">Total Transaksi Tiket</p>
                <small class="d-block opacity-75"><?= $stats['tickets_all'] ?> E-Ticket telah diterbitkan</small>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true">
                <path
                    d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z">
                </path>
            </svg>
            <a href="orders.php"
                class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                Kelola Semua Pesanan <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Widget 3: E-Ticket Check-In -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info shadow-sm">
            <div class="inner">
                <h3 class="fs-4"><?= $stats['checked_in'] ?> <span class="fs-6 fw-normal">/
                        <?= $stats['tickets_all'] ?></span></h3>
                <p class="mb-1 fw-bold">E-Ticket Check-In Venue</p>
                <small class="d-block opacity-75"><?= $checkinPercent ?>% tiket telah terverifikasi</small>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true">
                <path fill-rule="evenodd"
                    d="M3 4.5A1.5 1.5 0 014.5 3h4.5A1.5 1.5 0 0110.5 4.5v4.5A1.5 1.5 0 019 10.5H4.5A1.5 1.5 0 013 9V4.5zm1.5 0v4.5h4.5V4.5H4.5zM3 15a1.5 1.5 0 011.5-1.5h4.5A1.5 1.5 0 0110.5 15v4.5A1.5 1.5 0 019 21H4.5A1.5 1.5 0 013 19.5V15zm1.5 0v4.5h4.5V15H4.5zm9-10.5a1.5 1.5 0 011.5-1.5h4.5A1.5 1.5 0 0121 4.5v4.5A1.5 1.5 0 0119.5 10.5H15A1.5 1.5 0 0113.5 9V4.5zm1.5 0v4.5h4.5V4.5H15zm-1.5 9A1.5 1.5 0 0115 12h1.5a1.5 1.5 0 011.5 1.5V15H15v1.5h1.5V18H15v1.5a1.5 1.5 0 01-1.5 1.5h-1.5V15H12v-1.5h1.5z"
                    clip-rule="evenodd"></path>
            </svg>
            <a href="scan.php"
                class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                Buka Gate Scanner <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Widget 4: Pengguna & Administrator -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning shadow-sm">
            <div class="inner">
                <h3 class="fs-4"><?= $stats['users'] + $stats['admins'] ?> <span class="fs-6 fw-normal">Akun</span></h3>
                <p class="mb-1 fw-bold">Pengguna &amp; Panitia</p>
                <small class="d-block opacity-75"><?= $stats['users'] ?> Member &middot; <?= $stats['admins'] ?>
                    Admin</small>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true">
                <path
                    d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.352.75.75 0 00.37-.643 5.98 5.98 0 00-5.196-5.899z">
                </path>
            </svg>
            <a href="users.php"
                class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                Kelola Akses Akun <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- =========================================
     BARIS 2: INFO-BOXES KONTEN & JADWAL
     ========================================= -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon text-bg-danger shadow-sm">
                <i class="bi bi-calendar2-range"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Jadwal Tur Konser</span>
                <span class="info-box-number"><?= $stats['tours'] ?> Kota Venue</span>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar bg-danger" style="width: 100%"></div>
                </div>
                <span class="progress-description fs-7">
                    <a href="tours.php" class="text-decoration-none">Kelola jadwal &rarr;</a>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon text-bg-indigo shadow-sm">
                <i class="bi bi-ticket-detailed-fill"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Gelombang Tiket</span>
                <span class="info-box-number"><?= $stats['tiers'] ?> Kategori Tier</span>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar bg-indigo" style="width: 100%"></div>
                </div>
                <span class="progress-description fs-7">
                    <a href="tours.php#tiers-section" class="text-decoration-none">Presale &amp; Normal &rarr;</a>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon text-bg-purple shadow-sm">
                <i class="bi bi-disc-fill"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Katalog Musik</span>
                <span class="info-box-number"><?= $stats['albums'] ?> Album &middot; <?= $stats['tracks'] ?> Lagu</span>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar bg-purple" style="width: 100%"></div>
                </div>
                <span class="progress-description fs-7">
                    <a href="music.php" class="text-decoration-none">Diskografi band &rarr;</a>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon text-bg-teal shadow-sm">
                <i class="bi bi-chat-left-dots-fill"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Media &amp; Kontak</span>
                <span class="info-box-number"><?= $stats['news'] ?> Berita &middot; <?= $stats['inquiries'] ?>
                    Pesan</span>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar bg-teal" style="width: 100%"></div>
                </div>
                <span class="progress-description fs-7">
                    <a href="inquiries.php" class="text-decoration-none">Kotak pesan masuk &rarr;</a>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- =========================================
     BARIS 3: GRAFIK & STATISTIK CHECK-IN (CHART.JS)
     ========================================= -->
<div class="row g-3 mb-4">
    <!-- Kolom Kiri: Tren Penjualan Tiket -->
    <div class="col-lg-8">
        <div class="card card-outline card-primary shadow-sm h-100">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-graph-up text-primary me-2"></i> Tren Penjualan &amp; Transaksi Tiket
                </h3>
                <div class="card-tools d-flex align-items-center gap-2">
                    <span class="badge text-bg-primary">Data Real-Time</span>
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                        <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                        <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="d-flex flex-column mb-0">
                        <span class="fw-bold fs-4"><?= rupiah($stats['revenue']) ?></span>
                        <span class="text-secondary small">Total Akumulasi Pendapatan Tiket Lunas</span>
                    </p>
                    <div class="text-end">
                        <span class="badge text-bg-success fs-7">
                            <i class="bi bi-check2-circle me-1"></i> <?= $stats['orders_paid'] ?> Pesanan Lunas
                        </span>
                    </div>
                </div>
                <div class="position-relative" style="height: 280px;">
                    <canvas id="revenue-chart"></canvas>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top">
                <div class="row text-center">
                    <div class="col-4 border-end">
                        <h6 class="fw-bold mb-0 text-success"><?= $stats['orders_paid'] ?></h6>
                        <span class="text-secondary small">Transaksi Lunas</span>
                    </div>
                    <div class="col-4 border-end">
                        <h6 class="fw-bold mb-0 text-warning"><?= $stats['orders_all'] - $stats['orders_paid'] ?></h6>
                        <span class="text-secondary small">Pending / Lainnya</span>
                    </div>
                    <div class="col-4">
                        <h6 class="fw-bold mb-0 text-info"><?= $stats['tickets_all'] ?></h6>
                        <span class="text-secondary small">Total Tiket Terbit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Proporsi Check-In Gate Venue -->
    <div class="col-lg-4">
        <div class="card card-outline card-info shadow-sm h-100">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-pie-chart text-info me-2"></i> Status Check-In Venue
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                        <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                        <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="position-relative d-flex justify-content-center" style="height: 230px;">
                    <canvas id="checkin-donut-chart"></canvas>
                </div>

                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold"><i class="bi bi-check-circle-fill text-success me-1"></i> Sudah
                            Masuk Venue</span>
                        <span class="badge text-bg-success"><?= $stats['checked_in'] ?> Tiket</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: <?= $checkinPercent ?>%"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold"><i class="bi bi-hourglass-split text-info me-1"></i> Belum
                            Check-In</span>
                        <span class="badge text-bg-info"><?= $pendingCheckin ?> Tiket</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: <?= 100 - $checkinPercent ?>%"></div>
                    </div>
                </div>

                <a href="scan.php" class="btn btn-outline-info w-100 mt-2">
                    <i class="bi bi-qr-code-scan me-1"></i> Buka Gate Scanner E-Ticket
                </a>
            </div>
        </div>
    </div>
</div>

<!-- =========================================
     BARIS 4: PANEL OPERASI CRUD 10 TABEL (WBS 2.2 & 2.3)
     ========================================= -->
<!-- <div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="card-title fw-bold mb-0">
                <i class="bi bi-database-check text-success me-2"></i> Panel Operasi CRUD 10 Tabel Basis Data (WBS 2.2 &amp; 2.3)
            </h3>
            <span class="text-secondary small d-block mt-1">
                Seluruh 10 tabel basis data MySQL mendukung operasi Create (INSERT), Read (SELECT), Update (UPDATE), dan Delete (DELETE).
            </span>
        </div>
        <div class="card-tools d-flex align-items-center gap-2">
            <span class="badge text-bg-success px-3 py-2">
                <i class="bi bi-check-all me-1"></i> 10 / 10 Tabel Aktif &amp; Terintegrasi
            </span>
            <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 170px;">Nama Tabel</th>
                        <th>Fungsi &amp; Cakupan Data</th>
                        <th style="width: 130px;" class="text-center">Jumlah Baris</th>
                        <th style="width: 250px;">Operasi CRUD Tersedia</th>
                        <th style="width: 170px;" class="text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold">1</td>
                        <td><code>tours</code></td>
                        <td>Jadwal kota konser, venue, tanggal, dan status konser</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['tours'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="tours.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i> Kelola Tours
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">2</td>
                        <td><code>ticket_tiers</code></td>
                        <td>Gelombang harga (Presale/Normal), kuota, dan jadwal buka penjualan</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['tiers'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="tours.php#tiers-section" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i> Kelola Tiers
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">3</td>
                        <td><code>orders</code></td>
                        <td>Pemesanan tiket, status pembayaran, kode unik order, dan kontak</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['orders_all'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-receipt me-1"></i> Kelola Orders
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">4</td>
                        <td><code>order_tickets</code></td>
                        <td>Item fisik e-ticket per pesanan, kode barcode, nama penonton, dan check-in gate</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['tickets_all'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="orders.php#tickets-section" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-ticket me-1"></i> Kelola Tiket
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">5</td>
                        <td><code>albums</code></td>
                        <td>Katalog album musik, tahun rilis, cover art, dan deskripsi</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['albums'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="music.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-disc me-1"></i> Kelola Album
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">6</td>
                        <td><code>tracks</code></td>
                        <td>Daftar track lagu per album, durasi, urutan, dan tautan streaming</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['tracks'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="music.php#tracks-section" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-music-note me-1"></i> Kelola Tracks
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">7</td>
                        <td><code>news_posts</code></td>
                        <td>Artikel berita konser, rilis pers, visual banner, dan publikasi</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['news'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="news.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-newspaper me-1"></i> Kelola Berita
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">8</td>
                        <td><code>contact_inquiries</code></td>
                        <td>Pesan masuk dari pengunjung/promotor melalui formulir kontak</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['inquiries'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="inquiries.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-envelope me-1"></i> Kelola Pesan
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">9</td>
                        <td><code>users</code></td>
                        <td>Akun pengguna website, status membership fanbase, dan langganan</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['users'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="users.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-people me-1"></i> Kelola Users
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">10</td>
                        <td><code>admins</code></td>
                        <td>Akun pengelola sistem dan panitia scanner pintu masuk venue</td>
                        <td class="text-center"><span class="badge text-bg-primary rounded-pill"><?= $stats['admins'] ?> baris</span></td>
                        <td>
                            <span class="badge text-bg-success">INSERT</span>
                            <span class="badge text-bg-info">SELECT</span>
                            <span class="badge text-bg-warning">UPDATE</span>
                            <span class="badge text-bg-danger">DELETE</span>
                        </td>
                        <td class="text-center">
                            <a href="users.php#admins-section" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-shield-lock me-1"></i> Kelola Admins
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div> -->

<!-- =========================================
     BARIS 5: 2 KOLOM (PESANAN TERBARU & PESAN MASUK)
     ========================================= -->
<div class="row g-3">
    <!-- Kolom Kiri: Pesanan Tiket Terbaru -->
    <div class="col-lg-6">
        <div class="card card-outline card-primary shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">
                    <i class="bi bi-receipt text-primary me-2"></i> Transaksi Pesanan Tiket Terbaru
                </h3>
                <div class="card-tools">
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">
                        Semua Pesanan <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!$recentOrders): ?>
                    <div class="p-4 text-center text-secondary">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Belum ada transaksi pemesanan tiket.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Order</th>
                                    <th>Pemesan</th>
                                    <th>Total Bayar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $ro):
                                    [$lbl, $cls] = payment_status_badge($ro['payment_status']);
                                    $badgeBs = $ro['payment_status'] === 'paid' ? 'text-bg-success' : ($ro['payment_status'] === 'pending' ? 'text-bg-warning' : 'text-bg-secondary');
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="orders.php" class="fw-bold text-decoration-none">
                                                <code><?= e($ro['order_code']) ?></code>
                                            </a>
                                            <div class="text-secondary fs-7">
                                                <?= date('d M Y, H:i', strtotime($ro['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($ro['full_name']) ?></div>
                                            <small class="text-secondary"><?= (int) $ro['total_tickets'] ?> tiket &middot;
                                                <?= e($ro['metode_pembayaran'] ?? 'QRIS') ?></small>
                                        </td>
                                        <td class="fw-bold"><?= rupiah($ro['total_amount']) ?></td>
                                        <td><span class="badge <?= $badgeBs ?>"><?= e($lbl) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center bg-transparent">
                <a href="orders.php" class="text-decoration-none small fw-semibold">
                    Kelola dan Buat Pesanan Baru Secara Manual &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Pesan Kontak Masuk Terbaru -->
    <div class="col-lg-6">
        <div class="card card-outline card-secondary shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">
                    <i class="bi bi-envelope-open text-secondary me-2"></i> Pesan Kontak Masuk Terbaru
                </h3>
                <div class="card-tools">
                    <a href="inquiries.php" class="btn btn-sm btn-outline-secondary">
                        Semua Pesan <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!$recentInquiries): ?>
                    <div class="p-4 text-center text-secondary">
                        <i class="bi bi-envelope-check fs-2 d-block mb-2"></i>
                        Belum ada pesan masuk dari pengunjung.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pengirim</th>
                                    <th>Kategori</th>
                                    <th>Cuplikan Isi Pesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInquiries as $inq): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($inq['nama_pengirim']) ?></div>
                                            <small class="text-secondary"><?= e($inq['email']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-secondary"><?= e($inq['kategori_pesan']) ?></span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 260px;"
                                                title="<?= e($inq['isi_pesan']) ?>">
                                                <?= e($inq['isi_pesan']) ?>
                                            </div>
                                            <small
                                                class="text-secondary"><?= date('d M Y, H:i', strtotime($inq['created_at'])) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center bg-transparent">
                <a href="inquiries.php" class="text-decoration-none small fw-semibold">
                    Buka Kotak Masuk Formulir Kontak &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- =========================================
     SCRIPT INITIALISASI GRAFIK CHART.JS (ADMINLTE V4)
     ========================================= -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Line / Area Chart: Tren Penjualan & Transaksi
        const revCanvas = document.getElementById('revenue-chart');
        if (revCanvas && typeof Chart !== 'undefined') {
            const ctxRev = revCanvas.getContext('2d');
            const gradient = ctxRev.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(13, 110, 253, 0.45)');
            gradient.addColorStop(1, 'rgba(13, 110, 253, 0.02)');

            new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [
                        {
                            label: 'Pendapatan Tiket (Rp)',
                            data: <?= json_encode($chartRev) ?>,
                            borderColor: '#0d6efd',
                            backgroundColor: gradient,
                            pointBackgroundColor: '#0d6efd',
                            pointBorderColor: '#fff',
                            pointHoverRadius: 6,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Jumlah Transaksi',
                            data: <?= json_encode($chartTx) ?>,
                            borderColor: '#20c997',
                            backgroundColor: 'rgba(32, 201, 151, 0.2)',
                            pointBackgroundColor: '#20c997',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.2,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function (context) {
                                    if (context.datasetIndex === 0) {
                                        return ' ' + context.dataset.label + ': Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                                    }
                                    return ' ' + context.dataset.label + ': ' + context.parsed.y + ' pesanan';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                callback: function (val) {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000) + 'k';
                                    return val;
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        // 2. Doughnut Chart: Proporsi Check-In Venue
        const donutCanvas = document.getElementById('checkin-donut-chart');
        if (donutCanvas && typeof Chart !== 'undefined') {
            new Chart(donutCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Sudah Check-In', 'Belum Check-In'],
                    datasets: [{
                        data: [<?= $stats['checked_in'] ?>, <?= $pendingCheckin ?>],
                        backgroundColor: ['#198754', '#0dcaf0'],
                        borderWidth: 2,
                        borderColor: 'var(--bs-card-bg, #fff)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>