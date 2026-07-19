<?php
include 'koneksi.php';
$q = mysqli_query($conn, 'SELECT tanggal FROM tbl_penilaian_item ORDER BY tanggal DESC LIMIT 5');
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
echo "\n====\n";
$q2 = mysqli_query($conn, 'SELECT COUNT(*) as c FROM tbl_penilaian_item');
$r2 = mysqli_fetch_assoc($q2);
echo "Total: " . $r2['c'] . "\n";
