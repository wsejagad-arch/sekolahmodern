<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'sijurnal', 3306);
mysqli_set_charset($conn, 'utf8mb4');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . PHP_EOL;
    exit(1);
}

// Generate password baru
$newPassword = 'Admin@2026!Pusat';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password
$query = "UPDATE tbl_admin_pusat SET password = '" . mysqli_real_escape_string($conn, $hashedPassword) . "' WHERE username = 'pusatdata'";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "=== Password Admin Pusat Berhasil Direset ===" . PHP_EOL . PHP_EOL;
    echo "Username: pusatdata" . PHP_EOL;
    echo "Password Baru: $newPassword" . PHP_EOL . PHP_EOL;
    echo "Silakan login di http://localhost:8000/admin-pusat-login.php" . PHP_EOL;
} else {
    echo "Error: " . mysqli_error($conn) . PHP_EOL;
}

mysqli_close($conn);
