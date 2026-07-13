<?php
require 'koneksi.php';

echo "=== TBL_GURU ===\n";
$q1 = mysqli_query($conn, "SELECT id_guru, nip, nama_guru, username, password, id_sekolah FROM tbl_guru WHERE nama_guru LIKE '%widyana%' OR nama_guru LIKE '%alfin%' OR nama_guru LIKE '%widiana%'");
while($r = mysqli_fetch_assoc($q1)) {
    print_r($r);
}

echo "\n=== TBL_PENGGUNA ===\n";
$q2 = mysqli_query($conn, "SELECT id, username, nama, level, id_sekolah FROM tbl_pengguna WHERE nama LIKE '%widyana%' OR nama LIKE '%alfin%' OR nama LIKE '%widiana%'");
while($r = mysqli_fetch_assoc($q2)) {
    print_r($r);
}
?>
