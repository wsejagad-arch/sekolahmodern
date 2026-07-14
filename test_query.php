<?php
require 'koneksi.php';
$next_level = 'XI';
$idSekolah = 1;
$sql = "SELECT DISTINCT kelas FROM tbl_kelas WHERE id_sekolah = $idSekolah AND (kelas LIKE '$next_level %' OR kelas = '$next_level') ORDER BY kelas";
$q = mysqli_query($conn, $sql);
while($r = mysqli_fetch_assoc($q)) {
    echo $r['kelas'] . "\n";
}
