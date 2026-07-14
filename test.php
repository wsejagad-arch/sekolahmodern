<?php
require 'koneksi.php';
$q = mysqli_query($conn, "SELECT no_induk, password FROM tbl_pengguna LIMIT 10");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
echo "GURU:\n";
$q = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 10");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
