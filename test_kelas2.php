<?php
require 'koneksi.php';
$q = mysqli_query($conn, 'SELECT * FROM tbl_kelas LIMIT 10');
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
