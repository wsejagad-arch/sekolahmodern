<?php
$c = mysqli_connect('127.0.0.1', 'root', '', 'sijurnal');
$q = mysqli_query($c, 'SHOW COLUMNS FROM tbl_izin_siswa');
while ($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . "\n";
}
?>
