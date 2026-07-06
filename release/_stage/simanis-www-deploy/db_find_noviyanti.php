<?php
require 'koneksi.php';
$res = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE nama_guru LIKE '%Novi%'");
while ($r = mysqli_fetch_assoc($res)) {
    echo $r['no_induk'] . " - " . $r['nama_guru'] . "\n";
}
?>
