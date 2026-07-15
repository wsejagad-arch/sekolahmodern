<?php
require_once 'koneksi_local.php';
$conn = mysqli_connect($host, $user, $pass, $db);
$res = mysqli_query($conn, "SELECT id, tanggal, no_induk, status, status_akhir, sumber FROM tbl_absen ORDER BY id DESC LIMIT 10");
$data = [];
while($r = mysqli_fetch_assoc($res)) {
    $data[] = $r;
}
echo json_encode($data, JSON_PRETTY_PRINT);
