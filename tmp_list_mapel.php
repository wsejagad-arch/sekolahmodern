<?php
include "koneksi.php";
$res = mysqli_query($conn, "SELECT id_mapel,no_induk,nama_mapel,kelas FROM tbl_mapel_ampu LIMIT 10");
if (!$res) { echo "ERROR: " . mysqli_error($conn) . "\n"; exit(1); }
while($r = mysqli_fetch_assoc($res)){
    echo json_encode($r) . "\n";
}
?>