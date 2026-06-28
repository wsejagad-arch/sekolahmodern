<?php

/**
 * Auto Update Links Script
 * Scan dan replace semua direct paths pages/* dengan helper functions
 */

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Auto Update Links</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .warning { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; border-radius: 4px; }
        button { padding: 10px 20px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #f57c00; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td, table th { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔄 Auto Update Links to Hidden URLs</h1>
    
    <div class='warning'>
        <strong>⚠️ WARNING:</strong> Script ini akan modify file-file di server. Pastikan sudah backup terlebih dahulu!
    </div>
    
    <div class='info'>
        <strong>ℹ️ INFO:</strong> Script akan:
        <ul>
            <li>Scan semua file PHP</li>
            <li>Cari references ke <code>pages/guru/</code>, <code>pages/siswa/</code>, <code>pages/admin/</code></li>
            <li>Replace dengan helper functions: <code>guru_page()</code>, <code>siswa_page()</code>, <code>admin_page()</code></li>
            <li>Skip: vendor/, deploy_prod/, home5/, router files</li>
        </ul>
    </div>
    
    <form method='POST'>
        <button type='submit' name='do_update' value='yes'>✅ Start Auto Update</button>
    </form>
</div>
</body>
</html>";
    exit;
}

$doUpdate = $_POST['do_update'] ?? '';
if ($doUpdate !== 'yes') {
    die('Invalid request');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Update Progress</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; }
        h2 { color: #333; }
        .progress { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .file { background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 3px solid #2196f3; }
        .success { border-left-color: #4caf50; }
        .warning { border-left-color: #ff9800; }
        .error { border-left-color: #f44336; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 0.9em; }
        .changes { background: #f9f9f9; padding: 10px; border-radius: 3px; margin-top: 10px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔄 Updating Links...</h1>";

$rootDir = __DIR__;
$skipDirs = ['vendor', 'deploy_prod', 'home5', 'logs', '.git'];

// Find all PHP files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$phpFiles = [];
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getPathname();

        // Skip certain directories
        $skip = false;
        foreach ($skipDirs as $skipDir) {
            if (strpos($path, DIRECTORY_SEPARATOR . $skipDir . DIRECTORY_SEPARATOR) !== false) {
                $skip = true;
                break;
            }
        }

        if (!$skip && strpos($path, 'router') === false && strpos($path, 'auto_update') === false) {
            $phpFiles[] = $path;
        }
    }
}

$totalFiles = count($phpFiles);
$updatedFiles = 0;
$results = [];

foreach ($phpFiles as $filePath) {
    $relPath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $filePath);

    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Pattern 1: "pages/guru/filename.php" in strings
    $content = preg_replace_callback(
        '/"pages\/guru\/([a-z0-9_-]+)\.php"/',
        function ($m) {
            return '"<?= guru_page(\'' . $m[1] . '\') ?>"';
        },
        $content
    );

    $content = preg_replace_callback(
        '/"pages\/guru\/([a-z0-9_-]+)"/',
        function ($m) {
            return '"<?= guru_page(\'' . $m[1] . '\') ?>"';
        },
        $content
    );

    // Pattern 2: 'pages/guru/filename.php' in strings
    $content = preg_replace_callback(
        '/\'pages\/guru\/([a-z0-9_-]+)\.php\'/',
        function ($m) {
            return '\'<?= guru_page(\'' . $m[1] . '\') ?>\'';
        },
        $content
    );

    $content = preg_replace_callback(
        '/\'pages\/guru\/([a-z0-9_-]+)\'/',
        function ($m) {
            return '\'<?= guru_page(\'' . $m[1] . '\') ?>\'';
        },
        $content
    );

    // Same for siswa
    $content = preg_replace_callback(
        '/"pages\/siswa\/([a-z0-9_-]+)\.php"/',
        function ($m) {
            return '"<?= siswa_page(\'' . $m[1] . '\') ?>"';
        },
        $content
    );

    $content = preg_replace_callback(
        '/\'pages\/siswa\/([a-z0-9_-]+)\.php\'/',
        function ($m) {
            return '\'<?= siswa_page(\'' . $m[1] . '\') ?>\'';
        },
        $content
    );

    // Same for admin
    $content = preg_replace_callback(
        '/"pages\/admin\/([a-z0-9_-]+)\.php"/',
        function ($m) {
            return '"<?= admin_page(\'' . $m[1] . '\') ?>"';
        },
        $content
    );

    $content = preg_replace_callback(
        '/\'pages\/admin\/([a-z0-9_-]+)\.php\'/',
        function ($m) {
            return '\'<?= admin_page(\'' . $m[1] . '\') ?>\'';
        },
        $content
    );

    if ($content !== $originalContent) {
        if (file_put_contents($filePath, $content) !== false) {
            $updatedFiles++;
            $changes = count(array_diff_assoc(
                explode("\n", $content),
                explode("\n", $originalContent)
            ));

            echo "<div class='file success'>";
            echo "<strong>✓ Updated:</strong> <code>" . htmlspecialchars($relPath) . "</code>";
            echo "<div class='changes'>" . $changes . " lines changed</div>";
            echo "</div>";

            $results[] = ['file' => $relPath, 'status' => 'updated', 'changes' => $changes];
        }
    }
}

echo "<div class='progress'>";
echo "<strong>✅ Update Complete!</strong><br>";
echo "Updated <strong>" . $updatedFiles . "</strong> out of <strong>" . $totalFiles . "</strong> files scanned.<br>";
echo "</div>";

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Files Scanned:</strong> " . $totalFiles . "</p>";
echo "<p><strong>Files Updated:</strong> " . $updatedFiles . "</p>";

if ($updatedFiles === 0) {
    echo "<div style='background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; border-radius: 4px;'>";
    echo "⚠️ No files were updated. This could mean:<br>";
    echo "- All files already using helper functions<br>";
    echo "- No direct paths found in scanned files<br>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='setup_hidden_urls_summary.php'>← Back to Setup</a></p>";
echo "</div></body></html>";
