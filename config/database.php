<?php
$host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '';
$db   = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'sekolahmodern';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database configuration error.');
}

$conn->set_charset('utf8mb4');
?>
