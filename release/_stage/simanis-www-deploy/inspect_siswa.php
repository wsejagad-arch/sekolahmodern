<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SELECT * FROM tbl_siswa LIMIT 1");
$fields = mysqli_fetch_fields($res);
foreach ($fields as $field) {
    echo $field->name . "\n";
}
?>
