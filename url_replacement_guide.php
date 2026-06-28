<?php

/**
 * Update URL Links Script
 * Script untuk scan dan update semua links yang menunjuk ke pages/*
 * 
 * Contoh perubahan:
 * OLD: href="pages/guru/data-siswa.php"
 * NEW: href="<?= guru_page('data-siswa') ?>"
 */

require_once __DIR__ . '/bootstrap.php';
require_admin();

echo "<h2>📋 URL Replacement Summary</h2>";
echo "<hr>";

// Pattern mapping
$patterns = [
    // pages/guru/* patterns
    '/pages\/guru\/([a-zA-Z0-9_-]+)\.php/' => 'pages/guru',
    '/pages\/siswa\/([a-zA-Z0-9_-]+)\.php/' => 'pages/siswa',
    '/pages\/admin\/([a-zA-Z0-9_-]+)\.php/' => 'pages/admin',
];

$replacements = [
    'pages/guru' => [
        'pattern' => 'guru_page(\'$1\')',
        'example_old' => 'pages/guru/data-siswa.php',
        'example_new' => "<?= guru_page('data-siswa') ?>",
    ],
    'pages/siswa' => [
        'pattern' => 'siswa_page(\'$1\')',
        'example_old' => 'pages/siswa/presensi.php',
        'example_new' => "<?= siswa_page('presensi') ?>",
    ],
    'pages/admin' => [
        'pattern' => 'admin_page(\'$1\')',
        'example_old' => 'pages/admin/pengumuman.php',
        'example_new' => "<?= admin_page('pengumuman') ?>",
    ],
];

echo "<h3>📝 Pattern Replacements</h3>";

foreach ($replacements as $type => $info) {
    echo "<div style='background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-radius: 4px;'>";
    echo "<h4>" . htmlspecialchars($type) . "</h4>";
    echo "<p><strong>Old pattern:</strong> <code>" . htmlspecialchars($info['example_old']) . "</code></p>";
    echo "<p><strong>New pattern:</strong> <code>" . htmlspecialchars($info['example_new']) . "</code></p>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>🔍 File Scan</h3>";

// Scan for PHP files that contain page links
$filesToScan = glob(__DIR__ . '/**/*.php', GLOB_RECURSIVE);
$filesWithLinks = [];

foreach ($filesToScan as $file) {
    // Skip certain directories
    if (
        strpos($file, 'vendor') !== false ||
        strpos($file, '/pages/') !== false ||
        strpos($file, 'router.php') !== false ||
        strpos($file, '/deploy_prod/') !== false ||
        strpos($file, '/home5/') !== false
    ) {
        continue;
    }

    $content = file_get_contents($file);

    // Check if file contains links to pages/*
    if (preg_match('/pages\/(guru|siswa|admin)\/[a-zA-Z0-9_-]+\.php/', $content)) {
        $relPath = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
        $filesWithLinks[] = $relPath;
    }
}

echo "<p>Found <strong>" . count($filesWithLinks) . "</strong> files with pages/* links</p>";

if (!empty($filesWithLinks)) {
    echo "<table border='1' cellpadding='10' cellspacing='0' style='width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>File</th></tr>";

    foreach (array_slice($filesWithLinks, 0, 50) as $file) {
        echo "<tr><td><code style='font-size: 0.9em;'>" . htmlspecialchars($file) . "</code></td></tr>";
    }

    if (count($filesWithLinks) > 50) {
        echo "<tr><td colspan='1' style='text-align: center;'>... and " . (count($filesWithLinks) - 50) . " more</td></tr>";
    }

    echo "</table>";
}

echo "<hr>";

echo "<h3>📚 Usage Guide</h3>";
echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 4px;'>";
echo "<h4>1. In PHP Files</h4>";
echo "<p>Use these functions to generate friendly URLs:</p>";
echo "<pre style='background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
echo htmlspecialchars("// For guru pages
<a href=\"<?= guru_page('data-siswa') ?>\">Data Siswa</a>

// For siswa pages  
<a href=\"<?= siswa_page('presensi') ?>\">Presensi</a>

// For admin pages
<a href=\"<?= admin_page('pengumuman') ?>\">Pengumuman</a>");
echo "</pre>";

echo "<h4>2. In JavaScript Redirects</h4>";
echo "<pre style='background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
echo htmlspecialchars("// Old way (exposed)
window.location = 'pages/guru/data-siswa.php';

// New way (hidden)
window.location = '" . htmlspecialchars(guru_page('data-siswa')) . "';");
echo "</pre>";

echo "</div>";

echo "<hr>";
echo "<p><a href='login.php'>← Back to Home</a></p>";
