<?php
include "koneksi.php";
$q = mysqli_query($conn, "SHOW COLUMNS FROM tbl_absen");
while($r = mysqli_fetch_assoc($q)) echo $r['Field']."\n";
?>
