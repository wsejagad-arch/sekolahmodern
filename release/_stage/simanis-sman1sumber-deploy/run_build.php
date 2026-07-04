<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

$logFile = __DIR__ . '/build_output.log';
@unlink($logFile);

function writeLog($msg) {
    global $logFile;
    file_put_contents($logFile, $msg, FILE_APPEND);
}

writeLog("Starting build...\n");
chdir(__DIR__);

writeLog("Running build-hosting-simanis.ps1...\n");
$out1 = shell_exec('powershell -ExecutionPolicy Bypass -File build-hosting-simanis.ps1 2>&1');
writeLog($out1 . "\n");

writeLog("Running create-www-deploy.ps1...\n");
$out2 = shell_exec('powershell -ExecutionPolicy Bypass -File create-www-deploy.ps1 2>&1');
writeLog($out2 . "\n");

writeLog("Build finished.\n");
echo "OK";
?>
