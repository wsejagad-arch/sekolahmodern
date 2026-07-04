# Script untuk create deployment package untuk www.simanis.sman1sumber.sch.id
# Database: smasumb1_simanis1 / W@hyu123! / smasumb1_simanis

Write-Host "Creating SIMANIS deployment package for www.simanis.sman1sumber.sch.id..." -ForegroundColor Green

# Set variables
$root = $PSScriptRoot
$stageDir = Join-Path $root "release\_stage\simanis-www-deploy"
$zipFile = Join-Path $root "release\simanis-www-deploy.zip"

# Remove existing staging directory
if (Test-Path $stageDir) {
    Remove-Item $stageDir -Recurse -Force
    Write-Host "Removed existing staging directory"
}

# Create staging directory
New-Item -ItemType Directory -Path $stageDir -Force | Out-Null
Write-Host "Created staging directory"

# Copy application files (excluding unnecessary ones)
$excludeDirs = @('node_modules', 'deploy_prod', 'scratch', '.git', 'release', 'patch_date-to-tanggal', 'home5', 'backup', 'htaccess', 'deploy')
$excludeFiles = @('test_*.php', 'debug_*.php', 'check_*.php', 'config.local.php', 'koneksi_local.php', 'config.hosting.php', 'package.json', 'package-lock.json', 'composer.lock')

Copy-Item -Path $root -Destination $stageDir -Recurse -Force
Write-Host "Copied application files"

# Remove excluded directories
foreach ($dir in $excludeDirs) {
    $excludePath = Join-Path $stageDir $dir
    if (Test-Path $excludePath) {
        Remove-Item $excludePath -Recurse -Force
        Write-Host "Excluded directory: $dir"
    }
}

# Remove excluded files
Get-ChildItem -Path $stageDir -Recurse -Include $excludeFiles | Remove-Item -Force
Write-Host "Removed excluded files"

# Create upload directories
$uploadDirs = @('uploads', 'uploads\izin', 'uploads\tugas', 'uploads\twibbon', 'uploads\7kih', 'uploads\kurikulum-menu-icons')
foreach ($dir in $uploadDirs) {
    $uploadPath = Join-Path $stageDir $dir
    New-Item -ItemType Directory -Path $uploadPath -Force | Out-Null
    # Create .gitkeep files
    $gitkeep = Join-Path $uploadPath '.gitkeep'
    if (-not (Test-Path $gitkeep)) {
        New-Item -ItemType File -Path $gitkeep -Force | Out-Null
    }
}

# Copy deployment configuration
Copy-Item -Path "$root\deploy\config.hosting.simanis.php" -Destination "$stageDir\config.hosting.php" -Force
Write-Host "Copied database configuration"

Copy-Item -Path "$root\deploy\BACA-INI-DEPLOY.txt" -Destination "$stageDir\BACA-INI-DEPLOY.txt" -Force
Copy-Item -Path "$root\config.hosting.example.php" -Destination "$stageDir\config.hosting.example.php" -Force

# Copy htaccess files
Copy-Item -Path "$root\htaccess\*" -Destination "$stageDir\htaccess\" -Recurse -Force
Copy-Item -Path "$root\htaccess\.htaccess-litespeed" -Destination "$stageDir\.htaccess" -Force

# Copy installation files
$sqlDir = Join-Path $stageDir '_instalasi\sql'
New-Item -ItemType Directory -Path $sqlDir -Force | Out-Null
if (Test-Path "$root\sql") {
    Copy-Item -Path "$root\sql\*" -Destination $sqlDir -Recurse -Force
}

# Copy documentation
Copy-Item -Path "$root\PANDUAN_UPLOAD_HOSTING.md" -Destination "$stageDir\PANDUAN_UPLOAD_HOSTING.md" -Force

# Update URL in cek-hosting.php
$cekHostPath = Join-Path $stageDir 'cek-hosting.php'
if (Test-Path $cekHostPath) {
    $cekContent = Get-Content $cekHostPath -Raw
    $cekContent = $cekContent -replace 'https://simanis\.sman1sumber\.sch\.id/cek-hosting\.php', 'https://www.simanis.sman1sumber.sch.id/cek-hosting.php'
    Set-Content -Path $cekHostPath -Value $cekContent -Encoding UTF8
    Write-Host "Updated URL in cek-hosting.php"
}

# Create ZIP file
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stageDir, $zipFile)

# Calculate size
$sizeMb = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT PACKAGE READY!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "File: $zipFile" -ForegroundColor White
Write-Host "Size: $sizeMb MB" -ForegroundColor White
Write-Host ""
Write-Host "Database Configuration:" -ForegroundColor Yellow
Write-Host "  User: smasumb1_simanis1" -ForegroundColor White
Write-Host "  Password: W@hyu123!" -ForegroundColor White
Write-Host "  Database: smasumb1_simanis" -ForegroundColor White
Write-Host "  Site URL: https://www.simanis.sman1sumber.sch.id" -ForegroundColor White
Write-Host ""
Write-Host "Upload Instructions:" -ForegroundColor Yellow
Write-Host "1. Extract simanis-www-deploy.zip to www.simanis.sman1sumber.sch.id root" -ForegroundColor White
Write-Host "2. Set PHP version in cPanel to 8.1 or 8.0" -ForegroundColor White
Write-Host "3. Check permissions: folder 755, file 644" -ForegroundColor White
Write-Host "4. Test: https://www.simanis.sman1sumber.sch.id/ping.php" -ForegroundColor White
Write-Host "5. Access: https://www.simanis.sman1sumber.sch.id/splash.php" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Cyan