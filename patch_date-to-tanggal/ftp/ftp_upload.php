<?php
// ftp_upload.php
// Usage (CLI): php ftp_upload.php host username password /remote/path/ local_file
// Example: php ftp_upload.php ftp.example.com user pass /public_html/jurnal/ C:\xampp\htdocs\jurnal\patch_date-to-tanggal.zip

if ($argc < 6) {
    echo "Usage: php ftp_upload.php host username password remoteDir localFile\n";
    exit(1);
}
$host = $argv[1];
$user = $argv[2];
$pass = $argv[3];
$remoteDir = rtrim($argv[4], '/') . '/';
$localFile = $argv[5];

if (!file_exists($localFile)) {
    echo "Local file not found: $localFile\n";
    exit(1);
}

$conn = ftp_connect($host);
if (!$conn) {
    echo "FTP connect failed to $host\n";
    exit(1);
}

if (!ftp_login($conn, $user, $pass)) {
    echo "FTP login failed for $user\n";
    ftp_close($conn);
    exit(1);
}

ftp_pasv($conn, true);

$remoteFile = $remoteDir . basename($localFile);

// Ensure remote directory exists (basic, may need recursive mkdir depending on server)
@ftp_chdir($conn, $remoteDir);

$upload = ftp_put($conn, $remoteFile, $localFile, FTP_BINARY);
if ($upload) {
    echo "Upload successful: $remoteFile\n";
} else {
    echo "Upload failed. Check permissions and path.\n";
}

ftp_close($conn);
?>
