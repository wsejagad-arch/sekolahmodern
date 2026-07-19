<?php
session_start();
$_SESSION['username'] = 'admin';
$_SESSION['hakakses'] = 1;

// Mock $_GET and $_POST
$_GET['id_guru'] = '94'; // ADIF FATUS SYAROFAH
$_POST['submit'] = 'Simpan';
$_POST['nip'] = '0045';
$_POST['nama'] = 'ADIF FATUS SYAROFAH, S.Pd';
$_POST['no_wa'] = '08111222333';
$_POST['status_kepegawaian'] = 'Non-ASN';
$_POST['jabatan'] = '';
$_POST['status_keaktifan'] = 'Aktif';
$_POST['foto'] = 'default.jpg'; // old photo
$_FILES['file'] = [
    'name' => '',
    'type' => '',
    'tmp_name' => '',
    'error' => 4, // UPLOAD_ERR_NO_FILE
    'size' => 0
];

// We need to capture output to see if it dies or prints SweetAlert
ob_start();
try {
    include 'edit-guru.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
$output = ob_get_clean();

echo "=== Output Lengkap ===\n";
echo substr($output, 0, 500) . "...\n\n";

if (strpos($output, 'Berhasil merubah data guru') !== false) {
    echo "✅ SUCCESS: Form submission succeeded!\n";
} else {
    echo "❌ ERROR: Form submission failed. Looking for 'die' messages...\n";
    if (strpos($output, 'Database Update Error') !== false) {
        echo "FOUND DB ERROR in output.\n";
    }
}

// Check database to see if it updated
require 'koneksi.php';
$res = mysqli_query($conn, "SELECT no_wa FROM tbl_guru WHERE id_guru='94'");
$row = mysqli_fetch_assoc($res);
echo "Isi database no_wa sekarang: " . $row['no_wa'] . "\n";

