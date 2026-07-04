<?php
include 'c:/xampp/htdocs/jurnal/koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS tbl_tugas_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tugas INT NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    status ENUM('Menunggu Konfirmasi', 'Selesai') NOT NULL DEFAULT 'Menunggu Konfirmasi',
    waktu_submit DATETIME,
    waktu_konfirmasi DATETIME,
    UNIQUE KEY uniq_tugas_siswa (id_tugas, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "Tabel tbl_tugas_siswa berhasil dibuat atau sudah ada.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
