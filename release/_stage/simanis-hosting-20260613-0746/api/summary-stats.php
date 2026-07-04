<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include "../koneksi.php";

try {
    $stats = [];
    $idSekolah = mt_current_school_id();
    
    // 1. Total jurnal minggu ini (dari tbl_kehadiran)
    $query1 = "SELECT COUNT(*) as total 
               FROM tbl_kehadiran 
               WHERE id_sekolah = $idSekolah AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND status_kehadiran = 1";
    $result1 = mysqli_query($conn, $query1);
    $stats['totalJurnalMingguIni'] = $result1 ? mysqli_fetch_assoc($result1)['total'] : 0;
    
    // 2. Rata-rata kehadiran bulan ini
    $query2 = "SELECT 
                COUNT(*) as total_records,
                SUM(status_kehadiran) as total_hadir
               FROM tbl_kehadiran 
               WHERE id_sekolah = $idSekolah AND MONTH(tanggal) = MONTH(CURDATE()) 
               AND YEAR(tanggal) = YEAR(CURDATE())";
    $result2 = mysqli_query($conn, $query2);
    if ($result2) {
        $row2 = mysqli_fetch_assoc($result2);
        $totalRecords = (int)$row2['total_records'];
        $totalHadir = (int)$row2['total_hadir'];
        $stats['rataKehadiranBulanIni'] = $totalRecords > 0 ? round(($totalHadir / $totalRecords) * 100, 1) : 0;
    } else {
        $stats['rataKehadiranBulanIni'] = 0;
    }
    
    // 3. Guru aktif mengajar (dari tbl_guru)
    $query3 = "SELECT COUNT(*) as total 
               FROM tbl_guru 
               WHERE id_sekolah = $idSekolah AND (status = 'Aktif' OR status IS NULL)";
    $result3 = mysqli_query($conn, $query3);
    $stats['guruAktifMengajar'] = $result3 ? mysqli_fetch_assoc($result3)['total'] : 0;
    
    // 4. Kelengkapan data bulan ini (berdasarkan aktivitas guru)
    $query4 = "SELECT 
                COUNT(DISTINCT no_induk) as guru_aktif
               FROM tbl_kehadiran 
               WHERE id_sekolah = $idSekolah AND MONTH(tanggal) = MONTH(CURDATE()) 
               AND YEAR(tanggal) = YEAR(CURDATE())
               AND status_kehadiran = 1";
    $result4 = mysqli_query($conn, $query4);
    
    if ($result4) {
        $row4 = mysqli_fetch_assoc($result4);
        $guruAktif = (int)$row4['guru_aktif'];
        $totalGuru = (int)$stats['guruAktifMengajar'];
        $stats['kelengkapanDataBulanIni'] = $totalGuru > 0 ? round(($guruAktif / $totalGuru) * 100, 1) : 0;
    } else {
        $stats['kelengkapanDataBulanIni'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [
            'totalJurnalMingguIni' => 0,
            'rataKehadiranBulanIni' => 0,
            'guruAktifMengajar' => 0,
            'kelengkapanDataBulanIni' => 0
        ]
    ]);
}

mysqli_close($conn);
?>