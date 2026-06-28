<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include "../koneksi.php";

try {
    // Query untuk mendapatkan data status kepegawaian dari tbl_guru
    $idSekolah = mt_current_school_id();
    $query = "SELECT 
                status_kepegawaian,
                COUNT(*) as jumlah
              FROM tbl_guru 
              WHERE id_sekolah = $idSekolah AND (status != 'Non-Aktif' OR status IS NULL)
              GROUP BY status_kepegawaian
              ORDER BY jumlah DESC";
    
    $result = mysqli_query($conn, $query);
    
    $values = [0, 0, 0, 0]; // [ASN/PNS, CPNS, GTT/PTT, Non-ASN/Honorer]
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status = strtoupper(trim($row['status_kepegawaian']));
            $jumlah = (int)$row['jumlah'];
            
            // Group status kepegawaian berdasarkan data real
            if (strpos($status, 'ASN') !== false || strpos($status, 'PNS') !== false) {
                $values[0] += $jumlah; // ASN/PNS
            } elseif (strpos($status, 'CPNS') !== false) {
                $values[1] += $jumlah; // CPNS
            } elseif (strpos($status, 'GTT') !== false || strpos($status, 'PTT') !== false) {
                $values[2] += $jumlah; // GTT/PTT
            } else {
                $values[3] += $jumlah; // Non-ASN/Honorer/Lainnya
            }
        }
    }
    
    $response = [
        'success' => true,
        'labels' => ['ASN/PNS', 'CPNS', 'GTT/PTT', 'Non-ASN'],
        'values' => $values,
        'total' => array_sum($values)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'labels' => ['ASN/PNS', 'CPNS', 'GTT/PTT', 'Non-ASN'],
        'values' => [0, 0, 0, 0] // Return zero counts for clean new school
    ]);
}

mysqli_close($conn);
?>