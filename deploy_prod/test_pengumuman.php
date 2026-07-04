<?php
// Test file untuk debugging pengumuman
session_start();

echo "<h3>Debug Information</h3>";
echo "<pre>";

echo "1. Session Info:\n";
echo "   - Session Started: " . (session_status() == PHP_SESSION_ACTIVE ? "YES" : "NO") . "\n";
echo "   - Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "   - Hak Akses: " . ($_SESSION['hak_akses'] ?? 'NOT SET') . "\n";
echo "   - Nama: " . ($_SESSION['nama'] ?? 'NOT SET') . "\n\n";

echo "2. File Paths:\n";
echo "   - Current Dir: " . __DIR__ . "\n";
echo "   - koneksi.php exists: " . (file_exists(__DIR__ . "/koneksi.php") ? "YES" : "NO") . "\n";
echo "   - pages/admin/pengumuman.php exists: " . (file_exists(__DIR__ . "/pages/admin/pengumuman.php") ? "YES" : "NO") . "\n\n";

echo "3. Database Connection:\n";
if (file_exists(__DIR__ . "/koneksi.php")) {
    include __DIR__ . "/koneksi.php";
    if (isset($conn)) {
        if (mysqli_connect_errno()) {
            echo "   - Connection: FAILED\n";
            echo "   - Error: " . mysqli_connect_error() . "\n";
        } else {
            echo "   - Connection: SUCCESS\n";
            echo "   - Database: " . mysqli_get_host_info($conn) . "\n";
            
            // Check table
            $result = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pengumuman'");
            if ($result && mysqli_num_rows($result) > 0) {
                echo "   - Table tbl_pengumuman: EXISTS\n";
                
                // Count records
                $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_pengumuman");
                $row = mysqli_fetch_assoc($count);
                echo "   - Total records: " . $row['total'] . "\n";
            } else {
                echo "   - Table tbl_pengumuman: NOT EXISTS\n";
            }
        }
    } else {
        echo "   - Connection variable \$conn: NOT SET\n";
    }
} else {
    echo "   - koneksi.php: FILE NOT FOUND\n";
}

echo "\n4. Include Test:\n";
if (file_exists(__DIR__ . "/pages/admin/pengumuman.php")) {
    echo "   - Including pengumuman.php...\n";
    ob_start();
    include __DIR__ . "/pages/admin/pengumuman.php";
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "   - Output: EMPTY (no content generated)\n";
    } else {
        echo "   - Output Length: " . strlen($output) . " bytes\n";
        echo "   - First 200 chars: " . substr($output, 0, 200) . "...\n";
    }
} else {
    echo "   - File not found\n";
}

echo "</pre>";

echo "<hr>";
echo "<h3>Actual Page Content:</h3>";
echo "<div style='border: 2px solid blue; padding: 10px;'>";

// Set session for testing
if (!isset($_SESSION['hak_akses'])) {
    $_SESSION['hak_akses'] = 1; // Set as admin for testing
    $_SESSION['username'] = 'test_admin';
    $_SESSION['nama'] = 'Test Administrator';
    echo "<div class='alert alert-info'>Session set for testing (hak_akses=1)</div>";
}

// Include the actual file
if (file_exists(__DIR__ . "/pages/admin/pengumuman.php")) {
    include __DIR__ . "/pages/admin/pengumuman.php";
} else {
    echo "<div class='alert alert-danger'>File not found: pages/admin/pengumuman.php</div>";
}

echo "</div>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Pengumuman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        pre { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; }
        .alert { margin: 10px 0; }
    </style>
</head>
<body class="p-3">
</body>
</html>
