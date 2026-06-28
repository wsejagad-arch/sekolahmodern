<?php
require 'koneksi.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS `tbl_literasi_ampuh` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `no_induk_guru` varchar(50) NOT NULL,
      `kelas` varchar(100) NOT NULL,
      `id_sekolah` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `tbl_literasi_tugas` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `id_sekolah` int(11) DEFAULT NULL,
      `no_induk_guru` varchar(50) NOT NULL,
      `kelas` varchar(100) NOT NULL,
      `judul` varchar(255) NOT NULL,
      `deskripsi` text DEFAULT NULL,
      `tipe_media` enum('pdf','gambar','video') NOT NULL,
      `file_media` varchar(255) DEFAULT NULL,
      `durasi_minimal` int(11) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `tbl_literasi_soal` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `id_tugas` int(11) NOT NULL,
      `pertanyaan` text NOT NULL,
      `opsi_a` varchar(255) NOT NULL,
      `opsi_b` varchar(255) NOT NULL,
      `opsi_c` varchar(255) NOT NULL,
      `opsi_d` varchar(255) NOT NULL,
      `jawaban_benar` char(1) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `tbl_literasi_progress` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `id_tugas` int(11) NOT NULL,
      `no_induk_siswa` varchar(50) NOT NULL,
      `progress_persen` int(11) DEFAULT 0,
      `waktu_mulai` datetime DEFAULT NULL,
      `status` enum('belum','membaca','siap_evaluasi','selesai') DEFAULT 'belum',
      `skor_evaluasi` decimal(5,2) DEFAULT NULL,
      `predikat` char(1) DEFAULT NULL,
      `deskripsi_capaian` text DEFAULT NULL,
      `waktu_selesai` datetime DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
