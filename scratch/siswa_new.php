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
date_default_timezone_set('Asia/Jakarta');
$kls = $_SESSION['kelas'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);
$lembaga = data_lembaga();
$stat = "Aktif";
// Data tambahan untuk tampilan mobile siswa
$nisSiswa = $_SESSION['no_induk'];
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
  }
}
// Cek notifikasi izin terbaru (7 hari terakhir)
$izinNotifSiswa = [];
$__tblIzinN = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin_siswa'");
if ($__tblIzinN && mysqli_num_rows($__tblIzinN) > 0) {
  $__nisN = mysqli_real_escape_string($conn, $nisSiswa);
  $__rIzin = mysqli_query($conn, "SELECT status_izin, jenis_izin, tanggal_izin, catatan_penolakan
    FROM tbl_izin_siswa
    WHERE {$tenantIzin} AND no_induk_siswa='$__nisN'
      AND status_izin IN ('Disetujui Penuh','Ditolak')
      AND DATE(waktu_pengajuan) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY waktu_pengajuan DESC LIMIT 5");
  if ($__rIzin) while ($rw = mysqli_fetch_assoc($__rIzin)) $izinNotifSiswa[] = $rw;
}
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
      overflow: hidden;
      color: #fff;
    }

    /* Motif background biru */
    .app-header::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='180' cy='20' r='60' fill='rgba(255,255,255,.08)'/%3E%3Ccircle cx='20' cy='180' r='80' fill='rgba(255,255,255,.05)'/%3E%3C/svg%3E") no-repeat center/cover;
      opacity: 0.8;
      z-index: 1;
    }

    .header-content {
      position: relative;
      z-index: 2;
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

    .menu-item:active {
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
    .bg-yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .bg-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .bg-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .bg-purple { background: linear-gradient(135deg, #a855f7, #9333ea); }
    .bg-teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
    .bg-pink { background: linear-gradient(135deg, #ec4899, #db2777); }
    .bg-indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }

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
        <div class="notif-bell">
          <i class="fas fa-bell"></i>
          <?php if(count($izinNotifSiswa) > 0): ?>
            <span class="notif-badge"><?= count($izinNotifSiswa) ?></span>
          <?php endif; ?>
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
        <img src="../../img/hero_students.png" alt="Students" onerror="this.style.display='none'">
      </div>
    </div>
  </div>

  <!-- ── MAIN CONTENT ──────────────────────────────────────── -->
  <div class="main-wrap">

    <!-- Red Profile Card -->
    <div class="red-card">
      <div class="rc-top">
        <div class="rc-avatar">
          <div class="rc-avatar-fallback"><?= $firstLetter ?></div>
        </div>
        <div class="rc-greeting">
          <div class="rc-g-label">Selamat Pagi,</div>
          <div class="rc-g-name"><?= $studentName ?></div>
        </div>
        <i class="fas fa-grip-horizontal rc-grid-icon"></i>
      </div>
      
      <div class="rc-bottom">
        <div class="rc-stat-item">
          <div class="rc-stat-icon"><i class="fas fa-check-circle"></i></div>
          <div class="rc-stat-text">
            <span class="rc-stat-title">Hadir</span>
            <span class="rc-stat-val"><?= $absSummary['hadir'] ?> Hari</span>
          </div>
        </div>
        <div class="rc-stat-item">
          <div class="rc-stat-icon"><i class="fas fa-envelope-open-text"></i></div>
          <div class="rc-stat-text">
            <span class="rc-stat-title">Izin/Skt</span>
            <span class="rc-stat-val"><?= $absSummary['ijin'] + $absSummary['sakit'] ?> Hari</span>
          </div>
        </div>
        <div class="rc-stat-item">
          <div class="rc-stat-icon"><i class="fas fa-times-circle"></i></div>
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
      ['name' => 'Kehadiran', 'icon' => 'fa-calendar-check', 'color' => 'bg-blue',   'link' => 'presensi.php'],
      ['name' => 'Jurnal',    'icon' => 'fa-edit',             'color' => 'bg-yellow', 'link' => 'jurnal-7kih.php'],
      ['name' => 'Ajukan Izin','icon' => 'fa-file-signature',  'color' => 'bg-green',  'link' => 'ajukan-izin.php'],
      ['name' => 'Akademik',  'icon' => 'fa-graduation-cap',   'color' => 'bg-red',    'link' => '#'],
      ['name' => 'Konseling', 'icon' => 'fa-comments',         'color' => 'bg-purple', 'link' => 'aduan.php'],
      ['name' => 'Jadwal',    'icon' => 'fa-calendar-alt',     'color' => 'bg-teal',   'link' => 'kalender.php'],
      ['name' => 'Nilai',     'icon' => 'fa-file-alt',         'color' => 'bg-blue',   'link' => '#'],
      ['name' => 'Pelanggaran','icon' => 'fa-exclamation-triangle','color' => 'bg-purple','link' => 'pelanggaran.php'],
    ];
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

    <!-- Footer info -->
    <p style="text-align:center;font-size:0.7rem;color:var(--muted);padding: 8px 0 20px;">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($lembaga['nmsekolah'] ?? '') ?>
    </p>

  </div><!-- /.main-wrap -->

  <!-- ── BOTTOM NAV ────────────────────────────────────────── -->
  <nav class="bottom-nav">
    <a href="siswa.php" class="bnav-item active">
      <i class="fas fa-home"></i>
      <span class="bnav-label">Beranda</span>
    </a>
    <a href="presensi.php" class="bnav-item">
      <i class="fas fa-book-open"></i>
      <span class="bnav-label">Studi</span>
    </a>
    <a href="../../pengumuman.php" class="bnav-item">
      <i class="far fa-bell"></i>
      <span class="bnav-label">Notifikasi</span>
    </a>
    <a href="profil.php" class="bnav-item">
      <i class="far fa-user"></i>
      <span class="bnav-label">Profil</span>
    </a>
  </nav>

</body>
</html>
