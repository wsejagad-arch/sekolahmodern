<?php
$logFile = __DIR__ . '/logs/login_debug.log';

echo "<h2>Login Debug Log</h2>";
echo "<hr>";

if (!file_exists($logFile)) {
    echo "<p style='color: orange;'>Log file belum ada. Silakan coba login dulu!</p>";
} else {
    $content = file_get_contents($logFile);
    $lines = array_filter(explode("\n", $content));

    echo "<p>Total entries: " . count($lines) . "</p>";
    echo "<button onclick='document.location.reload()'>Refresh</button> | ";
    echo "<button onclick='fetch(\"clear_login_debug.php\").then(() => location.reload())'>Clear Log</button>";
    echo "<hr>";

    // Show latest 50 entries
    $latest = array_slice($lines, -50);

    foreach (array_reverse($latest) as $line) {
        if (trim($line) === '') continue;

        $data = json_decode($line, true);

        echo "<div style='border-left: 3px solid #ddd; padding-left: 10px; margin-bottom: 10px;'>";

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                echo "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(var_export($value, true)) . "<br>";
            }
        } else {
            echo htmlspecialchars($line);
        }

        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
