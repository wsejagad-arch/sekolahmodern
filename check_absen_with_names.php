<?php
include "koneksi.php";

echo "=== DATA ABSEN SISWA DENGAN NAMA ===" . PHP_EOL;

$result = mysqli_query($conn, "
    SELECT a.tanggal, a.kelas, s.nama_siswa, a.status 
    FROM tbl_absen a 
    LEFT JOIN tbl_siswa s ON a.no_induk = s.no_induk 
    WHERE a.tanggal >= '2024-09-01' 
    ORDER BY a.tanggal DESC, a.kelas, s.nama_siswa 
    LIMIT 20
");

if ($result && mysqli_num_rows($result) > 0) {
    $currentDate = '';
    $currentClass = '';
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($currentDate != $row['tanggal'] || $currentClass != $row['kelas']) {
            if ($currentDate != '') echo PHP_EOL;
            echo "📅 " . $row['tanggal'] . " - " . $row['kelas'] . ":" . PHP_EOL;
            $currentDate = $row['tanggal'];
            $currentClass = $row['kelas'];
        }
        
        $statusIcon = '';
        switch($row['status']) {
            case 'Sakit': $statusIcon = '🤒'; break;
            case 'Ijin': $statusIcon = '📝'; break;
            case 'Alpha': $statusIcon = '❌'; break;
            case 'Dispen': $statusIcon = '📋'; break;
            default: $statusIcon = '✅'; break;
        }
        
        echo "  $statusIcon " . $row['nama_siswa'] . " : " . $row['status'] . PHP_EOL;
    }
} else {
    echo "Tidak ada data absen siswa yang ditemukan." . PHP_EOL;
}

mysqli_close($conn);
?>