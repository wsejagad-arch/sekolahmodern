<?php
include "koneksi.php";
$q = mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_izin_siswa");
$r = mysqli_fetch_assoc($q);
echo "COUNT IS: " . $r['c'] . "\n";
?>
