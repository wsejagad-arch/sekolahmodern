<?php
// buka_file.php
// Proxy untuk menyamarkan URL file asli
session_start();

$f = $_GET['f'] ?? '';
if (empty($f)) {
    die("File tidak valid.");
}

// Decode base64 URL-safe (replace - with +, _ with /)
$b64 = str_replace(['-', '_'], ['+', '/'], $f);
$path = base64_decode($b64);

if (!$path || strpos($path, '..') !== false) {
    die("Akses ditolak.");
}

$realPath = realpath(__DIR__ . '/' . $path);

// Keamanan: pastikan file berada di dalam folder uploads/
$uploadDir = realpath(__DIR__ . '/uploads/');
if ($realPath === false || strpos($realPath, $uploadDir) !== 0 || !is_file($realPath)) {
    die("File tidak ditemukan atau akses ditolak.");
}

$mime = mime_content_type($realPath);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($realPath) . '"');
header('Content-Length: ' . filesize($realPath));
readfile($realPath);
exit;
