<?php
// Konfigurasi Database - Sesuaikan saat upload ke cPanel
$host = "localhost";
$user = "root";       // Ganti dengan username database cPanel (contoh: user_dbsekolah)
$pass = "";           // Ganti dengan password database cPanel
$db   = "sekolah_db"; // Ganti dengan nama database cPanel (contoh: user_sekolahdb)

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
