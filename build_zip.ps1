# PowerShell Script to bundle the project into a clean zip for aaPanel
$ErrorActionPreference = "Stop"

$ZipFile = "wa_sender_aapanel.zip"
$TempDir = "temp_build"

Write-Host "=== WhatsApp Notification Sender packager ===" -ForegroundColor Cyan

# 1. Clean up old build files
if (Test-Path $ZipFile) {
    Write-Host "Removing old zip file: $ZipFile"
    Remove-Item $ZipFile -Force
}
if (Test-Path $TempDir) {
    Write-Host "Removing old temp folder: $TempDir"
    Remove-Item $TempDir -Recurse -Force
}

# 2. Create clean temp directory
New-Item -ItemType Directory -Force -Path $TempDir | Out-Null
Write-Host "Created temporary build folder: $TempDir"

# List of root paths to copy (excluding venv, node_modules, etc.)
$ItemsToCopy = Get-ChildItem -Path . -Exclude $TempDir, $ZipFile, "venv", "notifications.db", ".git", ".idea", ".vscode", "build_zip.ps1", "build_zip.bat", "__pycache__"

foreach ($Item in $ItemsToCopy) {
    $DestPath = Join-Path $TempDir $Item.Name
    if ($Item.PSIsContainer) {
        # It's a directory (like templates, static, worker)
        Write-Host "Copying folder: $($Item.Name)"
        New-Item -ItemType Directory -Force -Path $DestPath | Out-Null
        
        # If copying worker, exclude node_modules and session folders
        if ($Item.Name -eq "worker") {
            Get-ChildItem -Path $Item.FullName -Exclude "node_modules", "session" | ForEach-Object {
                Copy-Item -Path $_.FullName -Destination $DestPath -Recurse -Force
            }
        } else {
            Copy-Item -Path $Item.FullName -Destination (Split-Path $DestPath -Parent) -Recurse -Force
        }
    } else {
        # It's a file (like main.py, requirements.txt, README.md, setup_aapanel.sh, DEPLOY.md)
        Write-Host "Copying file: $($Item.Name)"
        Copy-Item -Path $Item.FullName -Destination $DestPath -Force
    }
}

# 3. Compress clean folder to ZIP
Write-Host "Compressing files into $ZipFile..." -ForegroundColor Yellow
Compress-Archive -Path "$TempDir\*" -DestinationPath $ZipFile -Force

# 4. Clean up temp folder
Write-Host "Cleaning up temporary files..."
Remove-Item $TempDir -Recurse -Force

Write-Host "[OK] Successfully generated clean ZIP: $ZipFile" -ForegroundColor Green
