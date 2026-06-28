<?php
include "koneksi.php";
$res = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa");
$out = "";
while($r = mysqli_fetch_assoc($res)) {
    $out .= $r['Field'] . "\n";
}
file_put_contents('cols.txt', $out);
?>
