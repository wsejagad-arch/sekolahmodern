<?php
// Test AJAX request untuk journal form - simpan di pages/guru/test_ajax.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h3>Test AJAX Journal Form</h3>";

// Simulate session (untuk testing)
if (!isset($_SESSION['no_induk'])) {
    $_SESSION['no_induk'] = 'test_guru';
    $_SESSION['hak_akses'] = 2;
    echo "<p style='color: orange;'>⚠️ Simulating session for testing</p>";
}

echo "<h4>1. Test Path Resolution</h4>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>__DIR__: " . __DIR__ . "</p>";

$koneksi_path = "../../koneksi.php";
echo "<p>Koneksi path: $koneksi_path</p>";
echo "<p>Koneksi exists: " . (file_exists($koneksi_path) ? "✅ YES" : "❌ NO") . "</p>";

$functions_path = "../../functions.php";
echo "<p>Functions path: $functions_path</p>";
echo "<p>Functions exists: " . (file_exists($functions_path) ? "✅ YES" : "❌ NO") . "</p>";

echo "<h4>2. Test Include Files</h4>";
try {
    include $koneksi_path;
    echo "<p style='color: green;'>✅ koneksi.php included successfully</p>";
    
    if (isset($conn)) {
        echo "<p style='color: green;'>✅ \$conn variable available</p>";
        
        // Test database connection
        $test_query = "SELECT COUNT(*) as count FROM tbl_guru LIMIT 1";
        $result = mysqli_query($conn, $test_query);
        if ($result) {
            echo "<p style='color: green;'>✅ Database query successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Database query failed: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ \$conn variable not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error including koneksi.php: " . $e->getMessage() . "</p>";
}

try {
    include $functions_path;
    echo "<p style='color: green;'>✅ functions.php included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error including functions.php: " . $e->getMessage() . "</p>";
}

echo "<h4>3. Test POST Request Simulation</h4>";
$_POST['getDetail'] = true;
$_POST['kelas'] = '10A';
$_POST['mapel'] = 'Matematika';
$_POST['date'] = date('Y-m-d');

echo "<p>Simulating POST data:</p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Test the actual logic from detailmateri.php
if(isset($_POST['getDetail'])) {
    try {
        $kelas = $_POST['kelas'];
        $mapel = $_POST['mapel'];
        $date = $_POST['date'];
        
        echo "<h4>4. Test Database Query</h4>";
        // Gunakan query biasa untuk kompatibilitas hosting
        $kelas_escaped = mysqli_real_escape_string($conn, $kelas);
        $sql = "SELECT COUNT(*) as count FROM tbl_siswa WHERE kelas = '$kelas_escaped' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<p style='color: green;'>✅ Query executed successfully - Found " . $row['count'] . " students</p>";
        } else {
            echo "<p style='color: red;'>❌ Query execution failed: " . mysqli_error($conn) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exception in POST processing: " . $e->getMessage() . "</p>";
    }
}

echo "<h4>5. Test Complete</h4>";
echo "<p>Jika semua test menunjukkan ✅, maka koneksi database dan path sudah benar.</p>";
echo "<p>Jika ada ❌, periksa file koneksi.php dan pastikan path sudah benar.</p>";

?>