<?php
include "koneksi.php";
$q = mysqli_query($conn, "SELECT kelas, nip_wali FROM tbl_kelas WHERE nip_wali IN (SELECT no_induk FROM tbl_guru WHERE nama_guru LIKE '%Dwi Wahyu%')");
while($r = mysqli_fetch_assoc($q)) echo json_encode($r)."\n";
?>
