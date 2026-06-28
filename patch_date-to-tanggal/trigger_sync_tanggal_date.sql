-- trigger_sync_tanggal_date.sql
-- Tujuan: Tambahkan kolom `date` jika belum ada, sinkronkan nilainya dari `tanggal`, dan buat trigger
-- Pastikan backup database sebelum menjalankan skrip ini.

-- 1) Tambah kolom `date` bila belum ada (MySQL 8+ mendukung IF NOT EXISTS)
ALTER TABLE `tbl_materi` ADD COLUMN IF NOT EXISTS `date` DATE NULL AFTER `tanggal`;

-- 2) Sinkronkan data awal
UPDATE `tbl_materi` SET `date` = `tanggal` WHERE (`date` IS NULL OR `date` != `tanggal`);

-- 3) Hapus trigger lama jika ada
DROP TRIGGER IF EXISTS `trg_tbl_materi_sync_date_before_insert`;
DROP TRIGGER IF EXISTS `trg_tbl_materi_sync_date_before_update`;

-- 4) Buat trigger BEFORE INSERT untuk sinkronisasi
DELIMITER $$
CREATE TRIGGER `trg_tbl_materi_sync_date_before_insert` BEFORE INSERT ON `tbl_materi`
FOR EACH ROW
BEGIN
  SET NEW.`date` = NEW.`tanggal`;
END$$

CREATE TRIGGER `trg_tbl_materi_sync_date_before_update` BEFORE UPDATE ON `tbl_materi`
FOR EACH ROW
BEGIN
  SET NEW.`date` = NEW.`tanggal`;
END$$
DELIMITER ;

-- Selesai: sekarang setiap INSERT/UPDATE akan menjaga `date` = `tanggal`.
-- Catatan: Jika server MySQL lebih tua dan tidak mendukung "ADD COLUMN IF NOT EXISTS",
-- jalankan pemeriksaan manual di INFORMATION_SCHEMA atau gunakan phpMyAdmin untuk menambah kolom.
