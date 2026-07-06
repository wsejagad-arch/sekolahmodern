<?php
require 'koneksi.php';
$res = mysqli_query($conn, "DESCRIBE tbl_kelas");
while ($r = mysqli_fetch_assoc($res)) {
    echo $r['Field'] . "\n";
}
?>
