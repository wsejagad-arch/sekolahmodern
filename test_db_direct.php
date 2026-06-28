<?php

function verify_password(string $rawPassword, string $storedHash): bool
{
    // Check if hash is bcrypt format (starts with $2a$, $2b$, or $2y$)
    if (preg_match('/^\$2[aby]\$/', $storedHash)) {
        return password_verify($rawPassword, $storedHash);
    }

    // Otherwise treat as MD5 hash
    return hash_equals(md5($rawPassword), $storedHash);
}

echo "<h2>Direct Database Test</h2>";
echo "<hr>";

if (!$conn) {
    die("<p style='color: red;'><strong>ERROR:</strong> Database connection failed!</p>");
}

echo "<h3>1. Test Admin Query</h3>";
$username = 'admin';
$sql = 'SELECT id_user, username, nama, hak_akses, password FROM tbl_user WHERE username = ? LIMIT 1';
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<p style='color: red;'><strong>Prepare Error:</strong> " . htmlspecialchars($conn->error) . "</p>";
} else {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        echo "<p style='color: green;'>✓ Admin user ditemukan!</p>";
        echo "<ul>";
        echo "<li>ID: " . htmlspecialchars($user['id_user']) . "</li>";
        echo "<li>Username: " . htmlspecialchars($user['username']) . "</li>";
        echo "<li>Nama: " . htmlspecialchars($user['nama']) . "</li>";
        echo "<li>Password Hash: " . htmlspecialchars(substr($user['password'], 0, 40)) . "...</li>";
        echo "<li>Hak Akses: " . htmlspecialchars($user['hak_akses']) . "</li>";
        echo "</ul>";

        // Test password verification
        $testPassword = 'admin'; // Ubah sesuai password sebenarnya
        echo "<p><strong>Test Password: </strong><code>$testPassword</code></p>";

        $passwordInfo = password_get_info($user['password']);
        echo "<p><strong>Password Type:</strong> " . ($passwordInfo['algo'] === 0 ? "MD5" : "bcrypt") . "</p>";

        $isValid = false;
        if ($passwordInfo['algo'] !== 0) {
            $isValid = password_verify($testPassword, $user['password']);
        } else {
            $isValid = hash_equals(md5($testPassword), $user['password']);
        }

        if ($isValid) {
            echo "<p style='color: green;'><strong>✓ Password VALID!</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>✗ Password INVALID!</strong></p>";
            echo "<p>MD5 of '" . $testPassword . "': " . md5($testPassword) . "</p>";
            echo "<p>Stored hash: " . htmlspecialchars($user['password']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin user TIDAK ditemukan!</p>";
    }
}

echo "<hr>";

echo "<h3>2. Test Guru Query (No Induk: 199303012022211013)</h3>";
$noInduk = '199303012022211013';
$status = 'Aktif';
$sql = 'SELECT g.no_induk, g.nama_guru, g.status_kepegawaian, g.status, p.password FROM tbl_guru g JOIN tbl_pengguna p ON g.no_induk = p.no_induk WHERE g.no_induk = ? AND g.status = ? LIMIT 1';
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<p style='color: red;'><strong>Prepare Error:</strong> " . htmlspecialchars($conn->error) . "</p>";
} else {
    $stmt->bind_param('ss', $noInduk, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $guru = $result->fetch_assoc();
    $stmt->close();

    if ($guru) {
        echo "<p style='color: green;'>✓ Guru user ditemukan!</p>";
        echo "<ul>";
        echo "<li>No Induk: " . htmlspecialchars($guru['no_induk']) . "</li>";
        echo "<li>Nama Guru: " . htmlspecialchars($guru['nama_guru']) . "</li>";
        echo "<li>Status Kepegawaian: " . htmlspecialchars($guru['status_kepegawaian']) . "</li>";
        echo "<li>Status Aktif: " . htmlspecialchars($guru['status']) . "</li>";
        echo "<li>Password Hash: " . htmlspecialchars(substr($guru['password'], 0, 40)) . "...</li>";
        echo "</ul>";

        // Test password verification
        $testPassword = ''; // Guru bisa login tanpa password
        echo "<p><strong>Test Password (empty):</strong> " . ($testPassword === '' ? 'YES (empty)' : 'NO') . "</p>";

        if ($testPassword === '') {
            echo "<p style='color: green;'><strong>✓ Empty password allowed for guru!</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Guru user TIDAK ditemukan!</p>";

        // Debug: Check if guru exists di tbl_guru
        echo "<p><strong>Debug:</strong> Checking if guru exists in tbl_guru...</p>";
        $debugSql = "SELECT no_induk, nama_guru, status FROM tbl_guru WHERE no_induk = ? LIMIT 1";
        $debugStmt = $conn->prepare($debugSql);
        $debugStmt->bind_param('s', $noInduk);
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        $debugGuru = $debugResult->fetch_assoc();
        $debugStmt->close();

        if ($debugGuru) {
            echo "<p style='color: orange;'><strong>⚠ Guru ditemukan di tbl_guru tapi tidak di JOIN dengan tbl_pengguna!</strong></p>";
            echo "<ul>";
            echo "<li>No Induk: " . htmlspecialchars($debugGuru['no_induk']) . "</li>";
            echo "<li>Nama: " . htmlspecialchars($debugGuru['nama_guru']) . "</li>";
            echo "<li>Status: " . htmlspecialchars($debugGuru['status']) . "</li>";
            echo "</ul>";

            // Check tbl_pengguna
            echo "<p><strong>Debug:</strong> Checking tbl_pengguna...</p>";
            $checkSql = "SELECT no_induk, password FROM tbl_pengguna WHERE no_induk = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('s', $noInduk);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $penggunaData = $checkResult->fetch_assoc();
            $checkStmt->close();

            if ($penggunaData) {
                echo "<p style='color: green;'>✓ Data ditemukan di tbl_pengguna</p>";
                echo "<li>Password Hash: " . htmlspecialchars(substr($penggunaData['password'], 0, 40)) . "...</li>";
            } else {
                echo "<p style='color: red;'>✗ Data TIDAK ditemukan di tbl_pengguna!</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>✗ Guru TIDAK ditemukan di tbl_guru sama sekali!</strong></p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
