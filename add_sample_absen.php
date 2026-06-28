<?php
include "koneksi.php";

echo "=== MENAMBAHKAN SAMPLE DATA ABSEN UNTUK TEST SCROLL ===" . PHP_EOL;

// Sample data absen untuk tanggal 2024-09-15 dengan banyak siswa tidak hadir
$absenData = [
    ['05806', 'X E 5', 'Sakit'],
    ['05821', 'X E 5', 'Sakit'], 
    ['05422', 'X E 5', 'Ijin'],
    ['05263', 'X E 5', 'Sakit'],
    ['05232', 'X E 5', 'Ijin'],
    ['05801', 'X E 5', 'Alpha'],
    ['05802', 'X E 5', 'Sakit'],
    ['05803', 'X E 5', 'Ijin'],
    ['05804', 'X E 5', 'Sakit'],
    ['05805', 'X E 5', 'Alpha'],
    ['05810', 'X E 5', 'Ijin'],
    ['05815', 'X E 5', 'Sakit']
];

$tanggal = '2024-09-15';

// Hapus data lama untuk tanggal ini
mysqli_query($conn, "DELETE FROM tbl_absen WHERE tanggal = '$tanggal' AND kelas = 'X E 5'");

foreach ($absenData as $data) {
    $no_induk = $data[0];
    $kelas = $data[1];
    $status = $data[2];
    
    $query = "INSERT INTO tbl_absen (tanggal, kelas, no_induk, status) VALUES ('$tanggal', '$kelas', '$no_induk', '$status')";
    
    if (mysqli_query($conn, $query)) {
        echo "✅ Menambahkan absen: $no_induk - $status" . PHP_EOL;
    } else {
        echo "❌ Error: " . mysqli_error($conn) . PHP_EOL;
    }
}

echo PHP_EOL . "=== VERIFIKASI DATA YANG DITAMBAHKAN ===" . PHP_EOL;

$result = mysqli_query($conn, "
    SELECT s.nama_siswa, a.status 
    FROM tbl_absen a 
    LEFT JOIN tbl_siswa s ON a.no_induk = s.no_induk 
    WHERE a.tanggal = '$tanggal' AND a.kelas = 'X E 5' AND a.status != 'Hadir'
    ORDER BY s.nama_siswa
");

if ($result && mysqli_num_rows($result) > 0) {
    echo "📅 $tanggal - X E 5 (Siswa yang tidak hadir):" . PHP_EOL;
    while ($row = mysqli_fetch_assoc($result)) {
        $statusIcon = '';
        switch($row['status']) {
            case 'Sakit': $statusIcon = '🤒'; break;
            case 'Ijin': $statusIcon = '📝'; break;
            case 'Alpha': $statusIcon = '❌'; break;
            case 'Dispen': $statusIcon = '📋'; break;
        }
        echo "  $statusIcon " . $row['nama_siswa'] . " : " . $row['status'] . PHP_EOL;
    }
}

mysqli_close($conn);
?>