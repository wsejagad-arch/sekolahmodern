<?php
require 'koneksi.php';
$q = mysqli_query($conn, "SELECT s.no_induk, s.nama_siswa, s.status, p.password FROM tbl_siswa s LEFT JOIN tbl_pengguna p ON s.no_induk = p.no_induk WHERE s.nama_siswa LIKE '%JOJOK%'");
while($r=mysqli_fetch_assoc($q)) print_r($r);
?>
