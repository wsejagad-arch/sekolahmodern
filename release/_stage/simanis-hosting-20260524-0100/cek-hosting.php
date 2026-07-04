<?php
/**
 * Uji cepat setelah upload hosting.
 * Buka: https://domainanda.com/jurnal/cek-hosting.php
 * Hapus file ini setelah aplikasi normal.
 */
header('Content-Type: text/plain; charset=utf-8');
echo "SIMANIS hosting OK\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'Folder: ' . __DIR__ . "\n";
echo 'index.php: ' . (is_file(__DIR__ . '/index.php') ? 'ada' : 'TIDAK ADA') . "\n";
echo 'splash.php: ' . (is_file(__DIR__ . '/splash.php') ? 'ada' : 'TIDAK ADA') . "\n";
echo '.htaccess: ' . (is_file(__DIR__ . '/.htaccess') ? 'ada' : 'tidak ada') . "\n";
echo 'config.hosting.php: ' . (is_file(__DIR__ . '/config.hosting.php') ? 'ada' : 'belum dibuat') . "\n";

if (is_file(__DIR__ . '/config.hosting.php')) {
    $cfg = require __DIR__ . '/config.hosting.php';
    if (is_array($cfg) && !empty($cfg['database'])) {
        $host = $cfg['host'] ?? 'localhost';
        $user = $cfg['user'] ?? '';
        $pass = $cfg['password'] ?? '';
        $db = $cfg['database'] ?? '';
        $port = (int) ($cfg['port'] ?? 3306);
        $conn = @new mysqli($host, $user, $pass, $db, $port);
        echo 'Database: ' . ($conn->connect_error ? 'GAGAL - ' . $conn->connect_error : 'terhubung') . "\n";
        if ($conn instanceof mysqli) {
            $conn->close();
        }
    }
}

echo "\nJika baris ini terbaca tetapi index.php 403, ganti .htaccess dengan htaccess/.htaccess-minimal\n";
