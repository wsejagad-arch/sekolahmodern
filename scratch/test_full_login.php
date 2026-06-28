<?php
require_once __DIR__ . '/../bootstrap.php';

$username = '199303012022211013';
$password = '12345';
$status = 'Aktif';

echo "Testing login for $username\n";
echo "DB Connection: " . ($conn ? "Connected" : "Failed") . "\n";

if (!$conn) {
    echo "Error: " . mysqli_connect_error() . "\n";
}

// 1. Check Admin
echo "\nChecking Admin...\n";
$u = mysqli_real_escape_string($conn, $username);
$sql = "SELECT id_user, username, nama, hak_akses, password FROM tbl_user WHERE username = '$u' LIMIT 1";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $user = mysqli_fetch_assoc($res);
    echo "Admin Found! Hash: " . $user['password'] . "\n";
    echo "Password Verify: " . (md5($password) == $user['password'] ? "MATCH" : "MISMATCH") . "\n";
} else {
    echo "Admin Not Found\n";
}

// 2. Check Guru
echo "\nChecking Guru...\n";
$s = mysqli_real_escape_string($conn, $status);
$sql = "SELECT g.no_induk, g.nama_guru, g.status_kepegawaian, p.password 
        FROM tbl_guru g 
        JOIN tbl_pengguna p ON g.no_induk = p.no_induk 
        WHERE p.no_induk = '$u' AND g.status = '$s' LIMIT 1";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $user = mysqli_fetch_assoc($res);
    echo "Guru Found! Name: " . $user['nama_guru'] . "\n";
    echo "Hash: " . $user['password'] . "\n";
    echo "MD5 match: " . (md5($password) == $user['password'] ? "YES" : "NO") . "\n";
    echo "Bcrypt match: " . (password_verify($password, $user['password']) ? "YES" : "NO") . "\n";
} else {
    echo "Guru Not Found\n";
}

// 3. Check Siswa
echo "\nChecking Siswa...\n";
$sql = "SELECT s.no_induk, s.nama_siswa, s.kelas, p.password 
        FROM tbl_siswa s 
        JOIN tbl_pengguna p ON s.no_induk = p.no_induk 
        WHERE s.no_induk = '$u' AND s.status = '$s' LIMIT 1";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $user = mysqli_fetch_assoc($res);
    echo "Siswa Found! Name: " . $user['nama_siswa'] . "\n";
} else {
    echo "Siswa Not Found\n";
}
