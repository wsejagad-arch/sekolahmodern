<?php
include 'koneksi.php';
$check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
if (mysqli_num_rows($check) == 0) {
    echo "Column no_wa does not exist! Adding it... ";
    $add = mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru");
    if ($add) {
        echo "Success.\n";
    } else {
        echo "Failed: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Column no_wa exists.\n";
}
?>
