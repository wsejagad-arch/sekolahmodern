<?php
include 'koneksi.php';
$q = mysqli_query($conn, 'SELECT tanggal FROM tbl_penilaian_item ORDER BY tanggal DESC LIMIT 10');
$dates = [];
while ($r = mysqli_fetch_assoc($q)) {
    $dates[] = $r['tanggal'];
}
echo "Dates in DB: " . implode(', ', $dates) . "\n";
