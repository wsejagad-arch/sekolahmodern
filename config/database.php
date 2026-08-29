<?php
$host = 'localhost';
$user = 'smasumb1_web1';
$pass = 'wahyu1234567890';
$db   = 'smasumb1_smanis1';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database configuration error.');
}

$conn->set_charset('utf8mb4');
?>
