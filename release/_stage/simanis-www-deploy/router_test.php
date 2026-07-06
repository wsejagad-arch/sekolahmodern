<?php

/**
 * Router Test
 * Test apakah hidden page URLs bekerja
 */

require_once __DIR__ . '/bootstrap.php';
require_admin();

echo "<h2>🧪 Router Test</h2>";
echo "<hr>";

$testCases = [
    [
        'description' => 'Guru Page (data-siswa)',
        'url' => guru_page('data-siswa'),
        'path' => 'pages/guru/data-siswa',
    ],
    [
        'description' => 'Siswa Page (presensi)',
        'url' => siswa_page('presensi'),
        'path' => 'pages/siswa/presensi',
    ],
    [
        'description' => 'Admin Page (pengumuman)',
        'url' => admin_page('pengumuman'),
        'path' => 'pages/admin/pengumuman',
    ],
    [
        'description' => 'Guru Page with Query (data-siswa?kelas=X)',
        'url' => guru_page('data-siswa', ['kelas' => 'X']),
        'path' => 'pages/guru/data-siswa',
    ],
];

echo "<table border='1' cellpadding='15' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Description</th>";
echo "<th>Generated URL</th>";
echo "<th>File Path</th>";
echo "<th>File Exists?</th>";
echo "</tr>";

foreach ($testCases as $test) {
    $filePath = __DIR__ . '/' . $test['path'] . '.php';
    $exists = file_exists($filePath);
    $fileStatus = $exists ? '✓ YES' : '✗ NO';
    $fileStatusColor = $exists ? '#4caf50' : '#f44336';

    echo "<tr>";
    echo "<td>" . htmlspecialchars($test['description']) . "</td>";
    echo "<td><code style='font-size: 0.85em; word-break: break-all;'>" . htmlspecialchars($test['url']) . "</code></td>";
    echo "<td><code>" . htmlspecialchars($test['path'] . '.php') . "</code></td>";
    echo "<td style='color: $fileStatusColor; font-weight: bold;'>$fileStatus</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

echo "<h3>✅ How to Test in Browser</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 4px;'>";
echo "<p>1. Login as Guru</p>";
echo "<p>2. Try these URLs (they should work but show friendly path):</p>";
echo "<ul>";
echo "<li><a href='" . guru_page('data-siswa') . "' target='_blank'>" . guru_page('data-siswa') . "</a></li>";
echo "<li><a href='" . guru_page('presensi') . "' target='_blank'>" . guru_page('presensi') . "</a></li>";
echo "<li><a href='" . guru_page('nilai') . "' target='_blank'>" . guru_page('nilai') . "</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='url_replacement_guide.php'>📋 View URL Replacement Guide</a></p>";
