<?php
// === KONFIGURASI DATABASE UTAMA (VPS) ===
$db_primary = [
    'host' => '203.175.125.118',
    'port' => 3306, // Port MySQL bawaan di VPS
    'user' => 'vps_jurnal',
    'password' => 'WahyuJurnal123!',
    'database' => 'sijurnal'
];

// === KONFIGURASI DATABASE CADANGAN (LOKAL) ===
$db_fallback = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'password' => '',
    'database' => 'sijurnal'
];

mysqli_report(MYSQLI_REPORT_OFF);
$conn = null;

// 1. Mencoba Koneksi ke Database Utama (Lokal)
try {
    // Set timeout sementara agar tidak hang jika DB mati
    $old_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 3);
    
    // Gunakan new mysqli karena mysqli_init() ternyata tidak didukung di server ini
    $conn = @new mysqli($db_primary['host'], $db_primary['user'], $db_primary['password'], $db_primary['database'], $db_primary['port']);
    
    ini_set('default_socket_timeout', $old_timeout); // kembalikan timeout semula
    
    if ($conn->connect_error) {
        error_log('[koneksi_local.php] Database Utama gagal: ' . $conn->connect_error);
        $conn = null;
    }
} catch (Throwable $e) {
    error_log('[koneksi_local.php] Exception pada Database Utama: ' . $e->getMessage());
    $conn = null;
}

// 2. Jika Database Utama Gagal, Coba Database Cadangan (VPS)
if (!$conn) {
    try {
        $old_timeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 5);
        
        $conn = @new mysqli($db_fallback['host'], $db_fallback['user'], $db_fallback['password'], $db_fallback['database'], $db_fallback['port']);
        
        ini_set('default_socket_timeout', $old_timeout);
        
        if ($conn->connect_error) {
            error_log('[koneksi_local.php] Database Cadangan (VPS) juga gagal: ' . $conn->connect_error);
            $conn = null;
        } else {
            error_log('[koneksi_local.php] Beralih menggunakan Database Cadangan (VPS).');
        }
    } catch (Throwable $e) {
        error_log('[koneksi_local.php] Exception pada Database Cadangan (VPS): ' . $e->getMessage());
        $conn = null;
    }
}

// 3. Set Charset jika koneksi (dari manapun) berhasil
if ($conn) {
    mysqli_set_charset($conn, 'utf8');
}
