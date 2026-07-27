<?php
// Koneksi ke VPS
$conn = @new mysqli('203.175.125.118', 'vps_jurnal', 'WahyuJurnal123!', 'sijurnal', 3306);
if ($conn->connect_error) {
    die("Gagal konek ke VPS: " . $conn->connect_error . PHP_EOL);
}
echo "Terhubung ke VPS" . PHP_EOL;

echo PHP_EOL . "=== USER SATPAM (hak_akses=4) di tbl_user ===" . PHP_EOL;
$r = $conn->query("SELECT id_user, username, nama, hak_akses, password_plain FROM tbl_user WHERE hak_akses = 4");
if ($r && $r->num_rows > 0) {
    while ($row = $r->fetch_assoc()) {
        echo "ID: " . $row['id_user'] . " | Username: " . $row['username'] . " | Nama: " . $row['nama'] . " | Password: " . ($row['password_plain'] ?? '(MD5 only)') . PHP_EOL;
    }
} else {
    echo "Tidak ada user satpam di tbl_user. Total rows: " . ($r ? $r->num_rows : 0) . PHP_EOL;
    if ($conn->error) echo "Error: " . $conn->error . PHP_EOL;
}

echo PHP_EOL . "=== SEMUA USER di tbl_user ===" . PHP_EOL;
$r2 = $conn->query("SELECT id_user, username, nama, hak_akses, password_plain FROM tbl_user ORDER BY id_user ASC");
if ($r2) {
    while ($row = $r2->fetch_assoc()) {
        $label = $row['hak_akses'] == 1 ? 'Admin' : ($row['hak_akses'] == 4 ? 'SATPAM' : ($row['hak_akses'] == 5 ? 'Kepsek' : 'Lainnya(' . $row['hak_akses'] . ')'));
        echo "ID: " . $row['id_user'] . " | Username: " . $row['username'] . " | Nama: " . $row['nama'] . " | Role: " . $label . " | Pass: " . ($row['password_plain'] ?? '(MD5 only)') . PHP_EOL;
    }
} else {
    echo "Gagal query tbl_user: " . $conn->error . PHP_EOL;
}
