<?php
require_once 'c:\xampp\htdocs\jurnal\koneksi.php';

// Check schemas
$tables = ['tbl_tugas', 'tbl_literasi_tugas', 'tbl_tugas_kumpul', 'tbl_literasi_evaluasi_jawaban'];
foreach ($tables as $t) {
    echo "--- Table: $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE $t");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Table not found\n";
    }
}
?>
