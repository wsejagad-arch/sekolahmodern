<?php
include "koneksi.php";
$q = mysqli_query($conn, "SHOW TABLES LIKE '%jurnal%'");
while($r = mysqli_fetch_row($q)) echo $r[0]."\n";

$q2 = mysqli_query($conn, "SHOW TABLES LIKE '%absen%'");
while($r = mysqli_fetch_row($q2)) echo $r[0]."\n";

$q3 = mysqli_query($conn, "SHOW TABLES LIKE '%presen%'");
while($r = mysqli_fetch_row($q3)) echo $r[0]."\n";
?>
