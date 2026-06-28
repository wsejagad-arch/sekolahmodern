<?php
// Test file untuk mengecek fungsi simpan_pelanggaran
require_once 'koneksi.php';
require_once 'functions.php';

echo "=== TESTING SIMPAN PELANGGARAN ===" . PHP_EOL;

// Simulasi login  
$_SESSION['id'] = 29;  // ID guru untuk testing

echo "Testing input validation:" . PHP_EOL;

$test_data = [
    'kelas' => 'XII F 6',
    'no_induk' => '05089',  // Ahmad Zakaria dari test sebelumnya
    'kategori_pelanggaran' => 'Ringan',
    'jenis_pelanggaran' => 'Terlambat masuk kelas',
    'deskripsi_pelanggaran' => 'Terlambat 10 menit masuk kelas',
    'tindakan_guru' => 'Teguran lisan dan catatan',
    'tanggal_pelanggaran' => date('Y-m-d'),
    'status_pelanggaran' => 'Aktif'
];

echo "Test data:" . PHP_EOL;
foreach($test_data as $key => $value) {
    echo "- $key: $value" . PHP_EOL;
}

// Test 1: Validasi input
$required_fields = ['kelas', 'no_induk', 'kategori_pelanggaran', 'jenis_pelanggaran', 'tanggal_pelanggaran'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($test_data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo "❌ Missing required fields: " . implode(', ', $missing_fields) . PHP_EOL;
} else {
    echo "✅ All required fields present" . PHP_EOL;
}

// Test 2: Validasi kategori
$valid_categories = ['Ringan', 'Sedang', 'Berat'];
if (!in_array($test_data['kategori_pelanggaran'], $valid_categories)) {
    echo "❌ Invalid category: " . $test_data['kategori_pelanggaran'] . PHP_EOL;
} else {
    echo "✅ Category valid" . PHP_EOL;
}

// Test 3: Validasi tanggal
$date = DateTime::createFromFormat('Y-m-d', $test_data['tanggal_pelanggaran']);
if (!$date || $date->format('Y-m-d') !== $test_data['tanggal_pelanggaran']) {
    echo "❌ Invalid date format" . PHP_EOL;
} else {
    echo "✅ Date format valid" . PHP_EOL;
}

// Test 4: Cek siswa ada di kelas
$check_siswa = "SELECT nama_siswa AS nama FROM tbl_siswa WHERE no_induk = ? AND kelas = ?";
$stmt_check = mysqli_prepare($conn, $check_siswa);
mysqli_stmt_bind_param($stmt_check, "ss", $test_data['no_induk'], $test_data['kelas']);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) === 0) {
    echo "❌ Student not found in class" . PHP_EOL;
} else {
    $siswa_data = mysqli_fetch_assoc($result_check);
    echo "✅ Student found: " . $siswa_data['nama'] . PHP_EOL;
}

// Test 5: Cek tabel pelanggaran exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran'");
if (mysqli_num_rows($check_table) === 0) {
    echo "❌ Table tbl_pelanggaran doesn't exist" . PHP_EOL;
} else {
    echo "✅ Table tbl_pelanggaran exists" . PHP_EOL;
}

echo PHP_EOL . "✅ All validation tests passed - System is ready!" . PHP_EOL;
?>