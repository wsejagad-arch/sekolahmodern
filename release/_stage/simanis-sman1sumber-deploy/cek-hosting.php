<?php
/**
 * Uji hosting â€” https://simanis.sman1sumber.sch.id/cek-hosting.php
 */
header('Content-Type: text/plain; charset=utf-8');

echo "SIMANIS hosting OK\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'Waktu: ' . date('Y-m-d H:i:s') . "\n";
echo 'Folder: ' . __DIR__ . "\n";
echo 'index.php: ' . (is_file(__DIR__ . '/index.php') ? 'ada' : 'TIDAK ADA') . "\n";
echo '.htaccess: ' . (is_file(__DIR__ . '/.htaccess') ? 'ada (' . filesize(__DIR__ . '/.htaccess') . ' byte)' : 'tidak ada') . "\n";

$configFile = __DIR__ . '/config.hosting.php';
echo 'config.hosting.php: ' . (is_file($configFile) ? 'ada' : 'TIDAK ADA') . "\n";

if (!is_file($configFile)) {
    echo "\nBuat config.hosting.php dari config.hosting.example.php\n";
    exit(0);
}

try {
    $cfg = require $configFile;
} catch (Throwable $e) {
    echo 'config.hosting.php ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}

if (!is_array($cfg)) {
    echo "config.hosting.php harus return array.\n";
    exit(1);
}

$host = (string) ($cfg['host'] ?? 'localhost');
$user = (string) ($cfg['user'] ?? '');
$pass = (string) ($cfg['password'] ?? '');
$db   = (string) ($cfg['database'] ?? '');
$port = (int) ($cfg['port'] ?? 3306);

echo "DB host: {$host}:{$port}\n";
echo "DB user: {$user}\n";
echo "DB name: {$db}\n";

mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        echo 'Database: GAGAL - ' . $conn->connect_error . "\n";
    } else {
        echo "Database: terhubung ke {$db}\n";
        $conn->close();
    }
} catch (Throwable $e) {
    echo 'Database: GAGAL - ' . $e->getMessage() . "\n";
}

echo "\nJika Database terhubung, buka splash.php\n";

