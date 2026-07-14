<?php
include 'koneksi.php';
$q = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE nama_siswa LIKE '%JOJOK FAHRUL INDRAWAN%'");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        print_r($r);
    }
}
$q2 = mysqli_query($conn, "SELECT p.* FROM tbl_pengguna p JOIN tbl_siswa s ON p.no_induk=s.no_induk WHERE s.nama_siswa LIKE '%JOJOK FAHRUL INDRAWAN%'");
if ($q2) {
    while($r = mysqli_fetch_assoc($q2)) {
        print_r($r);
    }
}
