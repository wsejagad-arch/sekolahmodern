<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

// Set dummy session untuk testing
$_SESSION['username'] = 'admin';
$_SESSION['nama'] = 'Test Admin';

header('Content-Type: application/json');

// Simulate AJAX request
$_POST['action'] = 'hapus_wali';
$_POST['id_kelas'] = '1';

echo "Testing hapus_wali endpoint:\n";
echo "Action: " . $_POST['action'] . "\n";
echo "ID Kelas: " . $_POST['id_kelas'] . "\n\n";

// Check if class exists
$check = mysqli_query($conn, "SELECT * FROM tbl_kelas WHERE id_kelas=1");
if ($data = mysqli_fetch_array($check)) {
    echo "Found class data:\n";
    echo "ID: " . $data['id_kelas'] . "\n";
    echo "Kelas: " . $data['kelas'] . "\n";
    echo "Wali: " . ($data['wali_kelas'] ?? 'NULL') . "\n";
    echo "NIP: " . ($data['nip_wali'] ?? 'NULL') . "\n\n";
    
    // Update
    $update = "UPDATE tbl_kelas SET wali_kelas=NULL, nip_wali=NULL WHERE id_kelas=1";
    echo "Update query: $update\n";
    
    if (mysqli_query($conn, $update)) {
        echo "Update SUCCESS\n";
        echo "Affected rows: " . mysqli_affected_rows($conn) . "\n";
        
        // Check after update
        $verify = mysqli_query($conn, "SELECT * FROM tbl_kelas WHERE id_kelas=1");
        if ($after = mysqli_fetch_array($verify)) {
            echo "\nAfter update:\n";
            echo "Wali: " . ($after['wali_kelas'] ?? 'NULL') . "\n";
            echo "NIP: " . ($after['nip_wali'] ?? 'NULL') . "\n";
        }
    } else {
        echo "Update FAILED: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "No class found with id_kelas=1\n";
}
?>