<?php
include 'koneksi.php';
$q = mysqli_query($conn, "SELECT no_induk FROM tbl_siswa WHERE nama_siswa LIKE '%JOJOK FAHRUL%'");
if ($r = mysqli_fetch_assoc($q)) {
    $ni = $r['no_induk'];
    $hash = md5($ni); // Set password to his NIS
    mysqli_query($conn, "UPDATE tbl_pengguna SET password='$hash' WHERE no_induk='$ni'");
    file_put_contents('jojok_fixed.txt', 'Fixed password for ' . $ni);
}
