<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'sekolah_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database configuration error.');
}

$conn->set_charset('utf8mb4');
?>
