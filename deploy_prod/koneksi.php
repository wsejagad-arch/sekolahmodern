<?php
// Koneksi khusus PRODUKSI (web hosting) - jangan ubah saat sudah dihosting
$host = "localhost";
$user = "smasumb1_sijurnal1";
$password = "JU-gxs^([=UN";
$db = "smasumb1_sijurnal";
$port = 3306;

$conn = mysqli_connect($host, $user, $password, $db, $port);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8');
date_default_timezone_set('Asia/Jakarta');
?>