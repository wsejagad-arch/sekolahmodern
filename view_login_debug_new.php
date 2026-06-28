<?php
$logFile = __DIR__ . '/logs/login_debug.log';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Login Debug</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
    h2 { color: #333; }
    .entry { border-left: 4px solid #ccc; padding: 12px; margin-bottom: 10px; background-color: white; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .entry.success { border-left-color: #4caf50; background-color: #e8f5e9; }
    .entry.failed { border-left-color: #f44336; background-color: #ffebee; }
    .entry.check { border-left-color: #2196f3; background-color: #e3f2fd; }
    .key { font-weight: bold; color: #333; }
    .found-true { color: #4caf50; font-weight: bold; }
    .found-false { color: #f44336; font-weight: bold; }
    .found-true::before { content: '✓ '; }
    .found-false::before { content: '✗ '; }
    code { background-color: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    button { padding: 8px 16px; margin-right: 10px; cursor: pointer; background-color: #2196f3; color: white; border: none; border-radius: 4px; }
    button:hover { background-color: #1976d2; }
</style></head><body>";

echo "<h2>🔍 Login Debug Log</h2>";

if (!file_exists($logFile)) {
    echo "<p style='color: orange;'>📋 Log file belum ada. Silakan coba login dulu!</p>";
} else {
    $content = file_get_contents($logFile);
    $lines = array_filter(explode("\n", $content));

    echo "<p>Total entries: <strong>" . count($lines) . "</strong></p>";
    echo "<button onclick='document.location.reload()'>🔄 Refresh</button>";
    echo "<button onclick='if(confirm(\"Clear log?\")) fetch(\"clear_login_debug.php\").then(() => location.reload())'>🗑️ Clear Log</button>";
    echo "<hr>";

    // Show latest 100 entries
    $latest = array_slice($lines, -100);

    foreach (array_reverse($latest) as $line) {
        if (trim($line) === '') continue;

        $data = json_decode($line, true);
        if (!is_array($data)) continue;

        $msg = $data['msg'] ?? 'unknown';
        $class = 'entry';

        if (strpos($msg, 'success') !== false) {
            $class .= ' success';
        } elseif (strpos($msg, 'failed') !== false || strpos($msg, 'mismatch') !== false) {
            $class .= ' failed';
        } elseif (strpos($msg, 'check') !== false) {
            $class .= ' check';
        }

        echo "<div class='$class'>";

        foreach ($data as $key => $value) {
            if ($key === 'msg') {
                echo "<div style='font-size: 1.1em; margin-bottom: 8px;'><span class='key'>$key:</span> <strong>" . htmlspecialchars($value) . "</strong></div>";
            } elseif ($key === 'username') {
                echo "<div><span class='key'>$key:</span> <code>" . htmlspecialchars($value) . "</code></div>";
            } elseif (strpos($key, 'found') !== false || strpos($key, 'result') !== false) {
                $class = $value === true ? 'found-true' : 'found-false';
                $text = $value === true ? 'TRUE' : 'FALSE';
                echo "<div><span class='key'>$key:</span> <span class='$class'>$text</span></div>";
            } elseif ($key === 'password_hash' || strpos($key, 'password_hash') !== false) {
                echo "<div><span class='key'>$key:</span> <code style='color: #999;'>" . htmlspecialchars($value) . "...</code></div>";
            } elseif ($key === 'password_empty' || $key === 'password_verify_result') {
                $class = $value === true ? 'found-true' : 'found-false';
                $text = $value === true ? 'TRUE' : 'FALSE';
                echo "<div><span class='key'>$key:</span> <span class='$class'>$text</span></div>";
            } else {
                echo "<div><span class='key'>$key:</span> " . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . "</div>";
            }
        }

        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a> | <a href='debug_login.php'>Database Check →</a></p>";
echo "</body></html>";
