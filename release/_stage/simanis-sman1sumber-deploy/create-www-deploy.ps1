# Script untuk create deployment package untuk www.simanis.sman1sumber.sch.id
# Menggunakan build-hosting.ps1 yang aman dari loop rekursif
Write-Host "Creating SIMANIS deployment package for www.simanis.sman1sumber.sch.id..." -ForegroundColor Green

$root = $PSScriptRoot
$stageDir = Join-Path $root "release\_stage\simanis-www-deploy"
$zipFile = Join-Path $root "release\simanis-www-deploy.zip"

& (Join-Path $root 'build-hosting.ps1') `
    -HtaccessVariant 'litespeed' `
    -ZipName 'simanis-www-deploy' `
    -ConfigHostingFile (Join-Path $root 'deploy\config.hosting.simanis.php')

# Copy documentation khusus
$stage = Join-Path $root 'release\_stage\simanis-www-deploy'
Copy-Item (Join-Path $root 'deploy\BACA-INI-DEPLOY.txt') (Join-Path $stage 'BACA-INI-DEPLOY.txt') -Force

# Update URL in cek-hosting.php
$cekHostPath = Join-Path $stage 'cek-hosting.php'
if (Test-Path $cekHostPath) {
    $cekContent = Get-Content $cekHostPath -Raw
    $cekContent = $cekContent -replace 'https://simanis\.sman1sumber\.sch\.id/cek-hosting\.php', 'https://www.simanis.sman1sumber.sch.id/cek-hosting.php'
    Set-Content -Path $cekHostPath -Value $cekContent -Encoding UTF8
    Write-Host "Updated URL in cek-hosting.php"
}

# Re-zip setelah tambahan file
if (Test-Path $zipFile) { Remove-Item $zipFile -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stage, $zipFile)

$sizeMb = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT PACKAGE READY!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "File: $zipFile" -ForegroundColor White
Write-Host "Size: $sizeMb MB" -ForegroundColor White
Write-Host ""
Write-Host "Database Configuration (from config):" -ForegroundColor Yellow
Write-Host "  User: smasumb1_sijurnal1" -ForegroundColor White
Write-Host "  Password: JU-gxs^([=UN" -ForegroundColor White
Write-Host "  Database: smasumb1_sijurnal" -ForegroundColor White
Write-Host "  Site URL: https://www.simanis.sman1sumber.sch.id" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Cyan