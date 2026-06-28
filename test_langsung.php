<?php
/**
 * Test Langsung dengan Kredensial Lokal
 * Menggunakan koneksi langsung tanpa melalui koneksi.php
 */

// Kredensial lokal langsung
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'jurnal';
$port = 3307;

echo "<!DOCTYPE html><html><head><title>TEST LANGSUNG DENGAN KREDENSIAL LOKAL</title></head><body>";
echo "<h1>🔍 TEST LANGSUNG DENGAN KREDENSIAL LOKAL</h1>";
echo "<h2>Kredensial yang Digunakan:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host:$port</li>";
echo "<li><strong>User:</strong> $user</li>";
echo "<li><strong>Password:</strong> " . (empty($password) ? '(kosong)' : '***') . "</li>";
echo "<li><strong>Database:</strong> $db</li>";
echo "</ul>";

// Test koneksi langsung
$conn = mysqli_connect($host, $user, $password, $db, $port);

if (!$conn) {
    echo "<div style='color: red; font-size: 18px;'>❌ KONEKSI GAGAL!</div>";
    echo "<div style='color: red;'>Error: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit();
}

echo "<div style='color: green; font-size: 18px;'>✅ KONEKSI BERHASIL!</div>";
echo "<div style='color: green;'>Connected to: " . mysqli_get_host_info($conn) . "</div>";

// Test query jadwal
$guru = '0029';
$jadwalQuery = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
$jadwalResult = mysqli_query($conn, $jadwalQuery);
$jadwalCount = mysqli_num_rows($jadwalResult);

echo "<h3>Query Jadwal:</h3>";
echo "<code>$jadwalQuery</code><br>";
echo "<div style='color: " . ($jadwalCount > 0 ? "green" : "red") . ";'>";
echo ($jadwalCount > 0 ? "✅" : "❌") . " Ditemukan $jadwalCount jadwal untuk guru $guru";
echo "</div>";

// Test query jurnal
$tglAwal = '2025-07-01';
$tglAkhir = '2025-09-16';
$jurnalQuery = "SELECT COUNT(*) as total FROM tbl_materi WHERE no_induk = '$guru' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";
$jurnalResult = mysqli_query($conn, $jurnalQuery);
$jurnalData = mysqli_fetch_assoc($jurnalResult);
$jurnalCount = $jurnalData['total'];

echo "<h3>Query Jurnal:</h3>";
echo "<code>$jurnalQuery</code><br>";
echo "<div style='color: blue;'>ℹ️ Ditemukan $jurnalCount jurnal entries</div>";

// Test logika generate jadwal
function namaHariIndonesia($hariInggris) {
    $hariInggris = strtolower($hariInggris);
    switch ($hariInggris) {
        case 'monday': return 'Senin';
        case 'tuesday': return 'Selasa';
        case 'wednesday': return 'Rabu';
        case 'thursday': return 'Kamis';
        case 'friday': return 'Jumat';
        case 'saturday': return 'Sabtu';
        case 'sunday': return 'Minggu';
        default: return 'Tidak diketahui';
    }
}

// Reset jadwal result
mysqli_data_seek($jadwalResult, 0);
$jadwalArray = [];
while ($jadwal = mysqli_fetch_assoc($jadwalResult)) {
    $jadwalArray[] = $jadwal;
}

echo "<h3>Generate Data Array:</h3>";
$dataArray = [];
$currentDate = strtotime($tglAwal);
$endDate = strtotime($tglAkhir);
$generatedCount = 0;

while ($currentDate <= $endDate) {
    $tanggal = date('Y-m-d', $currentDate);
    $namaHariInggris = date('l', $currentDate);
    $namaHariIndonesia = namaHariIndonesia($namaHariInggris);

    foreach ($jadwalArray as $jadwal) {
        if (strtolower($jadwal['hari']) == strtolower($namaHariIndonesia)) {
            $key = $tanggal . '_' . $jadwal['id_mapel'];

            // Cek apakah ada jurnal untuk tanggal dan mapel ini
            $jurnalCheckQuery = "SELECT * FROM tbl_materi WHERE no_induk = '$guru' AND tanggal = '$tanggal' AND id_mapel = '{$jadwal['id_mapel']}'";
            $jurnalCheckResult = mysqli_query($conn, $jurnalCheckQuery);
            $jurnalEntry = mysqli_fetch_assoc($jurnalCheckResult);

            if ($jurnalEntry) {
                $data = $jurnalEntry;
                $data['tanggal'] = $tanggal;
                $data['nama_hari'] = $namaHariIndonesia;
                $data['status'] = 'Sudah Mengisi Jurnal';
            } else {
                $data = [
                    'tanggal' => $tanggal,
                    'nama_hari' => $namaHariIndonesia,
                    'nama_mapel' => $jadwal['nama_mapel'],
                    'kelas' => $jadwal['kelas'],
                    'jam_mulai' => $jadwal['jam_mulai'],
                    'jam_selesai' => $jadwal['jam_selesai'],
                    'materi' => '-',
                    'kegiatan' => '-',
                    'absen' => '-',
                    'status' => 'Belum Mengisi Jurnal'
                ];
            }

            $dataArray[] = $data;
            $generatedCount++;
        }
    }

    $currentDate = strtotime('+1 day', $currentDate);
}

echo "<div style='color: " . ($generatedCount > 0 ? "green" : "red") . "; font-weight: bold; font-size: 18px;'>";
echo ($generatedCount > 0 ? "✅" : "❌") . " TOTAL DATA ARRAY: $generatedCount entries";
echo "</div>";

// Test kondisi yang sama dengan cetak-jurnal.php
if (empty($dataArray)) {
    echo "<div style='color: red; font-size: 18px; margin-top: 20px;'>";
    echo "<h3>🚨 KONDISI ERROR AKAN TERPICU!</h3>";
    echo "<p>Tidak ada jadwal dalam periode yang dipilih</p>";
    echo "<p>Periode: $tglAwal - $tglAkhir</p>";
    echo "</div>";
} else {
    echo "<div style='color: green; font-size: 18px; margin-top: 20px;'>";
    echo "<h3>✅ KONDISI NORMAL - DATA AKAN DITAMPILKAN!</h3>";
    echo "<p>Akan menampilkan tabel dengan $generatedCount entries</p>";
    echo "</div>";

    echo "<h3>Sample Data (5 pertama):</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>No</th><th>Tanggal</th><th>Hari</th><th>Mapel</th><th>Kelas</th><th>Status</th></tr>";
    for ($i = 0; $i < min(5, count($dataArray)); $i++) {
        $data = $dataArray[$i];
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>{$data['tanggal']}</td>";
        echo "<td>{$data['nama_hari']}</td>";
        echo "<td>{$data['nama_mapel']}</td>";
        echo "<td>{$data['kelas']}</td>";
        echo "<td>{$data['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

mysqli_close($conn);
echo "</body></html>";
?>