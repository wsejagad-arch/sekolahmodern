<?php
require __DIR__ . '/config/database.php';
$username = 'superadmin';
$password = 'Super@2026';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $conn->query("ALTER TABLE admin ADD COLUMN role ENUM('superadmin', 'author') NOT NULL DEFAULT 'superadmin' AFTER password");
} catch (Throwable $e) {
    // Ignore duplicate column if already exists
}

$stmt = $conn->prepare('SELECT id FROM admin WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $ins = $conn->prepare('INSERT INTO admin (username, password, role) VALUES (?, ?, ?)');
    $role = 'superadmin';
    $ins->bind_param('sss', $username, $hash, $role);
    $ins->execute();
    echo "CREATED:{$username}:{$password}\n";
} else {
    $upd = $conn->prepare("UPDATE admin SET password = ?, role = 'superadmin' WHERE username = ?");
    $upd->bind_param('ss', $hash, $username);
    $upd->execute();
    echo "UPDATED:{$username}:{$password}\n";
}

$conn->close();
?>
