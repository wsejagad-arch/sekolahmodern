# ftp_upload.ps1
# PowerShell script to upload a file to FTP using FtpWebRequest
# Usage:
#  .\ftp_upload.ps1 -Host "ftp.example.com" -Username "user" -Password "pass" -LocalFile "C:\path\patch.zip" -RemotePath "/public_html/jurnal/"

param(
    [Parameter(Mandatory=$true)][string]$Host,
    [Parameter(Mandatory=$true)][string]$Username,
    [Parameter(Mandatory=$true)][string]$Password,
    [Parameter(Mandatory=$true)][string]$LocalFile,
    [string]$RemotePath = "/"
)

if (-not (Test-Path $LocalFile)) {
    Write-Error "Local file not found: $LocalFile"
    exit 1
}

$filename = [System.IO.Path]::GetFileName($LocalFile)
$uri = "ftp://$Host$RemotePath$filename"

$req = [System.Net.FtpWebRequest]::Create($uri)
$req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$req.Credentials = New-Object System.Net.NetworkCredential($Username,$Password)
$req.UseBinary = $true
$req.UsePassive = $true

[byte[]]$fileContents = [System.IO.File]::ReadAllBytes($LocalFile)
$req.ContentLength = $fileContents.Length

$stream = $req.GetRequestStream()
$stream.Write($fileContents, 0, $fileContents.Length)
$stream.Close()

$res = $req.GetResponse()
Write-Host "Upload status: $($res.StatusDescription)"
$res.Close()
