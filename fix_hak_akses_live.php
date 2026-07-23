<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "koneksi.php";

$q = mysqli_query($conn, "ALTER TABLE tbl_user MODIFY hak_akses ENUM('1','2','3','4','5')");
if ($q) {
    echo "SUCCESS: hak_akses altered on " . mysqli_get_host_info($conn) . " (DB: " . mysqli_query($conn, "SELECT DATABASE()")->fetch_row()[0] . ")";
} else {
    echo "ERROR: " . mysqli_error($conn);
}
?>
