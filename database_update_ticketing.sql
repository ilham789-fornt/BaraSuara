-- =====================================================================
-- UPDATE DATA DEMO TICKETING (jalankan sekali pada db_barasuara yang sudah ada)
-- Tujuan:
--   1. Memperbaiki role admin 'super_admin' yang tidak ada di ENUM -> 'admin'.
--   2. Menggeser jadwal konser ke Desember 2026 agar penjualan bisa diuji.
--   3. Menambahkan gelombang Normal untuk setiap kota (Presale -> Normal otomatis sesuai jadwal).
--
-- Skenario demo yang dihasilkan (relatif terhadap November 2026):
--   Jakarta    : Presale SEDANG BERLANGSUNG, Normal menyusul setelah Presale berakhir.
--   Bandung    : Presale BELUM DIBUKA (tampil "Segera Dibuka").
--   Yogyakarta : status_event = 0 (tampil "Sold Out").
-- =====================================================================

USE `db_barasuara`;

-- 1. Perbaikan role admin
UPDATE `admins` SET `role` = 'admin' WHERE `username` = 'superadmin';

-- 2. Jadwal konser
UPDATE `tours` SET `tanggal_konser` = '2026-12-12' WHERE `tour_code` = 'JKT-01';
UPDATE `tours` SET `tanggal_konser` = '2026-12-19' WHERE `tour_code` = 'BDG-01';
UPDATE `tours` SET `tanggal_konser` = '2026-12-26' WHERE `tour_code` = 'YOG-01';

-- 3. Gelombang tiket (Presale yang sudah ada diperbarui, Normal ditambahkan)
UPDATE `ticket_tiers` tt JOIN `tours` t ON t.id = tt.tour_id
SET tt.waktu_mulai = '2026-11-01 10:00:00', tt.waktu_berakhir = '2026-11-20 23:59:59'
WHERE t.tour_code = 'JKT-01' AND tt.nama_gelombang = 'Presale';

UPDATE `ticket_tiers` tt JOIN `tours` t ON t.id = tt.tour_id
SET tt.waktu_mulai = '2026-11-25 10:00:00', tt.waktu_berakhir = '2026-12-02 23:59:59'
WHERE t.tour_code = 'BDG-01' AND tt.nama_gelombang = 'Presale';

INSERT INTO `ticket_tiers` (`tour_id`, `nama_gelombang`, `price`, `kuota`, `max_per_order`, `waktu_mulai`, `waktu_berakhir`, `is_active`)
SELECT t.id, 'Normal', 225000.00, 700, 2, '2026-11-21 10:00:00', '2026-12-11 23:59:59', TRUE
FROM `tours` t
WHERE t.tour_code = 'JKT-01'
  AND NOT EXISTS (SELECT 1 FROM `ticket_tiers` x WHERE x.tour_id = t.id AND x.nama_gelombang = 'Normal');

INSERT INTO `ticket_tiers` (`tour_id`, `nama_gelombang`, `price`, `kuota`, `max_per_order`, `waktu_mulai`, `waktu_berakhir`, `is_active`)
SELECT t.id, 'Normal', 200000.00, 550, 2, '2026-12-03 10:00:00', '2026-12-18 23:59:59', TRUE
FROM `tours` t
WHERE t.tour_code = 'BDG-01'
  AND NOT EXISTS (SELECT 1 FROM `ticket_tiers` x WHERE x.tour_id = t.id AND x.nama_gelombang = 'Normal');

