<?php
/**
 * Bootstrap & Autentikasi khusus Panel Admin (tabel `admins`).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/ticketing.php';

/** Ambil data admin yang sedang login, atau null jika belum login / nonaktif. */
function current_admin(): ?array
{
    static $admin = false;

    if ($admin === false) {
        $admin = null;
        if (!empty($_SESSION['admin_id'])) {
            $stmt = db()->prepare(
                'SELECT id, username, email, role, is_active, last_login, created_at
                 FROM admins WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch() ?: null;
            if ($admin === null) {
                unset($_SESSION['admin_id']);
            }
        }
    }

    return $admin;
}

/**
 * Wajib login sebagai admin/scanner.
 * @param string[] $allowedRoles Role yang diizinkan mengakses halaman ini.
 */
function require_admin(array $allowedRoles = ['admin']): array
{
    $admin = current_admin();
    if ($admin === null) {
        flash('info', 'Silakan masuk ke Panel Admin terlebih dahulu.');
        redirect('login.php');
    }

    if (!in_array($admin['role'], $allowedRoles, true)) {
        flash('error', 'Role Anda (' . $admin['role'] . ') hanya memiliki akses ke halaman Gate Scanner.');
        redirect('scan.php');
    }

    return $admin;
}

/** Catat login admin & perbarui kolom last_login. */
function login_admin(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$adminId]);
}
