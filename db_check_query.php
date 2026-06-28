<?php
require 'koneksi.php';
$query = "SELECT COUNT(*) as total_misi, SUM(nilai) as total_skor FROM tbl_literasi_progress WHERE no_induk_siswa='something' AND status='selesai'";
$res = mysqli_query($conn, $query);
if (!$res) {
    echo "Error: " . mysqli_error($conn) . "\n";
} else {
    echo "Success!\n";
}
?>
