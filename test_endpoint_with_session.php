<?php
// Test login dan endpoint
require_once 'koneksi.php';
require_once 'functions.php';

echo "=== TESTING LOGIN DAN ENDPOINT ===" . PHP_EOL;

// Simulasi login guru
$_SESSION['no_induk'] = '0029';
$_SESSION['hak_akses'] = 2;
$_SESSION['nama_guru'] = 'Test Guru';

echo "Session set:" . PHP_EOL;
echo "- no_induk: " . $_SESSION['no_induk'] . PHP_EOL;
echo "- hak_akses: " . $_SESSION['hak_akses'] . PHP_EOL;

$kelas = 'XII F 6';

// Test fungsi get_siswa_by_kelas secara langsung
echo PHP_EOL . "Testing get_siswa_by_kelas untuk kelas: $kelas" . PHP_EOL;

// Validasi session
if (!isset($_SESSION['no_induk']) || $_SESSION['hak_akses'] != 2) {
    echo "❌ Session check failed" . PHP_EOL;
} else {
    echo "✅ Session check passed" . PHP_EOL;
    
    try {
        $query = "SELECT no_induk, nama_siswa AS nama FROM tbl_siswa WHERE kelas = ? ORDER BY nama_siswa ASC";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $kelas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $siswa = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $siswa[] = [
                'no_induk' => $row['no_induk'],
                'nama' => $row['nama']
            ];
        }
        
        echo "Found " . count($siswa) . " students" . PHP_EOL;
        echo "JSON Response:" . PHP_EOL;
        echo json_encode([
            'success' => true,
            'siswa' => $siswa
        ], JSON_PRETTY_PRINT) . PHP_EOL;
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    }
}
?>