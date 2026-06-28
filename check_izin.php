<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin");
$out = "";
while($r = mysqli_fetch_assoc($res)) {
    $out .= $r['Field'] . " - " . $r['Type'] . "\n";
}
file_put_contents('izin_cols.txt', $out);
?>
