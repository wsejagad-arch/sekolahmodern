<?php
include 'koneksi.php';

echo "<h2>Database Structure Check</h2>";

// Check tbl_kelas structure
echo "<h3>Table: tbl_kelas</h3>";
$query = "DESCRIBE tbl_kelas";
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check tbl_siswa structure
echo "<h3>Table: tbl_siswa</h3>";
$query = "DESCRIBE tbl_siswa";
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check existing data
echo "<h3>Existing Data in tbl_kelas</h3>";
$query = "SELECT * FROM tbl_kelas LIMIT 10";
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<table border='1'>";
    $first = true;
    while ($row = mysqli_fetch_assoc($result)) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

echo "<h3>Existing Data in tbl_siswa</h3>";
$query = "SELECT * FROM tbl_siswa LIMIT 10";
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<table border='1'>";
    $first = true;
    while ($row = mysqli_fetch_assoc($result)) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>