<?php
include "koneksi.php";
$res1 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin'");
$res2 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin_siswa'");
$out = "tbl_izin: " . mysqli_num_rows($res1) . "\n" . "tbl_izin_siswa: " . mysqli_num_rows($res2) . "\n";
file_put_contents('tables_out.txt', $out);
?>
