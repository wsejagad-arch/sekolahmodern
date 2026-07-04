<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); 
if (!isset($_SESSION["no_induk"])) { header("location: ../../index.php?haruslogin"); exit; }
if($_SESSION['hak_akses'] != 2) { echo '<script>window.location="../../404.html";</script>'; exit; }
require_once('../../koneksi.php');

// Check if vendor autoload exists
if (!file_exists('../../vendor/autoload.php')) {
    die('Vendor autoload tidak ditemukan. Jalankan composer install terlebih dahulu.');
}

require_once('../../vendor/autoload.php');

// Test TCPDF loading
try {
    if (!class_exists('TCPDF')) {
        throw new Exception('TCPDF class tidak ditemukan. Pastikan library terinstall dengan benar.');
    }
} catch (Exception $e) {
    die('Error loading TCPDF: ' . $e->getMessage());
}

$nipguru = $_SESSION['no_induk'];
$nis = mysqli_real_escape_string($conn, $_GET['nis'] ?? '');
$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? '');
$idmapel = (int)($_GET['idmapel'] ?? 0);
// Decode URL parameter untuk kelas
$kelas_raw = $_GET['kelas'] ?? '';
$kelas = mysqli_real_escape_string($conn, urldecode($kelas_raw));

if ($nis === '' || $tanggal === '' || $idmapel === 0) {
    die('Parameter tidak lengkap');
}

// Ambil data siswa
$qSiswa = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='".$nis."' LIMIT 1");
$siswa = mysqli_fetch_assoc($qSiswa);
if (!$siswa) die('Siswa tidak ditemukan');

// Ambil data items penilaian
$qItems = mysqli_query($conn, "SELECT * FROM tbl_penilaian_item WHERE tanggal='".$tanggal."' AND id_mapel=".$idmapel." AND no_induk_guru='".$nipguru."' ORDER BY id ASC");
$items = [];
while ($it = mysqli_fetch_assoc($qItems)) {
    $items[] = $it;
}

if (count($items) === 0) die('Tidak ada data penilaian');

// Ambil nilai siswa
$nilaiMap = [];
$ids = array_map(function($x){ return (int)$x['id']; }, $items);
$idStr = implode(',', $ids);
$qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_item IN (".$idStr.") AND no_induk_siswa='".$nis."'");
while ($nv = mysqli_fetch_assoc($qNil)) {
    $nilaiMap[$nv['id_item']] = $nv['nilai'];
}

// Ambil nama mapel
$mapelNama = $items[0]['mapel'] ?? 'Mata Pelajaran';

// Hitung rata-rata
$uhSum = 0; $uhCnt = 0; $asSum = 0; $asCnt = 0;
foreach ($items as $it) {
    $val = $nilaiMap[$it['id']] ?? '';
    if ($val !== '' && is_numeric($val)) {
        $num = (float)$val;
        $kode = strtoupper($it['kode_penilaian']);
        if (strpos($kode, 'UH') === 0) { $uhSum += $num; $uhCnt++; }
        if ($kode === 'ASAS' || $kode === 'ASAT') { $asSum += $num; $asCnt++; }
    }
}
$avgUH = $uhCnt ? round($uhSum / $uhCnt, 2) : '-';
$avgAS = $asCnt ? round($asSum / $asCnt, 2) : '-';

// Generate HTML untuk PDF
$html = '
<style>
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
    .header h2 { margin: 5px 0; }
    .info-table { width: 100%; margin-bottom: 20px; }
    .info-table td { padding: 5px; }
    .nilai-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .nilai-table th, .nilai-table td { border: 1px solid #333; padding: 8px; text-align: left; }
    .nilai-table th { background-color: #f0f0f0; font-weight: bold; }
    .text-center { text-align: center; }
    .summary { margin-top: 20px; }
    .summary-box { border: 2px solid #333; padding: 10px; margin-bottom: 10px; }
</style>
    
    <div class="header">
        <h2>LAPORAN NILAI SISWA</h2>
        <h3>'.htmlspecialchars($mapelNama).'</h3>
    </div>
    
    <table class="info-table">
        <tr>
            <td width="150"><strong>Nama Siswa</strong></td>
            <td>: '.htmlspecialchars($siswa['nama_siswa']).'</td>
        </tr>
        <tr>
            <td><strong>NIS</strong></td>
            <td>: '.htmlspecialchars($siswa['no_induk']).'</td>
        </tr>
        <tr>
            <td><strong>Kelas</strong></td>
            <td>: '.htmlspecialchars($kelas).'</td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>: '.date('d/m/Y', strtotime($tanggal)).'</td>
        </tr>
    </table>
    
    <table class="nilai-table">
        <thead>
            <tr>
                <th width="40" class="text-center">No</th>
                <th>Jenis Penilaian</th>
                <th>Materi</th>
                <th width="80" class="text-center">Nilai</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
foreach ($items as $it) {
    $val = $nilaiMap[$it['id']] ?? '-';
    $html .= '<tr>
                <td class="text-center">'.$no.'</td>
                <td>'.htmlspecialchars($it['kode_penilaian']).'</td>
                <td>'.htmlspecialchars($it['materi']).'</td>
                <td class="text-center">'.htmlspecialchars($val).'</td>
              </tr>';
    $no++;
}

$html .= '
        </tbody>
    </table>
    
    <div class="summary">
        <div class="summary-box">
            <strong>Rata-rata UH:</strong> '.htmlspecialchars($avgUH).'
        </div>
        <div class="summary-box">
            <strong>Rata-rata ASAS/ASAT:</strong> '.htmlspecialchars($avgAS).'
        </div>
    </div>
    
    <div style="margin-top: 40px; font-size: 10px; color: #666;">
        <p>Dicetak pada: '.date('d/m/Y H:i:s').'</p>
        <p>Guru: '.htmlspecialchars($_SESSION['nama'] ?? '').'</p>
    </div>';

// Generate PDF
try {
    // Create new PDF document
    $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SI Jurnal');
    $pdf->SetAuthor($_SESSION['nama'] ?? 'Guru');
    $pdf->SetTitle('Laporan Nilai Siswa');
    $pdf->SetSubject('Nilai Siswa');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'LAPORAN NILAI SISWA', htmlspecialchars($mapelNama));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(10, 25, 10);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Create HTML content
    $html = '
    <style>
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 5px 0; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .nilai-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .nilai-table th, .nilai-table td { border: 1px solid #333; padding: 8px; text-align: left; }
        .nilai-table th { background-color: #f0f0f0; font-weight: bold; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; }
        .summary-box { border: 2px solid #333; padding: 10px; margin-bottom: 10px; }
    </style>
    
    <div class="header">
        <h2>LAPORAN NILAI SISWA</h2>
        <h3>' . htmlspecialchars($mapelNama) . '</h3>
    </div>
    
    <table class="info-table">
        <tr>
            <td width="150"><strong>Nama Siswa</strong></td>
            <td>: ' . htmlspecialchars($siswa['nama_siswa']) . '</td>
        </tr>
        <tr>
            <td><strong>NIS</strong></td>
            <td>: ' . htmlspecialchars($siswa['no_induk']) . '</td>
        </tr>
        <tr>
            <td><strong>Kelas</strong></td>
            <td>: ' . htmlspecialchars($kelas) . '</td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>: ' . date('d/m/Y', strtotime($tanggal)) . '</td>
        </tr>
    </table>
    
    <table class="nilai-table">
        <thead>
            <tr>
                <th width="40" class="text-center">No</th>
                <th>Jenis Penilaian</th>
                <th>Materi</th>
                <th width="80" class="text-center">Nilai</th>
            </tr>
        </thead>
        <tbody>';

    $no = 1;
    foreach ($items as $it) {
        $val = $nilaiMap[$it['id']] ?? '-';
        $html .= '<tr>
                    <td class="text-center">' . $no . '</td>
                    <td>' . htmlspecialchars($it['kode_penilaian']) . '</td>
                    <td>' . htmlspecialchars($it['materi']) . '</td>
                    <td class="text-center">' . htmlspecialchars($val) . '</td>
                  </tr>';
        $no++;
    }

    $html .= '
        </tbody>
    </table>
    
    <div class="summary">
        <div class="summary-box">
            <strong>Rata-rata UH:</strong> ' . htmlspecialchars($avgUH) . '
        </div>
        <div class="summary-box">
            <strong>Rata-rata ASAS/ASAT:</strong> ' . htmlspecialchars($avgAS) . '
        </div>
    </div>
    
    <div style="margin-top: 40px; font-size: 10px; color: #666;">
        <p>Dicetak pada: ' . date('d/m/Y H:i:s') . '</p>
        <p>Guru: ' . htmlspecialchars($_SESSION['nama'] ?? '') . '</p>
    </div>';
    
    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $filename = 'Nilai_' . preg_replace('/[^a-zA-Z0-9]/', '_', $siswa['nama_siswa']) . '_' . date('Ymd', strtotime($tanggal)) . '.pdf';
    
    // Close and output PDF document
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    echo '<h1>Error: ' . $e->getMessage() . '</h1>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
    exit;
}
exit;
