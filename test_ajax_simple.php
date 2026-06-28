<?php
// Test AJAX endpoint tanpa session dependency
include "koneksi.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'connection' => $conn ? 'Connected' : 'Failed',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'test' => 'AJAX endpoint reachable'
]);
?>