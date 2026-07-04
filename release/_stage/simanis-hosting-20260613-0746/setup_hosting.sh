#!/bin/bash
# Script untuk setup hosting - jalankan setelah extract ZIP

echo "Setting up database connection for hosting..."

# Backup koneksi.php original
if [ -f "koneksi.php" ]; then
    cp koneksi.php koneksi_local_backup.php
    echo "✅ Local koneksi.php backed up"
fi

# Ganti dengan koneksi hosting
cat > koneksi.php << 'EOF'
<?php
// Database configuration untuk hosting
$host = "localhost";
$port = "3306";
$user = "smasumb1_sijurnal1";
$password = "JU-gxs^([=UN";
$database = "smasumb1_sijurnal";

// Create connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Set charset
mysqli_set_charset($conn, "utf8");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
EOF

echo "✅ Database connection updated for hosting"

# Set file permissions
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

echo "✅ File permissions set correctly"

# Remove test files (optional)
rm -f test_hosting.php
rm -f pages/guru/test_ajax.php
rm -f debug_jurnal.php

echo "✅ Test files removed"

echo ""
echo "🎉 Setup hosting completed!"
echo ""
echo "Database Credentials:"
echo "Host: localhost:3306"
echo "User: smasumb1_sijurnal1"
echo "Password: JU-gxs^([=UN"
echo "Database: smasumb1_sijurnal"
echo ""
echo "Next steps:"
echo "1. Test database connection: /test_hosting.php"
echo "2. Test journal form: /pages/guru/test_ajax.php"
echo "3. Login and test journal entry"