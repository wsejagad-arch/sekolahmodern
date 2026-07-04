# Script PowerShell untuk setup hosting - jalankan setelah extract ZIP

Write-Host "Setting up database connection for hosting..." -ForegroundColor Green

# Backup koneksi.php original
if (Test-Path "koneksi.php") {
    Copy-Item "koneksi.php" "koneksi_local_backup.php"
    Write-Host "✅ Local koneksi.php backed up" -ForegroundColor Green
}

# Ganti dengan koneksi hosting
$hostingConfig = @'
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
'@

$hostingConfig | Out-File -FilePath "koneksi.php" -Encoding UTF8
Write-Host "✅ Database connection updated for hosting" -ForegroundColor Green

# Remove test files (optional)
$testFiles = @("test_hosting.php", "pages\guru\test_ajax.php", "debug_jurnal.php")
foreach ($file in $testFiles) {
    if (Test-Path $file) {
        Remove-Item $file
        Write-Host "✅ Removed $file" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "🎉 Setup hosting completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Database Credentials:" -ForegroundColor Cyan
Write-Host "Host: localhost:3306"
Write-Host "User: smasumb1_sijurnal1"
Write-Host "Password: JU-gxs^([=UN"
Write-Host "Database: smasumb1_sijurnal"
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Test database connection: /test_hosting.php"
Write-Host "2. Test journal form: /pages/guru/test_ajax.php"
Write-Host "3. Login and test journal entry"