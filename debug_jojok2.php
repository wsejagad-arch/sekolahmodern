<?php
// Tampilkan semua error
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/koneksi.php';

if (!$conn) {
    echo "Gagal koneksi ke database: " . mysqli_connect_error();
    exit;
}

$u = '05803';
$sql = "SELECT s.no_induk, s.nama_siswa, s.kelas, s.status, s.id_sekolah, p.password 
        FROM tbl_siswa s 
        LEFT JOIN tbl_pengguna p ON s.no_induk = p.no_induk 
        WHERE s.nama_siswa LIKE '%JOJOK%' OR s.no_induk = '$u'";

$res = mysqli_query($conn, $sql);
if (!$res) {
    echo "Error query: " . mysqli_error($conn);
    exit;
}

$rows = [];
while($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}

echo "<h3>Data Jojok di Database Saat Ini:</h3>";
echo "<pre>";
print_r($rows);
echo "</pre>";
