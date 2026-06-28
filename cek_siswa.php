<?php
include "koneksi.php";
$q = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE no_induk = '5607'");
while($r = mysqli_fetch_assoc($q)) echo json_encode($r)."\n";
?>
