param(
  [Parameter(Mandatory=$true)][string]$remoteUserHost,
  [Parameter(Mandatory=$true)][int]$port,
  [Parameter(Mandatory=$true)][string]$keyOrDash,
  [Parameter(Mandatory=$true)][string]$localZip,
  [Parameter(Mandatory=$true)][string]$remoteDir
)

Write-Host "Uploading $localZip to $remoteUserHost:$remoteDir"
$zipName = Split-Path $localZip -Leaf
$remoteZipPath = "$remoteDir/$zipName"

if ($keyOrDash -eq '-') {
  & scp -P $port $localZip "$remoteUserHost:$remoteZipPath"
} else {
  & scp -i $keyOrDash -P $port $localZip "$remoteUserHost:$remoteZipPath"
}

Write-Host "Upload finished. Extracting on remote host..."
if ($keyOrDash -eq '-') {
  & ssh -p $port $remoteUserHost "mkdir -p '$remoteDir' && cd '$remoteDir' && unzip -o '$zipName' || echo 'EXTRACT_FAIL'"
} else {
  & ssh -i $keyOrDash -p $port $remoteUserHost "mkdir -p '$remoteDir' && cd '$remoteDir' && unzip -o '$zipName' || echo 'EXTRACT_FAIL'"
}

Write-Host "Done. Please verify files on the server."
