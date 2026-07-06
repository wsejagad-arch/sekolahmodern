<?php
require 'koneksi.php';
$res = mysqli_query($conn, "SELECT kelas FROM tbl_kelas LIMIT 5");
while ($r = mysqli_fetch_assoc($res)) {
    echo $r['kelas'] . "\n";
}
?>
