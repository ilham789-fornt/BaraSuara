<?php
/**
 * Navigasi utama bersama.
 * Variabel yang dibaca: $activePage (index|about|music|tour|contact|my-orders|login|register)
 */
$activePage = $activePage ?? '';
$navUser = current_user();
$hasAdminSession = !empty($_SESSION['admin_id']);

$navLinks = [
    'index'   => ['index.php', 'Beranda'],
    'about'   => ['about.php', 'Tentang'],
    'music'   => ['music.php', 'Musik'],
    'tour'    => ['tour.php', 'Jadwal Tour'],
    'contact' => ['contact.php', 'Kontak'],
];

$navLink = static function (string $key, string $href, string $label, string $extraClass = '') use ($activePage): string {
    $isActive = $key === $activePage;
    $class = trim(($isActive ? 'active ' : '') . $extraClass);
    return sprintf(
        '<a href="%s"%s%s>%s</a>',
        e($href),
        $class !== '' ? ' class="' . e($class) . '"' : '',
        $isActive ? ' aria-current="page"' : '',
        $label
    );
};
?>
<header>
    <div class="logo">
        <h1>Barasuara</h1>
    </div>
    <input type="checkbox" id="nav-toggle" class="hamburger-toggle" aria-hidden="true">
    <label for="nav-toggle" class="hamburger-btn" aria-label="Buka/tutup menu navigasi">
        <span></span>
        <span></span>
        <span></span>
    </label>
    <nav aria-label="Navigasi Utama">
        <?php foreach ($navLinks as $key => [$href, $label]): ?>
            <?= $navLink($key, $href, e($label)) ?>
        <?php endforeach; ?>

        <?php if ($hasAdminSession): ?>
            <a href="admin/index.php" class="nav-auth-link"><i class="fas fa-user-shield" aria-hidden="true"></i> Dashboard Admin</a>
        <?php endif; ?>

        <?php if ($navUser): ?>
            <?= $navLink('my-orders', 'my-orders.php', '<i class="fas fa-ticket-alt" aria-hidden="true"></i> Tiket Saya') ?>
            <form action="logout.php" method="post" class="nav-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="nav-logout-btn" title="Keluar dari akun <?= e($navUser['full_name']) ?>">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Keluar
                </button>
            </form>
        <?php elseif (!$hasAdminSession): ?>
            <?= $navLink('login', 'login.php', '<i class="fas fa-user" aria-hidden="true"></i> Masuk', 'nav-auth-link') ?>
        <?php endif; ?>
    </nav>
</header>
