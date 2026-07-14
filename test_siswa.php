<?php
include 'koneksi.php';
$q = mysqli_query($conn, "SELECT no_induk, COUNT(*) as c FROM tbl_siswa GROUP BY no_induk HAVING c > 1");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        echo $r['no_induk'] . " - " . $r['c'] . "\n";
    }
}
$q2 = mysqli_query($conn, "SELECT no_induk, COUNT(*) as c FROM tbl_pengguna WHERE hak_akses = 3 GROUP BY no_induk HAVING c > 1");
if ($q2) {
    while($r = mysqli_fetch_assoc($q2)) {
        echo "Duplicate in tbl_pengguna: " . $r['no_induk'] . " - " . $r['c'] . "\n";
    }
}
