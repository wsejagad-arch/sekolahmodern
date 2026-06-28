<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/koneksi.php';

if ($conn) {
    echo "Attempting to insert a sample student into tbl_anggota_ekskul...\n";
    $id_ekskul = 2;
    $no_induk = '05087'; // Ahmad Adrian Bagus Budianto from tbl_siswa
    
    // Clear first to test clean
    mysqli_query($conn, "DELETE FROM tbl_anggota_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$no_induk'");
    
    $res = mysqli_query($conn, "INSERT INTO tbl_anggota_ekskul (id_ekskul, no_induk_siswa) VALUES ($id_ekskul, '$no_induk')");
    if ($res) {
        echo "Insert succeeded!\n";
    } else {
        echo "Insert failed: " . mysqli_error($conn) . "\n";
    }

    echo "\nRows in tbl_anggota_ekskul now:\n";
    $q = mysqli_query($conn, "SELECT a.*, s.nama_siswa, s.kelas FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $id_ekskul");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            print_r($row);
        }
    } else {
        echo "SELECT failed: " . mysqli_error($conn) . "\n";
    }
}
?>
