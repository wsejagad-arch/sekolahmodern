<?php
/**
 * Debug Script untuk Cetak Jurnal
 * Melihat detail apa yang terjadi saat akses cetak-jurnal.php
 */

// Simulasi parameter yang mungkin dikirim
$_GET['guru'] = isset($_GET['guru']) ? $_GET['guru'] : '0029';
$_GET['tglAwal'] = isset($_GET['tglAwal']) ? $_GET['tglAwal'] : '2025-07-01';
$_GET['tglAkhir'] = isset($_GET['tglAkhir']) ? $_GET['tglAkhir'] : '2025-09-16';

// Simulasi session
session_start();
$_SESSION['username'] = $_GET['guru'];
$_SESSION['hak_akses'] = 1;

echo "<!DOCTYPE html><html><head><title>DEBUG CETAK JURNAL</title></head><body>";
echo "<h1>🔍 DEBUG CETAK JURNAL</h1>";
echo "<h2>Parameter yang Diterima:</h2>";
echo "<ul>";
echo "<li><strong>Guru:</strong> " . $_GET['guru'] . "</li>";
echo "<li><strong>Tanggal Awal:</strong> " . $_GET['tglAwal'] . "</li>";
echo "<li><strong>Tanggal Akhir:</strong> " . $_GET['tglAkhir'] . "</li>";
echo "<li><strong>Session Username:</strong> " . $_SESSION['username'] . "</li>";
echo "<li><strong>Session Hak Akses:</strong> " . $_SESSION['hak_akses'] . "</li>";
echo "</ul>";

// Test koneksi database
echo "<h2>🗄️ Test Koneksi Database:</h2>";

$host = 'localhost';
$user = 'root';
$password = '';
$db = 'jurnal';
$port = 3307;

$conn = mysqli_connect($host, $user, $password, $db, $port);

if (!$conn) {
    echo "<div style='color: red;'>❌ KONEKSI GAGAL: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit();
}

echo "<div style='color: green;'>✅ KONEKSI BERHASIL ke database '$db' di port $port</div>";

// Test query jadwal
$guru = $_GET['guru'];
$jadwalQuery = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
echo "<h3>📅 Query Jadwal:</h3>";
echo "<code>$jadwalQuery</code><br>";

$jadwalResult = mysqli_query($conn, $jadwalQuery);
$jadwalCount = mysqli_num_rows($jadwalResult);

echo "<div style='color: " . ($jadwalCount > 0 ? "green" : "red") . ";'>";
echo ($jadwalCount > 0 ? "✅" : "❌") . " Ditemukan $jadwalCount jadwal untuk guru $guru";
echo "</div>";

if ($jadwalCount > 0) {
    echo "<h4>Sample Data Jadwal:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID Mapel</th><th>NIP</th><th>Mata Pelajaran</th><th>Kelas</th><th>Hari</th><th>Jam Mulai</th><th>Jam Selesai</th></tr>";

    $counter = 0;
    while ($jadwal = mysqli_fetch_assoc($jadwalResult) && $counter < 5) {
        if ($jadwal) { // Check if jadwal is not false
            echo "<tr>";
            echo "<td>" . ($jadwal['id_mapel'] ?? '') . "</td>";
            echo "<td>" . ($jadwal['no_induk'] ?? '') . "</td>";
            echo "<td>" . ($jadwal['nama_mapel'] ?? '') . "</td>";
            echo "<td>" . ($jadwal['kelas'] ?? '') . "</td>";
            echo "<td>" . ($jadwal['hari'] ?? '') . "</td>";
            echo "<td>" . ($jadwal['jam_mulai'] ?? '') . "</td>";
            echo "<td>" . ($jadwal['jam_selesai'] ?? '') . "</td>";
            echo "</tr>";
            $counter++;
        } else {
            break; // Exit loop if no more results
        }
    }
    echo "</table>";
}

// Test query jurnal
$tglAwal = $_GET['tglAwal'];
$tglAkhir = $_GET['tglAkhir'];
$jurnalQuery = "SELECT COUNT(*) as total FROM tbl_materi WHERE no_induk = '$guru' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";
echo "<h3>📝 Query Jurnal:</h3>";
echo "<code>$jurnalQuery</code><br>";

$jurnalResult = mysqli_query($conn, $jurnalQuery);
$jurnalData = mysqli_fetch_assoc($jurnalResult);
$jurnalCount = $jurnalData['total'];

echo "<div style='color: " . ($jurnalCount >= 0 ? "blue" : "red") . ";'>";
echo "ℹ️ Ditemukan $jurnalCount jurnal yang sudah diisi untuk periode tersebut";
echo "</div>";

// Test logika generate jadwal
echo "<h2>🔄 Test Logika Generate Jadwal:</h2>";

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

echo "Jumlah jadwal di array: " . count($jadwalArray) . "<br>";

// Test generate untuk beberapa hari
$currentDate = strtotime($tglAwal);
$endDate = strtotime($tglAkhir);
$generatedCount = 0;
$testDays = 0;

echo "<h4>Test Generate untuk 7 hari pertama:</h4>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Tanggal</th><th>Hari (EN)</th><th>Hari (ID)</th><th>Jadwal Ditemukan</th><th>Detail</th></tr>";

while ($currentDate <= $endDate && $testDays < 7) {
    $tanggal = date('Y-m-d', $currentDate);
    $namaHariInggris = date('l', $currentDate);
    $namaHariIndonesia = namaHariIndonesia($namaHariInggris);

    $jadwalFound = 0;
    $detail = "";

    foreach ($jadwalArray as $jadwal) {
        if (strtolower($jadwal['hari']) == strtolower($namaHariIndonesia)) {
            $jadwalFound++;
            $detail .= $jadwal['nama_mapel'] . " " . $jadwal['kelas'] . " (" . $jadwal['jam_mulai'] . "-" . $jadwal['jam_selesai'] . ")<br>";
            $generatedCount++;
        }
    }

    echo "<tr>";
    echo "<td>$tanggal</td>";
    echo "<td>$namaHariInggris</td>";
    echo "<td>$namaHariIndonesia</td>";
    echo "<td style='color: " . ($jadwalFound > 0 ? "green" : "red") . ";'>$jadwalFound</td>";
    echo "<td>$detail</td>";
    echo "</tr>";

    $currentDate = strtotime('+1 day', $currentDate);
    $testDays++;
}

echo "</table>";
echo "<div style='color: " . ($generatedCount > 0 ? "green" : "red") . ";'>";
echo ($generatedCount > 0 ? "✅" : "❌") . " Total generated entries: $generatedCount";
echo "</div>";

// Test query yang sama dengan cetak-jurnal.php
echo "<h2>🔍 Test Query Lengkap (seperti di cetak-jurnal.php):</h2>";

$tglAwal = $_GET['tglAwal'];
$tglAkhir = $_GET['tglAkhir'];
$guru = $_GET['guru'];

echo "<h3>Parameter:</h3>";
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

echo "<h3>Query Jadwal:</h3>";
$jadwalQuery = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
echo "<code>$jadwalQuery</code><br>";

$jadwalResult = mysqli_query($conn, $jadwalQuery);
$jadwalArray = [];
while ($jadwal = mysqli_fetch_assoc($jadwalResult)) {
    $jadwalArray[] = $jadwal;
}

echo "<div style='color: " . (count($jadwalArray) > 0 ? "green" : "red") . ";'>";
echo (count($jadwalArray) > 0 ? "✅" : "❌") . " Ditemukan " . count($jadwalArray) . " jadwal";
echo "</div>";

echo "<h3>Query Jurnal:</h3>";
$jurnalQuery = "SELECT * FROM tbl_materi WHERE no_induk = '$guru' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";
echo "<code>$jurnalQuery</code><br>";

$jurnalResult = mysqli_query($conn, $jurnalQuery);
$jurnalArray = [];
while ($jurnal = mysqli_fetch_assoc($jurnalResult)) {
    $jurnalArray[$jurnal['date'] . '_' . $jurnal['id_mapel']] = $jurnal;
}

echo "<div style='color: blue;'>ℹ️ Ditemukan " . count($jurnalArray) . " jurnal entries</div>";

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
        }
    }

    $currentDate = strtotime('+1 day', $currentDate);
}

echo "<div style='color: " . ($generatedCount > 0 ? "green" : "red") . "; font-weight: bold;'>";
echo ($generatedCount > 0 ? "✅" : "❌") . " TOTAL DATA ARRAY: $generatedCount entries";
echo "</div>";

if ($generatedCount == 0) {
    echo "<h3 style='color: red;'>🚨 MASALAH DITEMUKAN!</h3>";
    echo "<p>Berikut kemungkinan penyebab:</p>";
    echo "<ol>";
    echo "<li>Parameter guru tidak dikirim dengan benar</li>";
    echo "<li>Tidak ada jadwal untuk guru tersebut</li>";
    echo "<li>Matching nama hari tidak sesuai</li>";
    echo "<li>Periode tanggal tidak valid</li>";
    echo "</ol>";

    echo "<h4>Debug Info:</h4>";
    echo "<ul>";
    echo "<li>Guru: $guru</li>";
    echo "<li>Jumlah jadwal di database: " . count($jadwalArray) . "</li>";
    echo "<li>Periode: $tglAwal sampai $tglAkhir</li>";
    echo "<li>Jumlah hari dalam periode: " . round((strtotime($tglAkhir) - strtotime($tglAwal)) / (60*60*24)) . " hari</li>";
    echo "</ul>";
} else {
    echo "<h3 style='color: green;'>✅ SISTEM BEKERJA DENGAN BAIK!</h3>";
    echo "<p>Data berhasil di-generate. Sistem cetak jurnal seharusnya berfungsi normal.</p>";
}

mysqli_close($conn);
echo "</body></html>";
?>