<?php
include "koneksi.php";
$q = mysqli_query($conn, "SELECT no_induk_siswa, kelas_siswa FROM tbl_izin_siswa ORDER BY id_izin DESC LIMIT 5");
while($r = mysqli_fetch_assoc($q)) { print_r($r); }
$q2 = mysqli_query($conn, "SELECT kelas, nip_wali FROM tbl_kelas WHERE nip_wali IN (SELECT no_induk FROM tbl_guru WHERE nama_guru LIKE '%Dwi Wahyu%')");
while($r = mysqli_fetch_assoc($q2)) { print_r($r); }
?>
