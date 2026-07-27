<?php
require 'koneksi_local.php';
$q = mysqli_query($conn, "SHOW COLUMNS FROM tbl_aduan_siswa");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        echo $r['Field'] . " ";
    }
}
?>
