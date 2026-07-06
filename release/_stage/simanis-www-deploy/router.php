<?php

/**
 * router.php
 * Central router untuk semua pages requests
 * Menerima: type (guru/siswa/admin), page (filename)
 * 
 * Usage:
 * - /pages/guru/data-siswa → router.php?type=guru&page=data-siswa
 * - /pages/siswa/presensi → router.php?type=siswa&page=presensi
 * - /pages/admin/pengumuman → router.php?type=admin&page=pengumuman
 */

require_once __DIR__ . '/auth_helper.php';

$type = $_GET['type'] ?? '';
$page = $_GET['page'] ?? '';

// Sanitize inputs
$type = preg_replace('/[^a-z]/', '', strtolower($type));
$page = preg_replace('/[^a-z0-9_-]/', '', strtolower($page));

if (!$type || !$page) {
    http_response_code(400);
    die('Invalid route');
}

// Map type to directory
$dirMap = [
    'guru' => 'pages/guru',
    'siswa' => 'pages/siswa',
    'admin' => 'pages/admin',
    'public' => 'pages'
];
$typeDir = $dirMap[$type] ?? null;

if (!$typeDir) {
    http_response_code(400);
    die('Invalid type');
}

// Build file path
$filePath = __DIR__ . '/' . $typeDir . '/' . $page . '.php';

// Security: verify file exists and is within correct directory
$realPath = realpath($filePath);
$allowedDir = realpath(__DIR__ . '/' . $typeDir);

if (
    !$realPath ||
    !$allowedDir ||
    strpos($realPath, $allowedDir) !== 0 ||
    !is_file($realPath)
) {
    http_response_code(404);
    die('Page not found');
}

// Check access permissions based on type
if ($type === 'guru') {
    require_login();
    if (!is_guru()) {
        http_response_code(403);
        die('Access denied: Guru only');
    }
} elseif ($type === 'siswa') {
    require_login();
    if (!is_siswa()) {
        http_response_code(403);
        die('Access denied: Siswa only');
    }
} elseif ($type === 'admin') {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied: Admin only');
    }
}

// Include the page from its own directory so legacy relative includes keep working.
$previousCwd = getcwd();
chdir(dirname($realPath));
include $realPath;
if ($previousCwd !== false) {
    chdir($previousCwd);
}
