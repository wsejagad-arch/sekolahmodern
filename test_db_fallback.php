<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test Primary
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'sijurnal';
$port = 3306;

echo "Testing Primary (Local)...\n";
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
@$conn->real_connect($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    echo "Primary failed: " . $conn->connect_error . "\n";
} else {
    echo "Primary OK!\n";
}

echo "\nTesting Fallback (VPS)...\n";
$host_vps = '203.175.125.118';
$user_vps = 'ubuntu';
$password_vps = 'wahyu123';
$database_vps = 'sijurnal';
$port_vps = 26035;

$conn_vps = mysqli_init();
$conn_vps->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
@$conn_vps->real_connect($host_vps, $user_vps, $password_vps, $database_vps, $port_vps);

if ($conn_vps->connect_error) {
    echo "Fallback failed: " . $conn_vps->connect_error . "\n";
} else {
    echo "Fallback OK!\n";
}
