<?php
require_once __DIR__ . '/../koneksi.php';
if ($conn === null) {
    echo "Connection is NULL\n";
    exit(1);
}

echo "=== APP CONFIG FOR WA ===\n";
$res = mysqli_query($conn, "SELECT * FROM tbl_app_config WHERE kunci LIKE 'wa_%'");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "Kunci: {$row['kunci']} | Nilai: {$row['nilai']}\n";
    }
} else {
    echo "Error querying tbl_app_config: " . mysqli_error($conn) . "\n";
}
?>
