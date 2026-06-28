<?php
require_once __DIR__ . '/../koneksi.php';

$username = '199303012022211013';

echo "Checking user: $username\n";

if (!$conn) {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
    exit;
}

// Check tbl_guru
$sql = "SELECT no_induk, nama_guru, status FROM tbl_guru WHERE no_induk = '$username'";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    echo "tbl_guru: FOUND\n";
    echo "Nama: " . $row['nama_guru'] . "\n";
    echo "Status: " . $row['status'] . "\n";
} else {
    echo "tbl_guru: NOT FOUND\n";
}

// Check tbl_pengguna
$sql = "SELECT no_induk, password, hak_akses FROM tbl_pengguna WHERE no_induk = '$username'";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    echo "tbl_pengguna: FOUND\n";
    echo "Hak Akses: " . $row['hak_akses'] . "\n";
    echo "Password Hash: " . $row['password'] . "\n";
} else {
    echo "tbl_pengguna: NOT FOUND\n";
}

// Check if there is another table for login
$sql = "SELECT id_user, username, nama, hak_akses FROM tbl_user WHERE username = '$username'";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    echo "tbl_user: FOUND\n";
    echo "Nama: " . $row['nama'] . "\n";
} else {
    echo "tbl_user: NOT FOUND\n";
}
