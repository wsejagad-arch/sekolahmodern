<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

// Simulasi session admin
$_SESSION["username"] = "admin";
$hakakses = 1;

echo "<h3>🔍 Debug Pengecekan Kelas</h3>";

// Ambil semua kelas untuk debugging
$sql_kelas = "SELECT id_kelas, kelas FROM tbl_kelas ORDER BY kelas";
$result_kelas = mysqli_query($conn, $sql_kelas);

if ($result_kelas && mysqli_num_rows($result_kelas) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nama Kelas</th><th>Siswa</th><th>Mata Pelajaran</th><th>Jadwal</th><th>Status</th><th>Query Debug</th>";
    echo "</tr>";
    
    while ($kelas = mysqli_fetch_array($result_kelas)) {
        $id_kelas = $kelas['id_kelas'];
        $nama_kelas = $kelas['kelas'];
        
        // Debug: tampilkan query yang dijalankan
        $query_siswa = "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '" . mysqli_real_escape_string($conn, $nama_kelas) . "'";
        $query_mapel = "SELECT COUNT(*) as total FROM tbl_mapel_ampu WHERE kelas = '" . mysqli_real_escape_string($conn, $nama_kelas) . "'";
        $query_jadwal = "SELECT COUNT(*) as total FROM tbl_jadwal WHERE kelas = '" . mysqli_real_escape_string($conn, $nama_kelas) . "'";
        
        // Eksekusi query
        $cek_siswa = mysqli_query($conn, $query_siswa);
        $row_siswa = mysqli_fetch_assoc($cek_siswa);
        
        $cek_mapel = mysqli_query($conn, $query_mapel);
        $row_mapel = mysqli_fetch_assoc($cek_mapel);
        
        $cek_jadwal = mysqli_query($conn, $query_jadwal);
        $row_jadwal = mysqli_fetch_assoc($cek_jadwal);
        
        $total_usage = $row_siswa['total'] + $row_mapel['total'] + $row_jadwal['total'];
        $status = $total_usage > 0 ? "❌ Tidak bisa dihapus" : "✅ Bisa dihapus";
        
        echo "<tr>";
        echo "<td>$id_kelas</td>";
        echo "<td>" . htmlspecialchars($nama_kelas) . "</td>";
        echo "<td>" . $row_siswa['total'] . "</td>";
        echo "<td>" . $row_mapel['total'] . "</td>";
        echo "<td>" . $row_jadwal['total'] . "</td>";
        echo "<td>$status</td>";
        echo "<td><small>";
        echo "Siswa: <code>$query_siswa</code><br>";
        echo "Mapel: <code>$query_mapel</code><br>";
        echo "Jadwal: <code>$query_jadwal</code>";
        echo "</small></td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h4>Test Alternatif dengan Query Berbeda:</h4>";

// Test query alternatif
if (isset($_GET['test_id'])) {
    $test_id = (int)$_GET['test_id'];
    
    echo "<h5>Test untuk Kelas ID: $test_id</h5>";
    
    // Metode 1: Query seperti yang sekarang (dengan subquery)
    $method1_siswa = "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = (SELECT kelas FROM tbl_kelas WHERE id_kelas = $test_id)";
    $method1_mapel = "SELECT COUNT(*) as total FROM tbl_mapel_ampu WHERE kelas = (SELECT kelas FROM tbl_kelas WHERE id_kelas = $test_id)";
    $method1_jadwal = "SELECT COUNT(*) as total FROM tbl_jadwal WHERE kelas = (SELECT kelas FROM tbl_kelas WHERE id_kelas = $test_id)";
    
    // Metode 2: Query dengan JOIN
    $method2_siswa = "SELECT COUNT(s.kelas) as total FROM tbl_siswa s JOIN tbl_kelas k ON s.kelas = k.kelas WHERE k.id_kelas = $test_id";
    $method2_mapel = "SELECT COUNT(m.kelas) as total FROM tbl_mapel_ampu m JOIN tbl_kelas k ON m.kelas = k.kelas WHERE k.id_kelas = $test_id";
    $method2_jadwal = "SELECT COUNT(j.kelas) as total FROM tbl_jadwal j JOIN tbl_kelas k ON j.kelas = k.kelas WHERE k.id_kelas = $test_id";
    
    // Metode 3: Query langsung dengan nama kelas
    $get_kelas_name = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_kelas = $test_id");
    $kelas_data = mysqli_fetch_assoc($get_kelas_name);
    $kelas_name = $kelas_data['kelas'];
    
    $method3_siswa = "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '" . mysqli_real_escape_string($conn, $kelas_name) . "'";
    $method3_mapel = "SELECT COUNT(*) as total FROM tbl_mapel_ampu WHERE kelas = '" . mysqli_real_escape_string($conn, $kelas_name) . "'";
    $method3_jadwal = "SELECT COUNT(*) as total FROM tbl_jadwal WHERE kelas = '" . mysqli_real_escape_string($conn, $kelas_name) . "'";
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metode</th><th>Siswa</th><th>Mapel</th><th>Jadwal</th><th>Total</th></tr>";
    
    // Test metode 1
    $r1_siswa = mysqli_fetch_assoc(mysqli_query($conn, $method1_siswa));
    $r1_mapel = mysqli_fetch_assoc(mysqli_query($conn, $method1_mapel));
    $r1_jadwal = mysqli_fetch_assoc(mysqli_query($conn, $method1_jadwal));
    $total1 = $r1_siswa['total'] + $r1_mapel['total'] + $r1_jadwal['total'];
    
    echo "<tr><td>Metode 1 (Subquery)</td><td>{$r1_siswa['total']}</td><td>{$r1_mapel['total']}</td><td>{$r1_jadwal['total']}</td><td>$total1</td></tr>";
    
    // Test metode 2
    $r2_siswa = mysqli_fetch_assoc(mysqli_query($conn, $method2_siswa));
    $r2_mapel = mysqli_fetch_assoc(mysqli_query($conn, $method2_mapel));
    $r2_jadwal = mysqli_fetch_assoc(mysqli_query($conn, $method2_jadwal));
    $total2 = $r2_siswa['total'] + $r2_mapel['total'] + $r2_jadwal['total'];
    
    echo "<tr><td>Metode 2 (JOIN)</td><td>{$r2_siswa['total']}</td><td>{$r2_mapel['total']}</td><td>{$r2_jadwal['total']}</td><td>$total2</td></tr>";
    
    // Test metode 3
    $r3_siswa = mysqli_fetch_assoc(mysqli_query($conn, $method3_siswa));
    $r3_mapel = mysqli_fetch_assoc(mysqli_query($conn, $method3_mapel));
    $r3_jadwal = mysqli_fetch_assoc(mysqli_query($conn, $method3_jadwal));
    $total3 = $r3_siswa['total'] + $r3_mapel['total'] + $r3_jadwal['total'];
    
    echo "<tr><td>Metode 3 (Direct)</td><td>{$r3_siswa['total']}</td><td>{$r3_mapel['total']}</td><td>{$r3_jadwal['total']}</td><td>$total3</td></tr>";
    echo "</table>";
    
    echo "<p><strong>Kesimpulan:</strong> " . ($total3 == 0 ? "✅ Kelas ini BISA dihapus" : "❌ Kelas ini TIDAK BISA dihapus") . "</p>";
}

// Tampilkan form untuk test
echo "<h4>Test Kelas Spesifik:</h4>";
$sql_select = "SELECT id_kelas, kelas FROM tbl_kelas ORDER BY kelas LIMIT 10";
$result_select = mysqli_query($conn, $sql_select);
if ($result_select) {
    echo "<form method='GET'>";
    echo "<select name='test_id'>";
    while ($opt = mysqli_fetch_array($result_select)) {
        echo "<option value='{$opt['id_kelas']}'>{$opt['id_kelas']} - {$opt['kelas']}</option>";
    }
    echo "</select>";
    echo "<button type='submit'>Test Kelas Ini</button>";
    echo "</form>";
}

echo "<p><a href='home.php?page=input-kelas' style='background: blue; color: white; padding: 10px; text-decoration: none;'>← Kembali ke Input Kelas</a></p>";
?>