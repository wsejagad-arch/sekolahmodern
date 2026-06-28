# Build paket ZIP SIMANIS untuk upload ke web hosting
param(
    [ValidateSet('subfolder', 'root', 'cpanel-minimal', 'litespeed')]
    [string]$HtaccessVariant = 'subfolder',
    [string]$ZipName = '',
    [string]$ConfigHostingFile = '',
    [switch]$NoHtaccess
)

$ErrorActionPreference = 'Stop'

$root = $PSScriptRoot
$dateStamp = Get-Date -Format 'yyyyMMdd-HHmm'
$outName = if ($ZipName) { $ZipName } else { "simanis-hosting-$dateStamp" }
$stage = Join-Path $root "release\_stage\$outName"
$zipPath = Join-Path $root "release\$outName.zip"

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue }
New-Item -ItemType Directory -Path $stage -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $root 'release') -Force | Out-Null

$excludeDirs = @(
    'node_modules', 'deploy_prod', 'scratch', '.git', 'release',
    'patch_date-to-tanggal', 'home5', 'backup', 'htaccess', 'deploy', 'cache'
)

$excludeFilePatterns = @(
    'test_*', 'debug_*', 'check_*', 'tmp_*',
    'config.local.php', 'koneksi_local.php', 'config.hosting.php',
    'package.json', 'package-lock.json', 'gulpfile.js', 'composer.lock',
    'build-hosting.ps1', 'build-hosting-simanis.ps1',
    'auto_update_links.php', 'auto_update_links_simple.php',
    'url_replacement_guide.php', 'HIDDEN_URLS_QUICK_REFERENCE.php',
    'reset_admin_password.php', 'cli_router.php', 'Untitled-1.sql',
    'deploy_prod.zip', '*.zip', '*.bak', '*.log'
)

function Should-SkipFile([string]$name) {
    foreach ($pat in $excludeFilePatterns) {
        if ($name -like $pat) { return $true }
    }
    return $false
}

function Copy-Tree([string]$src, [string]$dst, [string[]]$extraExcludeDirs) {
    $items = Get-ChildItem -LiteralPath $src -Force
    foreach ($item in $items) {
        $rel = $item.Name
        if ($item.PSIsContainer) {
            if ($excludeDirs -contains $rel) { continue }
            if ($extraExcludeDirs -contains $rel) { continue }
            if ($rel -eq 'vendor' -and $src -ne $root) { continue }
            $target = Join-Path $dst $rel
            New-Item -ItemType Directory -Path $target -Force | Out-Null
            if ($rel -eq 'fontawesome-free' -and $src -like '*\vendor') {
                Copy-FontAwesomeLite $item.FullName $target
                continue
            }
            if ($rel -eq 'logs') {
                New-Item -ItemType Directory -Path $target -Force | Out-Null
                Copy-Item (Join-Path $item.FullName '.htaccess') $target -ErrorAction SilentlyContinue
                continue
            }
            Copy-Tree $item.FullName $target $extraExcludeDirs
        } else {
            if (Should-SkipFile $rel) { continue }
            if ($rel -match '\.(md|bat|ps1|log|json)$' -and $rel -ne 'PANDUAN_UPLOAD_HOSTING.md') {
                if ($rel -match '\.(log|json)$') { continue }
                if ($rel -match '\.md$' -and $rel -ne 'PANDUAN_UPLOAD_HOSTING.md') { continue }
            }
            Copy-Item -LiteralPath $item.FullName -Destination (Join-Path $dst $rel) -Force
        }
    }
}

function Copy-FontAwesomeLite([string]$src, [string]$dst) {
    New-Item -ItemType Directory -Path $dst -Force | Out-Null
    $keep = @('css', 'webfonts', 'js', 'sprites', 'LICENSE.txt')
    foreach ($name in $keep) {
        $p = Join-Path $src $name
        if (Test-Path $p) {
            Copy-Item -LiteralPath $p -Destination (Join-Path $dst $name) -Recurse -Force
        }
    }
}

Write-Host "Menyalin file aplikasi..."
Copy-Tree $root $stage @()
if ($NoHtaccess) {
    Remove-Item (Join-Path $stage '.htaccess') -Force -ErrorAction SilentlyContinue
}

Write-Host "Menyalin library Composer (PDF)..."
$composerVendor = Join-Path $root 'deploy_prod\vendor'
if (Test-Path $composerVendor) {
    $destVendor = Join-Path $stage 'vendor'
    New-Item -ItemType Directory -Path $destVendor -Force | Out-Null
    foreach ($name in @('autoload.php', 'autoload_real.php', 'autoload_static.php', 'autoload_classmap.php', 'autoload_psr4.php', 'autoload_namespaces.php', 'autoload_files.php', 'ClassLoader.php', 'platform_check.php', 'installed.json', 'installed.php', 'composer')) {
        $p = Join-Path $composerVendor $name
        if (Test-Path $p) {
            Copy-Item -LiteralPath $p -Destination (Join-Path $destVendor $name) -Recurse -Force
        }
    }
    foreach ($pkg in @('tecnickcom', 'spipu')) {
        $p = Join-Path $composerVendor $pkg
        if (Test-Path $p) {
            Copy-Item -LiteralPath $p -Destination (Join-Path $destVendor $pkg) -Recurse -Force
        }
    }
}

Get-ChildItem -Path $stage -Recurse -File -Include 'test_*.php','debug_*.php','check_*.php' -ErrorAction SilentlyContinue |
    Remove-Item -Force

$uploadDirs = @('uploads', 'uploads\izin', 'uploads\tugas', 'uploads\twibbon', 'uploads\7kih', 'uploads\kurikulum-menu-icons')
foreach ($d in $uploadDirs) {
    $p = Join-Path $stage $d
    New-Item -ItemType Directory -Path $p -Force | Out-Null
    if (-not (Test-Path (Join-Path $p '.gitkeep'))) {
        New-Item -ItemType File -Path (Join-Path $p '.gitkeep') -Force | Out-Null
    }
}

$sqlStage = Join-Path $stage '_instalasi\sql'
New-Item -ItemType Directory -Path $sqlStage -Force | Out-Null
if (Test-Path (Join-Path $root 'sql')) {
    Copy-Item (Join-Path $root 'sql\*') $sqlStage -Recurse -Force
}

Copy-Item (Join-Path $root 'PANDUAN_UPLOAD_HOSTING.md') (Join-Path $stage 'PANDUAN_UPLOAD_HOSTING.md') -Force
Copy-Item (Join-Path $root 'config.hosting.example.php') (Join-Path $stage 'config.hosting.example.php') -Force
Copy-Item (Join-Path $root 'htaccess') (Join-Path $stage 'htaccess') -Recurse -Force

$htaccessSrc = switch ($HtaccessVariant) {
    'root'           { Join-Path $root 'htaccess\.htaccess-root' }
    'cpanel-minimal' { Join-Path $root 'htaccess\.htaccess-cpanel-minimal' }
    'litespeed'      { Join-Path $root 'htaccess\.htaccess-litespeed' }
    default          { Join-Path $root 'htaccess\.htaccess-subfolder' }
}
if (-not $NoHtaccess) {
    Copy-Item $htaccessSrc (Join-Path $stage '.htaccess') -Force
} else {
    Write-Host 'Tanpa .htaccess di root (uji 403) - gunakan file di folder htaccess/'
}
# Cadangan htaccess
Copy-Item (Join-Path $root 'htaccess\.htaccess-minimal') (Join-Path $stage 'htaccess\.htaccess-kosong') -Force -ErrorAction SilentlyContinue
Copy-Item (Join-Path $root 'htaccess\.htaccess-cpanel-minimal') (Join-Path $stage 'htaccess\.htaccess-cpanel-minimal') -Force -ErrorAction SilentlyContinue
Copy-Item (Join-Path $root 'htaccess\.htaccess-cpanel-rewrite') (Join-Path $stage 'htaccess\.htaccess-cpanel-rewrite') -Force -ErrorAction SilentlyContinue
Copy-Item (Join-Path $root 'htaccess\.htaccess-litespeed') (Join-Path $stage 'htaccess\.htaccess-litespeed') -Force -ErrorAction SilentlyContinue
if (Test-Path (Join-Path $root '.user.ini')) {
    Copy-Item (Join-Path $root '.user.ini') (Join-Path $stage '.user.ini') -Force
}

if ($ConfigHostingFile -and (Test-Path $ConfigHostingFile)) {
    Copy-Item $ConfigHostingFile (Join-Path $stage 'config.hosting.php') -Force
    Write-Host "config.hosting.php disertakan dari $ConfigHostingFile"
}

$bootstrapPath = Join-Path $stage 'bootstrap.php'
if (Test-Path $bootstrapPath) {
    $boot = Get-Content $bootstrapPath -Raw
    $boot = $boot -replace 'ini_set\(''display_errors'',\s*1\);', 'ini_set(''display_errors'', 0);'
    Set-Content -Path $bootstrapPath -Value $boot -Encoding UTF8
}

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stage, $zipPath)

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
Write-Host ""
Write-Host "Selesai: $zipPath ($sizeMb MB)" -ForegroundColor Green
if (-not $ConfigHostingFile) {
    Write-Host "Salin config.hosting.example.php -> config.hosting.php lalu isi kredensial database."
}
