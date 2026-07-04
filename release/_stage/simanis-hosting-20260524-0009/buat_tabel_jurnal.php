<?php
// Jalankan ini sekali untuk membuat tabel tbl_jurnal jika belum ada
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';
$sql = "CREATE TABLE IF NOT EXISTS tbl_jurnal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_induk VARCHAR(32) NOT NULL,
    kelas VARCHAR(32) NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME DEFAULT NULL,
    jam_selesai TIME DEFAULT NULL,
    mapel VARCHAR(64) DEFAULT NULL,
    jurnal TEXT DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (mysqli_query($conn, $sql)) {
    echo "Tabel tbl_jurnal berhasil dibuat atau sudah ada.";
} else {
    echo "Gagal membuat tabel: ".mysqli_error($conn);
}
?>