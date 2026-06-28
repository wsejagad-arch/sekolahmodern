<?php
include "koneksi.php";

echo "=== CHECK STRUKTUR TABEL MATERI ===" . PHP_EOL;

$result = mysqli_query($conn, "DESCRIBE tbl_materi");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . PHP_EOL;
}

mysqli_close($conn);
?>