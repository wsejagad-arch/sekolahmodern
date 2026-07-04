<?php
// koneksi.php - dispatcher: pilih koneksi lokal saat dijalankan di localhost

// If a local connection file exists and we're running on localhost/CLI, prefer it.
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';

$isLocal = false;
if (in_array(php_sapi_name(), ['cli', 'cli-server'], true)) {
    $isLocal = true;
} else {
    if (in_array($serverAddr, ['127.0.0.1', '::1'], true) ||
        stripos($serverName, 'localhost') !== false ||
        stripos($httpHost, 'localhost') !== false ||
        stripos($httpHost, '127.0.0.1') !== false ||
        empty($serverAddr)) {
        $isLocal = true;
    }
}

if ($isLocal && file_exists(__DIR__ . '/koneksi_local.php')) {
    include __DIR__ . '/koneksi_local.php';
    if (isset($conn) && $conn instanceof mysqli) {
        require_once __DIR__ . '/multi_tenant.php';
        mt_bootstrap($conn);
    }
    return;
}

// Database configuration untuk hosting
$host = 'localhost';
$port = 3306;
$user = '';
$password = '';
$database = '';

if (file_exists(__DIR__ . '/config.hosting.php')) {
    $cfg = require __DIR__ . '/config.hosting.php';
    if (is_array($cfg)) {
        $host = (string) ($cfg['host'] ?? $host);
        $port = (int) ($cfg['port'] ?? $port);
        $user = (string) ($cfg['user'] ?? $user);
        $password = (string) ($cfg['password'] ?? $password);
        $database = (string) ($cfg['database'] ?? $database);
    }
} else {
    // Fallback legacy (isi via config.hosting.php di production)
    $host = 'localhost';
    $port = 3306;
    $user = 'smasumb1_sijurnal1';
    $password = 'JU-gxs^([=UN';
    $database = 'smasumb1_sijurnal';
}

// Create connection
mysqli_report(MYSQLI_REPORT_OFF);
$conn = null;
try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        error_log('[koneksi.php] MySQL connect error: ' . $conn->connect_error);
        $conn = null;
    } else {
        mysqli_set_charset($conn, 'utf8');
        require_once __DIR__ . '/multi_tenant.php';
        mt_bootstrap($conn);
    }
} catch (Throwable $e) {
    error_log('[koneksi.php] MySQL exception: ' . $e->getMessage());
    $conn = null;
}
?>
