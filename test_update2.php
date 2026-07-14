<?php
include 'koneksi.php';
$kelasTujuanEsc = 'XI E 1';
$kelasAsalEsc = 'X E 1';
$tenantSiswa = '';
$query = "UPDATE tbl_siswa SET kelas = '$kelasTujuanEsc', rombel_saat_ini = '$kelasTujuanEsc', nama_kelas = '$kelasTujuanEsc' WHERE kelas = '$kelasAsalEsc' AND status = 'Aktif' {$tenantSiswa}";
$update = mysqli_query($conn, $query);
echo 'Affected: ' . mysqli_affected_rows($conn) . "\n";
?>
