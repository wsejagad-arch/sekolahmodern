<?php
// Test koneksi database dengan debug detail
echo "=== Database Connection Test ===\n\n";

// Load config lokal
echo "1. Loading config.local.php...\n";
if (file_exists('config.local.php')) {
    include 'config.local.php';
    echo "   ✓ Config loaded. DB: " . ($db ?? 'not set') . "\n";
} else {
    echo "   ✗ config.local.php not found\n";
}

// Cek variable
echo "\n2. Configuration values:\n";
echo "   Host: " . ($host ?? 'NOT SET') . "\n";
echo "   User: " . ($user ?? 'NOT SET') . "\n";
echo "   Password: " . (isset($password) ? '***' : 'NOT SET') . "\n";
echo "   Database: " . ($db ?? 'NOT SET') . "\n";
echo "   Port: " . ($port ?? 'NOT SET') . "\n";

// Test koneksi
echo "\n3. Testing connection...\n";
$test_host = $host ?? 'localhost';
$test_user = $user ?? 'root';
$test_pass = $password ?? '';
$test_db = $db ?? 'jurnal';
$test_port = $port ?? 3306;

$conn = @mysqli_connect($test_host, $test_user, $test_pass, $test_db, $test_port);

if ($conn) {
    echo "   ✓ Connection successful!\n";

    // Test query
    echo "\n4. Testing query...\n";
    $result = mysqli_query($conn, "SELECT 1 AS test");
    if ($result) {
        echo "   ✓ Query successful\n";
    } else {
        echo "   ✗ Query failed: " . mysqli_error($conn) . "\n";
    }

    // List tables
    echo "\n5. Available tables:\n";
    $tables = mysqli_query($conn, "SHOW TABLES");
    $count = 0;
    while ($row = mysqli_fetch_row($tables)) {
        echo "   - " . $row[0] . "\n";
        $count++;
    }
    echo "   Total: $count tables\n";

    mysqli_close($conn);
} else {
    echo "   ✗ Connection failed!\n";
    echo "   Error: " . mysqli_connect_error() . "\n";
}
