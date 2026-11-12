<?php
/**
 * Konfigurasi koneksi database (default Laragon).
 * Ubah nilai di bawah jika kredensial MySQL Anda berbeda.
 */

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'db_barasuara';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Mengembalikan satu instance PDO yang dipakai bersama (lazy singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Samakan zona waktu sesi MySQL dengan PHP (WIB) agar jadwal penjualan konsisten.
        $pdo->exec("SET time_zone = '+07:00'");
    }

    return $pdo;
}

