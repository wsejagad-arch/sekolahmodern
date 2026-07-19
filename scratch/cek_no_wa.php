<?php
include 'koneksi.php';

// Cek apakah kolom no_wa ada
$r = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
echo "Kolom no_wa: " . (mysqli_num_rows($r) > 0 ? 'ADA' : 'TIDAK ADA') . PHP_EOL;

// Tampilkan struktur kolom tbl_guru
echo PHP_EOL . "=== STRUKTUR tbl_guru ===" . PHP_EOL;
$cols = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru");
while ($col = mysqli_fetch_assoc($cols)) {
    echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Default'] . PHP_EOL;
}

// Tampilkan data guru dengan no_wa
echo PHP_EOL . "=== DATA GURU (no_wa) ===" . PHP_EOL;
$r2 = mysqli_query($conn, "SELECT id_guru, nama_guru, no_wa FROM tbl_guru LIMIT 10");
while ($row = mysqli_fetch_assoc($r2)) {
    echo $row['id_guru'] . " | " . $row['nama_guru'] . " => no_wa: [" . ($row['no_wa'] ?? 'NULL') . "]" . PHP_EOL;
}
