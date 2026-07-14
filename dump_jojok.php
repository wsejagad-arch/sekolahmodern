<?php
include 'koneksi.php';
$out = "";
$q = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE nama_siswa LIKE '%JOJOK FAHRUL%'");
while($r = mysqli_fetch_assoc($q)) {
    $out .= print_r($r, true);
}
$q2 = mysqli_query($conn, "SELECT p.* FROM tbl_pengguna p JOIN tbl_siswa s ON p.no_induk=s.no_induk WHERE s.nama_siswa LIKE '%JOJOK FAHRUL%'");
while($r = mysqli_fetch_assoc($q2)) {
    $out .= print_r($r, true);
}
file_put_contents('jojok_output.txt', $out);
echo "done";
