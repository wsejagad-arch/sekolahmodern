<?php
require_once 'koneksi.php';

echo "=== STRUKTUR TABEL PELANGGARAN ===" . PHP_EOL;

$result = mysqli_query($conn, 'DESCRIBE tbl_pelanggaran');
if (!$result) {
    echo "Error: " . mysqli_error($conn) . PHP_EOL;
} else {
    while($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
}
?>