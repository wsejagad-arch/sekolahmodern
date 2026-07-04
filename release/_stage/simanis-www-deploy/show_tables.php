<?php
include "koneksi.php";

echo "=== DAFTAR TABEL DI DATABASE ===" . PHP_EOL;

$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . PHP_EOL;
}

mysqli_close($conn);
?>