<?php
// Test file untuk mengecek fungsi get_siswa_by_kelas
require_once 'koneksi.php';

echo "=== TESTING GET SISWA BY KELAS ===" . PHP_EOL;

// Simulasi login
$_SESSION['id'] = 1;  // Mock session untuk testing

$kelas = 'XII F 6';
echo "Testing kelas: $kelas" . PHP_EOL;

try {
    // Query yang sudah diperbaiki
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
    
    echo "Found " . count($siswa) . " students:" . PHP_EOL;
    foreach($siswa as $s) {
        echo "- " . $s['no_induk'] . " | " . $s['nama'] . PHP_EOL;
    }
    
    $response = [
        'success' => true,
        'siswa' => $siswa
    ];
    
    echo PHP_EOL . "JSON Response:" . PHP_EOL;
    echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]) . PHP_EOL;
}
?>