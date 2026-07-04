<?php

/**
 * Diagnostic Sidebar Menu
 * File: diagnostic_sidebar.php
 * Purpose: Check database connections and table status
 */

echo "<h2>🔧 Diagnostic - Sidebar Menu System</h2>";
echo "<hr>";

// Connect to database
require_once 'config.php';

// 1. Check database connection
echo "<h3>1. Database Connection Status</h3>";
if (isset($conn)) {
    if ($conn instanceof mysqli) {
        echo "<span style='color: green;'>✓ MySQLi Connection Active</span><br>";
        echo "Host: " . $conn->host_info . "<br>";
        echo "Database: " . $conn->stat() . "<br>";
    } else {
        echo "<span style='color: orange;'>⚠ Connection type: PDO (not MySQLi)</span><br>";
    }
} else {
    echo "<span style='color: red;'>✗ No database connection found</span><br>";
}
echo "<hr>";

// 2. Check if tables exist
echo "<h3>2. Table Status</h3>";
$tables_to_check = ['pengumuman', 'quotes', 'user'];

foreach ($tables_to_check as $table) {
    if ($conn instanceof mysqli) {
        $result = @mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<span style='color: green;'>✓</span> Table '<strong>$table</strong>' exists<br>";

            // Check row count
            $count_result = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $table");
            if ($count_result) {
                $count_row = mysqli_fetch_assoc($count_result);
                echo "  └─ Records: " . $count_row['cnt'] . "<br>";
            }
        } else {
            echo "<span style='color: red;'>✗</span> Table '<strong>$table</strong>' NOT found<br>";
        }
    }
}
echo "<hr>";

// 3. Check session variables
echo "<h3>3. Session & User Info</h3>";
if (isset($_SESSION['hak_akses'])) {
    echo "Role (hak_akses): " . $_SESSION['hak_akses'];
    if ($_SESSION['hak_akses'] == 1) {
        echo " <span style='color: green;'>(✓ Admin - Should see Pengumuman & Quotes menus)</span><br>";
    } else {
        echo " <span style='color: orange;'>(Not Admin - Will NOT see Pengumuman & Quotes menus)</span><br>";
    }
} else {
    echo "<span style='color: red;'>✗ No session user role found</span><br>";
}

if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "<span style='color: red;'>✗ No user_id in session</span><br>";
}
echo "<hr>";

// 4. Sample queries
echo "<h3>4. Sample Data Queries</h3>";

if ($conn instanceof mysqli) {
    // Pengumuman
    echo "<strong>Pengumuman Aktif:</strong><br>";
    $result = @mysqli_query($conn, "SELECT COUNT(*) as jml FROM pengumuman WHERE status = 'aktif'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Count: " . $row['jml'] . "<br>";
    } else {
        echo "<span style='color: red;'>Error querying pengumuman table</span><br>";
    }

    // Quotes
    echo "<strong>Quotes Total:</strong><br>";
    $result = @mysqli_query($conn, "SELECT COUNT(*) as jml FROM quotes");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Count: " . $row['jml'] . "<br>";
    } else {
        echo "<span style='color: red;'>Error querying quotes table</span><br>";
    }
}
echo "<hr>";

// 5. File Status
echo "<h3>5. File Status</h3>";
$files_to_check = [
    'sidebar.php' => 'Main sidebar menu',
    'header.php' => 'Header with polyfill',
    'home.php' => 'Router with page routing',
    'pages/admin/pengumuman.php' => 'Pengumuman management',
    'pages/admin/kelola-quotes.php' => 'Quotes management',
    'setup_pengumuman.php' => 'Pengumuman table setup',
    'setup_quotes.php' => 'Quotes table setup'
];

foreach ($files_to_check as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<span style='color: green;'>✓</span> <strong>$file</strong> ($size bytes) - $desc<br>";
    } else {
        echo "<span style='color: red;'>✗</span> <strong>$file</strong> - NOT found!<br>";
    }
}
echo "<hr>";

// 6. Instructions
echo "<h3>6. Setup Instructions</h3>";
echo "<p>If tables don't exist, run these setup scripts:</p>";
echo "<ul>";
echo "<li><a href='setup_pengumuman.php' target='_blank'>Setup Pengumuman Table</a></li>";
echo "<li><a href='setup_quotes.php' target='_blank'>Setup Quotes Table</a></li>";
echo "</ul>";
echo "<hr>";

// 7. Files to upload
echo "<h3>7. Files to Upload to Hosting</h3>";
echo "<p>Upload these files to fix the sidebar menu:</p>";
echo "<pre>";
echo "sidebar.php
header.php
home.php
pages/admin/pengumuman.php
pages/admin/kelola-quotes.php
setup_pengumuman.php
setup_quotes.php
";
echo "</pre>";
