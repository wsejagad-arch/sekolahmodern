<?php
include 'koneksi.php';
$res = mysqli_query($conn, 'DESCRIBE tbl_siswa');
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . ' - ' . $row[1] . "\n";
}
?>
