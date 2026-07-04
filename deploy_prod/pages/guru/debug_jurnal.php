<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
echo "<h3>Debug Form Jurnal</h3>";

// Check session
echo "<h4>Session Info:</h4>";
if (isset($_SESSION['no_induk'])) {
    echo "✅ Session active - NIP: " . $_SESSION['no_induk'] . "<br>";
    echo "✅ Hak akses: " . ($_SESSION['hak_akses'] ?? 'not set') . "<br>";
} else {
    echo "❌ Session tidak ada<br>";
}

// Test database connection
echo "<h4>Database Connection:</h4>";
include '../koneksi.php';

if ($conn) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
    exit;
}

// Test POST request
echo "<h4>POST Data:</h4>";
if (isset($_POST['getDetail'])) {
    echo "✅ POST getDetail: " . $_POST['getDetail'] . "<br>";
    
    $id = (int)$_POST['getDetail'];
    $nipguru = $_SESSION['no_induk'] ?? '';
    
    echo "Checking jadwal ID: $id for guru: $nipguru<br>";
    
    // Gunakan query biasa untuk kompatibilitas hosting
    $id_escaped = mysqli_real_escape_string($conn, $id);
    $nipguru_escaped = mysqli_real_escape_string($conn, $nipguru);
    $query = "SELECT * FROM tbl_mapel_ampu WHERE id_mapel = '$id_escaped' AND no_induk = '$nipguru_escaped' LIMIT 1";
    $res = mysqli_query($conn, $query);
    
    if ($res) {
        $dat = mysqli_fetch_assoc($res);
        
        if ($dat) {
            echo "✅ Jadwal ditemukan: " . print_r($dat, true) . "<br>";
        } else {
            echo "❌ Jadwal tidak ditemukan untuk guru ini<br>";
            
            // Check if record exists for any guru
            $check = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE id_mapel = $id");
            if ($check && mysqli_num_rows($check) > 0) {
                $record = mysqli_fetch_assoc($check);
                echo "⚠️ Jadwal ada tapi untuk guru lain: " . print_r($record, true) . "<br>";
            } else {
                echo "❌ ID jadwal $id tidak ada sama sekali<br>";
            }
        }
    } else {
        echo "❌ Error preparing statement: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "ℹ️ No POST data (normal untuk GET request)<br>";
}

// Show available jadwal for current guru
if (isset($_SESSION['no_induk'])) {
    echo "<h4>Jadwal Available untuk guru " . $_SESSION['no_induk'] . ":</h4>";
    $jadwal = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '" . $_SESSION['no_induk'] . "'");
    if ($jadwal && mysqli_num_rows($jadwal) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Mapel</th><th>Kelas</th><th>Hari</th><th>Jam</th></tr>";
        while ($row = mysqli_fetch_assoc($jadwal)) {
            echo "<tr><td>{$row['id_mapel']}</td><td>{$row['nama_mapel']}</td><td>{$row['kelas']}</td><td>{$row['hari']}</td><td>{$row['jam_mulai']}-{$row['jam_selesai']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Tidak ada jadwal untuk guru ini<br>";
    }
}
?>