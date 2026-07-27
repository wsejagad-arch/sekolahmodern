<?php
include 'koneksi.php';
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
