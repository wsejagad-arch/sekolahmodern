<?php
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$password = '';
$database = 'sijurnal';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = null;
try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        error_log('[koneksi_local.php] MySQL connect error: ' . $conn->connect_error);
        $conn = null;
    } else {
        mysqli_set_charset($conn, 'utf8');
    }
} catch (Throwable $e) {
    error_log('[koneksi_local.php] MySQL exception: ' . $e->getMessage());
    $conn = null;
}
