<?php
require 'koneksi.php';
$q = mysqli_query($conn, 'DESCRIBE tbl_absen_sholat');
while($r = mysqli_fetch_assoc($q)){
    print_r($r);
}
?>
