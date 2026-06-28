<?php
require_once __DIR__ . '/../koneksi.php';

if (!$conn) {
    echo "Database connection failed.\n";
    exit;
}

$username = '199303012022211013';

echo "Checking Admin:\n";
$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
print_r($res->fetch_assoc());

echo "\nChecking Guru:\n";
$stmt = $conn->prepare("SELECT * FROM tbl_guru WHERE no_induk = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
print_r($res->fetch_assoc());

echo "\nChecking Pengguna (for password):\n";
$stmt = $conn->prepare("SELECT * FROM tbl_pengguna WHERE no_induk = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
print_r($res->fetch_assoc());
