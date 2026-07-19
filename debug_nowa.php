<?php
// Disable session untuk akses langsung
define('SKIP_SESSION_CHECK', true);
include 'koneksi.php';

echo "<pre style='font-family:monospace;font-size:13px'>";

// Cek apakah kolom no_wa ada
$r = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
echo "=== Kolom no_wa: " . (mysqli_num_rows($r) > 0 ? '<b style=color:green>ADA ✅</b>' : '<b style=color:red>TIDAK ADA ❌</b>') . " ===\n\n";

// Tampilkan semua kolom tbl_guru
echo "=== SEMUA KOLOM tbl_guru ===\n";
$cols = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru");
while ($col = mysqli_fetch_assoc($cols)) {
    echo str_pad($col['Field'], 30) . " | " . str_pad($col['Type'], 20) . " | Default: " . $col['Default'] . "\n";
}

// Tampilkan data guru dengan no_wa
echo "\n=== DATA GURU (id | nama | no_wa) ===\n";
$r2 = mysqli_query($conn, "SELECT id_guru, nama_guru, no_wa FROM tbl_guru ORDER BY nama_guru LIMIT 20");
if (mysqli_num_rows($r2) === 0) {
    echo "Tidak ada data guru\n";
}
while ($row = mysqli_fetch_assoc($r2)) {
    $wa = isset($row['no_wa']) ? ($row['no_wa'] ?: '(kosong)') : 'KOLOM_TIDAK_ADA';
    echo $row['id_guru'] . " | " . str_pad($row['nama_guru'], 30) . " | no_wa: [" . $wa . "]\n";
}

echo "</pre>";
