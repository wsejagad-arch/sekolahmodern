<?php
require 'koneksi.php';
$q = mysqli_query($conn, "SELECT * FROM tbl_absen ORDER BY id DESC LIMIT 5");
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}