<?php

/**
 * Password Hash Test
 * Test untuk find password yang cocok dengan hash
 */

require_once __DIR__ . '/bootstrap.php';

function verify_password(string $rawPassword, string $storedHash): bool
{
    // Check if hash is bcrypt format (starts with $2a$, $2b$, or $2y$)
    if (preg_match('/^\$2[aby]\$/', $storedHash)) {
        return password_verify($rawPassword, $storedHash);
    }

    // Otherwise treat as MD5 hash
    return hash_equals(md5($rawPassword), $storedHash);
}

echo "<h2>🔐 Password Hash Test</h2>";
echo "<hr>";

// Get admin user
$username = 'admin';
$sql = 'SELECT password FROM tbl_user WHERE username = ? LIMIT 1';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("<p style='color: red;'>Admin user tidak ditemukan!</p>");
}

$storedHash = $user['password'];

echo "<h3>Admin Password Hash Analysis</h3>";
echo "<p><strong>Stored Hash:</strong> <code style='word-break: break-all;'>" . htmlspecialchars($storedHash) . "</code></p>";
echo "<p><strong>Hash Length:</strong> " . strlen($storedHash) . " characters</p>";

$isBcrypt = preg_match('/^\$2[aby]\$/', $storedHash);
echo "<p><strong>Hash Format:</strong> " . ($isBcrypt ? "bcrypt" : "MD5 (32 hex chars)") . "</p>";

echo "<hr>";
echo "<h3>Testing Passwords</h3>";

$testPasswords = [
    'admin' => 'default password',
    '123456' => 'common password',
    'password' => 'common password',
    '' => 'empty password',
    '12345678' => 'common password',
    'admin123' => 'common password',
    'passwd' => 'common password',
    'Admin@123' => 'complex password',
    'simanis' => 'app name',
];

echo "<table border='1' cellpadding='10' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Password</th>";
echo "<th>MD5 Hash</th>";
echo "<th>Match?</th>";
echo "</tr>";

$foundMatch = false;
foreach ($testPasswords as $pwd => $description) {
    $md5Hash = md5($pwd);
    $isMatch = verify_password($pwd, $storedHash);

    $matchClass = $isMatch ? 'style="background-color: #e8f5e9; color: green; font-weight: bold;"' : '';

    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($pwd ?: '(empty)') . "</code></td>";
    echo "<td><code style='font-size: 0.85em;'>" . $md5Hash . "</code></td>";
    echo "<td $matchClass>" . ($isMatch ? '✓ MATCH!' : '✗ no') . "</td>";
    echo "</tr>";

    if ($isMatch) {
        $foundMatch = true;
    }
}

echo "</table>";

echo "<hr>";

if (!$foundMatch) {
    echo "<div style='background-color: #ffebee; padding: 15px; border-radius: 4px; border-left: 4px solid #f44336;'>";
    echo "<p><strong style='color: #f44336;'>⚠️ Tidak ada password yang cocok!</strong></p>";
    echo "<p>Hash yang tersimpan tidak sesuai dengan password apapun dari daftar yang di-test.</p>";
    echo "<p>Kemungkinan:</p>";
    echo "<ul>";
    echo "<li>Password di database sudah di-reset atau berubah</li>";
    echo "<li>Password di database corrupt atau tidak valid</li>";
    echo "<li>Password sudah di-enkripsi dengan cara lain</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background-color: #e8f5e9; padding: 15px; border-radius: 4px; border-left: 4px solid #4caf50;'>";
    echo "<p><strong style='color: #4caf50;'>✓ Password ditemukan!</strong></p>";
    echo "</div>";
}

echo "<hr>";

// Reset password
echo "<h3>Reset Password ke 'admin'</h3>";
$newPasswordHash = md5('admin');
echo "<p>New password: <code>admin</code></p>";
echo "<p>MD5 hash: <code>" . $newPasswordHash . "</code></p>";

echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='reset_admin_password'>";
echo "<button type='submit' style='padding: 10px 20px; background-color: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer;'>Reset Admin Password to 'admin'</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_admin_password') {
    $newHash = md5('admin');
    $updateSql = 'UPDATE tbl_user SET password = ? WHERE username = ? LIMIT 1';
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('ss', $newHash, $username);

    if ($updateStmt->execute()) {
        echo "<div style='background-color: #e8f5e9; padding: 15px; border-radius: 4px; margin-top: 10px;'>";
        echo "<p style='color: green; font-weight: bold;'>✓ Password berhasil di-reset ke 'admin'</p>";
        echo "<p>Silakan <a href='login.php'>login</a> dengan username: <code>admin</code> dan password: <code>admin</code></p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #ffebee; padding: 15px; border-radius: 4px; margin-top: 10px;'>";
        echo "<p style='color: red;'>Error: " . htmlspecialchars($updateStmt->error) . "</p>";
        echo "</div>";
    }
    $updateStmt->close();
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
