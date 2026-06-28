<?php
include __DIR__ . '/../koneksi.php';

$sql = mysqli_query($conn, "SELECT * FROM tbl_sekolah");
if ($sql) {
    while ($row = mysqli_fetch_assoc($sql)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
