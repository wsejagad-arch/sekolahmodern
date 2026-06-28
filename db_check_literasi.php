<?php
require 'koneksi.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE '%literasi%'");
while ($r = mysqli_fetch_row($res)) {
    echo $r[0] . "\n";
}
?>
