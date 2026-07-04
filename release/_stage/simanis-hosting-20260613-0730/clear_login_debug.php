<?php
$logFile = __DIR__ . '/logs/login_debug.log';
if (file_exists($logFile)) {
    unlink($logFile);
    echo "Log cleared!";
} else {
    echo "Log file not found";
}
