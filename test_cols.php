<?php
require 'koneksi.php';
$q = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_siswa');
while ($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . ' - ' . $r['Type'] . PHP_EOL;
}
?>
