<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'c:\xampp\htdocs\jurnal\koneksi.php';
$q = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa");
if($q) {
    while($r = mysqli_fetch_assoc($q)) echo $r['Field'] . "\n";
} else {
    echo "DB Error: " . mysqli_error($conn);
}
?>
