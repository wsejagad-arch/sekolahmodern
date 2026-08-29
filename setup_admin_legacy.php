<?php
require_once __DIR__ . '/config/database.php';

try {
    $username = 'admin';
    // Gunakan password default legacy yang diizinkan di login.php
    $password = 'W@hyu123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Pastikan kolom role ada
    try {
        $conn->query("ALTER TABLE admin ADD COLUMN role ENUM('superadmin', 'author') NOT NULL DEFAULT 'superadmin' AFTER password");
    } catch (Throwable $e) {}

    // Cek apakah user sudah ada
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update password
        $stmt = $conn->prepare("UPDATE admin SET password = ?, role = 'superadmin' WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $username);
        $stmt->execute();
        echo "<h1>BERHASIL!</h1><p>Password admin telah direset.</p>";
    } else {
        // Insert user baru
        $stmt = $conn->prepare("INSERT INTO admin (username, password, role) VALUES (?, ?, 'superadmin')");
        $stmt->bind_param("ss", $username, $hashed_password);
        $stmt->execute();
        echo "<h1>BERHASIL!</h1><p>User admin baru telah dibuat.</p>";
    }
    
    echo "<p>Silakan login di: <a href='/logsman1s'>/logsman1s</a><br>";
    echo "Username: <b>admin</b><br>Password: <b>W@hyu123</b></p>";
    echo "<p style='color:red;'><b>PENTING:</b> Hapus file setup_admin_legacy.php ini dari hosting setelah Anda berhasil login!</p>";
} catch (Exception $e) {
    echo "Terjadi kesalahan: " . $e->getMessage();
}
?>
