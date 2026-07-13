<?php
require_once __DIR__ . '/koneksi.php';

if (!$conn) {
    echo "Database connection failed.";
    exit;
}

$u = '05803';
$sql = "SELECT * FROM tbl_siswa WHERE no_induk LIKE '%05803%' OR nama_siswa LIKE '%JOJOK%'";
$res = mysqli_query($conn, $sql);
$rows = [];
if ($res) {
    while($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }
}

$sql2 = "SELECT * FROM tbl_pengguna WHERE no_induk LIKE '%05803%'";
$res2 = mysqli_query($conn, $sql2);
$rows2 = [];
if ($res2) {
    while($r = mysqli_fetch_assoc($res2)) {
        $rows2[] = $r;
    }
}

echo "<pre>";
echo "TBL_SISWA:\n";
print_r($rows);
echo "\nTBL_PENGGUNA:\n";
print_r($rows2);
echo "</pre>";
