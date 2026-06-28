<?php
/**
 * Test Koneksi Database dengan Logika yang Sama seperti koneksi.php
 */

// Simulasi deteksi environment seperti di koneksi.php
$serverName = $_SERVER['SERVER_NAME'] ?? 'cli';
$isLocal = in_array($serverName, ['localhost', '127.0.0.1']) || (strpos(php_uname('n'), 'DESKTOP') === 0);

echo "<!DOCTYPE html><html><head><title>TEST KONEKSI DATABASE</title></head><body>";
echo "<h1>🔍 TEST KONEKSI DATABASE</h1>";
echo "<h2>Environment Detection:</h2>";
echo "<ul>";
echo "<li><strong>Server Name:</strong> $serverName</li>";
echo "<li><strong>PHP uname:</strong> " . php_uname('n') . "</li>";
echo "<li><strong>Is Local:</strong> " . ($isLocal ? 'YES' : 'NO') . "</li>";
echo "</ul>";

// Default configuration (production)
$cfg = [
    'host' => 'localhost',
    'user' => 'smasumb1_simanis1',
    'password' => 'W@hyu123!',
    'db' => 'smasumb1_simanis',
    'port' => 3306,
];

echo "<h2>Default Configuration (Production):</h2>";
echo "<pre>" . print_r($cfg, true) . "</pre>";

// Jika local, override dengan local defaults
if ($isLocal) {
    $cfg['user'] = 'root';
    $cfg['password'] = '';
    $cfg['db'] = 'jurnal';

    echo "<div style='color: blue;'>ℹ️ Menggunakan konfigurasi lokal</div>";
} else {
    echo "<div style='color: orange;'>⚠️ Menggunakan konfigurasi production</div>";
}

// Cek apakah ada config.local.php
$localOverride = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
if ($isLocal && file_exists($localOverride)) {
    echo "<h3>Config.local.php ditemukan:</h3>";
    echo "<pre>";
    include $localOverride;
    echo "</pre>";

    echo "<div style='color: green;'>✅ config.local.php berhasil di-include</div>";
} else {
    echo "<div style='color: red;'>❌ config.local.php tidak ditemukan atau bukan local environment</div>";
}

echo "<h2>Final Configuration:</h2>";
echo "<pre>" . print_r($cfg, true) . "</pre>";

// Test koneksi
echo "<h2>Test Koneksi:</h2>";

$conn = mysqli_connect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['db'], $cfg['port']);

if (!$conn) {
    echo "<div style='color: red; font-size: 18px;'>❌ KONEKSI GAGAL!</div>";
    echo "<div style='color: red;'>Error: " . mysqli_connect_error() . "</div>";
    echo "<div style='color: red;'>Host: {$cfg['host']}:{$cfg['port']}</div>";
    echo "<div style='color: red;'>User: {$cfg['user']}</div>";
    echo "<div style='color: red;'>Database: {$cfg['db']}</div>";
} else {
    echo "<div style='color: green; font-size: 18px;'>✅ KONEKSI BERHASIL!</div>";
    echo "<div style='color: green;'>Connected to: " . mysqli_get_host_info($conn) . "</div>";
    echo "<div style='color: green;'>Server version: " . mysqli_get_server_info($conn) . "</div>";

    // Test query sederhana
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<div style='color: green;'>✅ Query berhasil - Total jadwal: " . $row['total'] . "</div>";
    } else {
        echo "<div style='color: red;'>❌ Query gagal: " . mysqli_error($conn) . "</div>";
    }

    mysqli_close($conn);
}

echo "</body></html>";
?>