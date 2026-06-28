<?php
include "koneksi.php";

echo "<h2>Check tbl_kelas Structure</h2>";

$result = mysqli_query($conn, "DESCRIBE tbl_kelas");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Sample Data:</h3>";
$sampleData = mysqli_query($conn, "SELECT * FROM tbl_kelas LIMIT 5");
if (mysqli_num_rows($sampleData) > 0) {
    echo "<table border='1'>";
    $first = true;
    while ($row = mysqli_fetch_assoc($sampleData)) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>{$key}</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

mysqli_close($conn);
?>