<?php
// Include database connection
require_once dirname(__DIR__) . '/koneksi.php';

// Find a class to test
$q = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE kelas <> '' LIMIT 1");
if ($row = mysqli_fetch_assoc($q)) {
    $kelasFilter = $row['kelas'];
    $kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
    echo "Testing class: $kelasFilter\n";

    // Run our query logic
    $waliKelasNama = '........................................';
    $waliKelasNip = '................................';

    // 1. Try tbl_wali_kelas joined with tbl_kelas and tbl_guru
    $checkWaliTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
    if ($checkWaliTable && mysqli_num_rows($checkWaliTable) > 0) {
        $checkGuruCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'nip_guru'");
        $nipSelect = ($checkGuruCol && mysqli_num_rows($checkGuruCol) > 0)
            ? "COALESCE(NULLIF(g.nip_guru,''), g.no_induk)"
            : "g.no_induk";
            
        $qWaliInfo = mysqli_query(
            $conn,
            "SELECT g.nama_guru, {$nipSelect} AS nip_guru
             FROM tbl_wali_kelas wk
             JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
             JOIN tbl_guru g ON g.no_induk = wk.nip_wali
             WHERE k.kelas = '$kelasEsc'
             LIMIT 1"
        );
        if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
            $waliKelasNama = (string)($rowWali['nama_guru'] ?? $waliKelasNama);
            $waliKelasNip = (string)($rowWali['nip_guru'] ?? $waliKelasNip);
            echo "Match Method 1: tbl_wali_kelas + tbl_guru\n";
        }
    }
    
    // 2. Try tbl_kelas + tbl_guru directly (legacy column nip_wali in tbl_kelas)
    if ($waliKelasNama === '........................................') {
        $checkKelasCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
        if ($checkKelasCol && mysqli_num_rows($checkKelasCol) > 0) {
            $checkGuruCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'nip_guru'");
            $nipSelect = ($checkGuruCol && mysqli_num_rows($checkGuruCol) > 0)
                ? "COALESCE(NULLIF(g.nip_guru,''), g.no_induk)"
                : "g.no_induk";
                
            $qWaliInfo = mysqli_query(
                $conn,
                "SELECT g.nama_guru, {$nipSelect} AS nip_guru
                 FROM tbl_kelas k
                 JOIN tbl_guru g ON g.no_induk = k.nip_wali
                 WHERE k.kelas = '$kelasEsc'
                 LIMIT 1"
            );
            if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
                $waliKelasNama = (string)($rowWali['nama_guru'] ?? $waliKelasNama);
                $waliKelasNip = (string)($rowWali['nip_guru'] ?? $waliKelasNip);
                echo "Match Method 2: tbl_kelas + tbl_guru (legacy column)\n";
            }
        }
    }
    
    // 3. Try tbl_wali_kelas nama_wali directly
    if ($waliKelasNama === '........................................') {
        $checkWaliTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
        if ($checkWaliTable && mysqli_num_rows($checkWaliTable) > 0) {
            $checkNamaWaliCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_wali_kelas LIKE 'nama_wali'");
            if ($checkNamaWaliCol && mysqli_num_rows($checkNamaWaliCol) > 0) {
                $qWaliInfo = mysqli_query(
                    $conn,
                    "SELECT wk.nama_wali, wk.nip_wali
                     FROM tbl_wali_kelas wk
                     JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
                     WHERE k.kelas = '$kelasEsc'
                     LIMIT 1"
                );
                if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
                    if (!empty($rowWali['nama_wali'])) {
                        $waliKelasNama = $rowWali['nama_wali'];
                        if (!empty($rowWali['nip_wali'])) {
                            $waliKelasNip = $rowWali['nip_wali'];
                        }
                        echo "Match Method 3: tbl_wali_kelas nama_wali directly\n";
                    }
                }
            }
        }
    }

    // 4. Try tbl_kelas wali_kelas column directly
    if ($waliKelasNama === '........................................') {
        $checkWaliCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'wali_kelas'");
        if ($checkWaliCol && mysqli_num_rows($checkWaliCol) > 0) {
            $qWaliInfo = mysqli_query(
                $conn,
                "SELECT k.wali_kelas, k.nip_wali
                 FROM tbl_kelas k
                 WHERE k.kelas = '$kelasEsc'
                 LIMIT 1"
            );
            if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
                if (!empty($rowWali['wali_kelas']) && $rowWali['wali_kelas'] !== '0') {
                    $waliKelasNama = $rowWali['wali_kelas'];
                    if (!empty($rowWali['nip_wali']) && $rowWali['nip_wali'] !== '0') {
                        $waliKelasNip = $rowWali['nip_wali'];
                    }
                    echo "Match Method 4: tbl_kelas wali_kelas directly\n";
                }
            }
        }
    }

    echo "Result:\n";
    echo "Wali Kelas Name: $waliKelasNama\n";
    echo "Wali Kelas NIP : $waliKelasNip\n";
} else {
    echo "No classes found in tbl_kelas.\n";
}
?>
