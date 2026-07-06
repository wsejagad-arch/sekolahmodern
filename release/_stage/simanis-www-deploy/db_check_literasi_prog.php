<?php
require 'koneksi.php';
$res = mysqli_query($conn, "DESCRIBE tbl_literasi_progress");
if (!$res) {
    echo "Error: " . mysqli_error($conn) . "\n";
} else {
    while ($r = mysqli_fetch_row($res)) {
        echo implode(", ", $r) . "\n";
    }
}
?>
