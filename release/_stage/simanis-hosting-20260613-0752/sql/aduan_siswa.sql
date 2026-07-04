-- Modul Aduan Siswa
-- Laporan tampil anonim untuk petugas, tetapi admin tetap dapat melihat identitas pelapor.

CREATE TABLE IF NOT EXISTS tbl_aduan_siswa (
    id_aduan INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_aduan VARCHAR(30) NOT NULL,
    no_induk_pelapor VARCHAR(50) NOT NULL,
    nama_pelapor VARCHAR(150) NOT NULL DEFAULT '',
    kelas_pelapor VARCHAR(80) NOT NULL DEFAULT '',
    kategori VARCHAR(80) NOT NULL,
    judul VARCHAR(180) NOT NULL,
    isi_laporan TEXT NOT NULL,
    lokasi VARCHAR(180) DEFAULT NULL,
    tanggal_kejadian DATE DEFAULT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'baru',
    tahap_aktif VARCHAR(40) NOT NULL DEFAULT 'stpks',
    prioritas VARCHAR(20) NOT NULL DEFAULT 'normal',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id_aduan),
    UNIQUE KEY uniq_kode_aduan (kode_aduan),
    KEY idx_status_tahap (status, tahap_aktif),
    KEY idx_pelapor (no_induk_pelapor),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbl_aduan_tindak_lanjut (
    id_tindak INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_aduan INT UNSIGNED NOT NULL,
    tahap VARCHAR(40) NOT NULL,
    aksi VARCHAR(60) NOT NULL,
    catatan TEXT DEFAULT NULL,
    handled_by VARCHAR(50) NOT NULL DEFAULT '',
    handled_name VARCHAR(150) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_tindak),
    KEY idx_aduan (id_aduan),
    CONSTRAINT fk_aduan_tindak
        FOREIGN KEY (id_aduan) REFERENCES tbl_aduan_siswa (id_aduan)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
