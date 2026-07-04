<?php
include "koneksi.php";
$res = mysqli_query($conn, "SELECT id_materi,id_mapel,no_induk,`tanggal` AS `date`,kelas,materi FROM tbl_materi ORDER BY id_materi DESC LIMIT 10");
if (!$res) { echo "ERROR: " . mysqli_error($conn) . "\n"; exit(1); }
while($r = mysqli_fetch_assoc($res)){
    echo json_encode($r) . "\n";
}
?>