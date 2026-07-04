-- Jurnal 7 KIH: 7 Kebiasaan Anak Indonesia Hebat
-- Jalankan pada database jurnal jika ingin membuat tabel secara manual.

CREATE TABLE IF NOT EXISTS tbl_7kih_jurnal (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    no_induk VARCHAR(50) NOT NULL,
    nama_siswa VARCHAR(150) NOT NULL DEFAULT '',
    kelas VARCHAR(60) NOT NULL DEFAULT '',
    tanggal DATE NOT NULL,
    habit_key VARCHAR(40) NOT NULL,
    habit_label VARCHAR(120) NOT NULL,
    prayer_key VARCHAR(30) NOT NULL DEFAULT '',
    submitted_at DATETIME NOT NULL,
    window_start TIME DEFAULT NULL,
    window_end TIME DEFAULT NULL,
    timeliness_status ENUM('sangat_tepat','tepat','terlambat','di_luar_waktu') NOT NULL DEFAULT 'tepat',
    score DECIMAL(5,2) NOT NULL DEFAULT 0,
    photo_path VARCHAR(255) DEFAULT NULL,
    photo_size INT UNSIGNED NOT NULL DEFAULT 0,
    photo_hash VARCHAR(80) DEFAULT NULL,
    is_photo_stored TINYINT(1) NOT NULL DEFAULT 1,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_7kih_daily_slot (no_induk, tanggal, habit_key, prayer_key),
    KEY idx_7kih_tanggal_kelas (tanggal, kelas),
    KEY idx_7kih_siswa_bulan (no_induk, tanggal),
    KEY idx_7kih_habit (habit_key, prayer_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
