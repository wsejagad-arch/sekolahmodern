<?php
require 'koneksi.php';
$q = mysqli_query($conn, "SELECT * FROM tbl_pengaturan");
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
