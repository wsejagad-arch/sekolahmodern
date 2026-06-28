param(
    [Parameter(Mandatory=$true)][string]$Host,
    [Parameter(Mandatory=$true)][string]$Username,
    [Parameter(Mandatory=$true)][string]$Password,
    [Parameter(Mandatory=$true)][string]$LocalRoot,
    [Parameter(Mandatory=$true)][string]$RemoteRoot
)

Write-Host "Starting FTP sync from $LocalRoot to ftp://$Host$RemoteRoot"

function Ensure-RemoteDir($host,$user,$pass,$dir){
    $uri = "ftp://$host$dir"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
    $req.Credentials = New-Object System.Net.NetworkCredential($user,$pass)
    $req.UsePassive = $true
    $req.UseBinary = $true
    $req.KeepAlive = $false
    try{ $res = $req.GetResponse(); $res.Close(); Write-Host "Created: $dir" -ForegroundColor Green }
    catch{ Write-Host "Ensure dir: $dir -> $($_.Exception.Message)" -ForegroundColor Yellow }
}

function Upload-File($host,$user,$pass,$local,$remote){
    $uri = "ftp://$host$remote"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.Credentials = New-Object System.Net.NetworkCredential($user,$pass)
    $req.UsePassive = $true
    $req.UseBinary = $true
    $req.KeepAlive = $false
    $bytes = [System.IO.File]::ReadAllBytes($local)
    $req.ContentLength = $bytes.Length
    try{
        $rs = $req.GetRequestStream();
        $rs.Write($bytes,0,$bytes.Length);
        $rs.Close();
        $res = $req.GetResponse();
        Write-Host "Uploaded: $remote" -ForegroundColor Green
        $res.Close();
    } catch {
        Write-Host "Upload failed: $local -> $remote : $($_.Exception.Message)" -ForegroundColor Red
    }
}

$LocalRoot = (Resolve-Path $LocalRoot).ProviderPath
$files = Get-ChildItem -Path $LocalRoot -Recurse -File | Where-Object {
    $p = $_.FullName.ToLower()
    -not ($p -like "*\\.git\\*" -or $p -like "*\\node_modules\\*" -or $p -like "*\\patch_date-to-tanggal\\*" -or $p -like "*\\.vscode\\sftp.json")
}

foreach ($f in $files){
    $rel = $f.FullName.Substring($LocalRoot.Length)
    if ($rel.StartsWith('\') -or $rel.StartsWith('/')) { $rel = $rel.Substring(1) }
    $remotePath = $RemoteRoot.TrimEnd('/') + '/' + ($rel -replace '\\','/')
    $remoteDir = [System.IO.Path]::GetDirectoryName($remotePath) -replace '\\','/'
    if ($remoteDir -ne ''){
        Ensure-RemoteDir -host $Host -user $Username -pass $Password -dir ($remoteDir + '/')
    }
    Upload-File -host $Host -user $Username -pass $Password -local $f.FullName -remote $remotePath
}

Write-Host "FTP sync done." -ForegroundColor Cyan
