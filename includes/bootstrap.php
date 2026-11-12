<?php
/**
 * Bootstrap aplikasi: session, zona waktu, koneksi DB, dan helper umum.
 * Wajib di-require di baris paling atas setiap halaman PHP (sebelum output HTML).
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/* ---------------------------------------------------------------------
 * Output & format
 * ------------------------------------------------------------------- */

/** Escape string untuk output HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format angka menjadi Rupiah, contoh: Rp 175.000 */
function rupiah(float|int|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

const BULAN_ID = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
    'Agustus', 'September', 'Oktober', 'November', 'Desember'];

/** Format tanggal Indonesia, contoh: 12 Desember 2026 (opsional dengan jam). */
function tanggal_id(string $datetime, bool $withTime = false): string
{
    $ts = strtotime($datetime);
    $text = date('j', $ts) . ' ' . BULAN_ID[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $text . ', ' . date('H:i', $ts) . ' WIB' : $text;
}

/** Nama bulan singkat (3 huruf) untuk kartu tanggal. */
function bulan_singkat(string $date): string
{
    return substr(BULAN_ID[(int) date('n', strtotime($date))], 0, 3);
}

/**
 * Memotong isi konten berita menjadi ringkasan yang rapi berdasarkan batas kata.
 */
function ringkasan_teks(string $text, int $maxWords = 22, int $maxChars = 140): string
{
    $clean = trim(strip_tags($text));
    if ($clean === '') {
        return '';
    }

    $words = preg_split('/\s+/', $clean);
    if (count($words) <= $maxWords && mb_strlen($clean) <= $maxChars) {
        return $clean;
    }

    $slicedWords = array_slice($words, 0, $maxWords);
    $excerpt = implode(' ', $slicedWords);

    if (mb_strlen($excerpt) > $maxChars) {
        $excerpt = mb_strimwidth($excerpt, 0, $maxChars, '');
        $lastSpace = mb_strrpos($excerpt, ' ');
        if ($lastSpace !== false) {
            $excerpt = mb_substr($excerpt, 0, $lastSpace);
        }
    }

    return rtrim($excerpt, " \t\n\r\0\x0B.,;:-") . '...';
}

/* ---------------------------------------------------------------------
 * Redirect & flash message
 * ------------------------------------------------------------------- */

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Hanya izinkan redirect ke halaman lokal (mencegah open-redirect).
 * Contoh valid: "order.php?tour=1".
 */
function safe_redirect_target(?string $target, string $fallback = 'index.php'): string
{
    $target = trim((string) $target);
    if ($target === '' || !preg_match('#^[a-zA-Z0-9_\-]+\.php(\?[a-zA-Z0-9_\-=&%.]*)?$#', $target)) {
        return $fallback;
    }
    return $target;
}

/** Simpan pesan sekali tampil. $type: success | error | info */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Ambil & hapus seluruh flash message. */
function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------------------------------------------------------------------
 * CSRF protection
 * ------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validasi token CSRF pada request POST; hentikan request jika tidak valid. */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        flash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect(safe_redirect_target($_SERVER['HTTP_REFERER'] ?? '', 'index.php'));
    }
}

/* ---------------------------------------------------------------------
 * Autentikasi user (member / non-member)
 * ------------------------------------------------------------------- */

/** Data user yang sedang login, atau null. */
function current_user(): ?array
{
    static $user = false;

    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, full_name, email, role FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if ($user === null) {
                unset($_SESSION['user_id']);
            }
        }
    }

    return $user;
}

/**
 * Wajib login. Jika belum, arahkan ke halaman login lalu kembali ke halaman ini.
 */
function require_login(string $message = 'Silakan masuk atau daftar akun terlebih dahulu.'): array
{
    $user = current_user();
    if ($user === null) {
        $self = basename($_SERVER['PHP_SELF']);
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $back = $query !== '' ? $self . '?' . $query : $self;
        flash('info', $message);
        redirect('login.php?redirect=' . urlencode(safe_redirect_target($back)));
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

