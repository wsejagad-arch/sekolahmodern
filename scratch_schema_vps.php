<?php
$conn = mysqli_connect('203.175.125.118', 'vps_jurnal', 'WahyuJurnal123!', 'sijurnal', 3306);
if (!$conn) die('Failed: ' . mysqli_connect_error());

$tables = ['tbl_literasi_tugas', 'tbl_literasi_soal', 'tbl_literasi_progress'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SHOW CREATE TABLE $t");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        echo $row['Create Table'] . "\n\n";
    } else {
        echo "Table $t not found.\n\n";
    }
}
