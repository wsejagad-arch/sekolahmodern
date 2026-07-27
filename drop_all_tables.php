<?php
require_once __DIR__ . '/koneksi_local.php';

if (!$conn) {
    die("Koneksi gagal!\n");
}

echo "Berhasil terhubung ke: " . mysqli_get_host_info($conn) . "\n";

// Get all tables
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

if (!empty($tables)) {
    echo "Ditemukan " . count($tables) . " tabel. Menghapus tabel-tabel tersebut...\n";
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");
    foreach ($tables as $table) {
        mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
        echo "Dropped $table\n";
    }
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");
} else {
    echo "Database sudah kosong.\n";
}
?>
