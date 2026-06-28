<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION["no_induk"])) {
    header("location: ../../index.php?haruslogin");
    exit;
} else if($_SESSION['hak_akses'] != 2) {
    echo "<script>window.location='404.html';</script>";
    exit;
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);
$lembaga = data_lembaga();
$sqlguru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipguru'");
$dataguru = mysqli_fetch_array($sqlguru);
// Siapkan nama depan untuk tampilan mobile (nama panggilan)
$namaLengkap = $dataguru['nama_guru'] ?? ($_SESSION["nama_guru"] ?? 'Guru');
$partsNama = preg_split('/\s+/', trim($namaLengkap));
$firstName = $partsNama[0] ?? $namaLengkap;
// Ambil semua jadwal hari ini (untuk grid actions)
$jadwalHariIni = [];
$qJ = mysqli_query($conn, "SELECT m.id_mapel, m.kelas, m.nama_mapel, m.jam_mulai, m.jam_selesai, g.foto FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk=g.no_induk WHERE m.no_induk='".$nipguru."' AND m.hari='".$hariini."' ORDER BY m.jam_mulai ASC");
while ($rowJ = mysqli_fetch_assoc($qJ)) { $jadwalHariIni[] = $rowJ; }
// Hitung progres pengisian jurnal hari ini
$totalJadwal = count($jadwalHariIni);
$mapelIds = [];
foreach($jadwalHariIni as $jj){ $mapelIds[] = (int)$jj['id_mapel']; }
$jurnalTerisi = 0;
if ($totalJadwal > 0) {
  $idList = implode(',', $mapelIds);
  $tglEsc = mysqli_real_escape_string($conn, $tglskr);

  // Deteksi nama kolom tanggal pada tbl_materi (beberapa instalasi pakai `date`, beberapa pakai `tanggal`)
  $dateCol = null;
  $colCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'date'");
  if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    $dateCol = 'date';
  } else {
    $colCheck2 = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'tanggal'");
    if ($colCheck2 && mysqli_num_rows($colCheck2) > 0) {
      $dateCol = 'tanggal';
    }
  }

  if ($dateCol) {
    $qC = mysqli_query($conn, "SELECT COUNT(DISTINCT id_mapel) as jml FROM tbl_materi WHERE `$dateCol`='$tglEsc' AND id_mapel IN ($idList)");
    if ($qC) { $rowC = mysqli_fetch_assoc($qC); $jurnalTerisi = (int)($rowC['jml'] ?? 0); }
    else {
      error_log('guru.php - jurnal count query failed: '.mysqli_error($conn));
    }
  } else {
    error_log('guru.php - tbl_materi has no date/tanggal column');
  }
}
$percentJurnal = ($totalJadwal>0)? round(($jurnalTerisi/$totalJadwal)*100,1) : 0;

// Helper functions for schedule status
function getTimeStatus($mulaiStr, $selesaiStr) {
  $now = date('H:i');
  $minutesNow = date('H') * 60 + date('i');
  $mulai = timeToMinutes($mulaiStr);
  $selesai = timeToMinutes($selesaiStr);
  
  if ($mulai === null || $selesai === null) return 'unknown';
  if ($minutesNow < $mulai) return 'upcoming';
  if ($minutesNow >= $mulai && $minutesNow <= $selesai) return 'ongoing';
  return 'finished';
}

function timeToMinutes($timeStr) {
  if (!$timeStr) return null;
  $parts = explode(':', $timeStr);
  if (count($parts) >= 2) {
    $h = intval($parts[0]);
    $m = intval($parts[1]);
    return $h * 60 + $m;
  }
  return null;
}

function calculateProgress($mulaiStr, $selesaiStr) {
  $minutesNow = date('H') * 60 + date('i');
  $mulai = timeToMinutes($mulaiStr);
  $selesai = timeToMinutes($selesaiStr);
  
  if ($mulai === null || $selesai === null) return 0;
  if ($minutesNow < $mulai) return 0;
  if ($minutesNow > $selesai) return 100;
  
  $duration = $selesai - $mulai;
  $elapsed = $minutesNow - $mulai;
  return round(($elapsed / $duration) * 100);
}

function getStatusText($status) {
  switch($status) {
    case 'upcoming': return 'Akan Datang';
    case 'ongoing': return 'Berlangsung';
    case 'finished': return 'Selesai';
    default: return 'Tidak Diketahui';
  }
}

function getTimeInfoText($status, $mulaiStr, $selesaiStr) {
  $minutesNow = date('H') * 60 + date('i');
  $mulai = timeToMinutes($mulaiStr);
  $selesai = timeToMinutes($selesaiStr);
  
  if ($mulai === null || $selesai === null) return 'Waktu tidak valid';
  
  switch($status) {
    case 'upcoming':
      $diff = $mulai - $minutesNow;
      $hours = floor($diff / 60);
      $mins = $diff % 60;
      $timeLeft = $hours > 0 ? "{$hours} jam {$mins} menit" : "{$mins} menit";
      return "Dimulai dalam {$timeLeft}";
      
    case 'ongoing':
      $diff = $selesai - $minutesNow;
      $hours = floor($diff / 60);
      $mins = $diff % 60;
      $timeLeft = $hours > 0 ? "{$hours} jam {$mins} menit" : "{$mins} menit";
      return "Selesai dalam {$timeLeft}";
      
    case 'finished':
      return "Telah selesai pada {$selesaiStr}";
      
    default:
      return "{$mulaiStr} - {$selesaiStr}";
  }
}

// Ambil seluruh jadwal guru (semua hari) untuk kebutuhan filter riwayat
$jadwalSemua = [];
$qAll = mysqli_query($conn, "SELECT id_mapel, hari, kelas, nama_mapel, jam_mulai, jam_selesai FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai");
while($rA = mysqli_fetch_assoc($qAll)) { $jadwalSemua[] = $rA; }

// Ambil daftar kelas yang diajar guru ini untuk dropdown pelanggaran
$kelasList = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY kelas");
while($rK = mysqli_fetch_assoc($qKelas)) { 
  $kelasList[] = $rK['kelas']; 
}

// --- Data Wali Kelas (untuk menu Data Wali Kelas) ---
$waliKelasNama = null;            // ex: "XI IPA 1"  
$kelasWali = $waliKelasNama;      // untuk default selection di dropdown
$waliKelasId   = null;            // id_kelas
$waliSiswaData = [];              // array berisi ringkasan per siswa
$waliAlpha3List = [];             // siswa yang alpha >= 3 (bulan berjalan)

// Cari kelas yang diwalikan guru ini
$nipEsc = mysqli_real_escape_string($conn, $nipguru);
$resWK  = mysqli_query($conn, "SELECT wk.id_kelas, k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON wk.id_kelas=k.id_kelas WHERE wk.nip_wali='".$nipEsc."' LIMIT 1");
if ($resWK && mysqli_num_rows($resWK) > 0) {
  $rowWK = mysqli_fetch_assoc($resWK);
  $waliKelasId = $rowWK['id_kelas'];
  $waliKelasNama = $rowWK['kelas'];
} else {
  // Fallback ke kolom legacy tbl_kelas.nip_wali bila tabel tbl_wali_kelas kosong
  $resLegacy = mysqli_query($conn, "SELECT id_kelas, kelas FROM tbl_kelas WHERE nip_wali='".$nipEsc."' LIMIT 1");
  if ($resLegacy && mysqli_num_rows($resLegacy) > 0) {
    $rowL = mysqli_fetch_assoc($resLegacy);
    $waliKelasId = $rowL['id_kelas'];
    $waliKelasNama = $rowL['kelas'];
  }
}

// Set variabel untuk default selection di dropdown pelanggaran
$kelasWali = $waliKelasNama;

if ($waliKelasNama) {
  // Ambil daftar siswa aktif pada kelas wali
  $kelasEsc = mysqli_real_escape_string($conn, $waliKelasNama);
  $qS = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='".$kelasEsc."' AND (status IS NULL OR status='' OR UPPER(status)='AKTIF') ORDER BY nama_siswa");
  $siswaList = [];
  while ($qS && ($r = mysqli_fetch_assoc($qS))) {
    $siswaList[$r['no_induk']] = [
      'no_induk' => $r['no_induk'],
      'nama'     => $r['nama_siswa'],
    ];
  }

  if (!empty($siswaList)) {
    $bulanNow = date('Y-m');

    // Agregasi absensi bulan berjalan untuk semua siswa kelas wali (1 query), guard jika tabel belum ada
    $absAgg = [];
    $tblAb = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_absen'");
    if ($tblAb && mysqli_num_rows($tblAb) > 0) {
      $qAbsen = mysqli_query($conn, "SELECT no_induk,
          SUM(status='Alpha')  AS alpha,
          SUM(status='Ijin')   AS ijin,
          SUM(status='Sakit')  AS sakit,
          SUM(status='Dispen') AS dispen
        FROM tbl_absen
        WHERE kelas='".$kelasEsc."' AND DATE_FORMAT(tanggal, '%Y-%m')='".$bulanNow."'
        GROUP BY no_induk");
      while ($qAbsen && ($ra = mysqli_fetch_assoc($qAbsen))) {
        $absAgg[$ra['no_induk']] = [
          'alpha' => (int)($ra['alpha'] ?? 0),
          'ijin'  => (int)($ra['ijin'] ?? 0),
          'sakit' => (int)($ra['sakit'] ?? 0),
          'dispen'=> (int)($ra['dispen'] ?? 0),
        ];
      }
    }

    // Rata-rata nilai tugas (semua waktu untuk kelas tsb) – dipakai sebagai indikator tugas (guard jika tabel belum ada)
    $nilaiAgg = [];
    $tblNil = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_nilai'");
    if ($tblNil && mysqli_num_rows($tblNil) > 0) {
      $qNil = mysqli_query($conn, "SELECT no_induk_siswa, AVG(NULLIF(nilai_tugas,0)) AS avg_tugas
        FROM tbl_nilai
        WHERE kelas='".$kelasEsc."'
        GROUP BY no_induk_siswa");
      while ($qNil && ($rn = mysqli_fetch_assoc($qNil))) {
        $nilaiAgg[$rn['no_induk_siswa']] = (float)$rn['avg_tugas'];
      }
    }

    // Data pelanggaran siswa (bulan berjalan)
    $pelanggaranAgg = [];
    $tblPelanggaran = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran'");
    if ($tblPelanggaran && mysqli_num_rows($tblPelanggaran) > 0) {
      $qPelanggaran = mysqli_query($conn, "SELECT no_induk,
          GROUP_CONCAT(CONCAT(DATE_FORMAT(tanggal_pelanggaran, '%d/%m'), ': ', jenis_pelanggaran, ' (', kategori_pelanggaran, ')') ORDER BY tanggal_pelanggaran DESC SEPARATOR '; ') AS catatan_pelanggaran,
          SUM(kategori_pelanggaran='Berat') AS pelanggaran_berat,
          SUM(kategori_pelanggaran='Sedang') AS pelanggaran_sedang,
          SUM(kategori_pelanggaran='Ringan') AS pelanggaran_ringan
        FROM tbl_pelanggaran
        WHERE kelas='".$kelasEsc."' AND DATE_FORMAT(tanggal_pelanggaran, '%Y-%m')='".$bulanNow."' AND status_pelanggaran='Aktif'
        GROUP BY no_induk");
      while ($qPelanggaran && ($rp = mysqli_fetch_assoc($qPelanggaran))) {
        $pelanggaranAgg[$rp['no_induk']] = [
          'catatan' => $rp['catatan_pelanggaran'] ?? '',
          'berat' => (int)($rp['pelanggaran_berat'] ?? 0),
          'sedang' => (int)($rp['pelanggaran_sedang'] ?? 0),
          'ringan' => (int)($rp['pelanggaran_ringan'] ?? 0),
        ];
      }
    }

    // Gabung dan skor
    foreach ($siswaList as $nis => $info) {
      $a = $absAgg[$nis] ?? ['alpha'=>0,'ijin'=>0,'sakit'=>0,'dispen'=>0];
      $avgTugas = isset($nilaiAgg[$nis]) ? round($nilaiAgg[$nis],1) : null;
      $pelanggaran = $pelanggaranAgg[$nis] ?? ['catatan'=>'', 'berat'=>0, 'sedang'=>0, 'ringan'=>0];

      // Skor masalah: Alpha bobot 3, ijin/sakit/dispen bobot 0.5, plus penalti jika nilai tugas rendah (<75), plus pelanggaran
      $score = ($a['alpha'] * 3) + (($a['ijin'] + $a['sakit'] + $a['dispen']) * 0.5);
      if ($avgTugas !== null && $avgTugas < 75) {
        $score += min(3, (75 - $avgTugas) / 10); // maksimum +3
      }
      // Tambah skor pelanggaran: Berat +5, Sedang +2, Ringan +1
      $score += ($pelanggaran['berat'] * 5) + ($pelanggaran['sedang'] * 2) + ($pelanggaran['ringan'] * 1);

      if ($a['alpha'] >= 3) {
        $waliAlpha3List[] = [ 'nis'=>$nis, 'nama'=>$info['nama'], 'alpha'=>$a['alpha'] ];
      }

      $waliSiswaData[] = [
        'no_induk' => $nis,
        'nama'     => $info['nama'],
        'alpha'    => $a['alpha'],
        'ijin'     => $a['ijin'],
        'sakit'    => $a['sakit'],
        'dispen'   => $a['dispen'],
        'avg_tugas'=> $avgTugas,
        'score'    => $score,
        'catatan_pelanggaran' => $pelanggaran['catatan'],
        'pelanggaran_berat' => $pelanggaran['berat'],
        'pelanggaran_sedang' => $pelanggaran['sedang'],
        'pelanggaran_ringan' => $pelanggaran['ringan'],
      ];
    }

    // Urutkan berdasarkan skor masalah (desc) lalu alpha desc
    usort($waliSiswaData, function($x,$y){
      if ($x['score'] == $y['score']) return $y['alpha'] <=> $x['alpha'];
      return ($y['score'] <=> $x['score']);
    });
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>Dashboard Guru</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../css/quotes-style.css">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 70px;
    }
    .img-profile {
      width: 40px;
      height: 40px;
      object-fit: cover;
      border-radius: 50%;
    }
    
    /* Modern styling for the schedule section */
    .schedule-container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
      padding: 1.5rem;
      margin-bottom: 2rem;
      overflow: hidden;
    }
    
    .schedule-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #f1f3f4;
      flex-wrap: wrap;
      gap: 15px;
    }
    
    .schedule-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1a73e8;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    
    .schedule-title-text {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .live-badge {
      background: #e53935;
      color: white;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 0.7rem;
      font-weight: 600;
      animation: pulse 1.5s infinite;
      white-space: nowrap;
    }
    
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.7; }
      100% { opacity: 1; }
    }
    
    .current-time {
      background: rgba(26, 115, 232, 0.1);
      padding: 10px 16px;
      border-radius: 12px;
      font-weight: 600;
      color: #1a73e8;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 140px;
      border: 2px solid rgba(26, 115, 232, 0.1);
    }
    
    .time-display {
      font-size: 1.4rem;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    
    .time-label {
      font-size: 0.75rem;
      opacity: 0.8;
      text-align: center;
      line-height: 1.2;
      margin-top: 2px;
    }
    
    /* Mobile responsive adjustments */
    @media (max-width: 768px) {
      .schedule-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        text-align: center;
      }
      
      .schedule-title {
        font-size: 1.1rem;
        justify-content: center;
        gap: 8px;
      }
      
      .schedule-title-text {
        justify-content: center;
        gap: 6px;
      }
      
      .live-badge {
        font-size: 0.65rem;
        padding: 3px 6px;
      }
      
      .current-time {
        align-self: center;
        padding: 12px 20px;
        min-width: 160px;
        background: linear-gradient(135deg, rgba(26, 115, 232, 0.1), rgba(26, 115, 232, 0.05));
        border: 2px solid rgba(26, 115, 232, 0.2);
        box-shadow: 0 2px 8px rgba(26, 115, 232, 0.1);
      }
      
      .time-display {
        font-size: 1.6rem;
        font-weight: 800;
      }
      
      .time-label {
        font-size: 0.8rem;
        opacity: 0.9;
      }
    }
    
    @media (max-width: 480px) {
      .schedule-container {
        padding: 1rem;
        margin-bottom: 1.5rem;
      }
      
      .schedule-title {
        font-size: 1rem;
      }
      
      .schedule-title i {
        font-size: 1rem;
      }
      
      .current-time {
        min-width: 140px;
        padding: 10px 16px;
      }
      
      .time-display {
        font-size: 1.4rem;
      }
      
      .time-label {
        font-size: 0.7rem;
      }
    }
    
    /* Schedule card styling */
    .schedule-card {
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 1rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      border: 1px solid #e8eaed;
      background: #fff;
    }
    
    .schedule-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    
    .card-header {
      padding: 12px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f8f9fa;
      border-bottom: 1px solid #e8eaed;
    }
    
    .class-info {
      font-weight: 600;
      color: #3c4043;
      font-size: 1rem;
    }
    
    .subject-name {
      font-weight: 600;
      color: #1a73e8;
      margin: 0;
      font-size: 1.1rem;
    }
    
    .time-range {
      color: #5f6368;
      font-size: 0.9rem;
      margin-bottom: 8px;
    }
    
    .status-indicator {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 16px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .status-upcoming {
      background: rgba(66, 133, 244, 0.1);
      color: #4285f4;
    }
    
    .status-ongoing {
      background: rgba(52, 168, 83, 0.1);
      color: #34a853;
      animation: blink 2s infinite;
    }
    
    @keyframes blink {
      0%, 50% { opacity: 1; }
      51%, 100% { opacity: 0.7; }
    }
    
    .status-finished {
      background: rgba(234, 67, 53, 0.1);
      color: #ea4335;
    }
    
    .card-body {
      padding: 16px;
    }
    
    .progress-container {
      margin: 12px 0;
    }
    
    .time-progress {
      height: 6px;
      background: #f1f3f4;
      border-radius: 3px;
      overflow: hidden;
    }
    
    .progress-bar {
      height: 100%;
      border-radius: 3px;
      transition: width 0.5s ease;
    }
    
    .progress-upcoming {
      background: #4285f4;
      width: 0%;
    }
    
    .progress-ongoing {
      background: linear-gradient(90deg, #34a853, #66bb6a);
    }
    
    .progress-finished {
      background: #ea4335;
      width: 100%;
    }
    
    .time-info {
      font-size: 0.85rem;
      color: #5f6368;
      margin-top: 4px;
    }
    
    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 16px;
    }
    
    /* Materi file section */
    .materi-file {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 12px;
      margin-top: 12px;
    }
    
    .file-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #e8eaed;
    }
    
    .file-item:last-child {
      border-bottom: none;
    }
    
    .file-link {
      color: #1a73e8;
      text-decoration: none;
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      margin-right: 10px;
    }
    
    /* Legacy card-jadwal for compatibility */
    .card-jadwal {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 1rem;
      background: #fff;
      margin-bottom: 1rem;
    }
    /* Footer Navigation - Modern Design */
    .footer-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff;
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-around;
      padding: 0.8rem 0;
      z-index: 1050;
      border-radius: 20px 20px 0 0;
      transition: all 0.3s ease;
    }

    .footer-nav a {
      color: #6c757d;
      font-size: 0.75rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      padding: 0.4rem 0.8rem;
      border-radius: 12px;
      transition: all 0.3s ease;
      position: relative;
    }

    .footer-nav a i {
      font-size: 1.2rem;
      margin-bottom: 0.3rem;
      transition: all 0.3s ease;
    }

    .footer-nav a.active {
      color: #0d6efd;
      background: rgba(13, 110, 253, 0.1);
      transform: translateY(-5px);
    }

    .footer-nav a.active i {
      transform: scale(1.2);
      color: #0d6efd;
    }

    .footer-nav a:not(.active):hover {
      color: #495057;
      background: rgba(108, 117, 125, 0.05);
    }

    /* Efek indikator aktif */
    .footer-nav a.active::before {
      content: '';
      position: absolute;
      top: -8px;
      width: 20px;
      height: 3px;
      background: #0d6efd;
      border-radius: 3px;
    }

    /* Animasi untuk feedback interaksi */
    @keyframes footerBounce {
      0% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
      100% { transform: translateY(-5px); }
    }

    .footer-nav a.active {
      animation: footerBounce 0.5s ease forwards;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
      .footer-nav {
        padding: 0.7rem 0;
      }
      
      .footer-nav a {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
      }
      
      .footer-nav a i {
        font-size: 1.1rem;
      }
      
      .footer-nav a.active::before {
        top: -6px;
        width: 16px;
        height: 2px;
      }
    }
    .header-custom {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1020;
    }
    .header-custom img {
      background: #fff;
      padding: 4px;
      border-radius: 50%;
      margin-right: 10px;
    }
    /* Responsive penyesuaian header untuk mobile */
    .first-name-mobile { display:none; font-weight:600; }
    .person-icon-mobile { display:none; }
    @media (max-width: 576px) {
      .header-custom small { display:none; } /* sembunyikan alamat sekolah */
      .header-custom .full-name-desktop { display:none !important; }
      .first-name-mobile { display:inline; }
      .person-icon-mobile { display:inline-flex; font-size:1.8rem; color:#fff; margin-right:6px; }
      .header-custom img.img-profile { display:none; } /* sembunyikan foto profil, pakai icon */
      .header-custom { padding: .75rem 1rem; }
      .header-custom h6 { font-size:.95rem; }
    }
    @media (min-width: 577px){
      .first-name-mobile { display:none !important; }
      .person-icon-mobile { display:none !important; }
    }
    /* Quick Actions Grid */
    .quick-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 12px;
    }
    .quick-card {
      border: none;
      border-radius: 16px;
      padding: 16px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      cursor: pointer;
      transition: transform .15s ease, box-shadow .15s ease;
      min-height: 76px;
    }
    .quick-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
    .qc-icon { font-size: 1.6rem; width: 42px; height: 42px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.18); border-radius: 12px; }
    .qc-title { font-weight: 600; margin: 0; line-height: 1.2; }
    .qc-sub { font-size: .8rem; opacity: .9; margin: 2px 0 0; }
    .bg-grad-primary { background: linear-gradient(135deg,#0d6efd,#6610f2); }
    .bg-grad-success { background: linear-gradient(135deg,#20c997,#0ea5e9); }
    .bg-grad-warning { background: linear-gradient(135deg,#f59e0b,#ef4444); }
    .bg-grad-info { background: linear-gradient(135deg,#0dcaf0,#6f42c1); }
    .bg-grad-secondary { background: linear-gradient(135deg,#6c757d,#495057); }
    .bg-grad-pink { background: linear-gradient(135deg,#e91e63,#ad1457); }
    .bg-grad-purple { background: linear-gradient(135deg,#6f42c1,#e91e63); }
    .bg-grad-danger { background: linear-gradient(135deg,#dc3545,#fd7e14); }
    .bg-grad-info { background: linear-gradient(135deg,#06b6d4,#3b82f6); }
    .bg-grad-secondary { background: linear-gradient(135deg,#64748b,#334155); }
    .bg-grad-pink { background: linear-gradient(135deg,#ec4899,#8b5cf6); }
    .bg-grad-purple { background: linear-gradient(135deg,#8b5cf6,#3b82f6); }
    /* Announcement Board */
    .ann-board { position:relative; background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:1.1rem 1.2rem 1rem; margin-bottom:1.8rem; box-shadow:0 6px 18px -6px rgba(0,0,0,.08); overflow:hidden; }
    .ann-board:before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 12% 18%,rgba(255,255,255,.7),rgba(255,255,255,0) 60%); pointer-events:none; mix-blend-mode:overlay; }
    .ann-header { display:flex; align-items:center; gap:.6rem; margin-bottom:.75rem; }
    .ann-header h6 { margin:0; font-weight:600; font-size:.95rem; letter-spacing:.5px; display:flex; align-items:center; gap:.5rem; }
    .ann-items { max-height:220px; overflow:auto; scrollbar-width:thin; }
    .ann-item { position:relative; padding:.65rem .75rem .6rem .85rem; border:1px solid #eef2f7; border-radius:14px; background:#f8fafc; margin-bottom:.55rem; display:flex; flex-direction:column; gap:.25rem; transition:background .3s,border-color .3s; }
    .ann-item:last-child { margin-bottom:.25rem; }
    .ann-item:hover { background:#ffffff; border-color:#dbe3ea; }
    .ann-title { font-size:.78rem; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .ann-meta { font-size:.62rem; letter-spacing:.5px; color:#64748b; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .ann-body { font-size:.7rem; line-height:1.25rem; color:#334155; white-space:pre-line; }
    .ann-badge-penting { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; padding:2px 8px; font-size:.55rem; border-radius:10px; font-weight:600; animation: glowPulse 2s ease-in-out infinite; }
    .ann-new-glow { animation: blinkGlow 1.2s linear infinite; }
    @keyframes glowPulse { 0%,100%{filter:brightness(1);} 50%{filter:brightness(1.35);} }
    @keyframes blinkGlow { 0%,60%{ box-shadow:0 0 0 0 rgba(236,72,153,.0); } 70%{ box-shadow:0 0 0 4px rgba(236,72,153,.25);} 100%{ box-shadow:0 0 0 0 rgba(236,72,153,0);} }
    .ann-empty { font-size:.7rem; color:#64748b; font-style:italic; text-align:center; padding:.6rem 0 .4rem; }
    .ann-scroller-fade { position:absolute; left:0; right:0; bottom:0; height:34px; background:linear-gradient(to bottom, rgba(255,255,255,0), #ffffff 80%); pointer-events:none; }
    .ann-refresh { font-size:.65rem; color:#64748b; display:flex; align-items:center; gap:.35rem; cursor:pointer; padding:4px 8px; border-radius:20px; border:1px solid #e2e8f0; background:#fff; transition:all .25s; }
    .ann-refresh:hover { background:#f1f5f9; color:#0f172a; }
    .ann-toolbar { display:flex; align-items:center; gap:.6rem; margin-left:auto; }
    @media (max-width:640px){ .ann-items { max-height:180px; } }
    .ann-item.border-danger { border-color:#ef4444 !important; }
    .ann-toast-blink { animation: annToastPulse 1.2s ease-in-out infinite alternate; }
    @keyframes annToastPulse { from { box-shadow:0 0 0 0 rgba(239,68,68,.35);} to { box-shadow:0 0 0 6px rgba(239,68,68,0);} }
    /* Riwayat Styles */
    .rw-trend-chart { display:flex; align-items:flex-end; gap:6px; min-height:90px; }
    .rw-trend-bar { flex:1; position:relative; text-align:center; }
    .rw-trend-bar div { width:100%; background:linear-gradient(180deg,#3b82f6,#1d4ed8); border-radius:4px 4px 2px 2px; position:relative; transition:height .3s; }
    .rw-trend-bar div:after { content:attr(data-count); position:absolute; top:-18px; left:50%; transform:translateX(-50%); font-size:10px; color:#334155; }
    .rw-trend-bar span { display:block; font-size:9px; margin-top:4px; color:#64748b; }
    .rw-item { background:#ffffff; }
    .rw-item-compact { padding:8px 10px; border:1px solid #e8e8f0; border-radius:10px; margin-bottom:6px; background:#fff; }
    .rw-item-compact .rw-ic-head { font-size:.72rem; font-weight:600; color:#334155; display:flex; flex-wrap:wrap; gap:6px; }
    .rw-item-compact .rw-ic-body { font-size:.7rem; color:#475569; margin-top:3px; }
    .rw-day-group { border:1px solid #e8e8f0; border-radius:14px; margin-bottom:14px; overflow:hidden; background:#fff; }
    .rw-day-header { background:linear-gradient(135deg,#f1f5f9,#e2e8f0); padding:6px 12px; font-size:.75rem; font-weight:600; color:#334155; word-break: break-word; }
    .rw-item-hari { display:grid; grid-template-columns:140px 1fr 60px; gap:10px; padding:10px 14px; border-top:1px solid #f1f5f9; font-size:.72rem; align-items:center; word-break: break-word; overflow-wrap: anywhere; }
    .rw-item-hari:first-of-type { border-top:none; }
    .rw-h-jam { font-weight:600; color:#1e3a8a; font-size:.7rem; white-space: nowrap; }
    .rw-h-mapel { font-weight:500; color:#334155; font-size:.7rem; line-height: 1.2; word-break: break-word; }
    .rw-h-col2 { color:#475569; font-size:.7rem; line-height: 1.3; word-break: break-word; overflow-wrap: anywhere; min-width: 0; }
    .rw-h-col3 .badge { font-size:.55rem; }
    /* Absen text styling untuk memastikan tidak keluar dari border */
    .rw-h-col2 .small { font-size:.65rem; line-height: 1.2; word-break: break-word; overflow-wrap: anywhere; }
    .rw-absen-text { display: block; word-break: break-word; overflow-wrap: anywhere; hyphens: auto; }
    /* Scroll container untuk daftar absen siswa */
    .rw-absen-scroll {
      max-height: 60px;
      overflow-y: auto;
      padding: 2px 4px;
      border-radius: 4px;
      background: rgba(248, 250, 252, 0.8);
      border: 1px solid #e8e8f0;
      margin-top: 2px;
    }
    .rw-absen-scroll::-webkit-scrollbar {
      width: 6px;
    }
    .rw-absen-scroll::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 3px;
    }
    .rw-absen-scroll::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }
    .rw-absen-scroll::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    .rw-absen-item {
      display: block;
      font-size: 0.6rem;
      line-height: 1.1;
      color: #475569;
      padding: 1px 0;
    }
    /* Compact view responsive */
    .rw-item-compact { word-break: break-word; overflow-wrap: anywhere; }
    .rw-item-compact .rw-ic-head { word-break: break-word; overflow-wrap: anywhere; }
    .rw-item-compact .rw-ic-body { word-break: break-word; overflow-wrap: anywhere; }
    /* List view responsive */
    .rw-item { word-break: break-word; overflow-wrap: anywhere; }
    .rw-item .fw-semibold { word-break: break-word; overflow-wrap: anywhere; }
    .rw-item .small { word-break: break-word; overflow-wrap: anywhere; }
    @media (max-width: 768px){
      .rw-item-hari { grid-template-columns:100px 1fr 50px; padding:8px 10px; gap:8px; }
      .rw-h-jam { font-size:.65rem; }
      .rw-h-mapel { font-size:.65rem; }
      .rw-h-col2 { font-size:.65rem; }
      .rw-h-col2 .small { font-size:.6rem; }
    }
    @media (max-width: 520px){
      .rw-item-hari { grid-template-columns:1fr; gap:4px; padding:8px 10px; }
      .rw-h-col3 { text-align:left !important; margin-top: 4px; }
      .rw-h-jam { font-size:.65rem; }
      .rw-h-mapel { font-size:.65rem; margin-bottom: 2px; }
      .rw-h-col2 { font-size:.65rem; }
      .rw-h-col2 .small { font-size:.6rem; line-height: 1.1; }
    }
  .circular-chart .circle-bg { stroke: #e6e6e6; }
  .circular-chart .circle { transition: stroke-dasharray .6s ease; }
  .circular-chart { max-width:100%; display:block; }
    @media (min-width: 992px) {
      .quick-grid { grid-template-columns: repeat(3,1fr); }
    }
    /* Cetak Jurnal Modal Enhancement */
    .cetak-modal { border-radius:24px; overflow:hidden; }
    @media (min-width:992px){ .cetak-modal { border:1px solid #e2e8f0; box-shadow:0 18px 50px -12px rgba(0,0,0,.15); } }
    .cetak-toolbar { background:linear-gradient(135deg,#f8fafc,#eef2f7); border:1px solid #e2e8f0; box-shadow:0 4px 14px -6px rgba(0,0,0,.08) inset,0 2px 8px rgba(0,0,0,.04); position:relative; }
    .cetak-toolbar:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 18% 22%,rgba(255,255,255,.7) 0%,rgba(255,255,255,0) 60%); pointer-events:none; mix-blend-mode:overlay; }
    .cetak-iframe { width:100%; height:70vh; background:#fff; border:0; }
    @media (max-width:576px){ .cetak-iframe { height:65vh; } }
    .cetak-frame-wrapper { background:#fff; border:1px solid #e2e8f0; box-shadow:0 4px 18px -4px rgba(0,0,0,.08); }
    .cetak-loading { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; background:linear-gradient(135deg,rgba(255,255,255,.85),rgba(255,255,255,.92)); z-index:10; }
    .cetak-frame-wrapper.loading:after { content:""; position:absolute; inset:0; backdrop-filter:blur(2px); }
    .cetak-toolbar input { font-size:.75rem; }
    .cetak-toolbar .btn { font-size:.7rem; letter-spacing:.4px; }
    .cetak-toolbar .btn i { font-size:.85rem; }
    #cetakStatus { min-height:18px; }
    .modal-fullscreen-lg-down .modal-content.cetak-modal { border-radius:0; }
    .cetak-modal .modal-footer { border-top:1px solid #e2e8f0; }
    .cetak-modal .modal-header { background:linear-gradient(135deg,#ffffff,#f1f5f9); }
    .cetak-modal .modal-title i { color:#0d6efd; }
  </style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="header-custom d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
      <img src="../../img/<?= $lembaga['logo']; ?>" width="50" alt="Logo">
      <div>
        <h6 class="mb-0 fw-bold"><?= $lembaga['nmsekolah']; ?></h6>
        <small><?= $lembaga['alamat']; ?></small>
      </div>
    </div>
    <div class="d-flex align-items-center">
      <i class="bi bi-person-circle person-icon-mobile"></i>
      <?php if(empty($dataguru['foto'])) { ?>
        <img src="../../img/no-photo.png" alt="" class="img-profile">
      <?php } else { ?>
        <img src="../../foto/<?= $dataguru['foto']; ?>" alt="" class="img-profile">
      <?php } ?>
      <span class="ms-2 full-name-desktop">Hai, <?= htmlspecialchars($namaLengkap); ?></span>
      <span class="ms-2 first-name-mobile">Hai, <?= htmlspecialchars($firstName); ?></span>
    </div>
  </div>
  <div class="container px-3" style="margin-top:-10px;">
    <?php
      if (isset($_GET["sukses"])) {
        echo '<div class="alert alert-success">Berhasil mengirim jurnal pembelajaran</div>';
      } else if (isset($_GET["gagal"])) {
        echo '<div class="alert alert-danger"><strong>Gagal!</strong> mengirim jurnal.</div>';
      } else if (isset($_GET["hapusmateri"])) {
        echo '<div class="alert alert-success"><strong>Berhasil!</strong> menghapus jurnal.</div>';
      } else if (isset($_GET["gagalhapusmateri"])) {
        echo '<div class="alert alert-danger"><strong>Gagal!</strong> menghapus jurnal.</div>';
      }
    ?>
    <div id="announcementBoard" class="ann-board d-none">
      <div class="ann-header">
        <h6><i class="bi bi-megaphone-fill text-danger"></i><span>Papan Pengumuman</span></h6>
        <div class="ann-toolbar ms-auto">
          <button id="annRefreshBtn" class="ann-refresh" type="button"><i class="bi bi-arrow-repeat"></i><span>Refresh</span></button>
        </div>
      </div>
      <div class="ann-items" id="annItems"></div>
      <div class="ann-scroller-fade"></div>
    </div>
    
    <div class="row align-items-stretch mb-4 g-3">
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius:18px; overflow:hidden;">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="progress-ring position-relative" style="width:80px; height:80px;">
              <svg viewBox="0 0 36 36" class="circular-chart" style="transform:rotate(-90deg); width:80px; height:80px;">
                <path class="circle-bg" stroke="#eee" stroke-width="3.5" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="circle" stroke="#0d6efd" stroke-linecap="round" stroke-width="3.5" fill="none" stroke-dasharray="<?= $percentJurnal; ?>,100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              </svg>
              <div class="position-absolute top-50 start-50 translate-middle text-center" style="font-size:.85rem; font-weight:600;">
                <?= $percentJurnal; ?>%<br><span class="text-muted" style="font-size:.65rem;">Jurnal</span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-1">Progres Jurnal Hari Ini</h6>
              <div class="small text-muted mb-1">Terisi: <?= $jurnalTerisi; ?> / <?= $totalJadwal; ?> jadwal</div>
              <div class="progress" style="height:6px; border-radius:4px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percentJurnal; ?>%" aria-valuenow="<?= $percentJurnal; ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <?php if($totalJadwal==0){ ?>
                <div class="small text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Tidak ada jadwal hari ini.</div>
              <?php } elseif($percentJurnal==100){ ?>
                <div class="small text-success mt-2"><i class="bi bi-check2-circle me-1"></i>Semua jurnal hari ini sudah terisi.</div>
              <?php } else { ?>
                <div class="small text-primary mt-2">Segera lengkapi jurnal yang belum terisi.</div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-8">
        <div class="quick-grid h-100">
          <div class="quick-card bg-grad-primary" id="qaInputJurnal" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-journal-text"></i></div>
            <div>
              <p class="qc-title">Input Jurnal</p>
              <p class="qc-sub">Isikan jurnal hari ini</p>
            </div>
          </div>
          <div class="quick-card bg-grad-warning" id="qaCetakJurnal" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-printer"></i></div>
            <div>
              <p class="qc-title">Cetak Jurnal</p>
              <p class="qc-sub">Lihat & cetak jurnal</p>
            </div>
          </div>
          <div class="quick-card bg-grad-success" id="qaInputNilai" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
              <p class="qc-title">Input Nilai</p>
              <p class="qc-sub">Penilaian per pertemuan</p>
            </div>
          </div>
          <div class="quick-card bg-grad-info" id="qaDaftarNilai" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-bar-chart"></i></div>
            <div>
              <p class="qc-title">Daftar Nilai</p>
              <p class="qc-sub">Rekap nilai siswa</p>
            </div>
          </div>
          <div class="quick-card bg-grad-secondary" id="qaDaftarPresensi" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-clipboard-check"></i></div>
            <div>
              <p class="qc-title text-uppercase">Rekap Presensi</p>
              <p class="qc-sub">Ringkasan absensi</p>
            </div>
          </div>
          <div class="quick-card bg-grad-pink" id="qaRiwayatPertemuan" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-clock-history"></i></div>
            <div>
              <p class="qc-title">Riwayat Pertemuan</p>
              <p class="qc-sub">Lihat materi sebelumnya</p>
            </div>
          </div>
          <div class="quick-card bg-grad-info" id="qaBeriPengumuman" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-megaphone"></i></div>
            <div>
              <p class="qc-title">Beri Pengumuman</p>
              <p class="qc-sub">Kirim pengumuman ke siswa</p>
            </div>
          </div>
          <div class="quick-card bg-grad-purple" id="qaHistoryTugas" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-list-task"></i></div>
            <div>
              <p class="qc-title">History Tugas</p>
              <p class="qc-sub">Kelola tugas yang dibuat</p>
            </div>
          </div>
          <div class="quick-card bg-grad-info" id="qaDataWaliKelas" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-people-fill"></i></div>
            <div>
              <p class="qc-title">Data Wali Kelas</p>
              <p class="qc-sub">Pantau siswa kelas wali</p>
            </div>
          </div>
          <div class="quick-card bg-grad-danger" id="qaCatatPelanggaran" role="button" tabindex="0">
            <div class="qc-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
              <p class="qc-title">Catat Pelanggaran</p>
              <p class="qc-sub">Catatan pelanggaran siswa</p>
            </div>
          </div>
        </div>
      </div>
    </div>

        <!-- Modal Riwayat Pertemuan -->
        <div class="modal fade" id="modalRiwayat" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
            <div class="modal-content" style="border-radius:24px; overflow:hidden;">
              <div class="modal-header" style="background:linear-gradient(135deg,#ffffff,#f1f5f9);">
                <h5 class="modal-title d-flex align-items-center gap-2 mb-0"><i class="bi bi-clock-history text-primary"></i> Riwayat Pertemuan & Analitik</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body p-3 p-lg-4">
                <div class="riwayat-toolbar rounded-4 p-3 mb-3" style="background:linear-gradient(135deg,#f8fafc,#eef2f7); border:1px solid #e2e8f0; position:relative;">
                  <div class="row g-3 align-items-end">
                    <div class="col-6 col-md-3">
                      <label class="form-label small fw-semibold mb-1">Kelas</label>
                      <select id="rwKelas" class="form-select form-select-sm"><option value="">Semua</option></select>
                    </div>
                    <div class="col-6 col-md-3">
                      <label class="form-label small fw-semibold mb-1">Mapel</label>
                      <select id="rwMapel" class="form-select form-select-sm"><option value="">Semua</option></select>
                    </div>
                    <div class="col-6 col-md-2">
                      <label class="form-label small fw-semibold mb-1">Rentang</label>
                      <select id="rwRange" class="form-select form-select-sm">
                        <option value="7d">7 Hari</option>
                        <option value="14d">14 Hari</option>
                        <option value="30d">30 Hari</option>
                        <option value="prev_week" selected>Minggu Lalu</option>
                        <option value="prev_day">Kemarin</option>
                        <option value="custom">Custom</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-2 d-none" id="rwCustomWrap">
                      <label class="form-label small fw-semibold mb-1">Tanggal</label>
                      <div class="d-flex gap-1">
                        <input type="date" id="rwStart" class="form-control form-control-sm">
                        <input type="date" id="rwEnd" class="form-control form-control-sm">
                      </div>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2 flex-wrap">
                      <button class="btn btn-sm btn-primary flex-grow-1" id="rwBtnApply"><i class="bi bi-search me-1"></i>Load</button>
                      <button class="btn btn-sm btn-outline-secondary" id="rwBtnReset" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                  </div>
                  <div id="rwStatus" class="small text-muted mt-2 ps-1"></div>
                </div>
                <div class="row g-4">
                  <div class="col-12 col-lg-4">
                    <div class="riwayat-analytics p-3 rounded-4 h-100" style="background:#fff; border:1px solid #e2e8f0;">
                      <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="bi bi-bar-chart-line text-primary"></i> Ringkasan</h6>
                      <div id="rwSummary" class="small"></div>
                      <hr>
                      <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2"><i class="bi bi-people text-primary"></i> Statistik Absen</h6>
                      <div id="rwAbsen" class="small"></div>
                      <hr>
                      <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2"><i class="bi bi-activity text-primary"></i> Tren Harian</h6>
                      <div id="rwTrend" class="small"></div>
                      <hr>
                      <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2"><i class="bi bi-lightbulb text-warning"></i> Rekomendasi</h6>
                      <div id="rwSuggest" class="small"></div>
                    </div>
                  </div>
                  <div class="col-12 col-lg-8">
                    <div class="riwayat-timeline p-3 rounded-4" style="background:#fff; border:1px solid #e2e8f0;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2"><i class="bi bi-journal-text text-primary"></i> Timeline Pertemuan</h6>
                        <div class="btn-group btn-group-sm" role="group">
                          <button class="btn btn-outline-secondary active" data-rw-view="list">List</button>
                          <button class="btn btn-outline-secondary" data-rw-view="compact">Compact</button>
                          <button class="btn btn-outline-secondary" data-rw-view="hari">Per Hari</button>
                        </div>
                      </div>
                      <div id="rwTimeline" class="rw-timeline-body"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer small justify-content-between bg-light-subtle">
                <span class="text-muted" id="rwFooterInfo"></span>
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
              </div>
            </div>
          </div>
        </div>
    <!-- Schedule Section - Modern Redesign -->
    <div class="schedule-container">
      <div class="schedule-header">
        <div class="schedule-title">
          <div class="schedule-title-text">
            <i class="bi bi-calendar-event"></i>
            <span>Jadwal Real Time Hari Ini</span>
          </div>
          <span class="live-badge">LIVE</span>
        </div>
        <div class="current-time">
          <div id="currentTime" class="time-display">00:00:00</div>
          <div class="time-label"><?= $hariini; ?>, <?= tgl_indo($tglskr); ?></div>
        </div>
      </div>
      
      <div id="scheduleCards">
        <?php
        $sql = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk = g.no_induk WHERE m.no_induk='$nipguru' AND m.hari='$hariini' ORDER BY m.jam_mulai ASC");
        $cekmapel = mysqli_num_rows($sql);
        
        if ($cekmapel > 0) {
          while ($data = mysqli_fetch_array($sql)) {
            $idmapel = $data['id_mapel'];
            $mulai = $data['jam_mulai'];
            $selesai = $data['jam_selesai'];
            
            // Determine initial status
            $status = getTimeStatus($mulai, $selesai);
            $statusText = getStatusText($status);
            $progress = calculateProgress($mulai, $selesai);
            $timeInfo = getTimeInfoText($status, $mulai, $selesai);
        ?>
        <div class="schedule-card" data-mulai="<?= htmlspecialchars($mulai); ?>" data-selesai="<?= htmlspecialchars($selesai); ?>" data-status="<?= $status ?>" id="card-<?= $idmapel; ?>">
          <div class="card-header">
            <div class="class-info">Kelas <?= $data['kelas']; ?></div>
            <span class="status-indicator status-<?= $status ?>" id="status-<?= $idmapel; ?>">
              <i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>
              <?= $statusText ?>
            </span>
          </div>
          
          <div class="card-body">
            <h5 class="subject-name"><?= $data['nama_mapel']; ?></h5>
            <div class="time-range">
              <i class="bi bi-clock me-1"></i>
              <?= $mulai; ?> - <?= $selesai; ?> WIB
            </div>
            
            <div class="progress-container">
              <div class="time-progress">
                <div class="progress-bar progress-<?= $status ?>" id="progress-<?= $idmapel; ?>" style="width: <?= $progress ?>%"></div>
              </div>
              <div class="time-info" id="time-info-<?= $idmapel; ?>">
                <i class="bi bi-info-circle me-1"></i>
                <?= $timeInfo ?>
              </div>
            </div>
            
            <?php
            // Only show materi file section if the class is not finished yet
            if ($status !== 'finished') {
              $mat = mysqli_query($conn, "SELECT * FROM tbl_materi WHERE id_mapel='$idmapel' AND `tanggal`='$tglskr'");
              if (mysqli_num_rows($mat) < 1) {
                echo '<div class="alert alert-warning mt-2 mb-0 py-2"><i class="bi bi-exclamation-triangle me-1"></i>Belum ada file materi!</div>';
              } else {
                echo '<div class="materi-file">';
                while ($dmat = mysqli_fetch_array($mat)) {
            ?>
              <div class="file-item">
                <a href="../../materi/<?= $dmat['file_materi']; ?>" class="file-link" target="_blank">
                  <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> 
                  <?= $dmat['file_materi']; ?>
                </a>
                <a href="delete-materi.php?id=<?= $dmat['id_materi']; ?>&file=<?= $dmat['file_materi']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin mau menghapus file jurnal ini?');">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            <?php 
                }
                echo '</div>';
              }
            }
            ?>
            
            <div class="action-buttons">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#show" data-id="<?= $data['id_mapel']; ?>">
                <i class="bi bi-journal-text me-1"></i>Isi Jurnal
              </button>
              <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalNilai" data-id="<?= $data['id_mapel']; ?>">
                <i class="bi bi-pencil-square me-1"></i>Input Nilai
              </button>
            </div>
          </div>
        </div>
        <?php 
          } 
        } else { 
        ?>
        <div class="empty-schedule">
          <div class="empty-icon">
            <i class="bi bi-calendar-x"></i>
          </div>
          <h5>Tidak ada jadwal hari ini</h5>
          <p>Anda tidak memiliki jadwal mengajar untuk hari ini</p>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Pilih Jadwal (untuk Input Jurnal/Nilai) -->
<div class="modal fade" id="selectJadwalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Jadwal Hari Ini</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (count($jadwalHariIni) === 0) { ?>
          <div class="alert alert-warning mb-0">Tidak ada jadwal untuk hari ini.</div>
        <?php } else { ?>
          <div class="list-group">
            <?php 
            // Check existing tasks for today - but only if table exists
            $today = date('Y-m-d');
            $nipguru_escaped = mysqli_real_escape_string($conn, $_SESSION['no_induk']);
            $existing_tasks = [];
            
            // Check if tbl_tugas table exists first
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_tugas'");
            $table_exists = ($table_check && mysqli_num_rows($table_check) > 0);
            
            if ($table_exists) {
              foreach ($jadwalHariIni as $j) {
                $idmapel_escaped = mysqli_real_escape_string($conn, $j['id_mapel']);
                $task_query = "SELECT id FROM tbl_tugas WHERE tanggal = '$today' AND id_mapel = '$idmapel_escaped' AND no_induk_guru = '$nipguru_escaped' AND status = 'aktif' LIMIT 1";
                $task_result = mysqli_query($conn, $task_query);
                $existing_tasks[$j['id_mapel']] = ($task_result && mysqli_num_rows($task_result) > 0);
              }
            }
            
            foreach ($jadwalHariIni as $j) { 
              $has_task = $existing_tasks[$j['id_mapel']] ?? false;
              $task_btn_text = $has_task ? 'Hapus Tugas' : 'Input Tugas';
              $task_btn_class = $has_task ? 'btn btn-sm btn-danger btn-pilih-tugas' : 'btn btn-sm btn-outline-success btn-pilih-tugas';
            ?>
              <div class="list-group-item d-flex align-items-center justify-content-between">
                <div>
                  <div class="fw-semibold">Kelas <?= htmlspecialchars($j['kelas']); ?> • <?= htmlspecialchars($j['nama_mapel']); ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($j['jam_mulai']); ?> - <?= htmlspecialchars($j['jam_selesai']); ?> WIB</div>
                </div>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-primary btn-pilih-jurnal" data-id="<?= (int)$j['id_mapel']; ?>">Input Jurnal</button>
                  <button class="btn btn-sm btn-outline-primary btn-pilih-nilai" data-id="<?= (int)$j['id_mapel']; ?>">Input Nilai</button>
                  <button class="<?= $task_btn_class ?>" data-id="<?= (int)$j['id_mapel']; ?>" data-has-task="<?= $has_task ? '1' : '0' ?>"><?= $task_btn_text ?></button>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  </div>

<!-- Modal Isi Jurnal -->
<div class="modal fade" id="show" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Isi Jurnal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="modal-data"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Input Nilai -->
<div class="modal fade" id="modalNilai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Input Nilai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="modal-nilai-body"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Input Tugas -->
<div class="modal fade" id="modalTugas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Input Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="modal-tugas-body"></div>
      </div>
    </div>
  </div>
</div>
<!-- Modal Data Wali Kelas -->
<div class="modal fade" id="modalWaliKelas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:20px; overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(135deg,#ffffff,#f1f5f9);">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i class="bi bi-people-fill text-danger"></i>
          Data Wali Kelas
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3 p-lg-4">
        <?php if (!$waliKelasNama) { ?>
          <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Anda belum terdaftar sebagai Wali Kelas.</div>
        <?php } else { ?>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
              <h6 class="mb-1">Kelas Wali: <span class="badge bg-danger-subtle text-danger border"><?= htmlspecialchars($waliKelasNama) ?></span></h6>
              <small class="text-muted">Periode: <?= date('F Y') ?> • Sumber: absensi harian dan nilai tugas</small>
            </div>
            <div>
              <button class="btn btn-sm btn-outline-secondary" onclick="printWaliKelasTable()"><i class="bi bi-printer"></i> Cetak</button>
            </div>
          </div>

          <?php if (!empty($waliAlpha3List)) { ?>
            <div class="alert alert-danger" role="alert">
              <strong>Peringatan!</strong> Ditemukan siswa dengan Alpha ≥ 3 bulan ini:
              <ul class="mb-0">
                <?php foreach ($waliAlpha3List as $w) { ?>
                  <li><strong><?= htmlspecialchars($w['nama']) ?></strong> (<?= htmlspecialchars($w['nis']) ?>) • Alpha: <?= (int)$w['alpha'] ?></li>
                <?php } ?>
              </ul>
            </div>
          <?php } ?>

          <div class="table-responsive">
            <table class="table align-middle table-bordered" id="tblWaliKelas">
              <thead class="table-light">
                <tr>
                  <th style="width:40px">No</th>
                  <th>Nama Siswa</th>
                  <th class="text-center">Alpha</th>
                  <th class="text-center">Izin</th>
                  <th class="text-center">Sakit</th>
                  <th class="text-center">Dispen</th>
                  <th class="text-center">Rata Nilai Tugas</th>
                  <th class="text-center">Skor Masalah</th>
                  <th>Catatan Pelanggaran</th>
                  <th>Rekomendasi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($waliSiswaData)) { ?>
                  <tr><td colspan="10" class="text-center text-muted">Belum ada data siswa/absensi untuk kelas ini.</td></tr>
                <?php } else { $no=1; foreach ($waliSiswaData as $sd) { 
                    $sev = $sd['score'];
                    $sevClass = $sev >= 10 ? 'bg-danger text-white' : ($sev >= 6 ? 'bg-warning text-dark' : ($sev >= 3 ? 'bg-info text-white' : 'bg-success text-white'));
                    $rekom = [];
                    if ($sd['alpha'] >= 3) $rekom[] = 'Panggilan orang tua';
                    if ($sd['alpha'] >= 1 && ($sd['avg_tugas'] === null || $sd['avg_tugas'] < 75)) $rekom[] = 'Pendampingan belajar';
                    if (($sd['ijin'] + $sd['sakit'] + $sd['dispen']) >= 3) $rekom[] = 'Koordinasi BK/Kesiswaan';
                    if ($sd['pelanggaran_berat'] > 0) $rekom[] = 'Tindakan tegas & konseling';
                    if ($sd['pelanggaran_sedang'] > 0) $rekom[] = 'Pembinaan intensif';
                    if (empty($rekom)) $rekom[] = 'Pertahankan dan beri apresiasi';
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($sd['nama']) ?></div>
                      <div class="text-muted small">NIS: <?= htmlspecialchars($sd['no_induk']) ?></div>
                    </td>
                    <td class="text-center"><span class="badge bg-<?= $sd['alpha']>0?'danger':'secondary' ?>"><?= (int)$sd['alpha'] ?></span></td>
                    <td class="text-center"><span class="badge bg-<?= $sd['ijin']>0?'warning text-dark':'secondary' ?>"><?= (int)$sd['ijin'] ?></span></td>
                    <td class="text-center"><span class="badge bg-<?= $sd['sakit']>0?'info text-dark':'secondary' ?>"><?= (int)$sd['sakit'] ?></span></td>
                    <td class="text-center"><span class="badge bg-<?= $sd['dispen']>0?'primary':'secondary' ?>"><?= (int)$sd['dispen'] ?></span></td>
                    <td class="text-center">
                      <?php if ($sd['avg_tugas']===null) { ?><span class="text-muted">-</span><?php } else { ?>
                        <span class="badge bg-<?= $sd['avg_tugas']<75?'danger':'success' ?>"><?= number_format($sd['avg_tugas'],1) ?></span>
                      <?php } ?>
                    </td>
                    <td class="text-center"><span class="badge <?= $sevClass ?>"><?= number_format($sd['score'],1) ?></span></td>
                    <td>
                      <?php if (!empty($sd['catatan_pelanggaran'])) { ?>
                        <div class="pelanggaran-scroll" style="max-height:80px; overflow-y:auto; font-size:0.8rem; line-height:1.2;">
                          <?= htmlspecialchars($sd['catatan_pelanggaran']) ?>
                        </div>
                        <div class="mt-1">
                          <?php if ($sd['pelanggaran_berat'] > 0) { ?><span class="badge bg-danger" style="font-size:0.6rem;">Berat: <?= $sd['pelanggaran_berat'] ?></span> <?php } ?>
                          <?php if ($sd['pelanggaran_sedang'] > 0) { ?><span class="badge bg-warning text-dark" style="font-size:0.6rem;">Sedang: <?= $sd['pelanggaran_sedang'] ?></span> <?php } ?>
                          <?php if ($sd['pelanggaran_ringan'] > 0) { ?><span class="badge bg-info" style="font-size:0.6rem;">Ringan: <?= $sd['pelanggaran_ringan'] ?></span> <?php } ?>
                        </div>
                      <?php } else { ?>
                        <span class="text-muted small">Tidak ada catatan</span>
                      <?php } ?>
                    </td>
                    <td>
                      <ul class="mb-0 small">
                        <?php foreach ($rekom as $r) { ?><li><?= htmlspecialchars($r) ?></li><?php } ?>
                      </ul>
                    </td>
                  </tr>
                <?php } } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<!-- Modal Cetak Jurnal (Enhanced) -->
<div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
    <div class="modal-content cetak-modal">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-semibold mb-1 d-flex align-items-center gap-2"><i class="bi bi-printer"></i> Cetak Jurnal Pembelajaran</h5>
          <div class="text-muted small">Filter rentang tanggal, kelas, lalu pratinjau sebelum cetak</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3 pb-0 px-3 px-lg-4">
        <div class="cetak-toolbar rounded-4 p-3 mb-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
              <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
              <input type="date" class="form-control form-control-sm" id="cetakStart">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
              <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
              <input type="date" class="form-control form-control-sm" id="cetakEnd">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
              <label class="form-label small fw-semibold mb-1">Kelas (opsional)</label>
              <select class="form-select form-select-sm" id="cetakKelas">
                <option value="">Semua Kelas</option>
                <?php
                  // Ambil seluruh kelas yang diampu guru dari semua jadwal ($jadwalSemua)
                  $kelasDistinct = [];
                  if (isset($jadwalSemua) && is_array($jadwalSemua)) {
                    foreach ($jadwalSemua as $jj) {
                      $k = isset($jj['kelas']) ? trim($jj['kelas']) : '';
                      if ($k !== '' && !in_array($k, $kelasDistinct, true)) {
                        $kelasDistinct[] = $k;
                      }
                    }
                  }
                  sort($kelasDistinct, SORT_NATURAL|SORT_FLAG_CASE);
                  foreach ($kelasDistinct as $k) {
                    echo '<option value="'.htmlspecialchars($k).'">'.htmlspecialchars($k).'</option>';
                  }
                ?>
              </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
              <label class="form-label small fw-semibold mb-1">Status</label>
              <select class="form-select form-select-sm" id="cetakStatusFilter">
                <option value="">Semua</option>
                <option value="filled">Hanya yang terisi</option>
                <option value="empty">Hanya yang belum terisi</option>
              </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-primary flex-grow-1" id="btnTerapkanFilter"><i class="bi bi-funnel me-1"></i>Terapkan</button>
              <button class="btn btn-sm btn-outline-secondary" id="btnResetFilter" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></button>
              <button class="btn btn-sm btn-success" id="btnPrintIframe" title="Cetak"><i class="bi bi-printer"></i></button>
              <button class="btn btn-sm btn-outline-primary" id="btnOpenNewTab" title="Buka Tab Baru"><i class="bi bi-box-arrow-up-right"></i></button>
            </div>
            <!-- Removed Debug / Force Local / Non Print options -->
          </div>
        </div>
        <div id="cetakStatus" class="small text-muted mb-2 ps-1"></div>
        <div class="cetak-frame-wrapper position-relative rounded-4 overflow-hidden">
          <div class="cetak-loading d-none" id="cetakLoading">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            <p class="mt-2 mb-0 small">Memuat pratinjau jurnal...</p>
          </div>
          <iframe src="" id="frameCetak" frameborder="0" class="cetak-iframe"></iframe>
        </div>
      </div>
      <div class="modal-footer small justify-content-between bg-light-subtle border-0 mt-3 rounded-bottom-4 px-3 px-lg-4">
        <span class="text-muted">Pastikan data sudah benar sebelum mencetak.</span>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-sm btn-primary" id="btnPrintFooter"><i class="bi bi-printer me-1"></i>Cetak</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Beri Pengumuman -->
<div class="modal fade" id="modalPengumuman" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header" style="background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-radius:16px 16px 0 0;">
        <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
          <i class="bi bi-megaphone"></i> Beri Pengumuman
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="pengumumanForm">
          <!-- Form will be loaded here -->
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat formulir...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Footer Navigation -->
<div class="footer-nav">
  <a href="#" class="active">
    <i class="bi bi-house-door-fill"></i>
    <small>Home</small>
  </a>
  <!-- Menu Detail Jadwal -->
  <a href="detail-jadwal.php?id=<?= htmlspecialchars($dataguru['id_guru'] ?? ''); ?>&no_induk=<?= htmlspecialchars($dataguru['no_induk'] ?? $nipguru); ?>">
    <i class="bi bi-calendar-check"></i>
    <small>Detail Jadwal</small>
  </a>
  <a href="../../logout.php" onclick="return confirm('Yakin mau logout?');">
    <i class="bi bi-box-arrow-right"></i>
    <small>Logout</small>
  </a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
  // Embed jadwal for quick decision
  window.JADWAL_TODAY = <?= json_encode(array_map(function($x){return [
    'id_mapel'=>(int)$x['id_mapel'],
    'kelas'=>$x['kelas'],
    'nama_mapel'=>$x['nama_mapel'],
    'jam_mulai'=>$x['jam_mulai'],
    'jam_selesai'=>$x['jam_selesai']
  ];}, $jadwalHariIni)); ?>;

  // Helper functions for time calculations
  function parseHM(str) {
    if (!str) return null;
    str = String(str);
    const parts = str.split(':');
    if (parts.length >= 2) {
      const h = parseInt(parts[0], 10);
      const m = parseInt(parts[1], 10);
      if (!isNaN(h) && !isNaN(m)) return h * 60 + m;
    }
    return null;
  }

  function getTimeStatus(mulaiStr, selesaiStr) {
    const now = new Date();
    const minutesNow = now.getHours() * 60 + now.getMinutes();
    const mulai = parseHM(mulaiStr);
    const selesai = parseHM(selesaiStr);
    
    if (mulai === null || selesai === null) return 'unknown';
    
    if (minutesNow < mulai) return 'upcoming';
    if (minutesNow >= mulai && minutesNow <= selesai) return 'ongoing';
    return 'finished';
  }

  function calculateProgress(mulaiStr, selesaiStr) {
    const now = new Date();
    const minutesNow = now.getHours() * 60 + now.getMinutes();
    const mulai = parseHM(mulaiStr);
    const selesai = parseHM(selesaiStr);
    
    if (mulai === null || selesai === null) return 0;
    if (minutesNow < mulai) return 0;
    if (minutesNow > selesai) return 100;
    
    const duration = selesai - mulai;
    const elapsed = minutesNow - mulai;
    return Math.round((elapsed / duration) * 100);
  }

  function getStatusText(status) {
    switch(status) {
      case 'upcoming': return 'Akan Datang';
      case 'ongoing': return 'Berlangsung';
      case 'finished': return 'Selesai';
      default: return 'Tidak Diketahui';
    }
  }

  function getTimeInfoText(status, mulaiStr, selesaiStr) {
    const now = new Date();
    const minutesNow = now.getHours() * 60 + now.getMinutes();
    const mulai = parseHM(mulaiStr);
    const selesai = parseHM(selesaiStr);
    
    if (mulai === null || selesai === null) return 'Waktu tidak valid';
    
    switch(status) {
      case 'upcoming':
        const diffUpcoming = mulai - minutesNow;
        const hoursUpcoming = Math.floor(diffUpcoming / 60);
        const minsUpcoming = diffUpcoming % 60;
        const timeLeftUpcoming = hoursUpcoming > 0 ? 
          `${hoursUpcoming} jam ${minsUpcoming} menit` : 
          `${minsUpcoming} menit`;
        return `Dimulai dalam ${timeLeftUpcoming}`;
        
      case 'ongoing':
        const diffOngoing = selesai - minutesNow;
        const hoursOngoing = Math.floor(diffOngoing / 60);
        const minsOngoing = diffOngoing % 60;
        const timeLeftOngoing = hoursOngoing > 0 ? 
          `${hoursOngoing} jam ${minsOngoing} menit` : 
          `${minsOngoing} menit`;
        return `Selesai dalam ${timeLeftOngoing}`;
        
      case 'finished':
        return `Telah selesai pada ${selesaiStr}`;
        
      default:
        return `${mulaiStr} - ${selesaiStr}`;
    }
  }

  // Update current time display
  function updateCurrentTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('id-ID', {
      hour: '2-digit', 
      minute: '2-digit', 
      second: '2-digit'
    });
    const timeEl = document.getElementById('currentTime');
    if (timeEl) timeEl.textContent = timeStr;
  }

  // Update schedule cards status and information
  function updateScheduleCards() {
    const cards = document.querySelectorAll('.schedule-card');
    
    cards.forEach(card => {
      const mulaiStr = card.getAttribute('data-mulai');
      const selesaiStr = card.getAttribute('data-selesai');
      const cardId = card.id;
      const idmapel = cardId ? cardId.split('-')[1] : '';
      
      const status = getTimeStatus(mulaiStr, selesaiStr);
      const statusText = getStatusText(status);
      const progress = calculateProgress(mulaiStr, selesaiStr);
      const timeInfo = getTimeInfoText(status, mulaiStr, selesaiStr);
      
      // Update status indicator
      const statusEl = card.querySelector('.status-indicator');
      if (statusEl) {
        statusEl.className = `status-indicator status-${status}`;
        statusEl.innerHTML = `<i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i> ${statusText}`;
      }
      
      // Update progress bar
      const progressBar = card.querySelector('.progress-bar');
      if (progressBar) {
        progressBar.className = `progress-bar progress-${status}`;
        progressBar.style.width = `${progress}%`;
      }
      
      // Update time info
      const timeInfoEl = card.querySelector('.time-info');
      if (timeInfoEl) {
        timeInfoEl.innerHTML = `<i class="bi bi-info-circle me-1"></i> ${timeInfo}`;
      }
      
      // Update card status attribute
      card.setAttribute('data-status', status);
    });
  }

  // Initial updates
  updateCurrentTime();
  updateScheduleCards();
  
  // Set up intervals for live updates
  setInterval(updateCurrentTime, 1000);
  setInterval(updateScheduleCards, 30000); // Update every 30 seconds

  // Modal functions for Jurnal and Nilai
  function openInputJurnal(idmapel){
    if (!idmapel) return;
    $('.modal-data').html('<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</div>');
    var el = document.getElementById('show');
    var m = new bootstrap.Modal(el);
    m.show();
    $.post('detailmateri.php', { getDetail: idmapel }, function(data){
      $('.modal-data').html(data);
    }).fail(function(xhr){
      var extra = '';
      if (xhr && xhr.status) {
        var snippet = (xhr.responseText || '').replace(/<script[\s\S]*?<\/script>/gi,'');
        if (snippet.length > 220) snippet = snippet.substring(0,220)+'...';
        extra = '<div class="small text-muted mt-2">Status: '+xhr.status+' '+xhr.statusText+'<br>Resp: '+$('<div>').text(snippet).html()+'</div>';
      }
      $('.modal-data').html('<div class="alert alert-danger">Gagal memuat form jurnal.'+extra+'</div>');
    });
  }
  
  function openInputNilai(idmapel){
    if (!idmapel) return;
    $('.modal-nilai-body').html('<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</div>');
    var el = document.getElementById('modalNilai');
    var m = new bootstrap.Modal(el);
    m.show();
    $.post('inputnilai.php', { getDetail: idmapel }, function(data){
      $('.modal-nilai-body').html(data);
    }).fail(function(xhr){
      var extra='';
      if(xhr && xhr.status){
        var snippet=(xhr.responseText||'').replace(/<script[\s\S]*?<\/script>/gi,'');
        if(snippet.length>220) snippet=snippet.substring(0,220)+'...';
        extra='<div class="small text-muted mt-2">Status: '+xhr.status+' '+xhr.statusText+'<br>Resp: '+$('<div>').text(snippet).html()+'</div>';
      }
      $('.modal-nilai-body').html('<div class="alert alert-danger">Gagal memuat form nilai.'+extra+'</div>');
    });
  }
  
  function openInputTugas(idmapel){
    if (!idmapel) return;
    $('.modal-tugas-body').html('<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</div>');
    var el = document.getElementById('modalTugas');
    var m = new bootstrap.Modal(el);
    m.show();
    $.post('inputtugas.php', { getDetail: idmapel }, function(data){
      $('.modal-tugas-body').html(data);
    }).fail(function(xhr){
      var extra='';
      if(xhr && xhr.status){
        var snippet=(xhr.responseText||'').replace(/<script[\s\S]*?<\/script>/gi,'');
        if(snippet.length>220) snippet=snippet.substring(0,220)+'...';
        extra='<div class="small text-muted mt-2">Status: '+xhr.status+' '+xhr.statusText+'<br>Resp: '+$('<div>').text(snippet).html()+'</div>';
      }
      $('.modal-tugas-body').html('<div class="alert alert-danger">Gagal memuat form tugas.'+extra+'</div>');
    });
  }

  // Quick Actions handlers
  $('#qaInputJurnal').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      if (window.JADWAL_TODAY.length === 1) {
        openInputJurnal(window.JADWAL_TODAY[0].id_mapel);
      } else {
        var sm = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
        sm.show();
      }
    }
  });
  $('#qaCetakJurnal').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      // Kosongkan filter untuk menampilkan semua data saat pertama dibuka
      $('#cetakStart').val('');
      $('#cetakEnd').val('');
      $('#cetakKelas').val('');
      $('#cetakStatus').text('Menampilkan semua jurnal (tanpa filter).');
      loadCetakFrame(true); // initial
      new bootstrap.Modal(document.getElementById('modalCetak')).show();
    }
  });
  $('#qaInputNilai').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      if (window.JADWAL_TODAY.length === 1) {
        openInputNilai(window.JADWAL_TODAY[0].id_mapel);
      } else {
        var sm = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
        sm.show();
      }
    }
  });
  $('#qaDaftarNilai').on('click keypress', function(e){ if (e.type==='click' || e.key==='Enter'){ window.location = 'nilai.php'; }});
  $('#qaDaftarPresensi').on('click keypress', function(e){ if (e.type==='click' || e.key==='Enter'){ window.location = 'presensi.php'; }});
  $('#qaRiwayatPertemuan').on('click keypress', function(e){ if (e.type==='click' || e.key==='Enter'){ openRiwayatModal(); }});
  $('#qaBeriPengumuman').on('click keypress', function(e){ 
    if (e.type==='click' || e.key==='Enter'){ 
      openPengumumanModal(); 
    }
  });
  $('#qaHistoryTugas').on('click keypress', function(e){ 
    if (e.type==='click' || e.key==='Enter'){ 
      openHistoryTugasModal();
    }
  });
  $('#qaDataWaliKelas').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      var el = document.getElementById('modalWaliKelas');
      var m = new bootstrap.Modal(el);
      m.show();
    }
  });
  // ------------ History Tugas Modal Functions ------------
  function openHistoryTugasModal() {
    // Show modal
    $('#historyTugasModal').modal('show');
    
    // Load content
    fetch('history-tugas-content.php')
      .then(response => response.text())
      .then(data => {
        $('#historyTugasContent').html(data);
      })
      .catch(error => {
        $('#historyTugasContent').html(`
          <div class="alert alert-danger m-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Gagal memuat data tugas. Silakan coba lagi.
          </div>
        `);
      });
  }

  // Global functions for task operations (called from modal content)
  window.viewTask = function(taskId) {
    $('#taskDetailContent').html('<div class="text-center"><div class="spinner-border"></div><div>Memuat...</div></div>');
    $('#taskDetailModal').modal('show');
    
    fetch('task-detail.php?id=' + taskId)
      .then(response => response.text())
      .then(data => {
        $('#taskDetailContent').html(data);
      })
      .catch(error => {
        $('#taskDetailContent').html('<div class="alert alert-danger">Gagal memuat detail tugas</div>');
      });
  };
  
  window.editTask = function(taskId) {
    $('#editTaskContent').html('<div class="text-center"><div class="spinner-border"></div><div>Memuat...</div></div>');
    $('#editTaskModal').modal('show');
    
    fetch('edit-tugas.php?id=' + taskId)
      .then(response => response.text())
      .then(data => {
        $('#editTaskContent').html(data);
      })
      .catch(error => {
        $('#editTaskContent').html('<div class="alert alert-danger">Gagal memuat form edit</div>');
      });
  };
  
  window.deleteTask = function(taskId) {
    Swal.fire({
      title: 'Hapus Tugas?',
      text: 'Tugas yang dihapus tidak dapat dikembalikan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('action', 'hapus');
        formData.append('tugas_id', taskId);
        
        fetch('simpan_tugas.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Terhapus!', 'Tugas berhasil dihapus.', 'success').then(() => {
              openHistoryTugasModal(); // Reload modal content
            });
          } else {
            Swal.fire('Error!', data.message || 'Gagal menghapus tugas', 'error');
          }
        })
        .catch(error => {
          Swal.fire('Error!', 'Terjadi kesalahan saat menghapus tugas', 'error');
        });
      }
    });
  };

  window.refreshHistoryTugas = function() {
    openHistoryTugasModal();
  };

  // ------------ Pengumuman (Announcements) ------------
  const Ann = { lastIds: new Set(), polling:null };
  function formatAnnouncementBody(raw){
    if(!raw) return '<span class="text-muted fst-italic">(kosong)</span>';
    let text = raw.trim();
    // Normalize line endings
    text = text.replace(/\r\n?|\n/g,'\n');
    const lines = text.split('\n').map(l=>l.trim()).filter(l=>l!=='');
    // Detect bullet usage
    const bullets = lines.every(l=>/^(\-|\*|\•)\s+/.test(l)) && lines.length>1;
    if(bullets){
      return '<ul class="mb-0 ps-3" style="list-style:disc;">'+lines.map(l=>'<li>'+escapeHtml(l.replace(/^(\-|\*|\•)\s+/,'').replace(/\s{2,}/g,' '))+'</li>').join('')+'</ul>';
    }
    // Paragraph join if long line
    if(lines.length===1){ return '<span>'+escapeHtml(lines[0])+'</span>'; }
    return lines.map(l=>'<div>'+escapeHtml(l)+'</div>').join('');
  }
  function fetchAnnouncements(manual=false){
    fetch('api_pengumuman.php').then(r=>r.json()).then(j=>{
      const board = $('#announcementBoard');
      if(!j.items || !j.items.length){ board.addClass('d-none'); return; }
      board.removeClass('d-none');
      const wrap = $('#annItems'); wrap.empty();
      const newIds = new Set();
      j.items.forEach(it=>{
        newIds.add(it.id);
        const isNew = !Ann.lastIds.has(it.id);
        const penting = it.penting==1; 
        const body = formatAnnouncementBody(it.isi);
        const unreadClass = (!it.read)?'border-danger ann-new-glow':'';
        const attach = it.lampiran?`<a href="../../materi/${escapeHtml(it.lampiran)}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2 ms-2"><i class="bi bi-file-earmark-pdf"></i></a>`:'';
        const readToggle = `<button data-ann-read='${it.id}' class='btn btn-sm btn-${it.read?'outline-success':'outline-danger'} ms-auto py-0 px-2' title='${it.read?'Tandai belum dibaca':'Tandai sudah dibaca'}'><i class='bi ${it.read?'bi-check-circle':'bi-eye'}'></i></button>`;
        const item = $(`
          <div class="ann-item ${unreadClass}" data-ann-id='${it.id}'>
             <div class="d-flex w-100 align-items-center gap-2">
               <div class="ann-title flex-grow-1">${penting?'<span class="ann-badge-penting">PENTING</span>':''}<span>${escapeHtml(it.judul)}</span>${attach}</div>
               ${readToggle}
             </div>
             <div class="ann-meta"><span><i class="bi bi-calendar-event"></i> ${escapeHtml(it.mulai)} s/d ${escapeHtml(it.selesai)}</span><span class="text-muted">Update: ${escapeHtml(it.updated_at.slice(0,16).replace('T',' '))}</span></div>
             <div class="ann-body">${body}</div>
           </div>`);
        wrap.append(item);
        if(isNew && penting){ showAnnToast('Pengumuman Penting: '+it.judul); }
      });
      Ann.lastIds = newIds;
    }).catch(e=>{
      if(manual) showToast('Gagal memuat pengumuman', 'error');
    });
  }
  $('#annRefreshBtn').on('click', function(){ fetchAnnouncements(true); });
  fetchAnnouncements();
  Ann.polling = setInterval(fetchAnnouncements, 120000); // 2 menit

  // Mark read / unread
  $(document).on('click','[data-ann-read]', function(){
    const id = $(this).data('ann-read');
    const btn = $(this);
    fetch('api_pengumuman_read.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+encodeURIComponent(id)})
      .then(r=>r.json()).then(js=>{ if(js.ok){ btn.removeClass('btn-outline-danger').addClass('btn-outline-success').html('<i class="bi bi-check-circle"></i>'); btn.closest('.ann-item').removeClass('ann-new-glow border-danger'); } });
  });

  // Simple toast
  function showAnnToast(msg){
    let box = document.getElementById('annToastBox');
    if(!box){
      box = document.createElement('div');
      box.id='annToastBox';
      box.style.position='fixed'; box.style.bottom='80px'; box.style.right='18px'; box.style.zIndex='2000'; box.style.display='flex'; box.style.flexDirection='column'; box.style.gap='8px';
      document.body.appendChild(box);
    }
    const card = document.createElement('div');
    card.className='shadow rounded-3 px-3 py-2 bg-white border border-danger-subtle ann-toast-blink';
    card.style.minWidth='240px'; card.style.fontSize='.75rem'; card.innerHTML='<div class="fw-semibold text-danger mb-1"><i class="bi bi-megaphone"></i> Pengumuman</div><div>'+escapeHtml(msg)+'</div>';
    box.appendChild(card);
    setTimeout(()=>{ card.style.opacity='0'; card.style.transition='opacity .5s'; setTimeout(()=>card.remove(),600); }, 6000);
  }


  // ------------ Riwayat Pertemuan Feature ------------
  const RW = {
    jadwalAll: <?= json_encode($jadwalSemua); ?>,
    state: { kelas:'', mapel:'', range:'prev_week', start:'', end:'', view:'list' },
    cache: {},
  };

  function buildDistinctLists(){
    const kelasSet = new Set(); const mapelSet = new Set();
    RW.jadwalAll.forEach(j=>{ if(j.kelas) kelasSet.add(j.kelas); if(j.nama_mapel) mapelSet.add(j.nama_mapel); });
    const kelasArr = Array.from(kelasSet).sort((a,b)=>a.localeCompare(b,'id',{numeric:true}));
    const mapelArr = Array.from(mapelSet).sort((a,b)=>a.localeCompare(b,'id',{numeric:true}));
    const selK = $('#rwKelas'); const keepK = selK.val(); selK.find('option:not(:first)').remove();
    kelasArr.forEach(k=> selK.append(`<option value="${escapeHtml(k)}">${escapeHtml(k)}</option>`));
    if(keepK) selK.val(keepK);
    const selM = $('#rwMapel'); const keepM = selM.val(); selM.find('option:not(:first)').remove();
    mapelArr.forEach(m=> selM.append(`<option value="${escapeHtml(m)}">${escapeHtml(m)}</option>`));
    if(keepM) selM.val(keepM);
  }

  function escapeHtml(str){ return String(str).replace(/[&<>\"]/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[s])); }

  function openPengumumanModal(){
    const modal = new bootstrap.Modal(document.getElementById('modalPengumuman'));
    modal.show();
    loadPengumumanForm();
  }

  function loadPengumumanForm(){
    $.ajax({
      url: '../../pengumuman-guru-form.php',
      method: 'GET',
      success: function(data) {
        $('#pengumumanForm').html(data);
      },
      error: function() {
        $('#pengumumanForm').html('<div class="alert alert-danger">Gagal memuat formulir pengumuman.</div>');
      }
    });
  }

  function openRiwayatModal(){
    buildDistinctLists();
    const modal = new bootstrap.Modal(document.getElementById('modalRiwayat'));
    modal.show();
    loadRiwayat();
  }

  function buildApiUrl(){
    const p = new URLSearchParams();
    if(RW.state.kelas) p.append('kelas', RW.state.kelas);
    if(RW.state.mapel) p.append('mapel', RW.state.mapel);
    if(RW.state.range==='custom'){ p.append('range','custom'); if(RW.state.start) p.append('start',RW.state.start); if(RW.state.end) p.append('end',RW.state.end); }
    else p.append('range', RW.state.range);
    return 'api_riwayat.php?'+p.toString();
  }

  function loadRiwayat(){
    const url = buildApiUrl();
    $('#rwStatus').html('<span class="text-primary"><span class="spinner-border spinner-border-sm me-1"></span>Memuat data...</span>');
    fetch(url).then(r=>{
      if (!r.ok) {
        // Try to parse JSON body for structured error, otherwise show generic message
        return r.text().then(t=>{ throw { httpStatus: r.status, body: t }; });
      }
      return r.json();
    }).then(json=>{
      if(json.error){
        // Friendly messages for known server-side error shapes
        if(json.error === 'missing_tables' && json.missing) {
          $('#rwStatus').html('<span class="text-danger">Gagal memuat: struktur database belum lengkap</span>');
          console.error('Riwayat: missing tables', json.missing);
          return;
        }
        $('#rwStatus').html('<span class="text-danger">Gagal memuat: server_error</span>');
        console.error('Riwayat API error:', json);
        return;
      }
      RW.cache.last = json;
      renderRiwayat(json);
    }).catch(err=>{
      // err may be network error, thrown object with httpStatus/body, or JSON parse error
      if (err && err.httpStatus) {
        // try to surface small hint to user, but log details to console
        $('#rwStatus').html('<span class="text-danger">Gagal memuat: server mengembalikan status '+err.httpStatus+'</span>');
        console.error('Riwayat HTTP Error:', err.httpStatus, err.body);
      } else {
        $('#rwStatus').html('<span class="text-danger">Kesalahan jaringan</span>');
        console.error('Riwayat Fetch Error:', err);
      }
    });
  }

  function renderRiwayat(data){
    const {period, count, distinct, trend, absen, data:rows} = data;
    $('#rwStatus').text(`Periode: ${period.label} | ${count} pertemuan`);
    $('#rwFooterInfo').text(`${count} pertemuan ditemukan. Kelas: ${distinct.kelas.length} • Mapel: ${distinct.mapel.length}`);
    // Summary
    let materiKosong=0, absenKosong=0; rows.forEach(r=>{ if(!r.materi||r.materi.trim()==='') materiKosong++; if(!r.absen||r.absen.trim()==='') absenKosong++; });
    const fillRate = count? Math.round(((count-materiKosong)/count)*100):0;
    $('#rwSummary').html(`
      <div>Total Pertemuan: <strong>${count}</strong></div>
      <div>Materi Terisi: <strong>${count-materiKosong}</strong> (${fillRate}%)</div>
      <div>Tanpa Materi: <strong class='text-danger'>${materiKosong}</strong></div>
      <div>Tanpa Absen: <strong class='text-warning'>${absenKosong}</strong></div>
      <div>Kelas Unik: ${distinct.kelas.length}</div>
      <div>Mapel Unik: ${distinct.mapel.length}</div>
    `);
    // Absen stats
    const totalAbsen = Object.values(absen).reduce((a,b)=>a+b,0) || 0;
    let absenHtml = '<div class="d-flex flex-wrap gap-2">';
    Object.entries(absen).forEach(([k,v])=>{
      if(v===0) return; const pct = totalAbsen? Math.round((v/totalAbsen)*100):0;
      const badgeCls = k==='A'?'danger':(k==='I'?'warning':(k==='S'?'info':'secondary'));
      absenHtml += `<span class='badge bg-${badgeCls}'>${k}: ${v} (${pct}%)</span>`;
    });
    if(totalAbsen===0) absenHtml+='<span class="text-muted">Tidak ada data absen</span>';
    absenHtml+='</div>';
    $('#rwAbsen').html(absenHtml);
    // Trend
    if(trend.length){
      let tHtml = '<div class="rw-trend-chart">';
      const maxCnt = Math.max(...trend.map(t=>t.count));
      trend.forEach(t=>{
        const h = maxCnt? ( (t.count/maxCnt)*60 + 8 ):8;
        tHtml += `<div class='rw-trend-bar' title='${t.date} (${t.count})'><div style='height:${h}px' data-count='${t.count}'></div><span>${t.date.slice(5)}</span></div>`;
      });
      tHtml += '</div>';
      $('#rwTrend').html(tHtml);
    } else {
      $('#rwTrend').html('<span class="text-muted">Tidak ada data tren</span>');
    }
    // Suggestions
    const suggestions = [];
    if(fillRate < 90 && count>5) suggestions.push('Lengkapi jurnal yang masih kosong untuk menjaga kelengkapan dokumentasi.');
    if(absen['A']>0) suggestions.push('Perhatikan tingkat ketidakhadiran (A) dan lakukan tindak lanjut.');
    if(!suggestions.length) suggestions.push('Riwayat terlihat baik. Pertahankan konsistensi.');
    $('#rwSuggest').html('<ul class="mb-0 ps-3">'+suggestions.map(s=>'<li>'+escapeHtml(s)+'</li>').join('')+'</ul>');
    // Timeline rendering
    renderTimeline(rows);
  }

  function renderTimeline(rows){
    const container = $('#rwTimeline');
    if(!rows.length){ container.html('<div class="text-muted fst-italic">Tidak ada pertemuan pada periode ini.</div>'); return; }
    if(RW.state.view==='list'){
      const html = rows.map(r=> timelineItem(r,'list')).join('');
      container.html('<div class="rw-list-view">'+html+'</div>');
    } else if(RW.state.view==='compact') {
      const html = rows.map(r=> timelineItem(r,'compact')).join('');
      container.html('<div class="rw-compact-view">'+html+'</div>');
    } else { // per hari grouping
      const byDate = {};
      rows.forEach(r=>{ (byDate[r.date] = byDate[r.date] || []).push(r); });
      let html='';
      Object.keys(byDate).sort().reverse().forEach(d=>{
        html += `<div class='rw-day-group'><div class='rw-day-header'>${d}</div>`;
        html += byDate[d].map(r=>timelineItem(r,'hari')).join('');
        html += '</div>';
      });
      container.html(html);
    }
  }

  function timelineItem(r,mode){
    const materiShort = r.materi? escapeHtml(r.materi.length>160? r.materi.slice(0,160)+'…':r.materi) : '<span class="text-muted fst-italic">(Belum diisi)</span>';
    // Handle absen info dengan scroll container untuk menampilkan semua siswa
    let absenInfo = '';
    
    // Use absen_detail from API if available, otherwise fall back to old absen field
    const absenData = r.absen_detail || r.absen || '';
    
    if(absenData && absenData.trim() !== '') {
      // Split by comma untuk list siswa
      const absenItems = absenData.split(',').map(item => item.trim()).filter(item => item !== '');
      
      if(absenItems.length > 0) {
        if(mode === 'hari' && absenItems.length > 2) {
          // Untuk mode hari, gunakan scroll container jika lebih dari 2 siswa
          const scrollItems = absenItems.map(item => '<span class="rw-absen-item">' + escapeHtml(item) + '</span>').join('');
          absenInfo = '<div class="text-muted">Absen:<div class="rw-absen-scroll">' + scrollItems + '</div></div>';
        } else {
          // Untuk mode lain atau jika sedikit siswa, tampilkan normal
          const displayText = absenItems.length > 3 ? 
            absenItems.slice(0,3).join(', ') + ' (+' + (absenItems.length-3) + ' lagi)' : 
            absenItems.join(', ');
          absenInfo = '<span class="text-muted">Absen: ' + escapeHtml(displayText) + '</span>';
        }
      } else {
        absenInfo = '<span class="text-muted">Absen: -</span>';
      }
    } else {
      absenInfo = '<span class="text-muted">Absen: -</span>';
    }
    
    const badge = r.materi? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Kosong</span>';
    if(mode==='compact'){
      return `<div class='rw-item-compact'><div class='rw-ic-head'><strong>${r.date}</strong> • ${escapeHtml(r.kelas)} • ${escapeHtml(r.nama_mapel)} ${badge}</div><div class='rw-ic-body'>${materiShort}</div></div>`;
    }
    if(mode==='hari'){
      return `<div class='rw-item-hari'><div class='rw-h-col1'><div class='rw-h-jam'>${r.jam_mulai}-${r.jam_selesai}</div><div class='rw-h-mapel'>${escapeHtml(r.nama_mapel)}<span class='ms-2 small text-muted'>${escapeHtml(r.kelas)}</span></div></div><div class='rw-h-col2'>${materiShort}<div class='small mt-1'>${absenInfo}</div></div><div class='rw-h-col3 text-end'>${badge}</div></div>`;
    }
    return `<div class='rw-item border rounded-3 p-3 mb-2'><div class='d-flex justify-content-between flex-wrap gap-2 mb-1'><div class='fw-semibold'>${r.date} • ${escapeHtml(r.kelas)} • ${escapeHtml(r.nama_mapel)}</div>${badge}</div><div class='small'>${materiShort}</div><div class='small mt-1 text-muted'>${absenInfo}</div></div>`;
  }

  // Events
  $('#rwRange').on('change', function(){
    RW.state.range = this.value; 
    if(this.value==='custom'){ $('#rwCustomWrap').removeClass('d-none'); } else { $('#rwCustomWrap').addClass('d-none'); }
  });
  $('#rwKelas').on('change', function(){ RW.state.kelas = this.value; });
  $('#rwMapel').on('change', function(){ RW.state.mapel = this.value; });
  $('#rwStart').on('change', function(){ RW.state.start = this.value; });
  $('#rwEnd').on('change', function(){ RW.state.end = this.value; });
  $('#rwBtnApply').on('click', function(){ loadRiwayat(); });
  $('#rwBtnReset').on('click', function(){
    RW.state = { kelas:'', mapel:'', range:'prev_week', start:'', end:'', view: RW.state.view };
    $('#rwKelas').val(''); $('#rwMapel').val(''); $('#rwRange').val('prev_week').trigger('change'); $('#rwStart').val(''); $('#rwEnd').val('');
    loadRiwayat();
  });
  $(document).on('click','[data-rw-view]', function(){
    $('[data-rw-view]').removeClass('active'); $(this).addClass('active');
    RW.state.view = $(this).data('rw-view');
    if(RW.cache.last) renderTimeline(RW.cache.last.data);
  });


  // ---------------- Cetak Jurnal Enhancement Logic ----------------
  function buildCetakUrl(initial=false){
    if(initial) return '/pages/guru/cetak_jurnal.php';
    const start = $('#cetakStart').val();
    const end = $('#cetakEnd').val();
    const kelas = $('#cetakKelas').val().trim();
    const statusF = $('#cetakStatusFilter').val ? $('#cetakStatusFilter').val() : '';
    const params = new URLSearchParams();
    // Guru endpoint expects tgl1/tgl2 instead of tglAwal/tglAkhir
    if(start) params.append('tgl1', start);
    if(end) params.append('tgl2', end);
    if(kelas) params.append('kelas', kelas);
    if(statusF) params.append('status', statusF);
    return '/pages/guru/cetak_jurnal.php'+(params.toString()?('?'+params.toString()):'');
  }

  function loadCetakFrame(initial=false){
    const url = buildCetakUrl(initial);
    const iframe = document.getElementById('frameCetak');
    if(!iframe) return;
    $('#cetakLoading').removeClass('d-none');
    iframe.classList.add('loading');
    iframe.src = url;
  }

  // Iframe load handler
  $('#frameCetak').on('load', function(){
    $('#cetakLoading').addClass('d-none');
    this.classList.remove('loading');
  });

  $('#btnTerapkanFilter').on('click', function(){
    const s = $('#cetakStart').val();
    const e = $('#cetakEnd').val();
    let msg = 'Menampilkan';
    if(s && e) msg += ' jurnal '+s+' s.d '+e; else if(s) msg += ' jurnal sejak '+s; else if(e) msg += ' jurnal sampai '+e; else msg += ' semua jurnal';
    const kelas = $('#cetakKelas').val().trim();
    if(kelas) msg += ' | Kelas '+kelas;
    const st = $('#cetakStatusFilter').val ? $('#cetakStatusFilter').val() : '';
    if(st==='filled') msg += ' | Hanya yang terisi';
    else if(st==='empty') msg += ' | Hanya yang belum terisi';
    $('#cetakStatus').text(msg+' ...');
    loadCetakFrame(false);
  });

  $('#btnResetFilter').on('click', function(){
    $('#cetakStart').val('');
    $('#cetakEnd').val('');
    $('#cetakKelas').val('');
    if($('#cetakStatusFilter').length) $('#cetakStatusFilter').val('');
    $('#cetakStatus').text('Menampilkan semua jurnal (tanpa filter).');
    loadCetakFrame(false);
  });

  $('#btnOpenNewTab').on('click', function(){
    window.open(buildCetakUrl(false), '_blank');
  });

  function printIframe(){
    const iframe = document.getElementById('frameCetak');
    if(!iframe || !iframe.contentWindow) return;
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
  }
  $('#btnPrintIframe, #btnPrintFooter').on('click', printIframe);

  // Print helper for Wali Kelas table
  window.printWaliKelasTable = function(){
    var tbl = document.getElementById('tblWaliKelas');
    if(!tbl){ return; }
    var w = window.open('', '_blank');
    var css = '<style>table{width:100%;border-collapse:collapse;font-family:Arial;font-size:12px;}th,td{border:1px solid #333;padding:6px;}th{background:#f1f5f9;}</style>';
    w.document.write('<html><head><title>Data Wali Kelas</title>'+css+'</head><body>');
    w.document.write('<h3>Data Wali Kelas - <?= htmlspecialchars($waliKelasNama?:''); ?> (<?= date('F Y'); ?>)</h3>');
    w.document.write(tbl.outerHTML);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    w.print();
    setTimeout(function(){ w.close(); }, 300);
  };

  // Optional: auto adjust tinggi iframe (fallback: fixed via CSS)
  const resizeObserver = new ResizeObserver(()=>{
    const iframe = document.getElementById('frameCetak');
    if(!iframe) return;
    // Keep fixed height or adapt later if needed
  });
  resizeObserver.observe(document.body);

  // Button in select modal
  $(document).on('click', '.btn-pilih-jurnal', function(){
    var id = $(this).data('id');
    openInputJurnal(id);
    $('#selectJadwalModal').modal('hide');
  });
  $(document).on('click', '.btn-pilih-nilai', function(){
    var id = $(this).data('id');
    openInputNilai(id);
    $('#selectJadwalModal').modal('hide');
  });
  $(document).on('click', '.btn-pilih-tugas', function(){
    var id = $(this).data('id');
    var hasTask = $(this).data('has-task') == '1';
    
    if (hasTask) {
      // Button is "Hapus Tugas" - show confirmation and delete (non-blocking)
      showConfirm('Yakin ingin menghapus tugas untuk mata pelajaran ini?').then(function(ok){
        if (!ok) return;
        // Call delete function directly
        var formData = new FormData();
        formData.append('action', 'hapus_by_mapel');
        formData.append('id_mapel', id);

        fetch(<?= guru_page('simpan_tugas') ?>, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Refresh the modal to update button states
            $('#selectJadwalModal').modal('hide');
            setTimeout(() => {
              $('#selectJadwalModal').modal('show');
            }, 300);
          } else {
            showToast('Error: ' + (data.message || 'Gagal menghapus tugas'), 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('Terjadi kesalahan saat menghapus tugas', 'error');
        });
      }
    } else {
      // Button is "Input Tugas" - open form
      openInputTugas(id);
      $('#selectJadwalModal').modal('hide');
    }
  });

  $('#show').on('show.bs.modal', function (e) {
    // Jika modal dibuka secara programatik (tanpa relatedTarget) atau tanpa data-id, jangan auto-load lagi
    if (!e.relatedTarget || !$(e.relatedTarget).data('id')) { return; }
    var getDetail = $(e.relatedTarget).data('id');
    $.ajax({
      type : 'post',
      url : 'detailmateri.php',
      data :  'getDetail='+ getDetail,
      success : function(data){
        $('.modal-data').html(data);
      }
    });
  });

  $('#modalNilai').on('show.bs.modal', function (e) {
    // Hindari double-load jika dibuka programatik
    if (!e.relatedTarget || !$(e.relatedTarget).data('id')) { return; }
    var getDetail = $(e.relatedTarget).data('id');
    $.ajax({
      type : 'post',
      url : 'inputnilai.php',
      data :  'getDetail='+ getDetail,
      success : function(data){
        $('.modal-nilai-body').html(data);
      }
    });
  });
});
</script>

<!-- Welcome popup removed: quotes welcome popup disabled per user request -->

<!-- History Tugas Modal -->
<div class="modal fade" id="historyTugasModal" tabindex="-1" aria-labelledby="historyTugasModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-gradient-primary text-white">
        <h5 class="modal-title" id="historyTugasModalLabel">
          <i class="fas fa-list-task me-2"></i>History Tugas
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" id="historyTugasContent">
        <div class="text-center p-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="mt-2">Memuat data tugas...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Task Detail Modal (nested) -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="taskDetailContent">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Edit Task Modal (nested) -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="editTaskContent">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Modal Catat Pelanggaran Siswa -->
<div class="modal fade" id="modalCatatPelanggaran" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Catat Pelanggaran Siswa
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formPelanggaran">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="selectKelasP" class="form-label fw-semibold">Pilih Kelas</label>
              <select class="form-select" id="selectKelasP" name="kelas" required>
                <option value="">-- Pilih Kelas --</option>
                <?php if (!empty($kelasList)) { 
                  foreach ($kelasList as $kelas) { ?>
                    <option value="<?= htmlspecialchars($kelas) ?>" <?= ($kelas == $kelasWali) ? 'selected' : '' ?>><?= htmlspecialchars($kelas) ?></option>
                  <?php } 
                } ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="selectSiswaP" class="form-label fw-semibold">Pilih Siswa</label>
              <select class="form-select" id="selectSiswaP" name="no_induk" required disabled>
                <option value="">-- Pilih Siswa --</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="kategoriPelanggaran" class="form-label fw-semibold">Kategori Pelanggaran</label>
              <select class="form-select" id="kategoriPelanggaran" name="kategori_pelanggaran" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Ringan" style="color:#17a2b8;">Ringan</option>
                <option value="Sedang" style="color:#ffc107;">Sedang</option>
                <option value="Berat" style="color:#dc3545;">Berat</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="jenisPelanggaran" class="form-label fw-semibold">Jenis Pelanggaran</label>
              <select class="form-select" id="jenisPelanggaran" name="jenis_pelanggaran" required disabled>
                <option value="">-- Pilih Jenis --</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="deskripsiPelanggaran" class="form-label fw-semibold">Deskripsi Pelanggaran</label>
            <textarea class="form-control" id="deskripsiPelanggaran" name="deskripsi_pelanggaran" rows="3" placeholder="Jelaskan detail pelanggaran yang dilakukan..."></textarea>
          </div>

          <div class="mb-3">
            <label for="tindakanGuru" class="form-label fw-semibold">Tindakan yang Diambil</label>
            <textarea class="form-control" id="tindakanGuru" name="tindakan_guru" rows="2" placeholder="Tindakan yang sudah/akan diambil..."></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label for="tanggalPelanggaran" class="form-label fw-semibold">Tanggal Pelanggaran</label>
              <input type="date" class="form-control" id="tanggalPelanggaran" name="tanggal_pelanggaran" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label for="statusPelanggaran" class="form-label fw-semibold">Status</label>
              <select class="form-select" id="statusPelanggaran" name="status_pelanggaran">
                <option value="Aktif">Aktif</option>
                <option value="Diselesaikan">Diselesaikan</option>
                <option value="Follow Up">Perlu Follow Up</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="btnSimpanPelanggaran">
          <i class="bi bi-check-lg me-1"></i>Simpan Catatan
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Data jenis pelanggaran berdasarkan kategori
const jenisPelanggaranData = {
  'Berat': [
    'Tindak kekerasan', 'Membawa minuman keras', 'Membawa senjata tajam/berbahaya', 
    'Merokok di area sekolah', 'Membawa/menggunakan narkoba', 'Perbuatan asusila', 
    'Bullying/intimidasi', 'Mencuri', 'Bolos berkepanjangan (>3 hari berturut)'
  ],
  'Sedang': [
    'Seragam tidak sesuai aturan', 'Terlambat berulang kali', 
    'Alpha tanpa keterangan (2-3 kali)', 'Tidak mengerjakan tugas berulang kali', 
    'Membawa HP saat ujian', 'Berkelahi ringan', 'Tidak hormat pada guru'
  ],
  'Ringan': [
    'Terlambat masuk kelas', 'Alpha tanpa keterangan (1 kali)', 
    'Tidak mengerjakan PR', 'Ramai di kelas', 'Tidak membawa buku/alat tulis', 
    'Makan di kelas saat pelajaran', 'Tidur di kelas'
  ]
};

// Handler untuk perubahan kategori pelanggaran
document.getElementById('kategoriPelanggaran').addEventListener('change', function() {
  const kategori = this.value;
  const selectJenis = document.getElementById('jenisPelanggaran');
  
  selectJenis.innerHTML = '<option value="">-- Pilih Jenis --</option>';
  selectJenis.disabled = !kategori;
  
  if (kategori && jenisPelanggaranData[kategori]) {
    jenisPelanggaranData[kategori].forEach(jenis => {
      const option = document.createElement('option');
      option.value = jenis;
      option.textContent = jenis;
      selectJenis.appendChild(option);
    });
  }
});

// Handler untuk perubahan kelas (load siswa)
document.getElementById('selectKelasP').addEventListener('change', function() {
  const kelas = this.value;
  const selectSiswa = document.getElementById('selectSiswaP');
  
  selectSiswa.innerHTML = '<option value="">-- Pilih Siswa --</option>';
  selectSiswa.disabled = !kelas;
  
  if (kelas) {
    // Load siswa berdasarkan kelas (kirim cookie session agar endpoint mengenali user)
    fetch(`../../get_siswa_by_kelas.php?kelas=${encodeURIComponent(kelas)}`, {
      credentials: 'same-origin'
    })
      .then(response => response.json())
      .then(data => {
        if (!data || !data.success) {
          const msg = data && data.message ? data.message : 'Gagal memuat data siswa';
          selectSiswa.innerHTML = `<option value="">-- ${msg} --</option>`;
          selectSiswa.disabled = true;
          return;
        }

        // Populate siswa
        if (Array.isArray(data.siswa) && data.siswa.length > 0) {
          data.siswa.forEach(siswa => {
            const option = document.createElement('option');
            option.value = siswa.no_induk;
            option.textContent = `${siswa.nama} (${siswa.no_induk})`;
            selectSiswa.appendChild(option);
          });
          selectSiswa.disabled = false;
        } else {
          selectSiswa.innerHTML = '<option value="">-- Tidak ada siswa di kelas ini --</option>';
          selectSiswa.disabled = true;
        }
      })
      .catch(err => {
        console.error('Fetch error:', err);
        selectSiswa.innerHTML = '<option value="">-- Gagal memuat data siswa --</option>';
        selectSiswa.disabled = true;
      })
      .catch(error => {
        console.error('Error loading siswa:', error);
        showToast('Gagal memuat data siswa', 'error');
      });
  }
});

// Handler untuk simpan pelanggaran
document.getElementById('btnSimpanPelanggaran').addEventListener('click', function() {
  const form = document.getElementById('formPelanggaran');
  const formData = new FormData(form);
  
  // Validasi form
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const btn = this;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Menyimpan...';
  btn.disabled = true;
  
  // Submit form
  fetch('../../simpan_pelanggaran.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
    .then(data => {
    if (data.success) {
      showToast('Catatan pelanggaran berhasil disimpan!', 'success');
      // Reset form
      form.reset();
      document.getElementById('selectSiswaP').disabled = true;
      document.getElementById('jenisPelanggaran').disabled = true;
      document.getElementById('tanggalPelanggaran').value = new Date().toISOString().split('T')[0];
      // Close modal
      bootstrap.Modal.getInstance(document.getElementById('modalCatatPelanggaran')).hide();
      // Refresh halaman untuk update data
      setTimeout(() => window.location.reload(), 500);
    } else {
      showToast('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'), 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Terjadi kesalahan saat menyimpan data', 'error');
  })
  .finally(() => {
    btn.innerHTML = originalText;
    btn.disabled = false;
  });
});

// Handler untuk quick action catat pelanggaran
document.getElementById('qaCatatPelanggaran').addEventListener('click', function() {
  new bootstrap.Modal(document.getElementById('modalCatatPelanggaran')).show();
});
</script>

</body>
</html>