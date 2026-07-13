<?php
require 'koneksi.php';
$q = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_kelas');
$cols = [];
while ($r = mysqli_fetch_assoc($q)) {
    $cols[] = $r['Field'];
}
print_r($cols);
?>
