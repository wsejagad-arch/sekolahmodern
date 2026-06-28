<?php

/**
 * Debug Login Script
 * Membantu diagnosa mengapa login gagal
 */

require_once __DIR__ . '/bootstrap.php';
require_admin();

// Pastikan koneksi database tersedia
if (!$conn) {
    die("ERROR: Koneksi database gagal!");
}

echo "<h2>Debug Login System</h2>";
echo "<hr>";

// Test 1: Cek data di database untuk Admin
echo "<h3>1. Cek User Admin</h3>";
$sqlAdmin = "SELECT id_user, username, nama, password FROM tbl_user LIMIT 5";
$resultAdmin = $conn->query($sqlAdmin);
if ($resultAdmin) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nama</th><th>Password Hash (first 30 char)</th></tr>";
    while ($row = $resultAdmin->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id_user']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['password'], 0, 30)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ERROR: " . $conn->error . "<br>";
}

echo "<hr>";

// Test 2: Cek data di database untuk Guru
echo "<h3>2. Cek User Guru</h3>";
$sqlGuru = "SELECT g.no_induk, g.nama_guru, g.status, p.no_induk as peng_no_induk, p.password 
            FROM tbl_guru g 
            LEFT JOIN tbl_pengguna p ON g.no_induk = p.no_induk 
            LIMIT 5";
$resultGuru = $conn->query($sqlGuru);
if ($resultGuru) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>No Induk</th><th>Nama Guru</th><th>Status</th><th>Ada di tbl_pengguna?</th><th>Password Hash (first 30)</th></tr>";
    while ($row = $resultGuru->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['no_induk']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_guru']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . ($row['peng_no_induk'] ? 'YA' : 'TIDAK') . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['password'] ?? '', 0, 30)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ERROR: " . $conn->error . "<br>";
}

echo "<hr>";

// Test 3: Cek data di database untuk Siswa
echo "<h3>3. Cek User Siswa</h3>";
$sqlSiswa = "SELECT s.no_induk, s.nama_siswa, s.kelas, s.status, p.no_induk as peng_no_induk, p.password 
             FROM tbl_siswa s 
             LEFT JOIN tbl_pengguna p ON s.no_induk = p.no_induk 
             LIMIT 5";
$resultSiswa = $conn->query($sqlSiswa);
if ($resultSiswa) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>No Induk</th><th>Nama Siswa</th><th>Kelas</th><th>Status</th><th>Ada di tbl_pengguna?</th><th>Password Hash (first 30)</th></tr>";
    while ($row = $resultSiswa->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['no_induk']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_siswa']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . ($row['peng_no_induk'] ? 'YA' : 'TIDAK') . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['password'] ?? '', 0, 30)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ERROR: " . $conn->error . "<br>";
}

echo "<hr>";

// Test 4: Test password verification
echo "<h3>4. Test Password Verification</h3>";
echo "<p>Form untuk test verifikasi password:</p>";
echo "<form method='POST'>";
echo "<div>";
echo "  <label>Username/No Induk:</label><br>";
echo "  <input type='text' name='test_username' required><br><br>";
echo "</div>";
echo "<div>";
echo "  <label>Password (plain text):</label><br>";
echo "  <input type='password' name='test_password' required><br><br>";
echo "</div>";
echo "<div>";
echo "  <label>User Type:</label><br>";
echo "  <select name='test_type' required>";
echo "    <option value='admin'>Admin</option>";
echo "    <option value='guru'>Guru</option>";
echo "    <option value='siswa'>Siswa</option>";
echo "  </select><br><br>";
echo "</div>";
echo "<button type='submit' name='test_login'>Test Login</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $test_username = $_POST['test_username'] ?? '';
    $test_password = $_POST['test_password'] ?? '';
    $test_type = $_POST['test_type'] ?? '';

    echo "<h4>Test Result:</h4>";
    echo "Username: " . htmlspecialchars($test_username) . "<br>";
    echo "User Type: " . htmlspecialchars($test_type) . "<br><br>";

    if ($test_type === 'admin') {
        $sql = "SELECT id_user, username, nama, password FROM tbl_user WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $test_username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            echo "<strong>User ditemukan!</strong><br>";
            echo "ID: " . htmlspecialchars($user['id_user']) . "<br>";
            echo "Username: " . htmlspecialchars($user['username']) . "<br>";
            echo "Nama: " . htmlspecialchars($user['nama']) . "<br>";
            echo "Password Hash: " . htmlspecialchars($user['password']) . "<br>";

            // Test password
            $isPasswordValid = false;
            if (strpos($user['password'], '$2') === 0) {
                // bcrypt
                $isPasswordValid = password_verify($test_password, $user['password']);
                echo "Password Type: bcrypt<br>";
            } else {
                // md5
                $isPasswordValid = hash_equals(md5($test_password), $user['password']);
                echo "Password Type: md5<br>";
            }

            if ($isPasswordValid) {
                echo "<span style='color: green; font-weight: bold;'>✓ Password BENAR</span><br>";
            } else {
                echo "<span style='color: red; font-weight: bold;'>✗ Password SALAH</span><br>";
                echo "MD5 test: " . md5($test_password) . "<br>";
            }
        } else {
            echo "<span style='color: red; font-weight: bold;'>✗ User TIDAK ditemukan di tbl_user</span><br>";
        }
    } elseif ($test_type === 'guru') {
        $sql = "SELECT g.no_induk, g.nama_guru, g.status, p.password 
                FROM tbl_guru g 
                JOIN tbl_pengguna p ON g.no_induk = p.no_induk 
                WHERE g.no_induk = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $test_username);
        $stmt->execute();
        $result = $stmt->get_result();
        $guru = $result->fetch_assoc();
        $stmt->close();

        if ($guru) {
            echo "<strong>Guru ditemukan!</strong><br>";
            echo "No Induk: " . htmlspecialchars($guru['no_induk']) . "<br>";
            echo "Nama: " . htmlspecialchars($guru['nama_guru']) . "<br>";
            echo "Status: " . htmlspecialchars($guru['status']) . "<br>";
            echo "Password Hash: " . htmlspecialchars($guru['password']) . "<br>";

            // Test password
            $isPasswordValid = false;
            if (strpos($guru['password'], '$2') === 0) {
                // bcrypt
                $isPasswordValid = password_verify($test_password, $guru['password']);
                echo "Password Type: bcrypt<br>";
            } else {
                // md5
                $isPasswordValid = hash_equals(md5($test_password), $guru['password']);
                echo "Password Type: md5<br>";
            }

            if ($isPasswordValid) {
                echo "<span style='color: green; font-weight: bold;'>✓ Password BENAR</span><br>";
            } else {
                echo "<span style='color: red; font-weight: bold;'>✗ Password SALAH</span><br>";
                echo "MD5 test: " . md5($test_password) . "<br>";
            }

            if ($guru['status'] !== 'Aktif') {
                echo "<span style='color: orange; font-weight: bold;'>⚠ Status BUKAN 'Aktif' (status saat ini: " . htmlspecialchars($guru['status']) . ")</span><br>";
            }
        } else {
            echo "<span style='color: red; font-weight: bold;'>✗ Guru TIDAK ditemukan</span><br>";
        }
    } elseif ($test_type === 'siswa') {
        $sql = "SELECT s.no_induk, s.nama_siswa, s.kelas, s.status, s.hak_akses, p.password 
                FROM tbl_siswa s 
                JOIN tbl_pengguna p ON s.no_induk = p.no_induk 
                WHERE s.no_induk = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $test_username);
        $stmt->execute();
        $result = $stmt->get_result();
        $siswa = $result->fetch_assoc();
        $stmt->close();

        if ($siswa) {
            echo "<strong>Siswa ditemukan!</strong><br>";
            echo "No Induk: " . htmlspecialchars($siswa['no_induk']) . "<br>";
            echo "Nama: " . htmlspecialchars($siswa['nama_siswa']) . "<br>";
            echo "Kelas: " . htmlspecialchars($siswa['kelas']) . "<br>";
            echo "Status: " . htmlspecialchars($siswa['status']) . "<br>";
            echo "Password Hash: " . htmlspecialchars($siswa['password']) . "<br>";

            // Test password
            $isPasswordValid = false;
            if (strpos($siswa['password'], '$2') === 0) {
                // bcrypt
                $isPasswordValid = password_verify($test_password, $siswa['password']);
                echo "Password Type: bcrypt<br>";
            } else {
                // md5
                $isPasswordValid = hash_equals(md5($test_password), $siswa['password']);
                echo "Password Type: md5<br>";
            }

            if ($isPasswordValid) {
                echo "<span style='color: green; font-weight: bold;'>✓ Password BENAR</span><br>";
            } else {
                echo "<span style='color: red; font-weight: bold;'>✗ Password SALAH</span><br>";
                echo "MD5 test: " . md5($test_password) . "<br>";
            }

            if ($siswa['status'] !== 'Aktif') {
                echo "<span style='color: orange; font-weight: bold;'>⚠ Status BUKAN 'Aktif' (status saat ini: " . htmlspecialchars($siswa['status']) . ")</span><br>";
            }
        } else {
            echo "<span style='color: red; font-weight: bold;'>✗ Siswa TIDAK ditemukan</span><br>";
        }
    }
}

echo "<hr>";
echo "<p><a href='debug_login.php'>← Refresh</a> | <a href='login.php'>Go to Login →</a></p>";
