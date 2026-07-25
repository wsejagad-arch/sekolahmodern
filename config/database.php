<?php
// Konfigurasi Database - Sesuaikan saat upload ke cPanel
$host = "localhost";
$user = "smasumb1_web1";       // Ganti dengan username database cPanel (contoh: user_dbsekolah)
$pass = "W@hyu123465";           // Ganti dengan password database cPanel
$db   = "smasumb1_smanis1"; // Ganti dengan nama database cPanel (contoh: user_sekolahdb)

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
