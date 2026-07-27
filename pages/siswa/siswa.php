<?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"])) {
  header("location: ../../index.php?haruslogin");
  exit;
} else if ($_SESSION['hak_akses'] != 3) { ?>
  <script>
    window.location = '404.html';
  </script>
<?php
}

include "../../koneksi.php";
include "../../functions.php";
require_once "../../plugins/FileCache.php";
date_default_timezone_set('Asia/Jakarta');
$kls = $_SESSION['kelas'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);
$lembaga = data_lembaga();
$stat = "Aktif";
// Data tambahan untuk tampilan mobile siswa
$nisSiswa = $_SESSION['no_induk'];
session_write_close(); // UNBLOCK SESSION UNTUK SKALABILITAS 900+ SISWA
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantAbsen = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_absen', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantIzin = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_izin_siswa', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$absSummary = ['hadir' => 0, 'ijin' => 0, 'sakit' => 0, 'dispen' => 0, 'alpha' => 0];
$bulanNow = date('Y-m');
// Cek tabel absensi sebelum query
$__tblAb = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_absen'");
if ($__tblAb && mysqli_num_rows($__tblAb) > 0) {
  $nisEsc = mysqli_real_escape_string($conn, $nisSiswa);
  $klsEsc = mysqli_real_escape_string($conn, $kls);
  
  $cacheKeyAbsSummary = 'abs_summary_' . $tenantId . '_' . md5($nisEsc . '_' . $klsEsc . '_' . $bulanNow);
  $cachedSummary = FileCache::get($cacheKeyAbsSummary);
  
  if ($cachedSummary === false) {
      $__qAbs = mysqli_query($conn, "SELECT 
          SUM(status='Hadir')  AS hadir,
          SUM(status='Ijin')   AS ijin,
          SUM(status='Sakit')  AS sakit,
          SUM(status='Dispen') AS dispen,
          SUM(status='Alpha')  AS alpha
        FROM tbl_absen
        WHERE {$tenantAbsen} AND no_induk='" . $nisEsc . "' AND kelas='" . $klsEsc . "' AND DATE_FORMAT(tanggal,'%Y-%m')='" . $bulanNow . "'");
      if ($__qAbs && ($__ra = mysqli_fetch_assoc($__qAbs))) {
        $absSummary = [
          'hadir'  => (int)($__ra['hadir'] ?? 0),
          'ijin'   => (int)($__ra['ijin'] ?? 0),
          'sakit'  => (int)($__ra['sakit'] ?? 0),
          'dispen' => (int)($__ra['dispen'] ?? 0),
          'alpha'  => (int)($__ra['alpha'] ?? 0),
        ];
        FileCache::set($cacheKeyAbsSummary, $absSummary, 900); // 15 menit
      }
  } else {
      $absSummary = $cachedSummary;
  }
}
// Cek notifikasi izin terbaru (7 hari terakhir)
$izinNotifSiswa = [];
$__tblIzinN = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin_siswa'");
if ($__tblIzinN && mysqli_num_rows($__tblIzinN) > 0) {
  $__nisN = mysqli_real_escape_string($conn, $nisSiswa);
  $__rIzin = mysqli_query($conn, "SELECT status_izin, jenis_izin, tanggal_izin, catatan_penolakan, validasi_wali_kelas, validator_wali_kelas, validasi_guru_bk, validator_guru_bk
    FROM tbl_izin_siswa
    WHERE {$tenantIzin} AND no_induk_siswa='$__nisN'
      AND status_izin NOT IN ('Menunggu Validasi') 
      AND DATE(waktu_pengajuan) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY waktu_pengajuan DESC LIMIT 5");
if ($__rIzin) while ($rw = mysqli_fetch_assoc($__rIzin)) $izinNotifSiswa[] = $rw;
}

// ==========================================
// PENGUMUMAN & NOTIFIKASI
// ==========================================
$all_notifications = [];

// 1. Izin Notif
foreach($izinNotifSiswa as $iz) {
    $status_text = 'Izin Anda ' . $iz['status_izin'];
    
    // Customize text based on validations
    if ($iz['status_izin'] === 'Disetujui Penuh' || $iz['status_izin'] === 'Disetujui') {
        $status_text = 'Izin Anda telah disetujui penuh.';
    } elseif ($iz['status_izin'] === 'Ditolak') {
        $status_text = 'Izin Anda ditolak.';
    } elseif ($iz['validasi_wali_kelas'] === 'Disetujui' && $iz['validasi_guru_bk'] === 'Menunggu') {
        $status_text = 'Izin di-ACC Wali Kelas (' . ($iz['validator_wali_kelas'] ?: 'Wali Kelas') . '), menunggu BK.';
    } elseif ($iz['validasi_guru_bk'] === 'Disetujui' && $iz['validasi_wali_kelas'] === 'Menunggu') {
        $status_text = 'Izin di-ACC BK (' . ($iz['validator_guru_bk'] ?: 'Guru BK') . '), menunggu Wali Kelas.';
    } else {
         $status_text = 'Status izin: ' . $iz['status_izin'];
    }

    $all_notifications[] = [
        'type' => 'izin',
        'icon' => 'fas fa-info-circle',
        'color' => '#3b82f6',
        'title' => 'Status Izin',
        'text' => $status_text,
        'link' => 'status-izin.php'
    ];
}

// 2. Pengingat Absen
$sudah_absen = false;
$__qAbsHariIni = @mysqli_query($conn, "SELECT id FROM tbl_absen WHERE no_induk='$nisEsc' AND tanggal=CURDATE() AND {$tenantAbsen} LIMIT 1");
if ($__qAbsHariIni && mysqli_num_rows($__qAbsHariIni) > 0) {
    $sudah_absen = true;
}
$ada_jadwal = false;
$__kls = mysqli_real_escape_string($conn, $kls);
$__hr = mysqli_real_escape_string($conn, $hariini);

$cacheKeyJadwal = 'jadwal_hari_ini_' . $tenantId . '_' . md5($__kls . '_' . $__hr);
$cachedJadwal = FileCache::get($cacheKeyJadwal);
if ($cachedJadwal === false) {
    $__qJadwal = @mysqli_query($conn, "SELECT id_mapel FROM tbl_mapel_ampu WHERE kelas='$__kls' AND hari='$__hr' LIMIT 1");
    if ($__qJadwal && mysqli_num_rows($__qJadwal) > 0) {
        $ada_jadwal = true;
    }
    FileCache::set($cacheKeyJadwal, $ada_jadwal, 3600);
} else {
    $ada_jadwal = $cachedJadwal;
}

if (!$sudah_absen && $ada_jadwal) {
    $all_notifications[] = [
        'type' => 'absen',
        'icon' => 'fas fa-fingerprint',
        'color' => '#ef4444',
        'title' => 'Pengingat Presensi',
        'text' => 'Anda belum melakukan presensi hari ini.',
        'link' => 'presensi.php'
    ];
}

// 3. Tugas Baru
$__tblTugasSiswa = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_tugas_siswa'");
if ($__tblTugasSiswa && mysqli_num_rows($__tblTugasSiswa) > 0) {
    $cacheKeyTugas = 'tugas_aktif_' . $tenantId . '_' . md5($klsEsc);
    $activeTugas = FileCache::get($cacheKeyTugas);
    if ($activeTugas === false) {
        $activeTugas = [];
        $__qTugas = @mysqli_query($conn, "SELECT id, judul_tugas FROM tbl_tugas WHERE kelas='$klsEsc' AND status='aktif' ORDER BY id DESC LIMIT 20");
        if ($__qTugas) {
            while($tg = mysqli_fetch_assoc($__qTugas)) {
                $activeTugas[] = $tg;
            }
        }
        FileCache::set($cacheKeyTugas, $activeTugas, 300); // 5 minutes
    }
    
    if (!empty($activeTugas)) {
        $doneTugas = [];
        $__qDone = @mysqli_query($conn, "SELECT id_tugas FROM tbl_tugas_siswa WHERE no_induk_siswa='$nisEsc'");
        if ($__qDone) {
            while($r = mysqli_fetch_assoc($__qDone)) {
                $doneTugas[] = $r['id_tugas'];
            }
        }
        
        $addedCount = 0;
        foreach ($activeTugas as $tg) {
            if (!in_array($tg['id'], $doneTugas)) {
                $all_notifications[] = [
                    'type' => 'tugas',
                    'icon' => 'fas fa-tasks',
                    'color' => '#f97316',
                    'title' => 'Tugas Baru',
                    'text' => htmlspecialchars($tg['judul_tugas']),
                    'link' => 'tugas.php'
                ];
                $addedCount++;
                if ($addedCount >= 5) break;
            }
        }
    }
}

// 4. Notifikasi Literasi
$__tblLit = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_literasi_tugas'");
if ($__tblLit && mysqli_num_rows($__tblLit) > 0) {
    $cacheKeyLit = 'literasi_aktif_' . $tenantId . '_' . md5($klsEsc);
    $activeLit = FileCache::get($cacheKeyLit);
    if ($activeLit === false) {
        $activeLit = [];
        $__qLit = @mysqli_query($conn, "SELECT id, judul FROM tbl_literasi_tugas WHERE kelas='$klsEsc' AND id_sekolah=$idSekolah ORDER BY id DESC LIMIT 20");
        if ($__qLit) {
            while($lit = mysqli_fetch_assoc($__qLit)) {
                $activeLit[] = $lit;
            }
        }
        FileCache::set($cacheKeyLit, $activeLit, 300); // 5 minutes
    }
    
    if (!empty($activeLit)) {
        $doneLit = [];
        $__qDone = @mysqli_query($conn, "SELECT id_tugas FROM tbl_literasi_progress WHERE no_induk_siswa='$nisEsc' AND status='selesai'");
        if ($__qDone) {
            while($r = mysqli_fetch_assoc($__qDone)) {
                $doneLit[] = $r['id_tugas'];
            }
        }
        
        $addedCount = 0;
        foreach ($activeLit as $lit) {
            if (!in_array($lit['id'], $doneLit)) {
                $all_notifications[] = [
                    'type' => 'literasi',
                    'icon' => 'fas fa-book-reader',
                    'color' => '#14b8a6',
                    'title' => 'Misi Literasi',
                    'text' => htmlspecialchars($lit['judul']),
                    'link' => 'literasi.php'
                ];
                $addedCount++;
                if ($addedCount >= 5) break;
            }
        }
    }
}

// 5. Pengingat Jurnal 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat)
$__tbl7kih = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_7kih_jurnal'");
if ($__tbl7kih && mysqli_num_rows($__tbl7kih) > 0) {
    $__q7kih = @mysqli_query($conn, "SELECT id FROM tbl_7kih_jurnal WHERE no_induk='$nisEsc' AND tanggal=CURDATE() LIMIT 1");
    if ($__q7kih && mysqli_num_rows($__q7kih) == 0) {
        $all_notifications[] = [
            'type' => 'jurnal',
            'icon' => 'fas fa-pray',
            'color' => '#a855f7',
            'title' => '7 KAIH',
            'text' => 'Anda belum mengisi jurnal hari ini.',
            'link' => 'jurnal-7kih.php'
        ];
    }
}

// 6. Pengumuman
$__tblPeng = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pengumuman'");
$__tblPengRead = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pengumuman_read'");
if ($__tblPeng && mysqli_num_rows($__tblPeng) > 0 && $__tblPengRead && mysqli_num_rows($__tblPengRead) > 0) {
    $cacheKeyPeng = 'pengumuman_aktif_' . $tenantId . '_' . md5($klsEsc);
    $activePeng = FileCache::get($cacheKeyPeng);
    if ($activePeng === false) {
        $activePeng = [];
        $__qPeng = @mysqli_query($conn, "SELECT p.id, p.judul FROM tbl_pengumuman p
            WHERE p.status='aktif' AND p.mulai <= CURDATE() AND p.selesai >= CURDATE()
            AND (p.target_scope='SEMUA' OR (p.target_scope='KELAS' AND p.target_value='$klsEsc'))
            ORDER BY p.id DESC LIMIT 20");
        if ($__qPeng) {
            while($png = mysqli_fetch_assoc($__qPeng)) {
                $activePeng[] = $png;
            }
        }
        FileCache::set($cacheKeyPeng, $activePeng, 600); // Cache 10 minutes per class
    }

    if (!empty($activePeng)) {
        // Fetch read ids for this student
        $readIds = [];
        $__qRead = @mysqli_query($conn, "SELECT pengumuman_id FROM tbl_pengumuman_read WHERE no_induk='$nisEsc'");
        if ($__qRead) {
            while($r = mysqli_fetch_assoc($__qRead)) {
                $readIds[] = $r['pengumuman_id'];
            }
        }

        $addedCount = 0;
        foreach ($activePeng as $png) {
            if (!in_array($png['id'], $readIds)) {
                $all_notifications[] = [
                    'type' => 'pengumuman',
                    'icon' => 'fas fa-bullhorn',
                    'color' => '#eab308',
                    'title' => 'Pengumuman Baru',
                    'text' => htmlspecialchars($png['judul']),
                    'link' => 'javascript:void(0)',
                    'action_onclick' => 'markReadAndGo('.$png['id'].', \'../../pengumuman.php\')'
                ];
                $addedCount++;
                if ($addedCount >= 5) break;
            }
        }
    }
}

// 6.5. Tugas Reguler & Literasi Belum Selesai (Batas Waktu)
$__tblTugas = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_tugas'");
$__tblTugasSiswa = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_tugas_siswa'");
if ($__tblTugas && mysqli_num_rows($__tblTugas) > 0 && $__tblTugasSiswa && mysqli_num_rows($__tblTugasSiswa) > 0) {
    $qTugas = @mysqli_query($conn, "
        SELECT t.id, t.judul_tugas, t.tanggal_pengumpulan 
        FROM tbl_tugas t 
        LEFT JOIN tbl_tugas_siswa ts ON t.id = ts.id_tugas AND ts.no_induk_siswa = '$nisEsc' 
        WHERE t.kelas = '$klsEsc' AND t.status = 'aktif' AND (ts.id IS NULL OR ts.status != 'Selesai') 
        ORDER BY t.tanggal_pengumpulan ASC LIMIT 5");
    if ($qTugas) {
        while($tg = mysqli_fetch_assoc($qTugas)) {
            $tenggat = $tg['tanggal_pengumpulan'] ? date('d/m/Y H:i', strtotime($tg['tanggal_pengumpulan'])) : 'Tidak ada tenggat';
            $is_late = $tg['tanggal_pengumpulan'] && strtotime($tg['tanggal_pengumpulan']) < time() ? true : false;
            
            $all_notifications[] = [
                'type' => 'tugas',
                'icon' => 'fas fa-book-open',
                'color' => $is_late ? '#ef4444' : '#f97316',
                'title' => 'Tugas ' . ($is_late ? 'Terlambat!' : 'Belum Selesai'),
                'text' => htmlspecialchars($tg['judul_tugas']) . ' (Tenggat: ' . $tenggat . ')',
                'link' => 'tugas.php'
            ];
        }
    }
}

$__tblLiterasi = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_literasi_tugas'");
$__tblLiterasiProg = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_literasi_progress'");
if ($__tblLiterasi && mysqli_num_rows($__tblLiterasi) > 0 && $__tblLiterasiProg && mysqli_num_rows($__tblLiterasiProg) > 0) {
    $qLit = @mysqli_query($conn, "
        SELECT t.id, t.judul, t.batas_waktu 
        FROM tbl_literasi_tugas t 
        LEFT JOIN tbl_literasi_progress p ON t.id = p.id_tugas AND p.no_induk_siswa = '$nisEsc' 
        WHERE t.kelas = '$klsEsc' AND t.id_sekolah = $idSekolah AND (p.id IS NULL OR p.status != 'Selesai') 
        ORDER BY t.batas_waktu ASC LIMIT 5");
    if ($qLit) {
        while($lit = mysqli_fetch_assoc($qLit)) {
            $tenggat = $lit['batas_waktu'] ? date('d/m/Y H:i', strtotime($lit['batas_waktu'])) : 'Tidak ada tenggat';
            $is_late = $lit['batas_waktu'] && strtotime($lit['batas_waktu']) < time() ? true : false;
            
            $all_notifications[] = [
                'type' => 'literasi',
                'icon' => 'fas fa-rocket',
                'color' => $is_late ? '#ef4444' : '#3b82f6',
                'title' => 'Literasi ' . ($is_late ? 'Terlambat!' : 'Belum Selesai'),
                'text' => htmlspecialchars($lit['judul']) . ' (Tenggat: ' . $tenggat . ')',
                'link' => 'literasi.php'
            ];
        }
    }
}

// 7. Leaderboard 7 KIH
$top_kih = [];
if ($__tbl7kih && mysqli_num_rows($__tbl7kih) > 0) {
    // Top 3 untuk bulan ini
    $qL = @mysqli_query($conn, "SELECT no_induk, nama_siswa, SUM(score) as total_score FROM tbl_7kih_jurnal WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulanNow' GROUP BY no_induk, nama_siswa ORDER BY total_score DESC LIMIT 3");
    if ($qL) {
        while($lb = mysqli_fetch_assoc($qL)) {
            $top_kih[] = $lb;
        }
    }
}

$notif_count = count($all_notifications);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#0ea5e9">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title><?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Portal Siswa') ?></title>
  <link rel="icon" href="../../img/<?= htmlspecialchars($lembaga['logo'] ?? 'favicon.ico'); ?>" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary: #0ea5e9;
      --primary-dark: #0284c7;
      --accent: #3b82f6;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #1e293b;
      --muted: #64748b;
      --radius: 20px;
      --shadow: 0 8px 30px rgba(0, 0, 0, .06);
      --bottom-h: 70px;
    }

    html {
      font-size: 15px;
      -webkit-tap-highlight-color: transparent;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      padding-bottom: calc(var(--bottom-h) + env(safe-area-inset-bottom));
    }

    /* ── TOP HEADER ─────────────────────────────────────── */
    .app-header {
      background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
      padding: 20px 20px 70px;
      position: relative;
      overflow: visible;
      color: #fff;
    }

    /* Motif background biru */
    .app-header::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='180' cy='20' r='60' fill='rgba(255,255,255,.08)'/%3E%3Ccircle cx='20' cy='180' r='80' fill='rgba(255,255,255,.05)'/%3E%3C/svg%3E") no-repeat center/cover;
      opacity: 0.8;
      clip-path: inset(0);
    }

    .header-content {
      position: relative;
    }

    .header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .school-info-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .school-logo {
      width: 42px;
      height: 42px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .school-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .school-text {
      display: flex;
      flex-direction: column;
    }

    .school-name {
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.2px;
    }

    .school-tagline {
      font-size: 0.65rem;
      font-weight: 500;
      opacity: 0.85;
      opacity: 0.85;
    }

    .notif-dropdown {
      position: absolute;
      top: 50px;
      right: 0;
      width: 300px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.15);
      z-index: 9999;
      display: none;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid #e2e8f0;
      animation: slideDown 0.3s ease;
      text-align: left;
    }
    .notif-dropdown.show {
      display: flex;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .notif-header {
      padding: 15px;
      border-bottom: 1px solid #f1f5f9;
      font-weight: 600;
      color: var(--text);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .notif-list {
      max-height: 350px;
      overflow-y: auto;
    }
    .notif-item {
      display: flex;
      padding: 15px;
      border-bottom: 1px solid #f1f5f9;
      text-decoration: none;
      transition: background 0.2s;
    }
    .notif-item:hover {
      background: #f8fafc;
    }
    .notif-item:last-child {
      border-bottom: none;
    }
    .notif-icon-wrap {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      flex-shrink: 0;
      font-size: 1.1rem;
    }
    .notif-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .notif-title {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 3px;
    }
    .notif-desc {
      font-size: 0.75rem;
      color: var(--muted);
      line-height: 1.3;
    }
    .notif-empty {
      padding: 30px 15px;
      text-align: center;
      color: var(--muted);
      font-size: 0.85rem;
    }
    .notif-empty i {
      font-size: 2rem;
      color: #cbd5e1;
      margin-bottom: 10px;
      display: block;
    }

    .notif-bell {
      width: 38px;
      height: 38px;
      background: #ffffff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      position: relative;
      font-size: 1.1rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      z-index: 999;
    }

    .notif-badge {
      position: absolute;
      top: -2px;
      right: -2px;
      background: #ef4444;
      color: #fff;
      font-size: 0.6rem;
      font-weight: 700;
      padding: 2px 5px;
      border-radius: 10px;
      border: 2px solid var(--primary);
    }

    .hero-banner {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .hero-text-wrap {
      flex: 1;
      max-width: 60%;
    }

    .hero-title {
      font-size: 1.25rem;
      font-weight: 800;
      line-height: 1.3;
      margin-bottom: 16px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .hero-btn {
      background: #ffffff;
      color: var(--primary-dark);
      border: none;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .hero-img-wrap {
      position: absolute;
      right: -10px;
      bottom: -60px;
      width: 180px;
      height: auto;
      z-index: 1;
      pointer-events: none;
    }
    
    .hero-img-wrap img {
      width: 100%;
      height: auto;
      object-fit: contain;
    }

    /* ── FLOATING CARDS WRAPPER ───────────────────────── */
    .main-wrap {
      padding: 0 20px 20px;
      margin-top: -50px;
      position: relative;
      z-index: 10;
    }

    /* ── RED PROFILE CARD ────────────────────────────── */
    .red-card {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      border-radius: var(--radius);
      box-shadow: 0 10px 25px rgba(220, 38, 38, 0.25);
      padding: 20px;
      color: #fff;
      position: relative;
      overflow: hidden;
      margin-bottom: 16px;
    }

    .red-card::after {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background: url("data:image/svg+xml,%3Csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M100 0 C150 50, 50 150, 200 200' stroke='rgba(255,255,255,0.1)' stroke-width='40' fill='none'/%3E%3C/svg%3E") no-repeat right center/cover;
      pointer-events: none;
    }

    .rc-top {
      display: flex;
      align-items: center;
      gap: 14px;
      position: relative;
      z-index: 2;
    }

    .rc-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #fff;
      padding: 2px;
    }

    .rc-avatar img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
    }

    .rc-avatar-fallback {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: #f1f5f9;
      color: #dc2626;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .rc-greeting {
      flex: 1;
    }

    .rc-g-label {
      font-size: 0.75rem;
      opacity: 0.9;
    }

    .rc-g-name {
      font-size: 1.1rem;
      font-weight: 700;
      margin-top: 2px;
    }

    .rc-grid-icon {
      font-size: 1.2rem;
      opacity: 0.8;
      align-self: flex-start;
    }

    .rc-bottom {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      position: relative;
      z-index: 2;
    }

    .rc-stat-item {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 1;
    }
    
    .rc-stat-item:not(:last-child) {
      border-right: 1px solid rgba(255,255,255,0.2);
    }
    
    .rc-stat-item:not(:first-child) {
      padding-left: 10px;
    }

    .rc-stat-icon {
      width: 32px;
      height: 32px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.9rem;
    }

    .rc-stat-text {
      display: flex;
      flex-direction: column;
    }

    .rc-stat-title {
      font-size: 0.75rem;
      font-weight: 700;
    }

    .rc-stat-val {
      font-size: 0.65rem;
      opacity: 0.9;
    }

    /* ── ORANGE BANNER CARD ──────────────────────────── */
    .orange-card {
      background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
      border-radius: var(--radius);
      box-shadow: 0 8px 20px rgba(234, 88, 12, 0.2);
      padding: 18px 20px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }
    
    .orange-card::before {
      content: '';
      position: absolute;
      left: -20px;
      bottom: -20px;
      width: 100px;
      height: 100px;
      background: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.1)' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9 22 9 12 15 12 15 22'/%3E%3C/svg%3E") no-repeat center/contain;
      opacity: 0.8;
      pointer-events: none;
    }

    .oc-content {
      flex: 1;
      position: relative;
      z-index: 2;
    }

    .oc-title {
      font-size: 1rem;
      font-weight: 800;
      margin-bottom: 6px;
      line-height: 1.2;
    }

    .oc-desc {
      font-size: 0.75rem;
      opacity: 0.9;
      line-height: 1.4;
      margin-bottom: 12px;
    }

    .oc-btn {
      background: #ffffff;
      color: #ea580c;
      border: none;
      padding: 6px 16px;
      border-radius: 16px;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    /* ── MENU GRID ───────────────────────────────────── */
    .menu-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px 12px;
      margin-bottom: 24px;
    }

    .menu-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: var(--text);
      cursor: pointer;
      -webkit-user-select: none;
      user-select: none;
      transition: transform 0.15s;
    }

    .menu-item.pressed {
      transform: scale(0.92);
    }

    .menu-icon-wrap {
      width: 60px;
      height: 60px;
      background: #ffffff;
      border-radius: 50%;
      box-shadow: 0 6px 16px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8px;
    }

    .menu-icon-inner {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
    }

    .menu-name {
      font-size: 0.7rem;
      font-weight: 600;
      text-align: center;
      line-height: 1.2;
    }

    /* Gradient colors for menu inner circles */
    .bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .bg-yellow { background: linear-gradient(135deg, #facc15, #eab308); }
    .bg-green { background: linear-gradient(135deg, #4ade80, #16a34a); }
    .bg-red { background: linear-gradient(135deg, #f87171, #dc2626); }
    .bg-purple { background: linear-gradient(135deg, #c084fc, #9333ea); }
    .bg-teal { background: linear-gradient(135deg, #2dd4bf, #0d9488); }
    .bg-pink { background: linear-gradient(135deg, #f472b6, #db2777); }
    .bg-indigo { background: linear-gradient(135deg, #818cf8, #4f46e5); }
    .bg-orange { background: linear-gradient(135deg, #fb923c, #ea580c); }

    /* ── BOTTOM NAV ──────────────────────────────────── */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: calc(var(--bottom-h) + env(safe-area-inset-bottom));
      background: var(--card);
      box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.08);
      display: flex;
      align-items: flex-start;
      justify-content: space-around;
      padding-top: 12px;
      padding-bottom: env(safe-area-inset-bottom);
      z-index: 100;
      border-radius: 24px 24px 0 0;
    }

    .bnav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      text-decoration: none;
      color: var(--muted);
      transition: all .2s;
      flex: 1;
      position: relative;
    }

    .bnav-item.active {
      color: var(--primary);
    }

    .bnav-item i {
      font-size: 1.3rem;
    }

    .bnav-label {
      font-size: 0.65rem;
      font-weight: 600;
    }

    .bnav-item.active::after {
      content: '';
      position: absolute;
      bottom: -8px;
      width: 4px;
      height: 4px;
      background: var(--primary);
      border-radius: 50%;
    }

  </style>
</head>

<body>

  <?php
  $studentName  = htmlspecialchars($_SESSION['nama_siswa'] ?? 'Siswa');
  $studentClass = htmlspecialchars($_SESSION['kelas'] ?? '');
  $firstLetter  = strtoupper(mb_substr(strip_tags($studentName), 0, 1));
  $logoFile     = $lembaga['logo'] ?? '';
  $logoPath     = "../../img/" . $logoFile;
  ?>

  <!-- ── TOP HEADER ───────────────────────────────────────── -->
  <div class="app-header">
    <div class="header-content">
      <div class="header-top">
        <div class="school-info-wrap">
          <div class="school-logo">
            <?php if ($logoFile && file_exists($logoPath)): ?>
              <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
            <?php else: ?>
              <i class="fas fa-school" style="color:var(--primary)"></i>
            <?php endif; ?>
          </div>
          <div class="school-text">
            <span class="school-name"><?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Portal Siswa') ?></span>
            <span class="school-tagline">Cerdas, Berkarakter, Berprestasi</span>
          </div>
        </div>
        <div class="notif-bell" onclick="toggleNotif(event)">
          <i class="fas fa-bell"></i>
          <?php if($notif_count > 0): ?>
            <span class="notif-badge"><?= $notif_count ?></span>
          <?php endif; ?>
          
          <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
            <div class="notif-header">
              <span>Notifikasi Anda</span>
              <?php if($notif_count > 0): ?>
                <span class="notif-badge-inline" style="background:#0ea5e9; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.7rem;"><?= $notif_count ?> Baru</span>
              <?php endif; ?>
            </div>
            <div class="notif-list">
              <?php if($notif_count > 0): ?>
                <?php foreach($all_notifications as $n): ?>
                  <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-item" <?= isset($n['action_onclick']) ? 'onclick="'.htmlspecialchars($n['action_onclick']).'"' : '' ?>>
                    <div class="notif-icon-wrap" style="color: <?= htmlspecialchars($n['color']) ?>; background: <?= htmlspecialchars($n['color']) ?>15;">
                      <i class="<?= htmlspecialchars($n['icon']) ?>"></i>
                    </div>
                    <div class="notif-content">
                      <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                      <div class="notif-desc"><?= htmlspecialchars($n['text']) ?></div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="notif-empty">
                  <i class="far fa-bell-slash"></i>
                  Tidak ada notifikasi baru
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      
      <div class="hero-banner">
        <div class="hero-text-wrap">
          <div class="hero-title">
            Persiapkan dirimu untuk menjadi yang terbaik
          </div>
          <a href="jurnal-7kih.php" class="hero-btn">
            Isi Jurnal <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
      
      <div class="hero-img-wrap">
        <!-- We use the generated image here -->
        <img src="../../img/hero_students_transparent.png" alt="Students" onerror="this.style.display='none'">
      </div>
    </div>
  </div>

  <!-- ── MAIN CONTENT ──────────────────────────────────────── -->
  <div class="main-wrap">

    <!-- Red Profile Card -->
    <?php
      // Penentuan jenis kelamin (heuristik dari nama) untuk avatar 3D
      $isFemale = preg_match('/\b(putri|sari|ayu|wati|ningrum|indah|nurul|siti|dewi|wiwid|nisa|aulia|zahra|salma|syifa|lestari|fitri|amelia|kartika|kusuma|maharani|mega|novia|pratiwi|puspa|ratna|retno|safira|sekarsari|susanti|tari|wulandari|cahyaningrum)\b/i', $studentName);
      $avatarFile = (isset($_SESSION['jk']) && $_SESSION['jk'] == 'P') || $isFemale ? 'avatar_female_3d.png' : 'avatar_male_3d.png';
    ?>
    <div class="red-card">
      <div class="rc-top">
        <div class="rc-avatar" style="padding: 0; background: transparent; overflow: hidden; border: 2px solid rgba(255,255,255,0.8);">
          <img src="../../img/<?= $avatarFile ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; background-color: #fff;">
        </div>
        <div class="rc-greeting">
          <div class="rc-g-label">Selamat Pagi,</div>
          <div class="rc-g-name"><?= $studentName ?></div>
        </div>
        <i class="fas fa-grip-horizontal rc-grid-icon"></i>
      </div>
      
      <div class="rc-bottom">
        <div class="rc-stat-item">
          <div class="rc-stat-icon"><i class="fas fa-file-alt"></i></div>
          <div class="rc-stat-text">
            <span class="rc-stat-title">Hadir</span>
            <span class="rc-stat-val"><?= $absSummary['hadir'] ?> Hari</span>
          </div>
        </div>
        <div class="rc-stat-item">
          <div class="rc-stat-icon"><i class="fas fa-star"></i></div>
          <div class="rc-stat-text">
            <span class="rc-stat-title">Izin/Skt</span>
            <span class="rc-stat-val"><?= $absSummary['ijin'] + $absSummary['sakit'] ?> Hari</span>
          </div>
        </div>
        <div class="rc-stat-item">
          <div class="rc-stat-icon"><i class="fas fa-history"></i></div>
          <div class="rc-stat-text">
            <span class="rc-stat-title">Alpha</span>
            <span class="rc-stat-val"><?= $absSummary['alpha'] ?> Hari</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Orange Banner Card -->
    <div class="orange-card">
      <div class="oc-content">
        <div class="oc-title">Selamat Datang di<br><?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Portal Siswa') ?></div>
        <div class="oc-desc">
          <?php if(!empty($izinNotifSiswa)): ?>
            Ada notifikasi izin baru untuk Anda. Silahkan periksa statusnya.
          <?php else: ?>
            Silahkan pilih menu di bawah ini untuk memulai aktivitas belajar Anda hari ini.
          <?php endif; ?>
        </div>
        <a href="<?= !empty($izinNotifSiswa) ? 'status-izin.php' : '../../pengumuman.php' ?>" class="oc-btn">
          <?= !empty($izinNotifSiswa) ? 'Cek Status Izin' : 'Lihat Pengumuman' ?> <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>

    <!-- Menu Grid -->
    <?php
    $menus = [
      ['name' => 'Presensi',    'icon' => 'fa-fingerprint',         'color' => 'bg-blue',   'link' => 'presensi.php'],
      ['name' => '7 KAIH', 'icon' => 'fa-star',                'color' => 'bg-yellow', 'link' => 'jurnal-7kih.php'],
      ['name' => 'Ajukan Izin', 'icon' => 'fa-file-signature',      'color' => 'bg-green',  'link' => 'ajukan-izin.php'],
      ['name' => 'Lentera',     'icon' => 'fa-rocket',              'color' => 'bg-orange', 'link' => 'literasi.php'],
      ['name' => 'Tugas dan Materi',       'icon' => 'fa-book-open',           'color' => 'bg-red',    'link' => 'tugas.php'],
      ['name' => 'Pengumuman',  'icon' => 'fa-bullhorn',            'color' => 'bg-purple', 'link' => '../../pengumuman.php'],
      ['name' => 'Pelanggaran', 'icon' => 'fa-exclamation-triangle','color' => 'bg-teal',   'link' => 'pelanggaran.php'],
      ['name' => 'Aduan',       'icon' => 'fa-shield-heart',        'color' => 'bg-pink',   'link' => 'aduan.php'],
      ['name' => 'Twibbon',     'icon' => 'fa-camera-retro',        'color' => 'bg-indigo', 'link' => 'twibbon.php'],
      ['name' => 'Kalender',    'icon' => 'fa-calendar-alt',        'color' => 'bg-blue',   'link' => 'kalender.php'],
      ['name' => 'Nilai',       'icon' => 'fa-chart-bar',           'color' => 'bg-green',  'link' => '#'],
      ['name' => 'Medsos',      'icon' => 'fa-hashtag',             'color' => 'bg-indigo', 'link' => 'medsos.php'],
    ];
    // Cek pengaturan admin apakah tombol Naik Kelas ditampilkan atau disembunyikan
    $showNaikKelas = '0';
    $qNk = @mysqli_query($conn, "SELECT nilai FROM tbl_app_config WHERE kunci = 'show_naik_kelas' LIMIT 1");
    if ($qNk && ($rNk = mysqli_fetch_assoc($qNk))) {
        $showNaikKelas = $rNk['nilai'];
    }
    if ($showNaikKelas === '1' && preg_match('/\b(X|XI|10|11)\b/i', $studentClass)) {
        $menus[] = ['name' => 'Naik Kelas', 'icon' => 'fa-level-up-alt', 'color' => 'bg-orange', 'link' => 'naik-kelas.php'];
    }
    ?>
    <div class="menu-grid">
      <?php foreach ($menus as $m): ?>
        <a href="<?= htmlspecialchars($m['link']) ?>" class="menu-item" role="button">
          <div class="menu-icon-wrap">
            <div class="menu-icon-inner <?= $m['color'] ?>">
              <i class="fas <?= $m['icon'] ?>"></i>
            </div>
          </div>
          <span class="menu-name"><?= $m['name'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Leaderboard 7 KAIH -->
    <?php if(!empty($top_kih)): ?>
    <div style="margin: 20px 20px 0; background: #fff; border-radius: 20px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b;"><i class="fas fa-trophy" style="color: #eab308; margin-right: 8px;"></i> Bintang 7 KAIH Bulan Ini</h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach($top_kih as $idx => $tk): 
                $rankColor = $idx == 0 ? '#eab308' : ($idx == 1 ? '#94a3b8' : '#cd7f32');
                $isMe = $tk['no_induk'] === $nisSiswa;
            ?>
            <div style="display: flex; align-items: center; justify-content: space-between; background: <?= $isMe ? '#f0fdf4' : '#f8fafc' ?>; padding: 12px 15px; border-radius: 12px; border: 1px solid <?= $isMe ? '#bbf7d0' : '#e2e8f0' ?>;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 30px; height: 30px; border-radius: 50%; background: <?= $rankColor ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;">
                        <?= $idx + 1 ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem; color: #1e293b;"><?= htmlspecialchars($tk['nama_siswa']) ?> <?= $isMe ? '<span style="color:#22c55e; font-size:0.75rem;">(Anda)</span>' : '' ?></div>
                        <div style="font-size: 0.75rem; color: #64748b;">Poin Disiplin: <?= number_format($tk['total_score'], 0) ?></div>
                    </div>
                </div>
                <?php if($idx == 0): ?>
                    <i class="fas fa-crown" style="color: <?= $rankColor ?>; font-size: 1.2rem;"></i>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer info -->
    <p style="text-align:center;font-size:0.7rem;color:var(--muted);padding: 8px 0 20px;">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($lembaga['nmsekolah'] ?? '') ?>
    </p>

  </div><!-- /.main-wrap -->

  <!-- ── BOTTOM NAV ────────────────────────────────────────── -->
  <?php include 'siswa_footer.php'; ?>


  <script>
    (function() {
      // Press animation on menu items
      var items = document.querySelectorAll('.menu-item');
      function press(el) { el.classList.add('pressed'); }
      function release() { items.forEach(function(el) { el.classList.remove('pressed'); }); }
      items.forEach(function(el) {
        el.addEventListener('touchstart', function() { press(el); }, { passive: true });
        el.addEventListener('touchend', release);
        el.addEventListener('touchcancel', release);
        el.addEventListener('mousedown', function() { press(el); });
      });
      document.addEventListener('mouseup', release);
    })();
    // Notification toggle logic
    function toggleNotif(e) {
      e.stopPropagation();
      document.getElementById('notifDropdown').classList.toggle('show');
    }
    document.addEventListener('click', function(e) {
      const drop = document.getElementById('notifDropdown');
      if (drop && drop.classList.contains('show')) {
        drop.classList.remove('show');
      }
    });

    function markReadAndGo(id, url) {
      fetch('ajax_read_pengumuman.php?id=' + id)
      .then(res => res.text())
      .then(() => {
        window.location.href = url;
      })
      .catch(() => {
        window.location.href = url;
      });
    }

  </script>

</body>
</html>
