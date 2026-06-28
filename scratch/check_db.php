<?php
include __DIR__ . '/../koneksi.php';

echo "=== TABLES ===\n";
$res = mysqli_query($conn, 'SHOW TABLES');
while($row = mysqli_fetch_array($res)) {
    echo "- " . $row[0] . "\n";
}

echo "\n=== DESCRIBE tbl_materi ===\n";
$res = mysqli_query($conn, 'DESCRIBE tbl_materi');
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']} - Default: {$row['Default']}\n";
    }
} else {
    echo "tbl_materi does not exist or error: " . mysqli_error($conn) . "\n";
}
?>
