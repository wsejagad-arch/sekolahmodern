<?php
// Test file untuk hosting - simpan di root folder
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Test Koneksi Database Hosting</h3>";

// Test direct connection
$host = "localhost";
$port = "3306";
$user = "smasumb1_simanis1";
$password = "W@hyu123!";
$database = "smasumb1_simanis";

echo "<h4>1. Test Direct Connection</h4>";
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    echo "<div style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</div>";
} else {
    echo "<div style='color: green;'>✅ Database connection successful!</div>";
    echo "<p>Host: " . $host . ":" . $port . "</p>";
    echo "<p>User: " . $user . "</p>";
    echo "<p>Database: " . $database . "</p>";

    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM tbl_guru");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Query test: Found " . $row['count'] . " records in tbl_guru</p>";
    } else {
        echo "<p style='color: red;'>❌ Query test failed: " . $conn->error . "</p>";
    }

    $conn->close();
}

echo "<h4>2. Test Include koneksi.php</h4>";
try {
    include "koneksi.php";
    if (isset($conn)) {
        echo "<div style='color: green;'>✅ koneksi.php loaded successfully!</div>";

        // Test query through koneksi.php
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_guru");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<p style='color: green;'>✅ Query through koneksi.php: Found " . $row['count'] . " records in tbl_guru</p>";
        } else {
            echo "<p style='color: red;'>❌ Query through koneksi.php failed: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<div style='color: red;'>❌ koneksi.php did not create \$conn variable</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error including koneksi.php: " . $e->getMessage() . "</div>";
}

echo "<h4>3. Test Path Resolution</h4>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>__DIR__: " . __DIR__ . "</p>";
echo "<p>Koneksi.php exists: " . (file_exists("koneksi.php") ? "✅ YES" : "❌ NO") . "</p>";
echo "<p>functions.php exists: " . (file_exists("functions.php") ? "✅ YES" : "❌ NO") . "</p>";

echo "<h4>4. Test Journal Form Path</h4>";
$jurnal_path = __DIR__ . '/pages/guru/detailmateri.php';
echo "<p>Journal form path exists: " . (file_exists($jurnal_path) ? "✅ YES" : "❌ NO") . "</p>";

if (file_exists($jurnal_path)) {
    echo "<p>Checking koneksi.php path from journal form...</p>";
    $from_journal = "pages/guru/../../koneksi.php";
    $resolved_path = realpath($from_journal);
    echo "<p>Path from journal: $from_journal</p>";
    echo "<p>Resolved path: " . ($resolved_path ? $resolved_path : "FAILED") . "</p>";
    echo "<p>Resolved path exists: " . ($resolved_path && file_exists($resolved_path) ? "✅ YES" : "❌ NO") . "</p>";
}
