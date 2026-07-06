<?php
include "koneksi.php";
$cek = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa LIKE 'id_sekolah'");
if(mysqli_num_rows($cek)>0) echo "has id_sekolah"; else echo "no id_sekolah";
?>
