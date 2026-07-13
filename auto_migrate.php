<?php
// auto_migrate.php
// Skrip ini akan dipanggil setiap kali koneksi.php berhasil tersambung ke database.
// Tujuannya adalah untuk membuat tabel-tabel yang belum ada secara otomatis.

function run_auto_migrations($conn) {
    // Array berisi daftar query CREATE TABLE IF NOT EXISTS
    // Tambahkan query tabel baru di dalam array ini.
    
    $migrations = [
        // Contoh Tabel Tugas Siswa (Sesuaikan kolomnya dengan struktur asli Anda jika punya)
        "CREATE TABLE IF NOT EXISTS `tbl_tugas_siswa` (
            `id_tugas` int(11) NOT NULL AUTO_INCREMENT,
            `no_induk_siswa` varchar(50) NOT NULL,
            `status` varchar(50) DEFAULT 'Menunggu Konfirmasi',
            `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP,
            `keterangan` text,
            PRIMARY KEY (`id_tugas`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // Contoh Tabel Nilai Tugas (Sesuaikan kolomnya jika perlu)
        "CREATE TABLE IF NOT EXISTS `tbl_nilai_tugas` (
            `id_nilai` int(11) NOT NULL AUTO_INCREMENT,
            `id_tugas` int(11) NOT NULL,
            `no_induk_siswa` varchar(50) NOT NULL,
            `nilai` decimal(5,2) DEFAULT '0.00',
            `catatan_guru` text,
            PRIMARY KEY (`id_nilai`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // Contoh Tabel Literasi Misi
        "CREATE TABLE IF NOT EXISTS `tbl_literasi_misi` (
            `id_misi` int(11) NOT NULL AUTO_INCREMENT,
            `judul_misi` varchar(255) NOT NULL,
            `deskripsi` text,
            `tanggal_dibuat` date DEFAULT NULL,
            PRIMARY KEY (`id_misi`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // Contoh Tabel Literasi Progress
        "CREATE TABLE IF NOT EXISTS `tbl_literasi_progress` (
            `id_progress` int(11) NOT NULL AUTO_INCREMENT,
            `id_misi` int(11) NOT NULL,
            `no_induk_siswa` varchar(50) NOT NULL,
            `status` varchar(50) DEFAULT 'Belum Selesai',
            `tanggal_update` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_progress`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // Tabel Notifikasi Intervensi Kepala Sekolah
        "CREATE TABLE IF NOT EXISTS `tbl_notifikasi` (
            `id_notif` int(11) NOT NULL AUTO_INCREMENT,
            `id_pengirim` int(11) NOT NULL,
            `id_penerima` int(11) NOT NULL,
            `jenis` varchar(50) NOT NULL,
            `pesan` text NOT NULL,
            `status_baca` int(1) DEFAULT 0,
            `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_notif`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        /* 
        ======================================================================
        CARA MENAMBAHKAN TABEL BARU:
        Copy-paste sintaks CREATE TABLE dari phpMyAdmin Anda (di tab Export), 
        pastikan menggunakan IF NOT EXISTS, lalu bungkus dengan tanda kutip ganda " "
        dan tambahkan koma ( , ) di akhir baris, seperti contoh di atas.
        ======================================================================
        */
        
        "CREATE TABLE IF NOT EXISTS `tbl_absen_sholat` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `no_induk` varchar(50) NOT NULL,
            `jenis_sholat` enum('Dzuhur','Jumat') NOT NULL,
            `tanggal` date NOT NULL,
            `waktu` time NOT NULL,
            `lat` varchar(100) DEFAULT NULL,
            `lng` varchar(100) DEFAULT NULL,
            `status_lokasi` varchar(100) DEFAULT 'Valid',
            `id_sekolah` int(11) DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_absen_sholat` (`no_induk`, `jenis_sholat`, `tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    // Eksekusi setiap migrasi secara diam-diam (suppress errors)
    foreach ($migrations as $query) {
        @mysqli_query($conn, $query);
    }

    // Eksekusi Alter Table terpisah karena akan error jika kolom sudah ada
    // Menambahkan kolom password_plain khusus untuk melihat password user non-admin
    @mysqli_query($conn, "ALTER TABLE `tbl_user` ADD COLUMN `password_plain` VARCHAR(255) DEFAULT ''");
    @mysqli_query($conn, "ALTER TABLE `tbl_pengguna` ADD COLUMN `password_plain` VARCHAR(255) DEFAULT ''");
}
