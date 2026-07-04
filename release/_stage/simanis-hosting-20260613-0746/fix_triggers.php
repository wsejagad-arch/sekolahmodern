<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'sijurnal');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SHOW TRIGGERS");
$fixes = 0;
while ($row = $result->fetch_assoc()) {
    $trigger = $row['Trigger'];
    $definer = $row['Definer'];
    if ($definer === '@' || $definer === '' || strpos($definer, '@') === 0 || strpos($definer, '@') === false) {
        $res2 = $conn->query("SHOW CREATE TRIGGER `$trigger`");
        if ($row2 = $res2->fetch_assoc()) {
            $createStmt = $row2['SQL Original Statement'];
            if (!$createStmt) {
                // If it's missing, build it
                $timing = $row['Timing'];
                $event = $row['Event'];
                $table = $row['Table'];
                $statement = $row['Statement'];
                $createStmt = "CREATE TRIGGER `$trigger` $timing $event ON `$table` FOR EACH ROW $statement";
            }
            
            // Remove DEFINER entirely
            $createStmt = preg_replace('/DEFINER=.*?\s+TRIGGER/i', 'TRIGGER', $createStmt);
            
            // Re-create
            $conn->query("DROP TRIGGER IF EXISTS `$trigger`");
            if (!$conn->query($createStmt)) {
                echo "Failed to recreate $trigger: " . $conn->error . "\n";
                echo "SQL: " . $createStmt . "\n";
            } else {
                echo "Fixed trigger: $trigger\n";
                $fixes++;
            }
        }
    }
}
echo "Fixed $fixes triggers.\n";
