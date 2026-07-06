<?php
include "koneksi.php";
$qIzin = mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_izin_siswa");
$r = mysqli_fetch_assoc($qIzin);
echo "Count: " . $r['c'] . "\n";
?>
