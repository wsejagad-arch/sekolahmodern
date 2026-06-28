<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'sijurnal', 3306);
mysqli_set_charset($conn, 'utf8mb4');

echo "=== Struktur tbl_kelas ===" . PHP_EOL;
$res = mysqli_query($conn, "DESCRIBE tbl_kelas");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . PHP_EOL;
}

echo PHP_EOL . "=== Struktur tbl_wali_kelas ===" . PHP_EOL;
$res = mysqli_query($conn, "DESCRIBE tbl_wali_kelas");
if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . PHP_EOL;
    }
} else {
    echo "Tabel tidak ditemukan atau kosong" . PHP_EOL;
}
