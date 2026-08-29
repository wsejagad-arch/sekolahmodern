CREATE TABLE `kelas` (
  `id_materi` int NOT NULL AUTO_INCREMENT,
  `id_mapel` int NOT NULL,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `materi` text COLLATE utf8mb4_general_ci NOT NULL,
  `kegiatan` text COLLATE utf8mb4_general_ci NOT NULL,
  `absen` text COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci NOT NULL,
  `file_materi` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_materi`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_7kih_jurnal` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_siswa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `kelas` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `tanggal` date NOT NULL,
  `habit_key` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `habit_label` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `prayer_key` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `lat` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lng` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `window_start` time DEFAULT NULL,
  `window_end` time DEFAULT NULL,
  `timeliness_status` enum('sangat_tepat','tepat','terlambat','di_luar_waktu') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tepat',
  `score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `photo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo_size` int unsigned NOT NULL DEFAULT '0',
  `photo_hash` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_photo_stored` tinyint(1) NOT NULL DEFAULT '1',
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_7kih_daily_slot` (`no_induk`,`tanggal`,`habit_key`,`prayer_key`),
  KEY `idx_7kih_tanggal_kelas` (`tanggal`,`kelas`),
  KEY `idx_7kih_siswa_bulan` (`no_induk`,`tanggal`),
  KEY `idx_7kih_habit` (`habit_key`,`prayer_key`),
  KEY `idx_tbl_7kih_jurnal_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=1358 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_absen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `id_mapel` int DEFAULT NULL,
  `no_induk_guru` varchar(25) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `no_induk` varchar(25) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `sumber` enum('guru','siswa') DEFAULT 'guru',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_guru` varchar(50) DEFAULT NULL,
  `status_akhir` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tbl_absen_id_sekolah` (`id_sekolah`),
  KEY `idx_absen_no_induk` (`no_induk`)
) ENGINE=InnoDB AUTO_INCREMENT=70168 DEFAULT CHARSET=latin1;

CREATE TABLE `tbl_absen_sholat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_sholat` enum('Dzuhur','Jumat') COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `lat` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lng` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_lokasi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Valid',
  `id_sekolah` int DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_absen_sholat` (`no_induk`,`jenis_sholat`,`tanggal`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_admin_pusat` (
  `id_admin_pusat` int NOT NULL AUTO_INCREMENT,
  `username` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Aktif','Non-Aktif') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aktif',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_admin_pusat`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_tbl_admin_pusat_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_aduan_siswa` (
  `id_aduan` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_aduan` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_pelapor` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_pelapor` varchar(150) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `kelas_pelapor` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `kategori` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `judul` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `isi_laporan` text COLLATE utf8mb4_general_ci NOT NULL,
  `lokasi` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_kejadian` date DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'baru',
  `tahap_aktif` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'stpks',
  `prioritas` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_aduan`),
  UNIQUE KEY `uniq_kode_aduan` (`kode_aduan`),
  KEY `idx_status_tahap` (`status`,`tahap_aktif`),
  KEY `idx_pelapor` (`no_induk_pelapor`),
  KEY `idx_created` (`created_at`),
  KEY `idx_tbl_aduan_siswa_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_aduan_tindak_lanjut` (
  `id_tindak` int unsigned NOT NULL AUTO_INCREMENT,
  `id_aduan` int unsigned NOT NULL,
  `tahap` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `aksi` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `handled_by` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `handled_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_tindak`),
  KEY `idx_aduan` (`id_aduan`),
  KEY `idx_tbl_aduan_tindak_lanjut_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_agenda_sekolah` (
  `id_agenda` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `agenda_date` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `dibuat_unit` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Kurikulum',
  `dibuat_oleh_role` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `dibuat_oleh_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `dibuat_oleh_nama` varchar(140) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_agenda`),
  KEY `idx_agenda_waktu` (`agenda_date`,`jam_selesai`),
  KEY `idx_agenda_status` (`is_active`),
  KEY `idx_tbl_agenda_sekolah_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_alumni` (
  `id_alumni` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_siswa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `histori_kelas` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `histori_wali_kelas` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_lulus` int DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_alumni`),
  KEY `idx_no_induk` (`no_induk`),
  KEY `idx_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_anggota_ekskul` (
  `id_anggota` int NOT NULL AUTO_INCREMENT,
  `id_ekskul` int NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` varchar(5) COLLATE utf8mb4_general_ci DEFAULT '',
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_anggota`),
  UNIQUE KEY `id_ekskul` (`id_ekskul`,`no_induk_siswa`),
  KEY `idx_tbl_anggota_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_app_config` (
  `kunci` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`kunci`),
  KEY `idx_tbl_app_config_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_bahan_ajar` (
  `id_bahan` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `id_mapel` int NOT NULL,
  `nama_mapel` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `file_pdf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `warna_bg` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'white',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_bahan`),
  KEY `idx_tbl_bahan_ajar_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_ekinerja_dokumen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_dokumen` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sumber_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `data_json` longtext COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_ekinerja_dokumen_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_ekskul` (
  `id_ekskul` int NOT NULL AUTO_INCREMENT,
  `nama_ekskul` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_ekskul`),
  KEY `idx_tbl_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_ekskul_eraport` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `source_no` int DEFAULT NULL,
  `nama_kelas_ekskul` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_ekskul` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_ekskul` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sekolah_id` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `synced_at` datetime NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ekskul` (`nama_kelas_ekskul`,`nama_ekskul`),
  KEY `idx_tbl_ekskul_eraport_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_ekskul_siswa_discovery_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_id` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `endpoint` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_code` int DEFAULT NULL,
  `has_keyword` tinyint(1) NOT NULL DEFAULT '0',
  `relations_found` int NOT NULL DEFAULT '0',
  `preview_text` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_run_id` (`run_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tbl_ekskul_siswa_discovery_log_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_ekskul_siswa_eraport` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nis` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_siswa` varchar(160) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas_siswa` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_ekskul` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `sumber_endpoint` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `synced_at` datetime NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_relasi` (`nis`,`nama_siswa`,`nama_ekskul`),
  KEY `idx_tbl_ekskul_siswa_eraport_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_guru` (
  `id_guru` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `status_kepegawaian` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `walas` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `nip_guru` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `is_guru_bk` tinyint(1) NOT NULL DEFAULT '0',
  `is_pendamping_literasi` tinyint(1) NOT NULL DEFAULT '0',
  `is_tim_aduan` tinyint(1) NOT NULL DEFAULT '0',
  `agama` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Islam',
  PRIMARY KEY (`id_guru`),
  KEY `idx_tbl_guru_id_sekolah` (`id_sekolah`),
  KEY `idx_tbl_guru_email` (`email`),
  KEY `idx_guru_no_induk` (`no_induk`),
  KEY `idx_guru_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_guru_bk` (
  `id_guru_bk` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_guru_bk`),
  UNIQUE KEY `unik_guru_kelas` (`no_induk`,`kelas`),
  KEY `idx_tbl_guru_bk_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_guru_wali_binaan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_guru_siswa` (`no_induk_guru`,`no_induk_siswa`),
  KEY `idx_guru` (`no_induk_guru`),
  KEY `idx_siswa` (`no_induk_siswa`),
  KEY `idx_kelas` (`kelas`),
  KEY `idx_tbl_guru_wali_binaan_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_guru_wali_jurnal_pendampingan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci NOT NULL,
  `tindak_lanjut` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Dipantau',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_guru_tanggal` (`no_induk_guru`,`tanggal`),
  KEY `idx_siswa` (`no_induk_siswa`),
  KEY `idx_status` (`status`),
  KEY `idx_tbl_guru_wali_jurnal_pendampingan_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_hari` (
  `id_hari` int NOT NULL AUTO_INCREMENT,
  `nama_hari` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_hari`),
  KEY `idx_tbl_hari_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_izin_siswa` (
  `id_izin` int NOT NULL AUTO_INCREMENT,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_izin` date NOT NULL,
  `jenis_izin` enum('Sakit','Izin','Dispensasi') COLLATE utf8mb4_general_ci NOT NULL,
  `detail_izin` text COLLATE utf8mb4_general_ci NOT NULL,
  `lokasi_izin` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_selfie` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waktu_pengajuan` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_izin` enum('Menunggu Wali Kelas','Menunggu Guru BK','Menunggu Validasi','Menunggu Satpam','Disetujui','Disetujui Penuh','Disetujui Sebagian','Ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu Validasi',
  `validasi_guru_mapel` enum('Menunggu','Disetujui','Ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `validator_guru_mapel` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waktu_validasi_guru_mapel` datetime DEFAULT NULL,
  `validasi_wali_kelas` enum('Menunggu','Disetujui','Ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `validator_wali_kelas` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waktu_validasi_wali_kelas` datetime DEFAULT NULL,
  `validasi_guru_bk` enum('Menunggu','Disetujui','Ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `validasi_satpam` enum('Menunggu','Diizinkan Keluar') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `validator_satpam` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waktu_validasi_satpam` datetime DEFAULT NULL,
  `validator_guru_bk` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waktu_validasi_guru_bk` datetime DEFAULT NULL,
  `catatan_penolakan` text COLLATE utf8mb4_general_ci,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `kategori_pengajuan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Tidak Masuk',
  `opsi_kembali` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acc_wali` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `acc_satpam` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `waktu_keluar` datetime DEFAULT NULL,
  `waktu_kembali` datetime DEFAULT NULL,
  `catatan_wali_kelas` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_guru_bk` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_izin`),
  KEY `idx_no_induk_siswa` (`no_induk_siswa`),
  KEY `idx_tanggal_izin` (`tanggal_izin`),
  KEY `idx_status_izin` (`status_izin`),
  KEY `idx_tbl_izin_siswa_id_sekolah` (`id_sekolah`),
  KEY `idx_izin_no_induk` (`no_induk_siswa`),
  KEY `idx_izin_tgl` (`tanggal_izin`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_jadwal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_mapel` int DEFAULT NULL,
  `no_induk` varchar(32) DEFAULT NULL,
  `hari` varchar(16) DEFAULT NULL,
  `kelas` varchar(32) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_jadwal_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `tbl_jadwal_ekskul` (
  `id_jadwal` int NOT NULL AUTO_INCREMENT,
  `id_ekskul` int NOT NULL,
  `hari` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_jadwal`),
  KEY `idx_tbl_jadwal_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_jurnal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `mapel` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jurnal` text COLLATE utf8mb4_general_ci,
  `catatan` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_jurnal_id_sekolah` (`id_sekolah`),
  KEY `idx_jurnal_tgl` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_jurnal_ekskul` (
  `id_jurnal` int NOT NULL AUTO_INCREMENT,
  `id_ekskul` int NOT NULL,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `materi` text COLLATE utf8mb4_general_ci,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_jurnal`),
  UNIQUE KEY `id_ekskul` (`id_ekskul`,`tanggal`),
  KEY `idx_tbl_jurnal_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_jurnal_pendampingan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `nis` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `tindak_lanjut` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Belum Selesai',
  `nip_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_jurnal_pendampingan_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_jurnal_tindak_lanjut` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `periode` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `rekomendasi` text COLLATE utf8mb4_general_ci NOT NULL,
  `progres_status` enum('Belum Dimulai','Berjalan','Selesai') COLLATE utf8mb4_general_ci DEFAULT 'Belum Dimulai',
  `catatan` text COLLATE utf8mb4_general_ci,
  `created_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_siswa_periode` (`no_induk`,`periode`),
  KEY `idx_kelas_periode` (`kelas`,`periode`),
  KEY `idx_tbl_jurnal_tindak_lanjut_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_kehadiran` (
  `id_kehadiran` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `jam_mulai` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jam_selesai` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_ketua_kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status_kehadiran` tinyint(1) NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_kehadiran`),
  KEY `idx_tbl_kehadiran_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `wali_kelas` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `nip_wali` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_kelas`),
  KEY `idx_tbl_kelas_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_konfirmasi_kehadiran_guru` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `id_mapel` int NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_guru` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci DEFAULT '',
  `nama_mapel` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `no_induk_ketua` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_ketua` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Hadir','Telat','Izin','Tidak Hadir Tanpa Tugas','Tidak Hadir Ada Tugas') COLLATE utf8mb4_general_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_konfirm` (`tanggal`,`id_mapel`,`kelas`),
  KEY `idx_tbl_konfirmasi_kehadiran_guru_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_kurikulum_menu` (
  `id_menu` int NOT NULL AUTO_INCREMENT,
  `menu_key` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `menu_title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `menu_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `menu_icon` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bi-link-45deg',
  `icon_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bootstrap',
  `icon_image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_by` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_menu`),
  UNIQUE KEY `uniq_menu_key` (`menu_key`),
  KEY `idx_sort_active` (`is_active`,`sort_order`),
  KEY `idx_tbl_kurikulum_menu_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_laporan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `deskripsi` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `tgl_mulai` (`tgl_mulai`),
  KEY `tgl_selesai` (`tgl_selesai`),
  KEY `idx_tbl_laporan_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `tbl_leger_nilai_raport_siswa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `run_id` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_at` datetime NOT NULL,
  `semester` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `nis` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nisn` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_siswa` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mapel` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `komponen` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` decimal(6,2) NOT NULL,
  `uploaded_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_file` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_run` (`run_id`),
  KEY `idx_kelas` (`kelas`),
  KEY `idx_nis` (`nis`),
  KEY `idx_mapel` (`mapel`),
  KEY `idx_uploaded_at` (`uploaded_at`),
  KEY `idx_tbl_leger_nilai_raport_siswa_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=1153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_leger_siswa_eraport` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_id` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `synced_at` datetime NOT NULL,
  `semester` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `nis` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_siswa` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nilai_rerata` decimal(6,2) DEFAULT NULL,
  `raw_row` longtext COLLATE utf8mb4_general_ci,
  `uploaded_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_file` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_run` (`run_id`),
  KEY `idx_kelas` (`kelas`),
  KEY `idx_nis` (`nis`),
  KEY `idx_synced_at` (`synced_at`),
  KEY `idx_tbl_leger_siswa_eraport_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_literasi_ampuh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_literasi_misi` (
  `id_misi` int NOT NULL AUTO_INCREMENT,
  `judul_misi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `tanggal_dibuat` date DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_misi`),
  KEY `idx_tbl_literasi_misi_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_literasi_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_tugas` int NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `progress_persen` int DEFAULT '0',
  `waktu_mulai` datetime DEFAULT NULL,
  `status` enum('belum','membaca','siap_evaluasi','selesai') COLLATE utf8mb4_general_ci DEFAULT 'belum',
  `skor_evaluasi` decimal(5,2) DEFAULT NULL,
  `predikat` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi_capaian` text COLLATE utf8mb4_general_ci,
  `waktu_selesai` datetime DEFAULT NULL,
  `durasi_detik` int DEFAULT '0',
  `skor_durasi` int DEFAULT '0',
  `skor_literasi` int DEFAULT '0',
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_literasi_progress_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_literasi_soal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_tugas` int NOT NULL,
  `pertanyaan` text COLLATE utf8mb4_general_ci NOT NULL,
  `opsi_a` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `opsi_b` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `opsi_c` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `opsi_d` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `jawaban_benar` char(1) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_literasi_soal_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_literasi_tugas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int DEFAULT NULL,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `tipe_media` enum('pdf','gambar','video') COLLATE utf8mb4_general_ci NOT NULL,
  `file_media` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `durasi_minimal` int DEFAULT '0',
  `batas_waktu` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_log` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `waktu` datetime NOT NULL,
  `isi_log` text COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_log`),
  KEY `idx_tbl_log_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=1820 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_mapel` (
  `id_mapel` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `nama_mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_mapel`),
  KEY `idx_tbl_mapel_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_mapel_ampu` (
  `id_mapel` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `id_guru` int NOT NULL,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `hari` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `ruang` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `thn_ajaran` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_mapel`),
  KEY `idx_tbl_mapel_ampu_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=506 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_materi` (
  `id_materi` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `id_mapel` int NOT NULL,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `materi` text COLLATE utf8mb4_general_ci,
  `kegiatan` text COLLATE utf8mb4_general_ci,
  `absen` text COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `file_materi` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id_materi`),
  KEY `idx_tbl_materi_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=8809 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_nilai_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `id_item` int NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` float DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_nilai_item` (`id_item`,`no_induk_siswa`),
  KEY `id_item` (`id_item`),
  KEY `idx_tbl_nilai_item_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=1866 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_nilai_tugas` (
  `id_nilai` int NOT NULL AUTO_INCREMENT,
  `id_tugas` int NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` decimal(5,2) DEFAULT '0.00',
  `catatan_guru` text COLLATE utf8mb4_general_ci,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_nilai`),
  KEY `idx_tbl_nilai_tugas_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_notifikasi` (
  `id_notif` int NOT NULL AUTO_INCREMENT,
  `id_pengirim` int NOT NULL,
  `id_penerima` int NOT NULL,
  `jenis` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_general_ci NOT NULL,
  `status_baca` int DEFAULT '0',
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_notif`),
  KEY `idx_tbl_notifikasi_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_notifikasi_outbox` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `channel` enum('email','whatsapp') COLLATE utf8mb4_general_ci NOT NULL,
  `tujuan` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `judul` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `percobaan` int NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_general_ci,
  `scheduled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_status` (`status`,`scheduled_at`),
  KEY `idx_notif_school` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=734 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pelanggaran` (
  `id_pelanggaran` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_siswa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pelanggaran` date NOT NULL,
  `kategori_pelanggaran` enum('Berat','Sedang','Ringan') COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_pelanggaran` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_pelanggaran` text COLLATE utf8mb4_general_ci,
  `tindakan_yang_diambil` text COLLATE utf8mb4_general_ci,
  `no_induk_guru` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `status_pelanggaran` enum('Aktif','Diselesaikan','Follow Up') COLLATE utf8mb4_general_ci DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_pelanggaran`),
  KEY `idx_siswa` (`no_induk`),
  KEY `idx_tanggal` (`tanggal_pelanggaran`),
  KEY `idx_kategori` (`kategori_pelanggaran`),
  KEY `idx_guru` (`no_induk_guru`),
  KEY `idx_tbl_pelanggaran_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pelanggaran_siswa` (
  `id_pelanggaran` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_siswa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_pelanggaran` date NOT NULL,
  `kategori_pelanggaran` enum('Berat','Sedang','Ringan') COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_pelanggaran` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_pelanggaran` text COLLATE utf8mb4_general_ci,
  `tindakan_yang_diambil` text COLLATE utf8mb4_general_ci,
  `no_induk_guru` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `status_pelanggaran` enum('Aktif','Diselesaikan','Follow Up') COLLATE utf8mb4_general_ci DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_pelanggaran`),
  KEY `idx_siswa` (`no_induk`),
  KEY `idx_tanggal` (`tanggal_pelanggaran`),
  KEY `idx_kategori` (`kategori_pelanggaran`),
  KEY `idx_guru` (`no_induk_guru`),
  KEY `idx_tbl_pelanggaran_siswa_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pembina_ekskul` (
  `id_pembina` int NOT NULL AUTO_INCREMENT,
  `id_ekskul` int NOT NULL,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_pembina`),
  UNIQUE KEY `id_ekskul` (`id_ekskul`,`no_induk_guru`),
  KEY `idx_tbl_pembina_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pengajuan_profil_guru` (
  `id_pengajuan` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_guru` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_kepegawaian` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pengajuan` enum('Menunggu','Disetujui','Ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `catatan_guru` text COLLATE utf8mb4_general_ci,
  `catatan_admin` text COLLATE utf8mb4_general_ci,
  `reviewed_by` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_pengajuan`),
  KEY `idx_pengajuan_no_induk` (`no_induk`),
  KEY `idx_pengajuan_status` (`status_pengajuan`),
  KEY `idx_tbl_pengajuan_profil_guru_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pengaturan` (
  `kunci` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`kunci`),
  KEY `idx_tbl_pengaturan_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pengguna` (
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `password` varchar(225) COLLATE utf8mb4_general_ci NOT NULL,
  `hak_akses` enum('1','2','3') COLLATE utf8mb4_general_ci NOT NULL,
  `password_plain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`no_induk`),
  KEY `idx_tbl_pengguna_id_sekolah` (`id_sekolah`),
  KEY `idx_pengguna_no_induk` (`no_induk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pengumuman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `isi` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_general_ci DEFAULT 'aktif',
  `penting` tinyint(1) DEFAULT '0',
  `mulai` date NOT NULL,
  `selesai` date NOT NULL,
  `target_scope` enum('SEMUA','KELAS','TINGKAT','GURU') COLLATE utf8mb4_general_ci DEFAULT 'SEMUA',
  `target_value` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lampiran` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_pengumuman_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_pengumuman_read` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pengumuman_id` int NOT NULL,
  `no_induk` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_read` (`pengumuman_id`,`no_induk`),
  KEY `idx_tbl_pengumuman_read_id_sekolah` (`id_sekolah`),
  CONSTRAINT `tbl_pengumuman_read_ibfk_1` FOREIGN KEY (`pengumuman_id`) REFERENCES `tbl_pengumuman` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_penilaian_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `tanggal` date NOT NULL,
  `id_mapel` int NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kode_penilaian` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `materi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_item` (`tanggal`,`id_mapel`,`kode_penilaian`),
  KEY `idx_tbl_penilaian_item_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_presensi_ekskul` (
  `id_presensi` int NOT NULL AUTO_INCREMENT,
  `id_ekskul` int NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Hadir',
  `foto_bukti` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_presensi`),
  UNIQUE KEY `id_ekskul` (`id_ekskul`,`no_induk_siswa`,`tanggal`),
  KEY `idx_tbl_presensi_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_presensi_setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `radius_m` int DEFAULT NULL,
  `jam_pulang_mulai` time DEFAULT '15:30:00',
  `jam_pulang_selesai` time DEFAULT '17:00:00',
  `jam_masuk_batas` time DEFAULT '07:00:00',
  `schedule` text COLLATE utf8mb4_general_ci,
  `holidays` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tbl_presensi_setting_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_sekolah` (
  `id_sekolah` int NOT NULL AUTO_INCREMENT,
  `kode_sekolah` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `npsn` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_sekolah` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `email_kontak` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hp_kontak` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_pimpinan` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nip_pimpinan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo` varchar(150) COLLATE utf8mb4_general_ci DEFAULT 'logo dash.png',
  `status` enum('Aktif','Non-Aktif') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sekolah`),
  UNIQUE KEY `kode_sekolah` (`kode_sekolah`),
  UNIQUE KEY `npsn` (`npsn`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_sertifikat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `folder_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Umum',
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_sertifikat_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_setting` (
  `id` int NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `nama_sekolah` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `logo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_pimpinan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nip_pimpinan` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `gemini_api_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `nama_aplikasi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'SIMANIS',
  `adsense_enabled` tinyint(1) DEFAULT '0',
  `adsense_script` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_tbl_setting_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_share_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_sumber` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sumber_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sumber_label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `data_json` longtext COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_tbl_share_links_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_siswa` (
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `nipd` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_siswa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `jk` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `jabatan` enum('Siswa','Ketua Kelas') COLLATE utf8mb4_general_ci DEFAULT 'Siswa',
  `status` enum('Aktif','Non-Aktif','Lulus') COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `lat` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lng` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_darurat` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_darurat` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tempat_lahir` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nik` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agama` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rt` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rw` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dusun` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelurahan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kecamatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode_pos` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_tinggal` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alat_transportasi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telepon` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hp` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `skhun` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rombel_saat_ini` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_peserta_un` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_seri_ijazah` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sekolah_asal` varchar(160) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerima_kps` tinyint(1) NOT NULL DEFAULT '0',
  `no_kps` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerima_kip` tinyint(1) NOT NULL DEFAULT '0',
  `nomor_kip` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_di_kip` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomor_kks` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomor_rekening_bank` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rekening_atas_nama` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `layak_pip` tinyint(1) NOT NULL DEFAULT '0',
  `alasan_layak_pip` text COLLATE utf8mb4_general_ci,
  `no_reg_akta_lahir` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kebutuhan_khusus` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `anak_ke` smallint unsigned DEFAULT NULL,
  `bujur` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_kk` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `berat_badan` decimal(5,2) DEFAULT NULL,
  `tinggi_badan` decimal(5,2) DEFAULT NULL,
  `lingkar_kepala` decimal(5,2) DEFAULT NULL,
  `jumlah_saudara_kandung` smallint unsigned DEFAULT NULL,
  `jarak_rumah_km` decimal(5,2) DEFAULT NULL,
  `ayah_nama` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ayah_tahun_lahir` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ayah_pendidikan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ayah_pekerjaan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ayah_penghasilan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ayah_nik` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ibu_nama` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ibu_tahun_lahir` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ibu_pendidikan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ibu_pekerjaan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ibu_penghasilan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ibu_nik` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wali_nama` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wali_tahun_lahir` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wali_pendidikan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wali_pekerjaan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wali_penghasilan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wali_nik` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lintang` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_kelas` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_depan_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `izin_edit_profil` tinyint(1) NOT NULL DEFAULT '0',
  `rencana_setelah_lulus` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rencana_detail` text COLLATE utf8mb4_general_ci,
  `minat_jurusan` varchar(160) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bakat_minat` text COLLATE utf8mb4_general_ci,
  `dukungan_dibutuhkan` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`no_induk`),
  KEY `idx_tbl_siswa_id_sekolah` (`id_sekolah`),
  KEY `idx_siswa_no_induk` (`no_induk`),
  KEY `idx_siswa_kelas` (`kelas`),
  KEY `idx_siswa_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_siswa_eraport` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_no` int DEFAULT NULL,
  `peserta_didik_id` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_siswa` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `nis` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nisn` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_kelamin` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agama` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tingkat` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `synced_at` datetime NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_peserta_didik` (`peserta_didik_id`),
  KEY `idx_nama_siswa` (`nama_siswa`),
  KEY `idx_kelas` (`kelas`),
  KEY `idx_tbl_siswa_eraport_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=4705 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_telat` (
  `id_telat` int NOT NULL AUTO_INCREMENT,
  `no_induk` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_siswa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_telat` time DEFAULT NULL,
  `id_mapel` int DEFAULT NULL,
  `no_induk_guru` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_telat`),
  KEY `idx_siswa` (`no_induk`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_mapel` (`id_mapel`),
  KEY `idx_guru` (`no_induk_guru`),
  KEY `idx_tbl_telat_id_sekolah` (`id_sekolah`),
  CONSTRAINT `tbl_telat_ibfk_1` FOREIGN KEY (`id_mapel`) REFERENCES `tbl_mapel_ampu` (`id_mapel`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_thn_ajaran` (
  `id_thn` int NOT NULL AUTO_INCREMENT,
  `tahun` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_thn`),
  KEY `idx_tbl_thn_ajaran_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_tugas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `tanggal` date NOT NULL,
  `id_mapel` int NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `mapel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `no_induk_guru` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `judul_tugas` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `link_tugas` text COLLATE utf8mb4_general_ci,
  `file_tugas` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_pengumpulan` date DEFAULT NULL,
  `batas_waktu` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('aktif','selesai','dihapus') COLLATE utf8mb4_general_ci DEFAULT 'aktif',
  PRIMARY KEY (`id`),
  KEY `tanggal` (`tanggal`,`id_mapel`),
  KEY `no_induk_guru` (`no_induk_guru`),
  KEY `idx_tbl_tugas_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_tugas_ekskul` (
  `id_tugas` int NOT NULL AUTO_INCREMENT,
  `id_ekskul` int NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `tanggal` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_tugas`),
  KEY `idx_tbl_tugas_ekskul_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_tugas_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_tugas` int NOT NULL,
  `no_induk_siswa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Menunggu Konfirmasi','Selesai') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `waktu_submit` datetime DEFAULT NULL,
  `waktu_konfirmasi` datetime DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tugas_siswa` (`id_tugas`,`no_induk_siswa`),
  KEY `idx_tbl_tugas_siswa_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_twibbon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `filename` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_pembuat` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aktif` tinyint(1) DEFAULT '1',
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tbl_twibbon_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `id_sekolah` int NOT NULL DEFAULT '1',
  `username` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `hak_akses` enum('1','2','3','4') COLLATE utf8mb4_general_ci NOT NULL,
  `password_plain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id_user`),
  KEY `idx_tbl_user_id_sekolah` (`id_sekolah`),
  KEY `idx_tbl_user_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_online` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_key` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `user_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `user_ref` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `display_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_activity` datetime NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT '1',
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_key` (`user_key`),
  KEY `idx_last_activity` (`last_activity`),
  KEY `idx_is_online` (`is_online`),
  KEY `idx_tbl_user_online_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=10024 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_wali_kelas` (
  `id_wali` int NOT NULL AUTO_INCREMENT,
  `id_kelas` int NOT NULL,
  `nip_wali` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_wali` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_wali`),
  UNIQUE KEY `uniq_guru` (`nip_wali`),
  UNIQUE KEY `uniq_kelas` (`id_kelas`),
  KEY `idx_kelas` (`id_kelas`),
  KEY `idx_nip` (`nip_wali`),
  KEY `idx_tbl_wali_kelas_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_wks_file` (
  `id_file` int unsigned NOT NULL AUTO_INCREMENT,
  `id_folder` int unsigned DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `file_title` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `file_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_file`),
  KEY `idx_folder` (`id_folder`),
  KEY `idx_unit_active` (`unit`,`is_active`),
  KEY `idx_sort` (`sort_order`,`id_file`),
  KEY `idx_tbl_wks_file_id_sekolah` (`id_sekolah`),
  CONSTRAINT `fk_wks_file_folder` FOREIGN KEY (`id_folder`) REFERENCES `tbl_wks_folder` (`id_folder`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_wks_folder` (
  `id_folder` int unsigned NOT NULL AUTO_INCREMENT,
  `unit` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `folder_name` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `folder_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_folder`),
  KEY `idx_unit_active` (`unit`,`is_active`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_sort` (`sort_order`,`id_folder`),
  KEY `idx_tbl_wks_folder_id_sekolah` (`id_sekolah`),
  CONSTRAINT `fk_wks_folder_parent` FOREIGN KEY (`parent_id`) REFERENCES `tbl_wks_folder` (`id_folder`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_wks_microsite` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `unit` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `microsite_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `folder_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_sekolah` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_unit_active` (`unit`,`is_active`),
  KEY `idx_sort` (`sort_order`,`id`),
  KEY `idx_tbl_wks_microsite_id_sekolah` (`id_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;