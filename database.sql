-- =====================================================================
-- DATABASE: db_barasuara
-- Proyek: Website Fanbase & Ticketing Barasuara (Jalaran Sadrah 2026)
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =====================================================================

-- CREATE DATABASE IF NOT EXISTS `db_barasuara`
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;

-- USE `db_barasuara`;

-- SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. TABEL: admins (Sistem & Scanner Panitia)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'gate_scanner') NOT NULL DEFAULT 'admin',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. TABEL: users (Member & Non-Member)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(250) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('member', 'non_member') NOT NULL DEFAULT 'non_member',
  `subscribe_token` VARCHAR(65) NULL UNIQUE,
  `is_subscribed` BOOLEAN NOT NULL DEFAULT FALSE,
  `subscribed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. TABEL: tours (Jadwal Rangkaian Konser)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `tours`;
CREATE TABLE `tours` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tour_code` VARCHAR(20) NOT NULL UNIQUE,
  `nama_tur` VARCHAR(150) NOT NULL DEFAULT 'Jalaran Sadrah 2026',
  `kota` VARCHAR(100) NOT NULL,
  `venue` VARCHAR(200) NOT NULL,
  `tanggal_konser` DATE NOT NULL,
  `status_event` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. TABEL: ticket_tiers (Gelombang Tiket: Presale / Normal)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `ticket_tiers`;
CREATE TABLE `ticket_tiers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tour_id` INT NOT NULL,
  `nama_gelombang` ENUM('Presale', 'Normal') NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `kuota` INT NOT NULL,
  `max_per_order` INT NOT NULL DEFAULT 2,
  `waktu_mulai` TIMESTAMP NOT NULL,
  `waktu_berakhir` TIMESTAMP NOT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ticket_tiers_tour`
    FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `uq_ticket_tiers_tour_wave`
    UNIQUE (`tour_id`, `nama_gelombang`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. TABEL: orders (Pemesanan & Transaksi Tiket)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(40) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `total_tickets` INT NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `no_whatsapp` VARCHAR(25) NOT NULL,
  `metode_pembayaran` VARCHAR(50) NULL DEFAULT NULL,
  `payment_status` ENUM('pending', 'paid', 'expired', 'failed') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. TABEL: order_tickets (E-Ticket Individual & QR Code)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `order_tickets`;
CREATE TABLE `order_tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `tier_id` INT NOT NULL,
  `ticket_code` VARCHAR(150) NOT NULL UNIQUE,
  `holder_name` VARCHAR(150) NOT NULL,
  `is_checked_in` BOOLEAN NOT NULL DEFAULT FALSE,
  `checked_in_at` TIMESTAMP NULL DEFAULT NULL,
  `scanned_by` INT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_order_tickets_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_tickets_tier`
    FOREIGN KEY (`tier_id`) REFERENCES `ticket_tiers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_order_tickets_admin`
    FOREIGN KEY (`scanned_by`) REFERENCES `admins` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. TABEL: albums (Katalog Diskografi)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `albums`;
CREATE TABLE `albums` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul_album` VARCHAR(150) NOT NULL,
  `tahun_rilis` INT NOT NULL,
  `cover_url` VARCHAR(255) NULL,
  `deskripsi` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. TABEL: tracks (Lagu & YouTube Embed Player)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `tracks`;
CREATE TABLE `tracks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `album_id` INT NOT NULL,
  `judul_lagu` VARCHAR(150) NOT NULL,
  `track_number` INT NULL,
  `youtube_url` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tracks_album`
    FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. TABEL: news_posts (Berita & Rilis Resmi di Beranda)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `news_posts`;
CREATE TABLE `news_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `konten` TEXT NOT NULL,
  `thumbnail_url` VARCHAR(255) NULL,
  `kategori` VARCHAR(50) NOT NULL DEFAULT 'Update',
  `author_id` INT NULL DEFAULT NULL,
  `published_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_news_author`
    FOREIGN KEY (`author_id`) REFERENCES `admins` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. TABEL: contact_inquiries (Pesan Masuk Formulir Kontak)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `contact_inquiries`;
CREATE TABLE `contact_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pengirim` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `no_whatsapp` VARCHAR(25) NOT NULL,
  `kategori_pesan` VARCHAR(50) NOT NULL,
  `tanggal_acara_usulan` DATE NULL DEFAULT NULL,
  `isi_pesan` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA AWAL (Contoh Data yang Selaras dengan Halaman Web)
-- =====================================================================

-- 1. Admin Default (Password: admin123 -> bcrypt)
INSERT INTO `admins` (`username`, `email`, `password_hash`, `role`) VALUES
('superadmin', 'admin@barasuara.com', '$2y$12$Rpofj5oaCldEF32D3LZvqe5oV1DLt6yBQjluEmj6KcmYOaoOalnFC', 'admin'),
('gatekeeper1', 'scanner@barasuara.com', '$2y$12$Rpofj5oaCldEF32D3LZvqe5oV1DLt6yBQjluEmj6KcmYOaoOalnFC', 'gate_scanner');

-- 2. User Contoh (Password: user123)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_subscribed`) VALUES
('Ilham Ramadhan', 'ilham@example.com', '$2y$12$kj6SON2uxQ620IIzViae2.1YSjY5VHJSrMfIFPGiROy9m2hzmJrPS', 'member', TRUE),
('Budi Santoso', 'budi@example.com', '$2y$12$kj6SON2uxQ620IIzViae2.1YSjY5VHJSrMfIFPGiROy9m2hzmJrPS', 'non_member', FALSE);

-- 3. Data Tour (Sesuai tour.php)
INSERT INTO `tours` (`tour_code`, `nama_tur`, `kota`, `venue`, `tanggal_konser`, `status_event`) VALUES
('JKT-01', 'Jalaran Sadrah 2026', 'Jakarta', 'Stadion Madya Gelora Bung Karno', '2026-12-12', TRUE),
('BDG-01', 'Jalaran Sadrah 2026', 'Bandung', 'Eldorado Dome', '2026-12-19', TRUE),
('YOG-01', 'Jalaran Sadrah 2026', 'Yogyakarta', 'Jogja Expo Center (JEC)', '2026-12-26', FALSE);

-- 4. Tier Tiket (Presale -> Normal, aktif otomatis sesuai jadwal penjualan)
INSERT INTO `ticket_tiers` (`tour_id`, `nama_gelombang`, `price`, `kuota`, `max_per_order`, `waktu_mulai`, `waktu_berakhir`, `is_active`) VALUES
(1, 'Presale', 175000.00, 300, 2, '2026-11-01 10:00:00', '2026-11-20 23:59:59', TRUE),
(2, 'Presale', 150000.00, 250, 2, '2026-11-25 10:00:00', '2026-12-02 23:59:59', TRUE),
(1, 'Normal',  225000.00, 700, 2, '2026-11-21 10:00:00', '2026-12-11 23:59:59', TRUE),
(2, 'Normal',  200000.00, 550, 2, '2026-12-03 10:00:00', '2026-12-18 23:59:59', TRUE);

-- 5. Album (Sesuai music.php)
INSERT INTO `albums` (`id`, `judul_album`, `tahun_rilis`, `cover_url`, `deskripsi`) VALUES
(1, 'Taifun', 2015, 'images/Taifun.png', 'Album perdana yang memperkenalkan karakter musik Barasuara yang eksplosif, penuh dengan harmoni vokal bersahutan dan lirik puitis.'),
(2, 'Pikiran dan Perjalanan', 2019, 'images/pikiran-perjalanan.png', 'Eksperimen sonik dengan aransemen instrumen berlapis yang jauh lebih kompleks serta membawa tema kedewasaan dan dinamika kehidupan.'),
(3, 'Jalaran Sadrah', 2024, 'images/JALARAN_SADRAH.png', 'Karya monumental pasrah yang memadukan kemegahan aransemen orkestra Erwin Gutawa serta kolaborasi folk magis bersama Sujiwo Tejo.');

-- 6. Tracks Lagu Beserta ID Video YouTube (Sesuai music.php)
-- Album 1: Taifun
INSERT INTO `tracks` (`album_id`, `judul_lagu`, `track_number`, `youtube_url`) VALUES
(1, 'Nyala Suara', 1, 'zCKf6bXI_44'),
(1, 'Sendu Melagu', 2, 'lYDZ-ov68P0'),
(1, 'Bahas Bahasa', 3, 'eOQ7aFm6gpo'),
(1, 'Hagia', 4, 'HihvMi2mRlo'),
(1, 'Api & Lentera', 5, '_in_UdCY478'),
(1, 'Mengunci Ingatan', 6, 'YbYQk2oyGWc'),
(1, 'Tarintih', 7, 'uP-w5EwMKyg'),
(1, 'Menunggang Badai', 8, '-IlA3kPjuw0'),
(1, 'Taifun', 9, 'Kfd1EjLIblM');

-- Album 2: Pikiran dan Perjalanan
INSERT INTO `tracks` (`album_id`, `judul_lagu`, `track_number`, `youtube_url`) VALUES
(2, 'Pikiran dan Perjalanan', 1, 'oto8TmSFEK8'),
(2, 'Guna Manusia', 2, 'Ik_O-r8kiIc'),
(2, 'Pancarona', 3, 'ia1jzbIVvkM'),
(2, 'Seribu Racun', 4, 'gt7PZcQyW34'),
(2, 'Masa Mesias', 5, 'f1oyBHjs3o4'),
(2, 'Tentukan Arah', 6, '8DFdoJknoSQ'),
(2, 'Tirai Cahaya', 7, 'h07PZ2IyxzM'),
(2, 'Haluan', 8, 'A1TCaBdyz_8'),
(2, 'Samara', 9, 'JoLryGtCxQA');

-- Album 3: Jalaran Sadrah
INSERT INTO `tracks` (`album_id`, `judul_lagu`, `track_number`, `youtube_url`) VALUES
(3, 'Antea', 1, 'a-sNUwW7g7Q'),
(3, 'Etalase', 2, 'OQWZe5klDaA'),
(3, 'Merayakan Fana', 3, 'QLyqdOQWMmw'),
(3, 'Fatalis', 4, 'RlaHAtv90Co'),
(3, 'Biyang (feat. S. Tejo)', 5, '1QBgKkSCsyE'),
(3, 'Terbuang Dalam Waktu', 6, 'xCchJ0ujd0o'),
(3, 'Manusia (Sumarah)', 7, 'nqxV4X6WkxA'),
(3, 'Hitam dan Biru', 8, 'RMtw_SINxcA'),
(3, 'Habis Gelap', 9, 'GJ4gDua4FNI');

-- 7. Berita Awal (Sesuai index.php)
INSERT INTO `news_posts` (`judul`, `slug`, `konten`, `kategori`, `author_id`) VALUES
('Persiapan Tour "Pikiran dan Perjalanan" 2026', 'persiapan-tour-pikiran-dan-perjalanan-2026', 'Simak persiapan intensif Iga Massardi dkk menjelang pembukaan tour resmi di Jakarta...', 'Tour', 1),
('Merchandise Eksklusif Edisi Terbatas', 'merchandise-eksklusif-edisi-terbatas', 'Kolaborasi visual terbaru untuk koleksi pakaian eksklusif dan cetakan piringan hitam vinyl album Taifun...', 'Update', 1);
