-- ========================================================
-- PATCH MIGRASI DATABASE SIJURNAL (PERUBAHAN TERBARU)
-- SIMANIS - SMAN 1 SUMBER
-- Tanggal: 22 Juli 2026
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- 1. Tambah kolom agama pada tbl_siswa dan tbl_guru jika belum ada
ALTER TABLE `tbl_siswa` ADD COLUMN IF NOT EXISTS `agama` VARCHAR(50) DEFAULT 'Islam';
ALTER TABLE `tbl_guru` ADD COLUMN IF NOT EXISTS `agama` VARCHAR(50) DEFAULT 'Islam';

-- 2. Update seluruh agama siswa & guru menjadi 'Islam'
UPDATE `tbl_siswa` SET `agama` = 'Islam';
UPDATE `tbl_guru` SET `agama` = 'Islam';

-- 3. Bersihkan spasi di awal/akhir pada no_induk, nama, kelas, status
UPDATE `tbl_siswa` SET 
    `no_induk` = TRIM(`no_induk`),
    `nama_siswa` = TRIM(`nama_siswa`),
    `kelas` = TRIM(`kelas`),
    `status` = TRIM(`status`);

UPDATE `tbl_pengguna` SET `no_induk` = TRIM(`no_induk`);

-- 4. Tambah kolom jam presensi pada tbl_presensi_setting
ALTER TABLE `tbl_presensi_setting` ADD COLUMN IF NOT EXISTS `jam_pulang_mulai` TIME DEFAULT '15:30:00';
ALTER TABLE `tbl_presensi_setting` ADD COLUMN IF NOT EXISTS `jam_pulang_selesai` TIME DEFAULT '17:00:00';
ALTER TABLE `tbl_presensi_setting` ADD COLUMN IF NOT EXISTS `jam_masuk_batas` TIME DEFAULT '07:00:00';

UPDATE `tbl_presensi_setting` SET 
    `jam_pulang_mulai` = '15:30:00',
    `jam_pulang_selesai` = '17:00:00',
    `jam_masuk_batas` = '07:00:00';

-- 5. Tambah / update konfigurasi jam presensi di tbl_app_config
INSERT INTO `tbl_app_config` (`kunci`, `nilai`) VALUES ('jam_pulang_mulai', '15:30:00') ON DUPLICATE KEY UPDATE `nilai`='15:30:00';
INSERT INTO `tbl_app_config` (`kunci`, `nilai`) VALUES ('jam_pulang_selesai', '17:00:00') ON DUPLICATE KEY UPDATE `nilai`='17:00:00';
INSERT INTO `tbl_app_config` (`kunci`, `nilai`) VALUES ('jam_masuk_batas', '07:00:00') ON DUPLICATE KEY UPDATE `nilai`='07:00:00';
