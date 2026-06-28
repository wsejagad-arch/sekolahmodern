<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

// Test simple tanpa buffering issues
echo json_encode([
    'session_valid' => isset($_SESSION["username"]),
    'username' => $_SESSION["username"] ?? null,
    'post_data' => $_POST,
    'connection' => $conn ? true : false
]);
?>