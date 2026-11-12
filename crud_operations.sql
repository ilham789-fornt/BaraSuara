-- =====================================================================
-- DOKUMEN DELIVERABLE WBS 2.2 & WBS 2.3 (PBL WEB PROGRAMMING 2026)
-- Proyek   : Website Fanbase & Ticketing Barasuara (db_barasuara)
-- Penyusun : Ilham Saputra
-- Cakupan  :
--   - WBS 2.2 : Pengisian & pembacaan data semua tabel (INSERT & SELECT)
--   - WBS 2.3 : Operasi UPDATE dan DELETE untuk semua tabel
-- =====================================================================

USE `db_barasuara`;

-- =====================================================================
-- 1. TABEL: admins (Sistem & Scanner Panitia)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `admins` (`username`, `email`, `password_hash`, `role`, `is_active`)
VALUES ('panitia_jkt', 'panitia.jkt@barasuara.com', '$2y$12$Rpofj5oaCldEF32D3LZvqe5oV1DLt6yBQjluEmj6KcmYOaoOalnFC', 'gate_scanner', TRUE);

-- [READ / SELECT]
SELECT `id`, `username`, `email`, `role`, `is_active`, `last_login`, `created_at`
FROM `admins`
ORDER BY `id` ASC;

-- [UPDATE]
UPDATE `admins`
SET `last_login` = NOW(), `role` = 'admin'
WHERE `username` = 'panitia_jkt';

-- [DELETE]
DELETE FROM `admins`
WHERE `username` = 'panitia_jkt';


-- =====================================================================
-- 2. TABEL: users (Member & Non-Member)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_subscribed`)
VALUES ('Ilham Saputra', 'ilhamsaputra@example.com', '$2y$12$kj6SON2uxQ620IIzViae2.1YSjY5VHJSrMfIFPGiROy9m2hzmJrPS', 'member', TRUE);

-- [READ / SELECT]
SELECT `id`, `full_name`, `email`, `role`, `is_subscribed`, `subscribed_at`, `created_at`
FROM `users`
ORDER BY `created_at` DESC;

-- [UPDATE]
UPDATE `users`
SET `role` = 'member', `is_subscribed` = TRUE, `subscribed_at` = NOW()
WHERE `email` = 'ilhamsaputra@example.com';

-- [DELETE]
DELETE FROM `users`
WHERE `email` = 'ilhamsaputra@example.com';


-- =====================================================================
-- 3. TABEL: tours (Jadwal Rangkaian Konser)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `tours` (`tour_code`, `nama_tur`, `kota`, `venue`, `tanggal_konser`, `status_event`)
VALUES ('SBY-01', 'Jalaran Sadrah 2026', 'Surabaya', 'Jatim Internasional Expo', '2026-12-28', TRUE);

-- [READ / SELECT]
SELECT `id`, `tour_code`, `nama_tur`, `kota`, `venue`, `tanggal_konser`, `status_event`
FROM `tours`
ORDER BY `tanggal_konser` ASC;

-- [UPDATE]
UPDATE `tours`
SET `venue` = 'Grand City Convex Surabaya', `status_event` = TRUE
WHERE `tour_code` = 'SBY-01';

-- [DELETE]
DELETE FROM `tours`
WHERE `tour_code` = 'SBY-01';


-- =====================================================================
-- 4. TABEL: ticket_tiers (Gelombang Tiket: Presale / Normal)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `ticket_tiers` (`tour_id`, `nama_gelombang`, `price`, `kuota`, `max_per_order`, `waktu_mulai`, `waktu_berakhir`, `is_active`)
VALUES (3, 'Presale', 150000.00, 200, 2, '2026-12-01 10:00:00', '2026-12-10 23:59:59', TRUE);

-- [READ / SELECT] (Lengkap dengan JOIN ke tabel tours & perhitungan sisa kuota)
SELECT tt.`id`, t.`kota`, tt.`nama_gelombang`, tt.`price`, tt.`kuota`,
       tt.`waktu_mulai`, tt.`waktu_berakhir`, tt.`is_active`
FROM `ticket_tiers` tt
JOIN `tours` t ON t.`id` = tt.`tour_id`
ORDER BY t.`tanggal_konser` ASC, tt.`waktu_mulai` ASC;

-- [UPDATE]
UPDATE `ticket_tiers`
SET `price` = 160000.00, `kuota` = 250
WHERE `tour_id` = 3 AND `nama_gelombang` = 'Presale';

-- [DELETE]
DELETE FROM `ticket_tiers`
WHERE `tour_id` = 3 AND `nama_gelombang` = 'Presale';


-- =====================================================================
-- 5. TABEL: orders (Pemesanan & Transaksi Tiket)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `orders` (`order_code`, `user_id`, `total_amount`, `total_tickets`, `email`, `no_whatsapp`, `metode_pembayaran`, `payment_status`)
VALUES ('BRS-261109-DEMO01', 1, 350000.00, 2, 'ilham@example.com', '081234567890', 'QRIS', 'pending');

-- [READ / SELECT] (JOIN dengan tabel users)
SELECT o.`id`, o.`order_code`, u.`full_name`, o.`total_tickets`, o.`total_amount`,
       o.`metode_pembayaran`, o.`payment_status`, o.`paid_at`, o.`created_at`
FROM `orders` o
JOIN `users` u ON u.`id` = o.`user_id`
ORDER BY o.`created_at` DESC;

-- [UPDATE] (Konfirmasi pembayaran QRIS menjadi lunas / paid)
UPDATE `orders`
SET `payment_status` = 'paid', `paid_at` = NOW()
WHERE `order_code` = 'BRS-261109-DEMO01';


-- =====================================================================
-- 6. TABEL: order_tickets (E-Ticket Individual & QR Code)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `order_tickets` (`order_id`, `tier_id`, `ticket_code`, `holder_name`, `is_checked_in`)
SELECT o.`id`, 1, 'TKT-JKT-01-DEMO0001', 'Ilham Saputra', FALSE
FROM `orders` o WHERE o.`order_code` = 'BRS-261109-DEMO01';

-- [READ / SELECT] (JOIN ke orders, ticket_tiers, tours, dan admins)
SELECT ot.`ticket_code`, ot.`holder_name`, o.`order_code`, o.`payment_status`,
       t.`kota`, tt.`nama_gelombang`, ot.`is_checked_in`, ot.`checked_in_at`, a.`username` AS `scanned_by_admin`
FROM `order_tickets` ot
JOIN `orders` o ON o.`id` = ot.`order_id`
JOIN `ticket_tiers` tt ON tt.`id` = ot.`tier_id`
JOIN `tours` t ON t.`id` = tt.`tour_id`
LEFT JOIN `admins` a ON a.`id` = ot.`scanned_by`;

-- [UPDATE] (Proses Gate Scanner Check-In di pintu masuk konser)
UPDATE `order_tickets`
SET `is_checked_in` = TRUE, `checked_in_at` = NOW(), `scanned_by` = 2
WHERE `ticket_code` = 'TKT-JKT-01-DEMO0001';

-- [DELETE] (Hapus e-ticket & order demo)
DELETE FROM `order_tickets` WHERE `ticket_code` = 'TKT-JKT-01-DEMO0001';
DELETE FROM `orders` WHERE `order_code` = 'BRS-261109-DEMO01';


-- =====================================================================
-- 7. TABEL: albums (Katalog Diskografi)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `albums` (`judul_album`, `tahun_rilis`, `cover_url`, `deskripsi`)
VALUES ('Live at Lokananta (Special Session)', 2025, 'images/JALARAN_SADRAH.png', 'Rekaman langsung sesi akustik dan orkestra terbatas.');

-- [READ / SELECT]
SELECT `id`, `judul_album`, `tahun_rilis`, `cover_url`, `deskripsi`
FROM `albums`
ORDER BY `tahun_rilis` ASC;

-- [UPDATE]
UPDATE `albums`
SET `deskripsi` = 'Rekaman langsung sesi spesial Barasuara di studio bersejarah Lokananta.'
WHERE `judul_album` = 'Live at Lokananta (Special Session)';


-- =====================================================================
-- 8. TABEL: tracks (Lagu & YouTube Embed Player)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `tracks` (`album_id`, `judul_lagu`, `track_number`, `youtube_url`)
SELECT `id`, 'Bahas Bahasa (Live Session)', 1, 'eOQ7aFm6gpo'
FROM `albums` WHERE `judul_album` = 'Live at Lokananta (Special Session)';

-- [READ / SELECT] (JOIN dengan tabel albums)
SELECT t.`id`, a.`judul_album`, t.`track_number`, t.`judul_lagu`, t.`youtube_url`
FROM `tracks` t
JOIN `albums` a ON a.`id` = t.`album_id`
ORDER BY a.`tahun_rilis` ASC, t.`track_number` ASC;

-- [UPDATE]
UPDATE `tracks`
SET `judul_lagu` = 'Bahas Bahasa (Live at Lokananta)'
WHERE `judul_lagu` = 'Bahas Bahasa (Live Session)';

-- [DELETE] (Hapus track & album demo)
DELETE FROM `tracks` WHERE `judul_lagu` = 'Bahas Bahasa (Live at Lokananta)';
DELETE FROM `albums` WHERE `judul_album` = 'Live at Lokananta (Special Session)';


-- =====================================================================
-- 9. TABEL: news_posts (Berita & Rilis Resmi di Beranda)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `news_posts` (`judul`, `slug`, `konten`, `kategori`, `author_id`)
VALUES ('Pembukaan Presale Tiket Tur Jalaran Sadrah', 'pembukaan-presale-tiket-tur-jalaran-sadrah', 'Tiket gelombang Presale kini resmi dibuka melalui portal resmi Barasuara.', 'Tour', 1);

-- [READ / SELECT]
SELECT np.`id`, np.`judul`, np.`slug`, np.`kategori`, a.`username` AS `author`, np.`published_at`
FROM `news_posts` np
LEFT JOIN `admins` a ON a.`id` = np.`author_id`
ORDER BY np.`published_at` DESC;

-- [UPDATE]
UPDATE `news_posts`
SET `judul` = 'Presale Tiket Tur Jalaran Sadrah 2026 Resmi Dibuka', `kategori` = 'Tour'
WHERE `slug` = 'pembukaan-presale-tiket-tur-jalaran-sadrah';

-- [DELETE]
DELETE FROM `news_posts`
WHERE `slug` = 'pembukaan-presale-tiket-tur-jalaran-sadrah';


-- =====================================================================
-- 10. TABEL: contact_inquiries (Pesan Masuk Formulir Kontak)
-- =====================================================================
-- [CREATE / INSERT]
INSERT INTO `contact_inquiries` (`nama_pengirim`, `email`, `no_whatsapp`, `kategori_pesan`, `tanggal_acara_usulan`, `isi_pesan`)
VALUES ('Ilham Saputra - Panitia Kampus', 'ilhamsaputra@example.com', '081234567890', 'booking', '2026-12-29', 'Undangan tampil sebagai bintang tamu utama penutupan festival kampus.');

-- [READ / SELECT]
SELECT `id`, `nama_pengirim`, `email`, `no_whatsapp`, `kategori_pesan`, `tanggal_acara_usulan`, `isi_pesan`, `created_at`
FROM `contact_inquiries`
ORDER BY `created_at` DESC;

-- [UPDATE]
UPDATE `contact_inquiries`
SET `kategori_pesan` = 'partnership', `isi_pesan` = 'Undangan kolaborasi dan bintang tamu utama penutupan festival kampus.'
WHERE `email` = 'ilhamsaputra@example.com';

-- [DELETE]
DELETE FROM `contact_inquiries`
WHERE `email` = 'ilhamsaputra@example.com';
