<?php
require_once dirname(__DIR__) . '/bootstrap.php';
global $conn;
$res = mysqli_query($conn, "SELECT username, password FROM tbl_pengguna LIMIT 5");
while($r = mysqli_fetch_assoc($res)) {
    echo "Pengguna: " . $r['username'] . " - " . $r['password'] . "\n";
}
$res = mysqli_query($conn, "SELECT no_induk, status FROM tbl_guru LIMIT 5");
while($r = mysqli_fetch_assoc($res)) {
    echo "Guru: " . $r['no_induk'] . " - " . $r['status'] . "\n";
}
$res = mysqli_query($conn, "SELECT no_induk, status FROM tbl_siswa LIMIT 5");
while($r = mysqli_fetch_assoc($res)) {
    echo "Siswa: " . $r['no_induk'] . " - " . $r['status'] . "\n";
}
