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
  <meta name="theme-color" content="#1e40af">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title><?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Portal Siswa') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary: #1e40af;
      --primary-lt: #3b82f6;
      --accent: #0ea5e9;
      --bg: #f0f4ff;
      --card: #ffffff;
      --text: #1e293b;
      --muted: #64748b;
      --radius: 16px;
      --shadow: 0 4px 20px rgba(30, 64, 175, .10);
      --bottom-h: 68px;
    }

    html {
      font-size: 15px;
      -webkit-tap-highlight-color: transparent;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      padding-bottom: calc(var(--bottom-h) + env(safe-area-inset-bottom));
    }

    /* â”€â”€ TOP HEADER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .app-header {
      background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 50%, var(--accent) 100%);
      padding: 16px 20px 56px;
      position: relative;
      overflow: hidden;
    }

    .app-header::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='160' cy='30' r='80' fill='rgba(255,255,255,.06)'/%3E%3Ccircle cx='20' cy='160' r='60' fill='rgba(255,255,255,.05)'/%3E%3C/svg%3E") no-repeat center/cover;
    }

    .header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
    }

    .school-logo {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(255, 255, 255, .20);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 18px;
      flex-shrink: 0;
      overflow: hidden;
    }

    .school-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .school-name {
      color: #fff;
      font-size: .82rem;
      font-weight: 700;
      letter-spacing: .3px;
      flex: 1;
      padding: 0 10px;
      line-height: 1.3;
    }

    .header-logout {
      background: rgba(255, 255, 255, .18);
      border: none;
      color: #fff;
      border-radius: 10px;
      padding: 7px 12px;
      font-size: .75rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      text-decoration: none;
      transition: background .2s;
      white-space: nowrap;
    }

    .header-logout:hover {
      background: rgba(255, 255, 255, .28);
    }

    .header-greeting {
      position: relative;
      margin-top: 18px;
      color: #fff;
    }

    .greeting-label {
      font-size: .78rem;
      opacity: .8;
    }

    .greeting-name {
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: -.2px;
      line-height: 1.2;
    }

    .greeting-class {
      font-size: .8rem;
      opacity: .75;
      margin-top: 3px;
    }

    /* â”€â”€ FLOATING CARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .main-wrap {
      padding: 0 16px 20px;
      margin-top: -38px;
      position: relative;
    }

    /* â”€â”€ PROFILE CARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .profile-card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 16px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
    }

    .profile-avatar {
      width: 54px;
      height: 54px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 22px;
      font-weight: 700;
      flex-shrink: 0;
      overflow: hidden;
    }

    .profile-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .profile-info {
      flex: 1;
      min-width: 0;
    }

    .profile-name {
      font-weight: 700;
      font-size: .95rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .profile-meta {
      font-size: .75rem;
      color: var(--muted);
      margin-top: 2px;
    }

    .profile-badge {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff;
      border-radius: 8px;
      padding: 4px 11px;
      font-size: .7rem;
      font-weight: 700;
      white-space: nowrap;
    }

    /* â”€â”€ DATE STRIP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .date-strip {
      background: var(--card);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 10px 16px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .date-strip i {
      color: var(--primary-lt);
      font-size: 1rem;
    }

    .date-strip span {
      font-size: .82rem;
      color: var(--muted);
      font-weight: 500;
    }

    .date-strip strong {
      color: var(--text);
    }

    /* â”€â”€ ABSEN SUMMARY â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .section-label {
      font-size: .75rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 10px;
      padding: 0 2px;
    }

    .abs-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 8px;
      margin-bottom: 20px;
    }

    .abs-card {
      background: var(--card);
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(30, 64, 175, .08);
      padding: 10px 6px;
      text-align: center;
      transition: transform .15s;
    }

    .abs-card:active {
      transform: scale(.96);
    }

    .abs-num {
      font-size: 1.25rem;
      font-weight: 800;
    }

    .abs-label {
      font-size: .62rem;
      color: var(--muted);
      font-weight: 600;
      text-transform: uppercase;
      margin-top: 2px;
    }

    .abs-hadir .abs-num {
      color: #16a34a;
    }

    .abs-ijin .abs-num {
      color: #d97706;
    }

    .abs-sakit .abs-num {
      color: #2563eb;
    }

    .abs-dispen .abs-num {
      color: #7c3aed;
    }

    .abs-alpha .abs-num {
      color: #dc2626;
    }

    .abs-hadir {
      border-top: 3px solid #16a34a;
    }

    .abs-ijin {
      border-top: 3px solid #d97706;
    }

    .abs-sakit {
      border-top: 3px solid #2563eb;
    }

    .abs-dispen {
      border-top: 3px solid #7c3aed;
    }

    .abs-alpha {
      border-top: 3px solid #dc2626;
    }

    /* â”€â”€ MENU GRID â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .menu-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    @media (min-width: 480px) {
      .menu-grid {
        grid-template-columns: repeat(5, 1fr);
      }
    }

    @media (min-width: 768px) {
      .menu-grid {
        grid-template-columns: repeat(6, 1fr);
      }
    }

    .menu-item {
      background: var(--card);
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(30, 64, 175, .08);
      padding: 14px 8px 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: var(--text);
      transition: transform .15s, box-shadow .15s;
      cursor: pointer;
      -webkit-user-select: none;
      user-select: none;
    }

    .menu-item:active,
    .menu-item.pressed {
      transform: scale(.94);
      box-shadow: 0 1px 5px rgba(30, 64, 175, .1);
    }

    .menu-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(30, 64, 175, .13);
    }

    .menu-icon {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    .menu-name {
      font-size: .64rem;
      font-weight: 700;
      text-align: center;
      color: var(--text);
      line-height: 1.2;
      text-transform: uppercase;
      letter-spacing: .2px;
    }

    /* icon colors */
    .ic-blue {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .ic-yellow {
      background: #fef3c7;
      color: #d97706;
    }

    .ic-green {
      background: #dcfce7;
      color: #16a34a;
    }

    .ic-orange {
      background: #ffedd5;
      color: #ea580c;
    }

    .ic-purple {
      background: #ede9fe;
      color: #7c3aed;
    }

    .ic-red {
      background: #fee2e2;
      color: #dc2626;
    }

    .ic-pink {
      background: #fce7f3;
      color: #db2777;
    }

    .ic-indigo {
      background: #e0e7ff;
      color: #4f46e5;
    }

    .ic-teal {
      background: #ccfbf1;
      color: #0d9488;
    }

    .ic-slate {
      background: #f1f5f9;
      color: #475569;
    }

    /* â”€â”€ SUBMENU STYLING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .menu-item-group {
      position: relative;
    }

    .menu-item-expandable {
      border: none;
      background: none;
      padding: 0;
      position: relative;
    }

    .menu-item-expandable .menu-expand-icon {
      position: absolute;
      bottom: 8px;
      right: 8px;
      background: rgba(30, 64, 175, .15);
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .5rem;
      transition: transform .3s;
    }

    .menu-item-expandable.expanded .menu-expand-icon {
      transform: rotate(180deg);
    }

    .menu-submenu {
      background: var(--card);
      border-radius: 12px;
      box-shadow: inset 0 2px 8px rgba(30, 64, 175, .08);
      margin-top: 8px;
      overflow: hidden;
      animation: slideDown .2s ease-out;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-8px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .menu-subitem {
      display: block;
      padding: 10px 14px;
      border-bottom: 1px solid #f3f4f6;
      color: var(--text);
      text-decoration: none;
      font-size: .75rem;
      font-weight: 600;
      transition: background .2s, padding-left .2s;
      cursor: pointer;
    }

    .menu-subitem:last-child {
      border-bottom: none;
    }

    .menu-subitem:hover {
      background: #f9fafb;
      padding-left: 18px;
    }

    .menu-subitem i {
      color: var(--primary);
      margin-right: 6px;
    }

    /* â”€â”€ BOTTOM NAV â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: calc(var(--bottom-h) + env(safe-area-inset-bottom));
      background: var(--card);
      box-shadow: 0 -4px 24px rgba(30, 64, 175, .12);
      display: flex;
      align-items: flex-start;
      justify-content: space-around;
      padding-top: 10px;
      padding-bottom: env(safe-area-inset-bottom);
      z-index: 100;
      border-radius: 20px 20px 0 0;
    }

    .bnav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      text-decoration: none;
      color: var(--muted);
      transition: color .2s;
      padding: 0 8px;
      flex: 1;
    }

    .bnav-item.active {
      color: var(--primary);
    }

    .bnav-item i {
      font-size: 1.15rem;
    }

    .bnav-label {
      font-size: .6rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .3px;
    }

    .bnav-item.active i {
      filter: drop-shadow(0 2px 4px rgba(30, 64, 175, .4));
    }

    /* â”€â”€ MISC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .section-card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 16px;
      margin-bottom: 16px;
    }

    @media (min-width: 768px) {
      .main-wrap {
        max-width: 640px;
        margin-left: auto;
        margin-right: auto;
      }

      .app-header {
        padding-left: calc(50% - 300px);
        padding-right: calc(50% - 300px);
      }
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

  <!-- â”€â”€ TOP HEADER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <div class="app-header">
    <div class="header-top">
      <div class="school-logo">
        <?php if ($logoFile && file_exists($logoPath)): ?>
          <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
        <?php else: ?>
          <i class="fas fa-school"></i>
        <?php endif; ?>
      </div>
      <div class="school-name"><?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Portal Siswa') ?></div>
      <a href="../../logout.php" class="header-logout">
        <i class="fas fa-sign-out-alt"></i> Keluar
      </a>
    </div>
    <div class="header-greeting">
      <div class="greeting-label">Selamat datang ðŸ‘‹</div>
      <div class="greeting-name"><?= $studentName ?></div>
      <div class="greeting-class"><i class="fas fa-door-open" style="font-size:.75rem;"></i> Kelas <?= $studentClass ?></div>
    </div>
  </div>

  <!-- â”€â”€ MAIN CONTENT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <div class="main-wrap">

    <!-- Date strip -->
    <div class="date-strip">
      <i class="fas fa-calendar-day"></i>
      <span><strong><?= $hariini ?></strong>, <?= tgl_indo($tglskr) ?></span>
    </div>

    <!-- Attendance Summary -->
    <div class="section-label">Kehadiran Bulan Ini</div>
    <div class="abs-grid">
      <div class="abs-card abs-hadir">
        <div class="abs-num"><?= $absSummary['hadir'] ?></div>
        <div class="abs-label">Hadir</div>
      </div>
      <div class="abs-card abs-ijin">
        <div class="abs-num"><?= $absSummary['ijin'] ?></div>
        <div class="abs-label">Izin</div>
      </div>
      <div class="abs-card abs-sakit">
        <div class="abs-num"><?= $absSummary['sakit'] ?></div>
        <div class="abs-label">Sakit</div>
      </div>
      <div class="abs-card abs-dispen">
        <div class="abs-num"><?= $absSummary['dispen'] ?></div>
        <div class="abs-label">Dispen</div>
      </div>
      <div class="abs-card abs-alpha">
        <div class="abs-num"><?= $absSummary['alpha'] ?></div>
        <div class="abs-label">Alpha</div>
      </div>
    </div>

    <!-- Menu Grid -->
    <div class="section-label">Menu Utama</div>
    <?php if (!empty($izinNotifSiswa)): ?>
      <div style="margin: 0 0 12px;">
        <?php foreach ($izinNotifSiswa as $notif): ?>
          <?php
          $nApproved = ($notif['status_izin'] === 'Disetujui Penuh');
          $nBg   = $nApproved ? '#dcfce7' : '#fee2e2';
          $nBord = $nApproved ? '#16a34a' : '#dc2626';
          $nIco  = $nApproved ? 'fa-check-circle' : 'fa-times-circle';
          $nIcoC = $nApproved ? '#16a34a' : '#dc2626';
          $nText = $nApproved ? '\u2705 Disetujui Penuh' : '\u274C Ditolak';
          ?>
          <div style="background:<?= $nBg ?>;border-left:4px solid <?= $nBord ?>;border-radius:12px;padding:12px 16px;margin-bottom:8px;display:flex;gap:12px;align-items:flex-start;">
            <i class="fas <?= $nIco ?>" style="color:<?= $nIcoC ?>;font-size:1.25rem;margin-top:2px;flex-shrink:0;"></i>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:.88rem;"><?= htmlspecialchars($notif['jenis_izin']) ?> &mdash; <?= $nText ?></div>
              <div style="font-size:.78rem;color:#475569;">Tanggal: <?= date('d/m/Y', strtotime($notif['tanggal_izin'])) ?></div>
              <?php if (!$nApproved && !empty($notif['catatan_penolakan'])): ?>
                <div style="font-size:.78rem;color:#dc2626;margin-top:4px;"><i class="fas fa-comment-dots"></i> <?= htmlspecialchars($notif['catatan_penolakan']) ?></div>
              <?php endif; ?>
            </div>
            <a href="status-izin.php" style="font-size:.75rem;color:var(--primary);white-space:nowrap;text-decoration:none;font-weight:700;">Lihat &rsaquo;</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php
    $menus = [
      ['name' => 'Presensi',    'icon' => 'fa-fingerprint',         'color' => 'ic-blue',   'link' => 'presensi.php'],
      ['name' => 'Jurnal 7KIH',  'icon' => 'fa-star',                'color' => 'ic-teal',   'link' => 'jurnal-7kih.php'],
      [
        'name' => 'Ajukan Izin',
        'icon' => 'fa-file-signature',
        'color' => 'ic-yellow',
        'link' => 'ajukan-izin.php',
        'submenu' => [
          ['name' => 'Ajukan Izin Baru', 'link' => 'ajukan-izin.php'],
          ['name' => 'Status Izin', 'link' => 'status-izin.php'],
        ]
      ],
      ['name' => 'Tugas',       'icon' => 'fa-book-open',           'color' => 'ic-orange', 'link' => '#'],
      ['name' => 'Pengumuman',  'icon' => 'fa-bullhorn',            'color' => 'ic-purple', 'link' => '../../pengumuman.php'],
      ['name' => 'Pelanggaran', 'icon' => 'fa-exclamation-triangle', 'color' => 'ic-red',    'link' => 'pelanggaran.php'],
      ['name' => 'Aduan',        'icon' => 'fa-shield-heart',         'color' => 'ic-red',    'link' => 'aduan.php'],
      ['name' => 'Twibbon',     'icon' => 'fa-camera-retro',        'color' => 'ic-pink',   'link' => 'twibbon.php'],
      ['name' => 'Kalender',    'icon' => 'fa-calendar-alt',        'color' => 'ic-indigo', 'link' => 'kalender.php'],
      ['name' => 'Nilai',       'icon' => 'fa-chart-bar',           'color' => 'ic-teal',   'link' => '#'],
      ['name' => 'Profil',      'icon' => 'fa-user-graduate',       'color' => 'ic-slate',  'link' => 'profil.php'],
    ];
    ?>
    <div class="menu-grid">
      <?php foreach ($menus as $m): ?>
        <?php if (!empty($m['submenu'])): ?>
          <div class="menu-item-group">
            <button class="menu-item menu-item-expandable" role="button" onclick="toggleSubmenu(this)">
              <div class="menu-icon <?= $m['color'] ?>">
                <i class="fas <?= $m['icon'] ?>"></i>
              </div>
              <span class="menu-name"><?= $m['name'] ?></span>
              <span class="menu-expand-icon"><i class="fas fa-chevron-down"></i></span>
            </button>
            <div class="menu-submenu" style="display:none;">
              <?php foreach ($m['submenu'] as $sub): ?>
                <a href="<?= htmlspecialchars($sub['link']) ?>" class="menu-subitem">
                  <i class="fas fa-arrow-right"></i> <?= $sub['name'] ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= htmlspecialchars($m['link']) ?>" class="menu-item" role="button">
            <div class="menu-icon <?= $m['color'] ?>">
              <i class="fas <?= $m['icon'] ?>"></i>
            </div>
            <span class="menu-name"><?= $m['name'] ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- Footer info -->
    <p style="text-align:center;font-size:.7rem;color:var(--muted);padding: 8px 0 4px;">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($lembaga['nmsekolah'] ?? '') ?>
    </p>

  </div><!-- /.main-wrap -->

  <!-- â”€â”€ BOTTOM NAV â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <nav class="bottom-nav">
    <a href="siswa.php" class="bnav-item active">
      <i class="fas fa-home"></i>
      <span class="bnav-label">Beranda</span>
    </a>
    <a href="presensi.php" class="bnav-item">
      <i class="fas fa-fingerprint"></i>
      <span class="bnav-label">Presensi</span>
    </a>
    <a href="../../pengumuman.php" class="bnav-item">
      <i class="fas fa-bullhorn"></i>
      <span class="bnav-label">Pengumuman</span>
    </a>
    <a href="profil.php" class="bnav-item">
      <i class="fas fa-user-circle"></i>
      <span class="bnav-label">Profil</span>
    </a>
  </nav>

  <script>
    function toggleSubmenu(btn) {
      const group = btn.parentElement;
      const submenu = group.querySelector('.menu-submenu');
      const isVisible = submenu.style.display !== 'none';

      // Close other open submenus
      document.querySelectorAll('.menu-item-expandable').forEach(function(b) {
        if (b !== btn) {
          b.classList.remove('expanded');
          b.parentElement.querySelector('.menu-submenu').style.display = 'none';
        }
      });

      // Toggle current submenu
      if (isVisible) {
        submenu.style.display = 'none';
        btn.classList.remove('expanded');
      } else {
        submenu.style.display = 'block';
        btn.classList.add('expanded');
      }
    }

    (function() {
      // Press animation on menu items
      var items = document.querySelectorAll('.menu-item:not(.menu-item-expandable)');

      function press(el) {
        el.classList.add('pressed');
      }

      function release() {
        items.forEach(function(el) {
          el.classList.remove('pressed');
        });
      }
      items.forEach(function(el) {
        el.addEventListener('touchstart', function() {
          press(el);
        }, {
          passive: true
        });
        el.addEventListener('touchend', release);
        el.addEventListener('touchcancel', release);
        el.addEventListener('mousedown', function() {
          press(el);
        });
      });
      document.addEventListener('mouseup', release);
    })();
  </script>

</body>

</html>
