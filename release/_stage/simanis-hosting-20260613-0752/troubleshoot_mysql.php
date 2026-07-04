<?php
/**
 * MySQL Troubleshooting Script
 * Cek status MySQL dan troubleshooting koneksi
 */

echo "=== MYSQL TROUBLESHOOTING ===\n\n";

// 1. Cek koneksi tanpa database
echo "1. Testing connection without database...\n";
$conn = @mysqli_connect('localhost', 'root', '', null, 3307);
if ($conn) {
    echo "✅ Connection to MySQL server: SUCCESS\n";
    echo "   Server version: " . mysqli_get_server_info($conn) . "\n";
    echo "   Client version: " . mysqli_get_client_info() . "\n\n";

    // 2. List databases
    echo "2. Available databases:\n";
    $result = mysqli_query($conn, "SHOW DATABASES");
    $databases = [];
    while ($row = mysqli_fetch_array($result)) {
        $databases[] = $row[0];
        echo "   - " . $row[0] . "\n";
    }
    echo "\n";

    // 3. Cek apakah database 'jurnal' ada
    if (in_array('jurnal', $databases)) {
        echo "✅ Database 'jurnal' EXISTS\n";

        // 4. Coba koneksi ke database jurnal
        mysqli_close($conn);
        $conn = @mysqli_connect('localhost', 'root', '', 'jurnal', 3307);
        if ($conn) {
            echo "✅ Connection to database 'jurnal': SUCCESS\n\n";

            // 5. Cek tabel tbl_mapel_ampu
            echo "5. Checking table 'tbl_mapel_ampu'...\n";
            $result = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_mapel_ampu'");
            if (mysqli_num_rows($result) > 0) {
                echo "✅ Table 'tbl_mapel_ampu' EXISTS\n\n";

                // 6. Ambil data
                echo "6. Getting data from tbl_mapel_ampu...\n";
                $result = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu ORDER BY no_induk, hari, jam_mulai");

                if ($result) {
                    $total = mysqli_num_rows($result);
                    echo "📊 Total records: $total\n\n";

                    if ($total > 0) {
                        echo "📋 DATA:\n";
                        echo str_repeat("-", 80) . "\n";
                        printf("%-3s %-15s %-20s %-8s %-8s %-8s\n", "No", "NIP", "Mapel", "Kelas", "Hari", "Jam");
                        echo str_repeat("-", 80) . "\n";

                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            printf("%-3d %-15s %-20s %-8s %-8s %-8s\n",
                                   $no++,
                                   $row['no_induk'] ?? '',
                                   substr($row['nama_mapel'] ?? '', 0, 18),
                                   $row['kelas'] ?? '',
                                   $row['hari'] ?? '',
                                   $row['jam_mulai'] ?? '');
                        }
                        echo str_repeat("-", 80) . "\n\n";

                        // Statistik
                        mysqli_data_seek($result, 0);
                        $guru_count = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $guru_count[$row['no_induk']] = ($guru_count[$row['no_induk']] ?? 0) + 1;
                        }

                        echo "👨‍🏫 Guru Statistics:\n";
                        foreach ($guru_count as $nip => $count) {
                            echo "   • $nip: $count jadwal\n";
                        }

                    } else {
                        echo "⚠️  Table is EMPTY - No schedule data found\n\n";
                        echo "🔧 SOLUTION: Add schedule data using SQL:\n";
                        echo "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai)\n";
                        echo "VALUES ('123456789', 'Matematika', 'X-A', 'Senin', '07:00:00', '08:30:00');\n\n";
                    }
                } else {
                    echo "❌ Query failed: " . mysqli_error($conn) . "\n";
                }

            } else {
                echo "❌ Table 'tbl_mapel_ampu' does NOT exist\n\n";
                echo "🔧 SOLUTION: Create table using SQL:\n";
                echo "CREATE TABLE tbl_mapel_ampu (\n";
                echo "    id_mapel INT PRIMARY KEY AUTO_INCREMENT,\n";
                echo "    no_induk VARCHAR(20) NOT NULL,\n";
                echo "    nama_mapel VARCHAR(100) NOT NULL,\n";
                echo "    kelas VARCHAR(20) NOT NULL,\n";
                echo "    hari VARCHAR(20) NOT NULL,\n";
                echo "    jam_mulai TIME NOT NULL,\n";
                echo "    jam_selesai TIME NOT NULL\n";
                echo ");\n\n";
            }

        } else {
            echo "❌ Connection to database 'jurnal' FAILED: " . mysqli_connect_error() . "\n\n";
            echo "🔧 SOLUTION: Create database 'jurnal' in phpMyAdmin\n";
        }

    } else {
        echo "❌ Database 'jurnal' does NOT exist\n\n";
        echo "🔧 SOLUTION: Create database and import structure:\n";
        echo "1. Open phpMyAdmin\n";
        echo "2. Create database 'jurnal'\n";
        echo "3. Import file: include/db_appsiswa.sql\n\n";
    }

    mysqli_close($conn);

} else {
    echo "❌ MySQL server connection FAILED: " . mysqli_connect_error() . "\n\n";
    echo "🔧 TROUBLESHOOTING:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Check XAMPP Control Panel\n";
    echo "3. Try restarting MySQL service\n";
    echo "4. Check if port 3306 is available\n";
}

echo "\n=== END TROUBLESHOOTING ===\n";
?>