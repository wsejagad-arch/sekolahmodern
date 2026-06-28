# Paket deploy siap pakai: https://simanis.sman1sumber.sch.id
$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot

& (Join-Path $root 'build-hosting.ps1') `
    -HtaccessVariant 'litespeed' `
    -ZipName 'simanis-sman1sumber-deploy' `
    -ConfigHostingFile (Join-Path $root 'deploy\config.hosting.simanis.php')

$stage = Join-Path $root 'release\_stage\simanis-sman1sumber-deploy'
$zipPath = Join-Path $root 'release\simanis-sman1sumber-deploy.zip'

Copy-Item (Join-Path $root 'deploy\BACA-INI-DEPLOY.txt') (Join-Path $stage 'BACA-INI-DEPLOY.txt') -Force

$cekPath = Join-Path $stage 'cek-hosting.php'
if (Test-Path $cekPath) {
    $cek = Get-Content $cekPath -Raw
    $cek = $cek -replace 'https://domainanda\.com/jurnal/cek-hosting\.php', 'https://simanis.sman1sumber.sch.id/cek-hosting.php'
    Set-Content -Path $cekPath -Value $cek -Encoding UTF8
}

# Re-zip setelah tambahan file
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stage, $zipPath)

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Host ""
Write-Host '=== PAKET SIAP DEPLOY ===' -ForegroundColor Cyan
Write-Host "File : $zipPath"
Write-Host "Ukuran: $sizeMb MB"
Write-Host 'URL  : https://simanis.sman1sumber.sch.id'
Write-Host 'Baca : BACA-INI-DEPLOY.txt di dalam ZIP'
