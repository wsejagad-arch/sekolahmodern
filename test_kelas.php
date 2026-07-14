<?php
require 'koneksi.php';
$q = mysqli_query($conn, 'SELECT DISTINCT kelas FROM tbl_siswa');
while ($r = mysqli_fetch_assoc($q)) {
    echo $r['kelas'] . "\n";
}
