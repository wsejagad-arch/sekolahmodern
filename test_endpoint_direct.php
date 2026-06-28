<?php
// Simple test untuk session dan endpoint
session_start();

echo "=== TESTING SESSION DAN ENDPOINT ===" . PHP_EOL;

// Set session seperti login guru
$_SESSION['no_induk'] = '0029';
$_SESSION['hak_akses'] = 2;
$_SESSION['nama_guru'] = 'Test Guru';

echo "Session set:" . PHP_EOL;
echo "- Session ID: " . session_id() . PHP_EOL;
echo "- no_induk: " . $_SESSION['no_induk'] . PHP_EOL;
echo "- hak_akses: " . $_SESSION['hak_akses'] . PHP_EOL;

// Sekarang test endpoint dengan memanggil langsung
echo PHP_EOL . "=== TESTING ENDPOINT LANGSUNG ===" . PHP_EOL;

// Set GET parameter
$_GET['kelas'] = 'XII F 6';

// Include endpoint file
ob_start();
include 'get_siswa_by_kelas.php';
$output = ob_get_clean();

echo "Output dari endpoint:" . PHP_EOL;
echo $output . PHP_EOL;

// Parse JSON untuk verify
$result = json_decode($output, true);
if ($result) {
    echo PHP_EOL . "Parsed result:" . PHP_EOL;
    echo "- Success: " . ($result['success'] ? 'true' : 'false') . PHP_EOL;
    if (isset($result['siswa'])) {
        echo "- Jumlah siswa: " . count($result['siswa']) . PHP_EOL;
    }
    if (isset($result['message'])) {
        echo "- Message: " . $result['message'] . PHP_EOL;
    }
}
?>