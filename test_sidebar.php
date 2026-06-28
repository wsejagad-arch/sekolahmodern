<?php
// Test file untuk debug sidebar
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';
require_login();

echo "<!DOCTYPE html><html><head><title>Test Sidebar</title>";
echo '<link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">';
echo '<link href="css/sb-admin-2.min.css" rel="stylesheet">';
echo "</head><body>";

echo "<h1>Test Include Sidebar</h1>";
echo "<hr>";

echo "<h2>Konten Sidebar:</h2>";
echo "<div style='border:2px solid red; padding:20px;'>";
include "sidebar.php";
echo "</div>";

echo "<hr>";
echo "<p>Jika sidebar muncul dengan benar di atas, berarti file sidebar.php OK</p>";
echo "<p>Jika muncul error atau kosong, ada masalah dengan sidebar.php atau koneksi database</p>";

echo "</body></html>";
?>
