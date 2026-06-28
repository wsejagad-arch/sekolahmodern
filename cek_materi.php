<?php
include "koneksi.php";

echo "=== STRUKTUR TABEL TBL_MATERI ===\n";

$result = mysqli_query($conn, "DESCRIBE tbl_materi");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== SAMPLE DATA ===\n";
$result2 = mysqli_query($conn, "SELECT id_materi, tanggal, date FROM tbl_materi LIMIT 5");
while ($row2 = mysqli_fetch_assoc($result2)) {
    echo "ID: {$row2['id_materi']}, tanggal: {$row2['tanggal']}, date: {$row2['date']}\n";
}

mysqli_close($conn);
?>