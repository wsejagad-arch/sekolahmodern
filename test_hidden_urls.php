<?php

/**
 * Test Hidden URL System
 * Verify that:
 * 1. Router.php exists and is functional
 * 2. Bootstrap.php helper functions exist
 * 3. .htaccess rewrite rules are in place
 */

echo "=== HIDDEN URL SYSTEM TEST ===\n\n";

// Test 1: Check if bootstrap.php exists and loads
echo "TEST 1: Bootstrap.php and Helper Functions\n";
echo "---\n";
if (file_exists(__DIR__ . '/bootstrap.php')) {
    echo "✓ bootstrap.php exists\n";
    require_once __DIR__ . '/bootstrap.php';

    // Check if helper functions exist
    if (function_exists('guru_page')) {
        echo "✓ guru_page() function exists\n";
        $url = guru_page('nilai');
        echo "  Example: guru_page('nilai') → $url\n";
    } else {
        echo "✗ guru_page() function NOT found\n";
    }

    if (function_exists('siswa_page')) {
        echo "✓ siswa_page() function exists\n";
        $url = siswa_page('presensi');
        echo "  Example: siswa_page('presensi') → $url\n";
    } else {
        echo "✗ siswa_page() function NOT found\n";
    }

    if (function_exists('admin_page')) {
        echo "✓ admin_page() function exists\n";
        $url = admin_page('pengumuman');
        echo "  Example: admin_page('pengumuman') → $url\n";
    } else {
        echo "✗ admin_page() function NOT found\n";
    }
} else {
    echo "✗ bootstrap.php NOT found\n";
}

echo "\n";

// Test 2: Check if router.php exists
echo "TEST 2: Router Configuration\n";
echo "---\n";
if (file_exists(__DIR__ . '/pages/router.php')) {
    echo "✓ pages/router.php exists\n";
} else {
    echo "✗ pages/router.php NOT found\n";
}

echo "\n";

// Test 3: Check .htaccess files
echo "TEST 3: Apache Rewrite Rules\n";
echo "---\n";

if (file_exists(__DIR__ . '/.htaccess')) {
    $root_htaccess = file_get_contents(__DIR__ . '/.htaccess');
    if (strpos($root_htaccess, 'pages/guru/') !== false) {
        echo "✓ Root .htaccess contains pages/guru routing\n";
    } else {
        echo "⚠ Root .htaccess may not contain pages/guru routing\n";
    }

    if (strpos($root_htaccess, 'router.php') !== false) {
        echo "✓ Root .htaccess contains router.php references\n";
    } else {
        echo "⚠ Root .htaccess may not contain router.php references\n";
    }
} else {
    echo "✗ Root .htaccess NOT found\n";
}

if (file_exists(__DIR__ . '/pages/.htaccess')) {
    $pages_htaccess = file_get_contents(__DIR__ . '/pages/.htaccess');
    if (strpos($pages_htaccess, 'FilesMatch') !== false && strpos($pages_htaccess, '.php') !== false) {
        echo "✓ pages/.htaccess contains PHP file blocking rules\n";
    } else {
        echo "⚠ pages/.htaccess may not contain proper blocking rules\n";
    }
} else {
    echo "✗ pages/.htaccess NOT found\n";
}

echo "\n";

// Test 4: Check if key pages exist
echo "TEST 4: Key Pages Exist\n";
echo "---\n";
$pages = ['pages/guru/nilai.php', 'pages/siswa/presensi.php', 'pages/admin/pengumuman.php'];
foreach ($pages as $page) {
    if (file_exists(__DIR__ . '/' . $page)) {
        echo "✓ $page exists\n";
    } else {
        echo "⚠ $page not found (but may not be needed)\n";
    }
}

echo "\n";

// Test 5: Verify helper functions work with parameters
echo "TEST 5: Helper Functions with Parameters\n";
echo "---\n";
if (function_exists('guru_page')) {
    $url1 = guru_page('data-siswa');
    $url2 = guru_page('nilai', ['kelas' => 'XI IPA 1']);
    echo "guru_page('data-siswa') → $url1\n";
    echo "guru_page('nilai', ['kelas'=>'XI IPA 1']) → $url2\n";
}

echo "\n";

// Test 6: Check files that were updated
echo "TEST 6: Updated Files Check\n";
echo "---\n";
$files_to_check = [
    'login_action.php',
    'pengumuman.php',
    'ubah-password.php',
    'home.php'
];

foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        $guru_page_count = substr_count($content, 'guru_page(');
        $siswa_page_count = substr_count($content, 'siswa_page(');

        if ($guru_page_count > 0 || $siswa_page_count > 0) {
            echo "✓ $file uses guru_page/siswa_page helpers ($guru_page_count guru, $siswa_page_count siswa calls)\n";
        } else {
            echo "⚠ $file may not use helper functions yet\n";
        }
    }
}

echo "\n";
echo "=== TEST COMPLETE ===\n";
echo "If all tests pass, the hidden URL system is properly configured.\n";
echo "Access pages through helper functions instead of direct paths.\n";
