<?php
// Database configuration untuk hosting
$host = "localhost";
$port = "3306";
$user = "smasumb1_web1";
$password = "wahyu1234567890";
$database = "smasumb1_smanis1";

// Create connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Set charset
mysqli_set_charset($conn, "utf8");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>