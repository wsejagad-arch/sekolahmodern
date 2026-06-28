<?php
include "koneksi.php";

echo "=== CHECKING tbl_kehadiran DATA ===" . PHP_EOL;

// Check all data in tbl_kehadiran
$result = mysqli_query($conn, "SELECT * FROM tbl_kehadiran ORDER BY tanggal DESC");
if ($result) {
    echo "Total records: " . mysqli_num_rows($result) . PHP_EOL;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Date: " . $row['tanggal'] . " | Status: " . $row['status_kehadiran'] . " | Guru: " . $row['nama_guru'] . " | Kelas: " . $row['kelas'] . PHP_EOL;
    }
} else {
    echo "Error: " . mysqli_error($conn) . PHP_EOL;
}

echo PHP_EOL . "=== ADDING SAMPLE DATA FOR CURRENT MONTH ===" . PHP_EOL;

// Add more recent sample data for September 2024
$sampleData = [
    ['2024-09-01', 'YOSI KUSUMAWARDANI, S. Pd.', 'Matematika', 'X E 1'],
    ['2024-09-02', 'AHMAD RAHMAN, S. Pd.', 'Bahasa Indonesia', 'X E 2'],
    ['2024-09-03', 'SITI NURHASANAH, S. Pd.', 'Fisika', 'XI F 1'],
    ['2024-09-05', 'BAMBANG SUHARTO, S. Pd.', 'Kimia', 'XI F 2'],
    ['2024-09-10', 'MAYA SARI, S. Pd.', 'Biologi', 'XII F 1'],
    ['2024-09-15', 'DEDI KURNIAWAN, S. Pd.', 'Sejarah', 'X E 3'],
    ['2024-09-18', 'RINA WIJAYA, S. Pd.', 'Geografi', 'XI F 3'],
    ['2024-09-20', 'AGUS SANTOSO, S. Pd.', 'Ekonomi', 'XII F 2'],
    ['2024-09-22', 'LINA MARLINA, S. Pd.', 'Sosiologi', 'XII F 3'],
    ['2024-09-25', 'FERRY HIDAYAT, S. Pd.', 'PKN', 'X E 4'],
    ['2024-09-27', 'NOVI ANDRIANI, S. Pd.', 'Bahasa Inggris', 'XI F 4'],
    ['2024-09-28', 'RAHMAT HIDAYAT, S. Pd.', 'Olahraga', 'XII F 4'],
];

foreach ($sampleData as $data) {
    $checkQuery = "SELECT COUNT(*) as count_data FROM tbl_kehadiran WHERE tanggal = '{$data[0]}' AND nama_guru = '{$data[1]}'";
    $checkResult = mysqli_query($conn, $checkQuery);
    $exists = mysqli_fetch_assoc($checkResult)['count_data'];
    
    if ($exists == 0) {
        $insertQuery = "INSERT INTO tbl_kehadiran (tanggal, no_induk, nama_guru, nama_mapel, kelas, nama_ketua_kelas, status_kehadiran, catatan) 
                        VALUES ('{$data[0]}', 'G" . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT) . "', '{$data[1]}', '{$data[2]}', '{$data[3]}', 'Ketua Kelas', 1, 'Hadir mengajar')";
        if (mysqli_query($conn, $insertQuery)) {
            echo "Added sample data: {$data[0]} - {$data[1]} - {$data[3]}" . PHP_EOL;
        } else {
            echo "Error adding data: " . mysqli_error($conn) . PHP_EOL;
        }
    }
}

echo PHP_EOL . "=== TESTING MONTHLY QUERY AFTER DATA ADDITION ===" . PHP_EOL;

// Test monthly query again
$query = "SELECT 
            MONTH(tanggal) as bulan,
            YEAR(tanggal) as tahun,
            COUNT(*) as jumlah_aktivitas
          FROM tbl_kehadiran 
          WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          AND status_kehadiran = 1
          GROUP BY YEAR(tanggal), MONTH(tanggal)
          ORDER BY tahun ASC, bulan ASC";

$result = mysqli_query($conn, $query);
if ($result) {
    $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'];
    while ($row = mysqli_fetch_assoc($result)) {
        $monthName = $monthNames[$row['bulan']];
        echo "Year: " . $row['tahun'] . " | Month: " . $monthName . " (" . $row['bulan'] . ") | Count: " . $row['jumlah_aktivitas'] . PHP_EOL;
    }
} else {
    echo "Monthly query error: " . mysqli_error($conn) . PHP_EOL;
}

mysqli_close($conn);
?>