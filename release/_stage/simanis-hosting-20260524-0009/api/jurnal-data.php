<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include "../koneksi.php";

$period = $_GET['period'] ?? 'monthly';

try {
    $response = [
        'success' => true,
        'period' => $period
    ];
    
    if ($period === 'weekly') {
        // Data aktivitas guru mingguan dari tbl_kehadiran (7 hari terakhir)
        $idSekolah = mt_current_school_id();
        $query = "SELECT 
                    DATE(tanggal) as tanggal,
                    COUNT(*) as jumlah_aktivitas
                  FROM tbl_kehadiran 
                  WHERE id_sekolah = $idSekolah AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  AND status_kehadiran = 1
                  GROUP BY DATE(tanggal)
                  ORDER BY tanggal ASC";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        $labels = [];
        
        // Generate labels for last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime($date));
            $labels[] = $dayName;
            $data[] = 0; // Default value
        }
        
        // Fill actual data
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dayIndex = 6 - (strtotime('today') - strtotime($row['tanggal'])) / 86400;
                if ($dayIndex >= 0 && $dayIndex < 7) {
                    $data[$dayIndex] = (int)$row['jumlah_aktivitas'];
                }
            }
        }
        
        $response['labels'] = $labels;
        $response['data'] = $data;
        $response['target'] = array_fill(0, 7, 8); // Target 8 aktivitas per hari
        
    } else if ($period === 'monthly') {
        // Data aktivitas guru bulanan dari tbl_kehadiran (12 bulan terakhir)
        $idSekolah = mt_current_school_id();
        $query = "SELECT 
                    MONTH(tanggal) as bulan,
                    YEAR(tanggal) as tahun,
                    COUNT(*) as jumlah_aktivitas
                  FROM tbl_kehadiran 
                  WHERE id_sekolah = $idSekolah AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  AND status_kehadiran = 1
                  GROUP BY YEAR(tanggal), MONTH(tanggal)
                  ORDER BY tahun ASC, bulan ASC";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'];
        
        // Initialize with zeros
        $monthlyData = array_fill(0, 12, 0);
        
        // Fill actual data
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $monthIndex = (int)$row['bulan'] - 1;
                if ($monthIndex >= 0 && $monthIndex < 12) {
                    $monthlyData[$monthIndex] = (int)$row['jumlah_aktivitas'];
                }
            }
        }
        
        $response['labels'] = $labels;
        $response['data'] = $monthlyData;
        $response['target'] = array_fill(0, 12, 50); // Target 50 aktivitas per bulan
        
    } else { // yearly
        // Data aktivitas guru tahunan dari tbl_kehadiran
        $idSekolah = mt_current_school_id();
        $query = "SELECT 
                    YEAR(tanggal) as tahun,
                    COUNT(*) as jumlah_aktivitas
                  FROM tbl_kehadiran 
                  WHERE id_sekolah = $idSekolah AND status_kehadiran = 1
                  GROUP BY YEAR(tanggal)
                  ORDER BY tahun ASC";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        $labels = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['tahun'];
                $data[] = (int)$row['jumlah_aktivitas'];
            }
        }
        
        // If no data, create dummy data
        if (empty($data)) {
            $currentYear = date('Y');
            $labels = [$currentYear - 3, $currentYear - 2, $currentYear - 1, $currentYear];
            $data = [0, 0, 0, 0];
        }
        
        $response['labels'] = $labels;
        $response['data'] = $data;
        $response['target'] = array_fill(0, count($data), 600); // Target 600 aktivitas per tahun
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Fallback data
    $fallbackData = [
        'weekly' => [
            'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            'data' => [12, 19, 15, 25, 22, 18],
            'target' => [15, 20, 18, 25, 25, 20]
        ],
        'monthly' => [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'],
            'data' => [85, 92, 78, 95, 88, 91, 96, 89, 93, 87, 90, 94],
            'target' => [90, 90, 90, 90, 90, 90, 90, 90, 90, 90, 90, 90]
        ],
        'yearly' => [
            'labels' => ['2021', '2022', '2023', '2024'],
            'data' => [1250, 1420, 1380, 1560],
            'target' => [1300, 1400, 1500, 1600]
        ]
    ];
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'period' => $period,
        ...$fallbackData[$period]
    ]);
}

mysqli_close($conn);
?>