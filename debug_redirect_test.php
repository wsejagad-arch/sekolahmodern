<?php
// Debug script to inspect URL generation and redirect normalization
require_once __DIR__ . '/bootstrap.php';

// Emulate server variables as if under Apache
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 8000;
$_SERVER['HTTPS'] = 'off';
$_SERVER['SCRIPT_NAME'] = '/jurnal/index.php';

function normalize_target_for_test(string $target): string
{
    if (strpos($target, 'http') === 0 || strpos($target, '/') === 0) {
        $url = $target;
    } else {
        $url = get_app_url() . get_base_path() . '/' . ltrim($target, '/');
    }
    $hasQuery = parse_url($url, PHP_URL_QUERY) !== null;
    $url .= ($hasQuery ? '&' : '?') . 'gagallogin';
    return $url;
}

// Print helper outputs
echo "get_app_url(): " . get_app_url() . PHP_EOL;
echo "get_base_path(): " . get_base_path() . PHP_EOL;
echo "guru_page('nilai'): " . guru_page('nilai') . PHP_EOL;
echo "siswa_page('presensi'): " . siswa_page('presensi') . PHP_EOL;
echo "admin_page('pengumuman'): " . admin_page('pengumuman') . PHP_EOL;

// Test normalization for various targets
$targets = [
    'login.php',
    '/login.php',
    'http://localhost:8000/jurnal/login.php',
    guru_page('nilai'),
    "pages/guru/nilai.php",
];

foreach ($targets as $t) {
    echo "\nTarget: $t\n";
    echo "Normalized: " . normalize_target_for_test($t) . PHP_EOL;
}
