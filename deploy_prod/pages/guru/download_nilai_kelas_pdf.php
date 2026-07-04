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
$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? '');
$idmapel = (int)($_GET['idmapel'] ?? 0);
// Decode URL parameter untuk kelas
$kelas_raw = $_GET['kelas'] ?? '';
$kelas = mysqli_real_escape_string($conn, urldecode($kelas_raw));

if ($tanggal === '' || $idmapel === 0 || $kelas === '') {
    die('Parameter tidak lengkap');
}

// Ambil data items penilaian
$qItems = mysqli_query($conn, "SELECT * FROM tbl_penilaian_item WHERE tanggal='".$tanggal."' AND id_mapel=".$idmapel." AND no_induk_guru='".$nipguru."' AND kelas='".$kelas."' ORDER BY id ASC");
$items = [];
while ($it = mysqli_fetch_assoc($qItems)) {
    $items[] = $it;
}

if (count($items) === 0) die('Tidak ada data penilaian');

$mapelNama = $items[0]['mapel'] ?? 'Mata Pelajaran';

// Ambil semua siswa di kelas ini
$qSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='".$kelas."' AND status='Aktif' ORDER BY nama_siswa ASC");
$siswaList = [];
while ($s = mysqli_fetch_assoc($qSiswa)) {
    $siswaList[] = $s;
}

if (count($siswaList) === 0) die('Tidak ada siswa di kelas ini');

// Ambil semua nilai
$nilaiMap = [];
$ids = array_map(function($x){ return (int)$x['id']; }, $items);
$idStr = implode(',', $ids);
$qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_item IN (".$idStr.")");
while ($nv = mysqli_fetch_assoc($qNil)) {
    $nilaiMap[$nv['id_item']][$nv['no_induk_siswa']] = $nv['nilai'];
}

// Generate HTML untuk PDF
$html = '
<style>
    .info { margin-bottom: 10px; font-size: 11px; }
    .nilai-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9px; }
    .nilai-table th, .nilai-table td { border: 1px solid #333; padding: 4px; text-align: left; }
    .nilai-table th { background-color: #e0e0e0; font-weight: bold; }
    .text-center { text-align: center; }
    .footer { margin-top: 15px; font-size: 8px; color: #666; }
</style>

<div class="info">
    <strong>Tanggal:</strong> ' . date('d/m/Y', strtotime($tanggal)) . ' &nbsp;&nbsp;&nbsp;
    <strong>Guru:</strong> ' . htmlspecialchars($_SESSION['nama'] ?? '') . '
</div>

<table class="nilai-table">
    <thead>
        <tr>
            <th width="30" class="text-center">No</th>
            <th>Nama Siswa</th>
            <th width="80">NIS</th>';

// Header kolom nilai
foreach ($items as $it) {
    $html .= '<th class="text-center" width="40">'.htmlspecialchars($it['kode_penilaian']).'</th>';
}

$html .= '
                <th width="50" class="text-center">Rata² UH</th>
                <th width="50" class="text-center">Rata² AS</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
foreach ($siswaList as $siswa) {
    $nis = $siswa['no_induk'];
    $html .= '<tr>
                <td class="text-center">'.$no.'</td>
                <td>'.htmlspecialchars($siswa['nama_siswa']).'</td>
                <td class="text-center">'.htmlspecialchars($nis).'</td>';
    
    // Nilai per item
    $uhSum = 0; $uhCnt = 0; $asSum = 0; $asCnt = 0;
    foreach ($items as $it) {
        $val = $nilaiMap[$it['id']][$nis] ?? '';
        $display = ($val === '' || $val === null) ? '-' : $val;
        $html .= '<td class="text-center">'.htmlspecialchars($display).'</td>';
        
        // Hitung rata-rata
        if ($val !== '' && $val !== null && is_numeric($val)) {
            $num = (float)$val;
            $kode = strtoupper($it['kode_penilaian']);
            if (strpos($kode, 'UH') === 0) { $uhSum += $num; $uhCnt++; }
            if ($kode === 'ASAS' || $kode === 'ASAT') { $asSum += $num; $asCnt++; }
        }
    }
    
    $avgUH = $uhCnt ? round($uhSum / $uhCnt, 2) : '-';
    $avgAS = $asCnt ? round($asSum / $asCnt, 2) : '-';
    
    $html .= '<td class="text-center">'.htmlspecialchars($avgUH).'</td>
              <td class="text-center">'.htmlspecialchars($avgAS).'</td>
              </tr>';
    $no++;
}

$html .= '
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        <strong>Keterangan:</strong><br>';

// List item penilaian
foreach ($items as $it) {
    $html .= htmlspecialchars($it['kode_penilaian']).' = '.htmlspecialchars($it['materi']).'<br>';
}

$html .= '
    </div>
    
    <div class="footer">
        <p>Dicetak pada: '.date('d/m/Y H:i:s').'</p>
    </div>';

// Generate PDF
try {
    // Create new PDF document
    $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SI Jurnal');
    $pdf->SetAuthor($_SESSION['nama'] ?? 'Guru');
    $pdf->SetTitle('Daftar Nilai Kelas ' . $kelas);
    $pdf->SetSubject('Nilai Siswa');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'DAFTAR NILAI KELAS', 'Kelas ' . $kelas . ' - ' . $mapelNama);
    
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
    $pdf->SetFont('helvetica', '', 9);
    
    // Add a page
    $pdf->AddPage();
    
    // Create HTML content
    $html = '
    <style>
        .info { margin-bottom: 10px; font-size: 11px; }
        .nilai-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9px; }
        .nilai-table th, .nilai-table td { border: 1px solid #333; padding: 4px; text-align: left; }
        .nilai-table th { background-color: #e0e0e0; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { margin-top: 15px; font-size: 8px; color: #666; }
    </style>
    
    <div class="info">
        <strong>Tanggal:</strong> ' . date('d/m/Y', strtotime($tanggal)) . ' &nbsp;&nbsp;&nbsp;
        <strong>Guru:</strong> ' . htmlspecialchars($_SESSION['nama'] ?? '') . '
    </div>
    
    <table class="nilai-table">
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Nama Siswa</th>
                <th width="80">NIS</th>';
    
    // Header kolom nilai
    foreach ($items as $it) {
        $html .= '<th class="text-center" width="40">' . htmlspecialchars($it['kode_penilaian']) . '</th>';
    }
    
    $html .= '
                <th width="50" class="text-center">Rata² UH</th>
                <th width="50" class="text-center">Rata² AS</th>
            </tr>
        </thead>
        <tbody>';
    
    $no = 1;
    foreach ($siswaList as $siswa) {
        $nis = $siswa['no_induk'];
        $html .= '<tr>
                    <td class="text-center">' . $no . '</td>
                    <td>' . htmlspecialchars($siswa['nama_siswa']) . '</td>
                    <td class="text-center">' . htmlspecialchars($nis) . '</td>';
        
        // Nilai per item
        $uhSum = 0; $uhCnt = 0; $asSum = 0; $asCnt = 0;
        foreach ($items as $it) {
            $val = $nilaiMap[$it['id']][$nis] ?? '';
            $display = ($val === '' || $val === null) ? '-' : $val;
            $html .= '<td class="text-center">' . htmlspecialchars($display) . '</td>';
            
            // Hitung rata-rata
            if ($val !== '' && $val !== null && is_numeric($val)) {
                $num = (float)$val;
                $kode = strtoupper($it['kode_penilaian']);
                if (strpos($kode, 'UH') === 0) { $uhSum += $num; $uhCnt++; }
                if ($kode === 'ASAS' || $kode === 'ASAT') { $asSum += $num; $asCnt++; }
            }
        }
        
        $avgUH = $uhCnt ? round($uhSum / $uhCnt, 2) : '-';
        $avgAS = $asCnt ? round($asSum / $asCnt, 2) : '-';
        
        $html .= '<td class="text-center">' . htmlspecialchars($avgUH) . '</td>
                  <td class="text-center">' . htmlspecialchars($avgAS) . '</td>
                  </tr>';
        $no++;
    }
    
    $html .= '
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        <strong>Keterangan:</strong><br>';
    
    // List item penilaian
    foreach ($items as $it) {
        $html .= htmlspecialchars($it['kode_penilaian']) . ' = ' . htmlspecialchars($it['materi']) . '<br>';
    }
    
    $html .= '
    </div>
    
    <div class="footer">
        <p>Dicetak pada: ' . date('d/m/Y H:i:s') . '</p>
    </div>';
    
    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Clean filename
    $cleanKelas = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kelas);
    $filename = $cleanKelas . '.pdf';
    
    // Close and output PDF document
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    echo '<h1>Error: ' . $e->getMessage() . '</h1>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
    exit;
}
exit;
