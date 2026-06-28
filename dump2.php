<?php
include "koneksi.php";
$qIzin = mysqli_query($conn, "SELECT id_izin, no_induk_siswa, kelas_siswa, kategori_pengajuan, validasi_wali_kelas FROM tbl_izin_siswa LIMIT 50");
while($r = mysqli_fetch_assoc($qIzin)) {
    echo $r['id_izin']." - ".$r['no_induk_siswa']." - ".$r['kelas_siswa']." - ".$r['validasi_wali_kelas']."\n";
}
?>
