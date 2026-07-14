<?php
require 'koneksi.php';
$q = mysqli_query($conn, "SELECT * FROM tbl_user");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
