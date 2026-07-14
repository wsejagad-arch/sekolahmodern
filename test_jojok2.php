<?php
$conn = mysqli_connect('127.0.0.1', 'root', '', 'jurnal_sman1sumber');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$q = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas, status FROM tbl_siswa WHERE nama_siswa LIKE '%JOJOK FAHRUL INDRAWAN%'");
while($r = mysqli_fetch_assoc($q)) {
    echo "tbl_siswa: " . print_r($r, true) . "\n";
}
$q2 = mysqli_query($conn, "SELECT p.no_induk, p.password FROM tbl_pengguna p JOIN tbl_siswa s ON p.no_induk=s.no_induk WHERE s.nama_siswa LIKE '%JOJOK FAHRUL INDRAWAN%'");
while($r = mysqli_fetch_assoc($q2)) {
    echo "tbl_pengguna: " . print_r($r, true) . "\n";
}
