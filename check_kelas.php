<?php
require 'koneksi.php';

echo "--- tbl_kelas ---\n";
$q = mysqli_query($conn, "SELECT kelas, COUNT(*) as c FROM tbl_kelas GROUP BY kelas HAVING c > 1");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        print_r($r);
    }
}

echo "--- All XII classes in tbl_kelas ---\n";
$q = mysqli_query($conn, "SELECT id, kelas FROM tbl_kelas WHERE kelas LIKE 'XII%'");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        print_r($r);
    }
}

echo "--- Alumni count in tbl_siswa ---\n";
$q = mysqli_query($conn, "SELECT kelas, COUNT(*) as c FROM tbl_siswa WHERE status IN ('Lulus', 'Alumni') GROUP BY kelas");
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        print_r($r);
    }
}
?>
