<?php
require_once __DIR__ . '/koneksi_local.php';

if ($conn) {
    echo "Koneksi berhasil!\n";
    echo "Host info: " . mysqli_get_host_info($conn) . "\n";
    
    // Coba query sederhana
    $result = mysqli_query($conn, "SHOW TABLES");
    if ($result) {
        echo "Menemukan " . mysqli_num_rows($result) . " tabel di database.\n";
        echo "5 Tabel pertama:\n";
        $i = 0;
        while ($row = mysqli_fetch_row($result)) {
            echo "- " . $row[0] . "\n";
            $i++;
            if ($i >= 5) break;
        }
    } else {
        echo "Gagal mengambil daftar tabel: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Koneksi gagal!\n";
}
