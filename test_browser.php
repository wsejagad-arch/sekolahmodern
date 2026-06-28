<?php
/**
 * Test Cetak Jurnal via Browser
 * Script ini akan mengakses cetak-jurnal.php dengan parameter yang sama
 */

// Simulasi parameter GET
$_GET['guru'] = '0029';
$_GET['tglAwal'] = '2025-07-01';
$_GET['tglAkhir'] = '2025-09-16';

// Simulasi session
session_start();
$_SESSION['username'] = '0029';
$_SESSION['hak_akses'] = 1;

echo "<!DOCTYPE html><html><head><title>TEST CETAK JURNAL VIA BROWSER</title></head><body>";
echo "<h1>🌐 TEST CETAK JURNAL VIA BROWSER</h1>";
echo "<h2>Parameter yang Akan Dikirim:</h2>";
echo "<ul>";
echo "<li><strong>Guru:</strong> {$_GET['guru']}</li>";
echo "<li><strong>Tanggal Awal:</strong> {$_GET['tglAwal']}</li>";
echo "<li><strong>Tanggal Akhir:</strong> {$_GET['tglAkhir']}</li>";
echo "<li><strong>Session Username:</strong> {$_SESSION['username']}</li>";
echo "</ul>";

// Test koneksi database terlebih dahulu
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'jurnal';
$port = 3307;

$conn = mysqli_connect($host, $user, $password, $db, $port);
if (!$conn) {
    echo "<div style='color: red; font-size: 18px;'>❌ KONEKSI DATABASE GAGAL</div>";
    echo "<div style='color: red;'>Error: " . mysqli_connect_error() . "</div>";
    echo "</body></html>";
    exit();
}

echo "<div style='color: green;'>✅ Koneksi database berhasil</div>";

// Test query yang sama dengan cetak-jurnal.php
$guru = $_GET['guru'];
$tglAwal = $_GET['tglAwal'];
$tglAkhir = $_GET['tglAkhir'];

$jadwalQuery = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
$jadwalResult = mysqli_query($conn, $jadwalQuery);
$jadwalArray = [];
while ($jadwal = mysqli_fetch_assoc($jadwalResult)) {
    $jadwalArray[] = $jadwal;
}

$jurnalQuery = "SELECT *, tanggal AS date FROM tbl_materi WHERE no_induk = '$guru' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";
$jurnalResult = mysqli_query($conn, $jurnalQuery);
$jurnalArray = [];
while ($jurnal = mysqli_fetch_assoc($jurnalResult)) {
    $jurnalArray[$jurnal['date'] . '_' . $jurnal['id_mapel']] = $jurnal;
}

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

echo "<h3>Hasil Generate Data:</h3>";
echo "<div style='color: " . ($generatedCount > 0 ? "green" : "red") . "; font-weight: bold; font-size: 18px;'>";
echo ($generatedCount > 0 ? "✅" : "❌") . " TOTAL DATA: $generatedCount entries";
echo "</div>";

if (empty($dataArray)) {
    echo "<div style='color: red; font-size: 18px; margin: 20px 0;'>";
    echo "<h3>🚨 MASALAH: Data array kosong!</h3>";
    echo "<p>Ini akan menyebabkan error 'Tidak ada jadwal dalam periode yang dipilih'</p>";
    echo "</div>";
} else {
    echo "<div style='color: green; font-size: 18px; margin: 20px 0;'>";
    echo "<h3>✅ SOLUSI: Data array terisi!</h3>";
    echo "<p>Cetak jurnal akan menampilkan tabel dengan data</p>";
    echo "</div>";

    echo "<h3>URL untuk Test:</h3>";
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; font-family: monospace;'>";
    echo "http://localhost:8000/cetak-jurnal.php?guru=0029&tglAwal=2025-07-01&tglAkhir=2025-09-16";
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