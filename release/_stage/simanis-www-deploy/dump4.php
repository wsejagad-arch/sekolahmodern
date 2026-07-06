<?php
include "koneksi.php";
$q = mysqli_query($conn, "SELECT id_izin, no_induk_siswa, kelas_siswa, kategori_pengajuan, validasi_wali_kelas, id_sekolah FROM tbl_izin_siswa");
while($r = mysqli_fetch_assoc($q)) {
    echo json_encode($r) . "\n";
}
?>
