<?php
// pages/siswa/literasi.php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';

// Auto migration check
@mysqli_query($conn, "ALTER TABLE tbl_literasi_progress ADD COLUMN durasi_detik INT DEFAULT 0 AFTER waktu_selesai");
@mysqli_query($conn, "ALTER TABLE tbl_literasi_progress ADD COLUMN skor_durasi INT DEFAULT 0 AFTER durasi_detik");
@mysqli_query($conn, "ALTER TABLE tbl_literasi_progress ADD COLUMN skor_literasi INT DEFAULT 0 AFTER skor_durasi");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

$nis = $_SESSION['no_induk'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$kelas = $_SESSION['kelas'] ?? '';
$kelasEsc = mysqli_real_escape_string($conn, $kelas);
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

$qTugas = mysqli_query($conn, "
    SELECT t.*, p.status as p_status, p.skor_evaluasi as nilai, p.skor_durasi, p.skor_literasi, p.durasi_detik
    FROM tbl_literasi_tugas t
    LEFT JOIN tbl_literasi_progress p ON p.id_tugas = t.id AND p.no_induk_siswa = '$nisEsc'
    WHERE t.kelas = '$kelasEsc' AND t.id_sekolah = $idSekolah
    ORDER BY t.id DESC
");

$misiAktif = [];
$riwayat = [];

if ($qTugas) {
    while($t = mysqli_fetch_assoc($qTugas)){
        if (($t['p_status'] ?? 'belum') === 'selesai') {
            $riwayat[] = $t;
        } else {
            $misiAktif[] = $t;
        }
    }
}

// Helper for Grade
function getGradeBadge($skor) {
    if ($skor >= 90) return ['badge' => 'A', 'bg' => '#0284c7', 'text' => 'Sangat Baik'];
    if ($skor >= 80) return ['badge' => 'B+', 'bg' => '#f59e0b', 'text' => 'Baik'];
    if ($skor >= 70) return ['badge' => 'B', 'bg' => '#10b981', 'text' => 'Cukup'];
    if ($skor >= 60) return ['badge' => 'C', 'bg' => '#f97316', 'text' => 'Kurang'];
    return ['badge' => 'D', 'bg' => '#ef4444', 'text' => 'Perlu Evaluasi'];
}

// Background patterns for cards
$bgPatterns = [
    'linear-gradient(135deg, #10b981, #047857)',
    'linear-gradient(135deg, #f59e0b, #b45309)',
    'linear-gradient(135deg, #0ea5e9, #0369a1)',
    'linear-gradient(135deg, #8b5cf6, #5b21b6)',
    'linear-gradient(135deg, #ec4899, #be185d)'
];

$studentName = $_SESSION['nama'] ?? 'Siswa';
$isFemale = preg_match('/\b(putri|sari|ayu|wati|ningrum|indah|nurul|siti|dewi|wiwid|nisa|aulia|zahra|salma|syifa|lestari|fitri|amelia|kartika|kusuma|maharani|mega|novia|pratiwi|puspa|ratna|retno|safira|sekarsari|susanti|tari|wulandari|cahyaningrum)\b/i', $studentName);
$avatarFile = (isset($_SESSION['jk']) && $_SESSION['jk'] == 'P') || $isFemale ? 'avatar_female_3d.png' : 'avatar_male_3d.png';
$avatarUrl = "../../img/" . $avatarFile;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Siswa - LENTERA Literasi</title>
  <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
      :root {
          --vintage-bg: #f5f3ec;
          --vintage-text: #4e342e;
          --vintage-accent: #a7d0c8;
          --vintage-dark: #3e2723;
          --vintage-gray: #a19d94;
          --vintage-brown-light: #d7ccc8;
      }
      body {
          background-color: var(--vintage-bg);
          font-family: 'Nunito', sans-serif;
          margin: 0;
          padding: 0;
          color: var(--vintage-text);
          -webkit-font-smoothing: antialiased;
          padding-bottom: 80px; /* Space for bottom nav */
      }
      /* Top Header */
      .top-header {
          background: linear-gradient(135deg, #10b981 0%, #047857 100%);
          border-bottom-left-radius: 30px;
          border-bottom-right-radius: 30px;
          padding: 20px;
          color: white;
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
          position: relative;
      }
      .btn-back {
          position: absolute;
          top: 15px;
          right: 20px;
          color: rgba(255,255,255,0.9);
          font-size: 13px;
          text-decoration: none;
          display: flex;
          align-items: center;
          gap: 5px;
          font-family: 'Lora', serif;
          font-weight: 600;
      }
      .btn-back:hover { color: white; text-decoration: none; }
      
      .header-flex {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-top: 20px;
          margin-bottom: 20px;
      }
      .logo-area {
          display: flex;
          align-items: center;
          gap: 12px;
      }
      .logo-icon-wrap {
          position: relative;
          width: 45px;
          height: 45px;
          background: rgba(255, 255, 255, 0.2);
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
      }
      .logo-icon-wrap i.fa-book {
          font-size: 24px;
      }
      .logo-icon-wrap i.fa-fire {
          position: absolute;
          bottom: 5px;
          right: 5px;
          font-size: 14px;
          color: #fde047;
      }
      .logo-text {
          line-height: 1.1;
      }
      .logo-title {
          font-family: 'Lora', serif;
          font-weight: 700;
          font-size: 24px;
          letter-spacing: 1px;
          color: white;
      }
      .logo-subtitle {
          font-family: 'Lora', serif;
          font-size: 11px;
          color: rgba(255, 255, 255, 0.85);
      }
      .user-area {
          display: flex;
          align-items: center;
          gap: 10px;
          text-align: right;
      }
      .greeting-text {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.85);
          margin-bottom: 2px;
      }
      .user-name {
          font-weight: 800;
          font-size: 16px;
          color: white;
          font-family: 'Lora', serif;
      }
      .user-avatar {
          width: 45px;
          height: 45px;
          border-radius: 50%;
          border: 2px solid rgba(255, 255, 255, 0.5);
          background-color: transparent;
          object-fit: cover;
      }
      /* Search Bar */
      .search-container {
          background: #f3f4f6;
          border-radius: 20px;
          padding: 10px 15px;
          display: flex;
          align-items: center;
          gap: 10px;
      }
      .search-container input {
          background: transparent;
          border: none;
          outline: none;
          width: 100%;
          font-size: 13px;
          color: #4b5563;
      }
      .search-container i {
          color: #9ca3af;
      }
      
      /* Sections */
      .section-container {
          padding: 25px 20px 0 20px;
      }
      .section-title {
          font-family: 'Lora', serif;
          font-weight: 700;
          font-size: 16px;
          margin-bottom: 15px;
          color: var(--vintage-dark);
          text-transform: uppercase;
      }
      
      /* Misi Aktif Carousel */
      .misi-carousel {
          display: flex;
          overflow-x: auto;
          gap: 15px;
          padding-bottom: 15px;
          scrollbar-width: none; /* Firefox */
      }
      .misi-carousel::-webkit-scrollbar {
          display: none; /* Chrome */
      }
      .misi-card {
          flex: 0 0 240px;
          background: white;
          border-radius: 16px;
          overflow: hidden;
          box-shadow: 0 4px 15px rgba(0,0,0,0.03);
          text-decoration: none;
          color: inherit;
          display: block;
          border-top: 6px solid var(--vintage-accent);
          position: relative;
      }
      .misi-body {
          padding: 15px;
      }
      .misi-mapel {
          font-size: 11px;
          color: #9ca3af;
          margin-bottom: 5px;
      }
      .misi-title {
          font-family: 'Lora', serif;
          font-size: 16px;
          font-weight: 700;
          line-height: 1.3;
          color: var(--vintage-dark);
          margin-bottom: 15px;
          min-height: 42px;
      }
      .misi-action {
          display: flex;
          justify-content: flex-end;
          align-items: center;
          margin-bottom: 15px;
      }
      .misi-play-btn {
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 5px;
      }
      .play-icon-wrap {
          position: relative;
          color: #8d6e63;
          font-size: 32px;
      }
      .play-icon-wrap .fa-compass {
          position: absolute;
          bottom: -5px;
          left: -10px;
          font-size: 18px;
          color: #d7ccc8;
          background: white;
          border-radius: 50%;
      }
      .play-text {
          font-size: 10px;
          color: #795548;
      }
      
      .progress-container {
          background: #e5e7eb;
          border-radius: 10px;
          height: 20px;
          position: relative;
          display: flex;
          align-items: center;
          margin-bottom: 15px;
      }
      .progress-fill {
          height: 100%;
          background: var(--vintage-accent);
          border-radius: 10px;
          display: flex;
          align-items: center;
          padding-left: 25px;
          color: white;
          font-size: 11px;
          font-weight: 600;
          white-space: nowrap;
      }
      .progress-icon {
          position: absolute;
          left: -5px;
          top: 50%;
          transform: translateY(-50%);
          width: 26px;
          height: 26px;
          background: #d7ccc8;
          border: 2px solid white;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--vintage-dark);
          font-size: 12px;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      .progress-val {
          position: absolute;
          right: 10px;
          font-size: 11px;
          font-weight: 800;
          color: var(--vintage-dark);
      }
      
      .misi-footer {
          display: flex;
          justify-content: space-between;
          border-top: 1px dashed #e5e7eb;
          padding-top: 12px;
      }
      .misi-footer-col {
          display: flex;
          flex-direction: column;
          gap: 4px;
      }
      .misi-footer-label {
          font-size: 9px;
          font-weight: 800;
          color: var(--vintage-dark);
      }
      .misi-footer-val {
          font-size: 11px;
          color: #795548;
          display: flex;
          align-items: center;
          gap: 5px;
      }
      .misi-footer-val i {
          color: #d7ccc8;
          font-size: 14px;
      }
      
      /* Riwayat Literasi */
      .riwayat-list {
          display: flex;
          flex-direction: column;
          gap: 12px;
      }
      .riwayat-card {
          background: white;
          border-radius: 16px;
          padding: 15px;
          display: flex;
          flex-direction: column;
          gap: 10px;
          box-shadow: 0 4px 15px rgba(0,0,0,0.03);
          text-decoration: none;
          color: inherit;
      }
      .riwayat-top {
          display: flex;
          align-items: center;
          gap: 15px;
      }
      .riwayat-thumb {
          width: 50px;
          height: 50px;
          display: grid;
          place-items: center;
          color: #8d6e63;
          font-size: 32px;
          position: relative;
      }
      .riwayat-thumb .fa-feather-alt {
          position: absolute;
          top: -5px;
          right: -5px;
          font-size: 20px;
          color: #d7ccc8;
      }
      .riwayat-info {
          flex: 1;
      }
      .riwayat-title {
          font-family: 'Lora', serif;
          font-weight: 700;
          font-size: 14px;
          margin-bottom: 2px;
          color: var(--vintage-dark);
          text-transform: uppercase;
      }
      .riwayat-subtitle {
          font-size: 11px;
          color: #795548;
      }
      .riwayat-grade-circle {
          width: 30px;
          height: 30px;
          border-radius: 50%;
          border: 2px solid #8d6e63;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--vintage-dark);
          font-weight: 800;
          font-size: 14px;
          background: #f5f3ec;
      }
      .riwayat-score {
          text-align: right;
          min-width: 65px;
      }
      .riwayat-score-val {
          font-weight: 800;
          font-size: 13px;
          color: var(--vintage-dark);
          display: flex;
          align-items: center;
          justify-content: flex-end;
          gap: 4px;
      }
      .riwayat-score-val i {
          color: #d7ccc8;
      }
      .riwayat-score-text {
          font-size: 9px;
          color: #795548;
      }
      .riwayat-bottom-bar {
          height: 6px;
          background: #e5e7eb;
          border-radius: 3px;
          width: 100%;
      }
      
      /* Bottom Nav */
      .bottom-nav {
          position: fixed;
          bottom: 0;
          left: 0;
          right: 0;
          background: white;
          display: flex;
          justify-content: space-around;
          padding: 12px 10px;
          box-shadow: 0 -4px 20px rgba(0,0,0,0.03);
          border-top-left-radius: 20px;
          border-top-right-radius: 20px;
          z-index: 10;
      }
      .nav-item {
          text-align: center;
          color: #d7ccc8;
          text-decoration: none;
          font-size: 10px;
          font-family: 'Lora', serif;
          font-weight: 600;
      }
      .nav-item.active {
          color: #8d6e63;
      }
      .nav-item i {
          font-size: 20px;
          display: block;
          margin-bottom: 4px;
      }
  </style>
</head>
<body>

<div class="top-header">
    <a href="siswa.php" class="btn-back"><i class="fas fa-scroll"></i> Tutup Lentera</a>
    <div class="header-flex">
        <div class="logo-area">
            <div class="logo-icon-wrap">
                <i class="fas fa-book"></i>
                <i class="fas fa-fire"></i>
            </div>
            <div class="logo-text">
                <div class="logo-title">LENTERA</div>
                <div class="logo-subtitle">Literasi Elektronik Nusantara</div>
            </div>
        </div>
        <div class="user-area">
            <div class="user-text text-right">
                <div class="greeting-text">Selamat Belajar,</div>
                <div class="user-name"><?= htmlspecialchars(explode(' ', $_SESSION['nama'] ?? 'Siswa')[0]) ?>!</div>
            </div>
            <img src="<?= $avatarUrl ?>" class="user-avatar" alt="User">
        </div>
    </div>
    
    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search">
    </div>
</div>

<div class="section-container">
    <div class="section-title">MISI AKTIF</div>
    <?php if (empty($misiAktif)): ?>
        <div style="background: white; border-radius: 16px; padding: 20px; text-align: center; color: #6b7280; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 6px solid var(--vintage-accent);">
            <i class="fas fa-check-circle fa-3x mb-2" style="color: var(--vintage-accent);"></i>
            <h4 style="font-weight: 800; font-size: 16px; color: var(--vintage-dark); margin: 0; font-family: 'Lora', serif;">Semua Misi Selesai!</h4>
            <p style="font-size: 13px; margin-top: 5px; margin-bottom: 0;">Tunggu gurumu memberikan misi baru ya.</p>
        </div>
    <?php else: ?>
        <div class="misi-carousel">
            <?php foreach ($misiAktif as $i => $misi): 
                $isReading = ($misi['p_status'] === 'membaca');
                $progressPct = $isReading ? 50 : 0; // Simulated progress if reading
                $progressText = 'Tugas Membaca';
                $actionIcon = 'fa-file-alt';
                if ($misi['tipe_media'] === 'video') { $actionIcon = 'fa-play'; $progressText = 'Tonton Video'; }
                if ($misi['tipe_media'] === 'gambar') { $actionIcon = 'fa-image'; $progressText = 'Lihat Gambar'; }
            ?>
            <a href="literasi_misi.php?id=<?= $misi['id'] ?>" class="misi-card">
                <div class="misi-body">
                    <div class="misi-mapel">Bahasa Indonesia (<?= htmlspecialchars($kelas) ?>)</div>
                    <div class="misi-title"><?= htmlspecialchars($misi['judul']) ?></div>
                    
                    <div class="misi-action">
                        <div class="misi-play-btn">
                            <div class="play-icon-wrap">
                                <i class="fas <?= $actionIcon ?>"></i>
                                <i class="fas fa-compass"></i>
                            </div>
                            <div class="play-text"><?= $progressText ?></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-fill" style="width: <?= $progressPct ?>%;">
                            <?= $progressPct > 0 ? $progressPct . '% Selesai' : '' ?>
                        </div>
                        <div class="progress-icon">
                            <i class="fas fa-compass"></i>
                        </div>
                        <div class="progress-val"><?= $progressPct ?>%</div>
                    </div>
                    
                    <div class="misi-footer">
                        <div class="misi-footer-col">
                            <div class="misi-footer-label">DURASI</div>
                            <div class="misi-footer-val"><i class="fas fa-hourglass-half"></i> Minimal: <?= ceil($misi['durasi_minimal']/60) ?> menit</div>
                        </div>
                        <div class="misi-footer-col" style="text-align: right; align-items: flex-end;">
                            <div class="misi-footer-label">SKOR EVALUASI</div>
                            <?php if (($misi['p_status'] ?? 'belum') === 'belum'): ?>
                                <div class="misi-footer-val"><i class="fas fa-star" style="color: #e5e7eb;"></i> Skor Akhir</div>
                            <?php else: ?>
                                <div class="misi-footer-val"><i class="fas fa-star" style="color: #fbbf24;"></i> <span style="font-weight: 800; color: var(--vintage-dark);">--</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="section-container" style="padding-bottom: 25px;">
    <div class="section-title">RIWAYAT LITERASI</div>
    <div class="riwayat-list">
        <?php if (empty($riwayat)): ?>
            <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 13px;">Belum ada riwayat literasi.</div>
        <?php else: ?>
            <?php foreach ($riwayat as $i => $r): 
                $finalScore = isset($r['skor_literasi']) ? (int)$r['skor_literasi'] : ($r['nilai'] ?? 0);
                $grade = getGradeBadge($finalScore);
            ?>
            <a href="literasi_misi.php?id=<?= $r['id'] ?>" class="riwayat-card">
                <div class="riwayat-top">
                    <div class="riwayat-thumb">
                        <i class="fas fa-layer-group"></i>
                        <i class="fas fa-feather-alt"></i>
                    </div>
                    <div class="riwayat-info">
                        <div class="riwayat-title"><?= htmlspecialchars($r['judul']) ?></div>
                        <div class="riwayat-subtitle">
                            <i class="far fa-clock text-info"></i> Durasi: <?= floor(($r['durasi_detik'] ?? 0) / 60) ?>m <?= ($r['durasi_detik'] ?? 0) % 60 ?>s (Skor: <?= (int)($r['skor_durasi'] ?? 0) ?>) &nbsp;|&nbsp; 
                            <i class="fas fa-edit text-success"></i> Kuis: <?= number_format((float)($r['nilai'] ?? 0), 0) ?>
                        </div>
                    </div>
                    <div class="riwayat-grade-circle">
                        <?= $grade['badge'] ?>
                    </div>
                    <div class="riwayat-score">
                        <div class="riwayat-score-val"><i class="fas fa-star"></i> <?= number_format((float)$finalScore, 0) ?></div>
                        <div class="riwayat-score-text"><?= $grade['text'] ?></div>
                    </div>
                </div>
                <div class="riwayat-bottom-bar"></div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'siswa_footer.php'; ?>


</body>
</html>
