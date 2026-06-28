<?php
echo "=== TESTING ORIGINAL CONNECTION METHOD ===\n";

// Simulasi koneksi seperti di koneksi.php
$cfg = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'db' => 'jurnal',
    'port' => 3306,
];

echo "Attempting connection with:\n";
echo "- Host: {$cfg['host']}\n";
echo "- User: {$cfg['user']}\n";
echo "- Database: {$cfg['db']}\n";
echo "- Port: {$cfg['port']}\n\n";

$conn = mysqli_connect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['db'], $cfg['port']);

if (!$conn) {
    echo "❌ CONNECTION FAILED: " . mysqli_connect_error() . "\n";

    // Coba tanpa database
    echo "\n=== TRYING CONNECTION WITHOUT DATABASE ===\n";
    $conn2 = mysqli_connect($cfg['host'], $cfg['user'], $cfg['password']);
    if (!$conn2) {
        echo "❌ CONNECTION TO MYSQL SERVER FAILED: " . mysqli_connect_error() . "\n";
        exit(1);
    }

    echo "✅ Connected to MySQL server (without database)\n";

    // Cek apakah database 'jurnal' ada
    $result = mysqli_query($conn2, "SHOW DATABASES");
    $found = false;
    echo "Available databases:\n";
    while ($row = mysqli_fetch_row($result)) {
        echo "- " . $row[0] . "\n";
        if ($row[0] == 'jurnal') {
            $found = true;
        }
    }

    if (!$found) {
        echo "\n❌ Database 'jurnal' not found!\n";
        echo "Available databases are listed above.\n";
        echo "Please create the database or check the database name.\n";
    } else {
        echo "\n✅ Database 'jurnal' exists!\n";
        echo "The issue might be with user permissions.\n";
    }

    mysqli_close($conn2);
    exit(1);
}

echo "✅ DATABASE CONNECTION SUCCESSFUL\n\n";

echo "=== CHECKING TABLES ===\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "- " . $row[0] . "\n";
}

echo "\n=== CHECKING TEACHER DATA ===\n";
if (in_array('tbl_guru', $tables)) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_guru");
    $row = mysqli_fetch_assoc($result);
    echo "Total teachers: " . $row['total'] . "\n";

    if ($row['total'] > 0) {
        $result = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 3");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "- NIP: " . $row['no_induk'] . " | Name: " . $row['nama_guru'] . "\n";
        }
    }
} else {
    echo "❌ Table 'tbl_guru' not found\n";
}

echo "\n=== CHECKING SCHEDULE DATA ===\n";
if (in_array('tbl_mapel_ampu', $tables)) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
    $row = mysqli_fetch_assoc($result);
    echo "Total schedules: " . $row['total'] . "\n";

    if ($row['total'] > 0) {
        $result = mysqli_query($conn, "SELECT no_induk, COUNT(*) as count FROM tbl_mapel_ampu GROUP BY no_induk LIMIT 3");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "- NIP: " . $row['no_induk'] . " | Schedules: " . $row['count'] . "\n";
        }
    }
} else {
    echo "❌ Table 'tbl_mapel_ampu' not found\n";
}

mysqli_close($conn);
?>