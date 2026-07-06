<?php
include "c:\xampp\htdocs\jurnal\koneksi.php";

$qIzin = mysqli_query($conn, "SELECT id_izin, no_induk_siswa, kelas_siswa, kategori_pengajuan, validasi_wali_kelas FROM tbl_izin_siswa");
$data = [];
while($r = mysqli_fetch_assoc($qIzin)) {
    $data[] = $r;
}
file_put_contents("c:\xampp\htdocs\jurnal\izin_debug.json", json_encode($data, JSON_PRETTY_PRINT));
?>
