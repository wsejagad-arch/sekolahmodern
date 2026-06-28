<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'sijurnal', 3306);
mysqli_set_charset($conn, 'utf8mb4');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . PHP_EOL;
    exit(1);
}

$result = mysqli_query($conn, 'SELECT id_admin_pusat, username, nama, email, status FROM tbl_admin_pusat');

if ($result) {
    $count = mysqli_num_rows($result);
    echo "=== Admin Pusat Data ===" . PHP_EOL . PHP_EOL;

    if ($count === 0) {
        echo "BELUM ADA ADMIN PUSAT" . PHP_EOL;
        echo "Anda perlu membuat admin pusat baru." . PHP_EOL;
    } else {
        echo "Total admin pusat: $count" . PHP_EOL . PHP_EOL;

        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: " . $row['id_admin_pusat'] . PHP_EOL;
            echo "Username: " . $row['username'] . PHP_EOL;
            echo "Nama: " . $row['nama'] . PHP_EOL;
            echo "Email: " . ($row['email'] ?: 'N/A') . PHP_EOL;
            echo "Status: " . $row['status'] . PHP_EOL;
            echo "---" . PHP_EOL;
        }
    }
} else {
    echo "Error: " . mysqli_error($conn) . PHP_EOL;
}

mysqli_close($conn);
