<?php
$c = mysqli_connect('127.0.0.1', 'root', '', 'sijurnal');
$q=mysqli_query($c, 'SELECT id_izin, no_induk_siswa, validasi_wali_kelas, status_izin, foto_selfie FROM tbl_izin_siswa ORDER BY id_izin DESC LIMIT 5');
while($r=mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
