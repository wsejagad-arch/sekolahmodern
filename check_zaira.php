<?php
require 'koneksi_local.php';
$q = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas, status FROM tbl_siswa WHERE nama_siswa LIKE '%zaira%'");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        print_r($r);
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
