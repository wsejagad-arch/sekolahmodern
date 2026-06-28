<#
full_deploy.ps1
Menjalankan langkah-langkah deployment dari mesin lokal Anda ke server cPanel via SSH:
 1) Upload `authorized_keys` ke server
 2) Set permission pada server
 3) Tes koneksi SSH
 4) Buat ZIP proyek lokal
 5) Upload ZIP ke server
 6) Ekstrak ZIP di server

Usage (PowerShell, jalankan dari folder mana saja):
  powershell -ExecutionPolicy Bypass -File .\full_deploy.ps1 \
    -PrivateKey "C:\xampp\htdocs\jurnal\patch_date-to-tanggal\ftp\id_rsa_cpanel" \
    -AuthorizedKeys "C:\xampp\htdocs\jurnal\patch_date-to-tanggal\ftp\authorized_keys" \
    -LocalProjectPath "C:\xampp\htdocs\jurnal" \
    -RemoteUserHost "smasumb1@sijurnal.sma1sumber.sch.id" \
    -Port 22 \
    -RemoteHome "/home5/smasumb1" \
    -RemoteWebDir "/home5/smasumb1/public_html/sijurnal.sma1sumber.sch.id"

Perhatikan: pastikan `ssh` dan `scp` tersedia di PATH (Windows 10+ biasanya punya OpenSSH client). Jika diminta passphrase, masukkan sesuai.
#>
param(
    [Parameter(Mandatory=$true)][string]$PrivateKey,
    [Parameter(Mandatory=$true)][string]$AuthorizedKeys,
    [Parameter(Mandatory=$true)][string]$LocalProjectPath,
    [Parameter(Mandatory=$true)][string]$RemoteUserHost,
    [int]$Port = 22,
    [string]$RemoteHome = '/home/smasumb1',
    [string]$RemoteWebDir = '/home5/smasumb1/public_html/sijurnal.sma1sumber.sch.id',
    [string]$LocalZip = "$env:TEMP\jurnal_deploy.zip",
    [string]$LogFile = "$env:TEMP\full_deploy_log.txt"
)

function Log { param($s) $t = Get-Date -Format "yyyy-MM-dd HH:mm:ss"; "$t  $s" | Out-File -FilePath $LogFile -Append }

# Clear / create log
"" | Out-File -FilePath $LogFile
Log "Starting full_deploy.ps1"

# Validate files
if (-not (Test-Path $PrivateKey)) { Log "ERROR: Private key not found: $PrivateKey"; Write-Error "Private key not found: $PrivateKey"; exit 1 }
if (-not (Test-Path $AuthorizedKeys)) { Log "ERROR: authorized_keys not found: $AuthorizedKeys"; Write-Error "authorized_keys not found: $AuthorizedKeys"; exit 1 }
if (-not (Test-Path $LocalProjectPath)) { Log "ERROR: Local project path not found: $LocalProjectPath"; Write-Error "Local project path not found: $LocalProjectPath"; exit 1 }

# 1) Upload authorized_keys
Log "Uploading authorized_keys to $RemoteUserHost:~/.ssh/authorized_keys"
$scpAuthCmd = @('scp','-i',$PrivateKey,'-P',$Port,$AuthorizedKeys,"$RemoteUserHost:~/.ssh/authorized_keys")
Log "Running: $($scpAuthCmd -join ' ')"
$proc = Start-Process -FilePath $scpAuthCmd[0] -ArgumentList $scpAuthCmd[1..($scpAuthCmd.Length-1)] -NoNewWindow -Wait -PassThru
if ($proc.ExitCode -ne 0) { Log "ERROR: scp authorized_keys failed (exit $($proc.ExitCode))"; Write-Error "scp authorized_keys failed"; exit 2 }
Log "authorized_keys uploaded"

# 2) Set permission authorized_keys on server
Log "Setting permissions on remote ~/.ssh and authorized_keys"
$sshPermCmd = "chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys && echo AUTH_OK"
$sshPermCmdFull = @('ssh','-i',$PrivateKey,'-p',$Port,$RemoteUserHost,$sshPermCmd)
Log "Running: $($sshPermCmdFull -join ' ')"
$proc2 = Start-Process -FilePath $sshPermCmdFull[0] -ArgumentList $sshPermCmdFull[1..($sshPermCmdFull.Length-1)] -NoNewWindow -Wait -PassThru
if ($proc2.ExitCode -ne 0) { Log "ERROR: ssh set perms failed (exit $($proc2.ExitCode))"; Write-Error "ssh set perms failed"; exit 3 }
Log "Permissions set (or command returned exit $($proc2.ExitCode))"

# 3) Test SSH quick
Log "Testing SSH connection"
$sshTestCmd = @('ssh','-i',$PrivateKey,'-o','BatchMode=yes','-o','ConnectTimeout=10','-p',$Port,$RemoteUserHost,'echo SSH_OK')
Log "Running: $($sshTestCmd -join ' ')"
$proc3 = Start-Process -FilePath $sshTestCmd[0] -ArgumentList $sshTestCmd[1..($sshTestCmd.Length-1)] -NoNewWindow -Wait -PassThru
if ($proc3.ExitCode -ne 0) { Log "ERROR: ssh test failed (exit $($proc3.ExitCode))"; Write-Error "ssh test failed"; exit 4 }
Log "SSH test OK"

# 4) Create ZIP of project
Log "Creating ZIP of $LocalProjectPath -> $LocalZip"
if (Test-Path $LocalZip) { Remove-Item $LocalZip -Force }
try {
    Compress-Archive -Path (Join-Path $LocalProjectPath '*') -DestinationPath $LocalZip -Force -ErrorAction Stop
    Log "ZIP created: $LocalZip"
} catch {
    Log "ERROR: Compress-Archive failed: $_"
    Write-Error "Compress-Archive failed: $_"
    exit 5
}

# 5) Upload ZIP
Log "Uploading ZIP to $RemoteUserHost:$RemoteHome/"
$scpZipCmd = @('scp','-i',$PrivateKey,'-P',$Port,$LocalZip,"$RemoteUserHost:$RemoteHome/")
Log "Running: $($scpZipCmd -join ' ')"
$proc4 = Start-Process -FilePath $scpZipCmd[0] -ArgumentList $scpZipCmd[1..($scpZipCmd.Length-1)] -NoNewWindow -Wait -PassThru
if ($proc4.ExitCode -ne 0) { Log "ERROR: scp zip failed (exit $($proc4.ExitCode))"; Write-Error "scp zip failed"; exit 6 }
Log "ZIP uploaded"

# 6) Extract on server
$zipBase = [System.IO.Path]::GetFileName($LocalZip)
$remoteExtractCmd = "mkdir -p '$RemoteWebDir' && cd $RemoteHome && unzip -o $zipBase -d '$RemoteWebDir' && rm $zipBase && echo EXTRACT_OK"
Log "Extracting on remote: $remoteExtractCmd"
$sshExtractCmd = @('ssh','-i',$PrivateKey,'-p',$Port,$RemoteUserHost,$remoteExtractCmd)
$proc5 = Start-Process -FilePath $sshExtractCmd[0] -ArgumentList $sshExtractCmd[1..($sshExtractCmd.Length-1)] -NoNewWindow -Wait -PassThru
if ($proc5.ExitCode -ne 0) { Log "ERROR: remote extract failed (exit $($proc5.ExitCode))"; Write-Error "remote extract failed"; exit 7 }
Log "Remote extract complete"

# 7) Fix permissions (optional)
$sshPermWebCmd = "find '$RemoteWebDir' -type d -exec chmod 755 {} \; && find '$RemoteWebDir' -type f -exec chmod 644 {} \; && echo PERMS_OK"
$sshPermWebCmdFull = @('ssh','-i',$PrivateKey,'-p',$Port,$RemoteUserHost,$sshPermWebCmd)
$proc6 = Start-Process -FilePath $sshPermWebCmdFull[0] -ArgumentList $sshPermWebCmdFull[1..($sshPermWebCmdFull.Length-1)] -NoNewWindow -Wait -PassThru
Log "Set web perms exit code: $($proc6.ExitCode)"

Log "Deployment finished successfully"
Write-Host "Deployment finished. Lihat log: $LogFile"
exit 0
