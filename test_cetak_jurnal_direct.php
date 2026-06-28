<?php
/**
 * Test Akses Langsung ke cetak-jurnal.php
 * Menggunakan parameter yang sama seperti user
 */

// Simulasi parameter GET yang dikirim user
$_GET['guru'] = '0029';
$_GET['tglAwal'] = '2025-07-01';
$_GET['tglAkhir'] = '2025-09-16';

// Simulasi session
session_start();
$_SESSION['username'] = '0029';
$_SESSION['hak_akses'] = 1;

echo "<!DOCTYPE html><html><head><title>TEST CETAK JURNAL LANGSUNG</title></head><body>";
echo "<h1>🧪 TEST AKSES CETAK-JURNAL.PHP</h1>";
echo "<h2>Parameter yang Disimulasikan:</h2>";
echo "<ul>";
echo "<li><strong>Guru:</strong> " . $_GET['guru'] . "</li>";
echo "<li><strong>Tanggal Awal:</strong> " . $_GET['tglAwal'] . "</li>";
echo "<li><strong>Tanggal Akhir:</strong> " . $_GET['tglAkhir'] . "</li>";
echo "<li><strong>Session Username:</strong> " . $_SESSION['username'] . "</li>";
echo "<li><strong>Session Hak Akses:</strong> " . $_SESSION['hak_akses'] . "</li>";
echo "</ul>";

// Test koneksi database
include "koneksi.php";

if (!$conn) {
    echo "<div style='color: red;'>❌ KONEKSI DATABASE GAGAL</div>";
    echo "</body></html>";
    exit();
}

echo "<div style='color: green;'>✅ Koneksi database berhasil</div>";

// Simulasi logika yang sama dengan cetak-jurnal.php
$tglAwal = isset($_GET['tglAwal']) ? $_GET['tglAwal'] : '';
$tglAkhir = isset($_GET['tglAkhir']) ? $_GET['tglAkhir'] : '';
$guru = isset($_GET['guru']) ? $_GET['guru'] : '';

echo "<h3>Parameter yang diproses:</h3>";
echo "<ul>";
echo "<li>tglAwal: $tglAwal</li>";
echo "<li>tglAkhir: $tglAkhir</li>";
echo "<li>guru: $guru</li>";
echo "</ul>";

// Jika tidak ada parameter tanggal, set default periode tahun ajaran
if (empty($tglAwal) || empty($tglAkhir)) {
    $tahunAjaran = date('Y');
    $tglAwal = $tahunAjaran . '-07-01';
    $tglAkhir = ($tahunAjaran + 1) . '-06-30';
    echo "<div style='color: blue;'>ℹ️ Menggunakan default periode: $tglAwal sampai $tglAkhir</div>";
}

// Jika tidak ada guru yang dipilih, ambil dari session
if (empty($guru)) {
    $guru = $_SESSION['username'] ?? '';
    echo "<div style='color: blue;'>ℹ️ Menggunakan guru dari session: $guru</div>";
}

echo "<h3>Final Parameter:</h3>";
echo "<ul>";
echo "<li>tglAwal: $tglAwal</li>";
echo "<li>tglAkhir: $tglAkhir</li>";
echo "<li>guru: $guru</li>";
echo "</ul>";

// Ambil data jadwal mengajar dari tbl_mapel_ampu
$jadwalQuery = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
$jadwalResult = mysqli_query($conn, $jadwalQuery);
$jadwalArray = [];
while ($jadwal = mysqli_fetch_assoc($jadwalResult)) {
    $jadwalArray[] = $jadwal;
}

echo "<h3>Query Jadwal:</h3>";
echo "<code>$jadwalQuery</code><br>";
echo "<div style='color: " . (count($jadwalArray) > 0 ? "green" : "red") . ";'>";
echo (count($jadwalArray) > 0 ? "✅" : "❌") . " Ditemukan " . count($jadwalArray) . " jadwal";
echo "</div>";

$jurnalQuery = "SELECT *, tanggal AS date FROM tbl_materi WHERE no_induk = '$guru' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";
$jurnalResult = mysqli_query($conn, $jurnalQuery);
$jurnalArray = [];
while ($jurnal = mysqli_fetch_assoc($jurnalResult)) {
    $jurnalArray[$jurnal['date'] . '_' . $jurnal['id_mapel']] = $jurnal;
}

echo "<h3>Query Jurnal:</h3>";
echo "<code>$jurnalQuery</code><br>";
echo "<div style='color: blue;'>ℹ️ Ditemukan " . count($jurnalArray) . " jurnal entries</div>";

// Generate data untuk ditampilkan
$dataArray = [];
$currentDate = strtotime($tglAwal);
$endDate = strtotime($tglAkhir);

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

$generatedCount = 0;
$daysProcessed = 0;

echo "<h3>Generate Data Array:</h3>";
echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
echo "<tr><th>Tanggal</th><th>Hari</th><th>Jadwal Hari Ini</th><th>Generated</th></tr>";

while ($currentDate <= $endDate) {
    $tanggal = date('Y-m-d', $currentDate);
    $namaHariInggris = date('l', $currentDate);
    $namaHariIndonesia = namaHariIndonesia($namaHariInggris);

    $jadwalHariIni = 0;
    $generatedHariIni = 0;

    // Untuk setiap jadwal di hari tersebut
    foreach ($jadwalArray as $jadwal) {
        if (strtolower($jadwal['hari']) == strtolower($namaHariIndonesia)) {
            $jadwalHariIni++;
            $key = $tanggal . '_' . $jadwal['id_mapel'];

            if (isset($jurnalArray[$key])) {
                $data = $jurnalArray[$key];
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
            $generatedHariIni++;
        }
    }

    echo "<tr>";
    echo "<td>$tanggal</td>";
    echo "<td>$namaHariIndonesia</td>";
    echo "<td>$jadwalHariIni</td>";
    echo "<td>$generatedHariIni</td>";
    echo "</tr>";

    $currentDate = strtotime('+1 day', $currentDate);
    $daysProcessed++;

    // Limit untuk testing
    if ($daysProcessed >= 10) break;
}

echo "</table>";

echo "<div style='color: " . ($generatedCount > 0 ? "green" : "red") . "; font-weight: bold; font-size: 18px;'>";
echo ($generatedCount > 0 ? "✅" : "❌") . " TOTAL DATA ARRAY: $generatedCount entries";
echo "</div>";

echo "<div style='color: blue;'>ℹ️ Diproses $daysProcessed hari dari periode $tglAwal sampai $tglAkhir</div>";

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
}

echo "<h3>Sample Data Array (5 pertama):</h3>";
echo "<pre>";
for ($i = 0; $i < min(5, count($dataArray)); $i++) {
    print_r($dataArray[$i]);
    echo "\n---\n";
}
echo "</pre>";

mysqli_close($conn);
echo "</body></html>";
?>