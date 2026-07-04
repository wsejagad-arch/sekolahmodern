<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk'])) {
    header('Location: ../../index.php?haruslogin'); exit;
}
if ($_SESSION['hak_akses'] != 3) {
    header('Location: ../../403.php'); exit;
}

require_once '../../koneksi.php';
require_once '../../functions.php';

date_default_timezone_set('Asia/Jakarta');
$nisSiswa  = $_SESSION['no_induk'];
$namaSiswa = $_SESSION['nama'] ?? $nisSiswa;
$kelas     = $_SESSION['kelas'] ?? '';
$lembaga   = function_exists('data_lembaga') ? data_lembaga() : [];

// Pastikan tabel ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_twibbon (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  filename VARCHAR(255) NOT NULL,
  created_by VARCHAR(50) NOT NULL,
  nama_pembuat VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  aktif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ambil id dari GET (buka langsung ke editor)
$activeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil semua template aktif
$res = mysqli_query($conn, "SELECT * FROM tbl_twibbon WHERE aktif=1 ORDER BY created_at DESC");
$templates = [];
if ($res) while ($r = mysqli_fetch_assoc($res)) $templates[] = $r;

// Data template aktif (jika ada dari URL)
$activeTpl = null;
if ($activeId > 0) {
    foreach ($templates as $t) {
        if ((int)$t['id'] === $activeId) { $activeTpl = $t; break; }
    }
    // Fallback: cari termasuk nonaktif agar link tetap bisa dibuka
    if (!$activeTpl) {
        $chk = mysqli_query($conn, "SELECT * FROM tbl_twibbon WHERE id=$activeId LIMIT 1");
        if ($chk) $activeTpl = mysqli_fetch_assoc($chk);
    }
}

// Base URL untuk share
$appBase = '';
if (function_exists('get_base_path')) {
    $appBase = rtrim(get_base_path(), '/');
} else {
    $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(.*)/pages/siswa/[^/]+$#', $sn, $m)) {
        $appBase = ($m[1] === '/' || $m[1] === '') ? '' : $m[1];
    }
}
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $appBase . '/pages/siswa/twibbon.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <title>Twibbon – <?= htmlspecialchars($lembaga['nama_sekolah'] ?? 'Jurnal') ?></title>

  <!-- Open Graph (for share preview) -->
  <?php if ($activeTpl): ?>
  <meta property="og:title" content="Twibbon – <?= htmlspecialchars($activeTpl['judul']) ?>">
  <meta property="og:description" content="Buat foto twibbon <?= htmlspecialchars($activeTpl['judul']) ?> sekarang!">
  <meta property="og:image" content="<?= $baseUrl ?>/../../../uploads/twibbon/<?= rawurlencode($activeTpl['filename']) ?>">
  <meta property="og:url" content="<?= $baseUrl ?>?id=<?= $activeTpl['id'] ?>">
  <?php endif; ?>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary: #0ea5e9;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #1e293b;
      --muted: #64748b;
      --radius: 20px;
      --bottom-h: 70px;
    }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, sans-serif;
      min-height: 100vh;
      padding-bottom: calc(var(--bottom-h) + env(safe-area-inset-bottom) + 20px);
      overflow-x: hidden;
    }

    /* ── HEADER ── */
    .app-header {
      background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
      padding: 20px 20px 60px;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .app-header::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='180' cy='20' r='60' fill='rgba(255,255,255,.08)'/%3E%3Ccircle cx='20' cy='180' r='80' fill='rgba(255,255,255,.05)'/%3E%3C/svg%3E") no-repeat center/cover;
      opacity: 0.8;
      z-index: 1;
    }
    .header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
      z-index: 2;
    }
    .back-btn {
      color: #fff;
      font-size: 1.2rem;
      text-decoration: none;
      padding: 5px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .page-title {
      font-size: 1.2rem;
      font-weight: 700;
    }

    /* ── MAIN CONTENT ── */
    .main-wrap {
      padding: 0 20px;
      margin-top: -30px;
      position: relative;
      z-index: 10;
    }

    /* ───── GALLERY ───── */
    #gallerySection { display: block; }
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
    }
    @media(min-width: 640px) {
      .gallery-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .tpl-card {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      cursor: pointer;
      box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
      transition: transform 0.2s, box-shadow 0.2s;
      border: 3px solid transparent;
      display: flex;
      flex-direction: column;
    }
    .tpl-card:hover {
      transform: scale(1.03);
    }
    .tpl-card.active {
      border-color: var(--primary);
    }
    .checkered {
      background-image: linear-gradient(45deg, #e2e8f0 25%, transparent 25%), 
                        linear-gradient(-45deg, #e2e8f0 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #e2e8f0 75%), 
                        linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
      background-size: 10px 10px;
      background-position: 0 0, 0 5px, 5px -5px, -5px 0px;
      background-color: #cbd5e1;
      width: 100%;
      aspect-ratio: 1/1;
      position: relative;
    }
    .checkered img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      position: absolute;
      top: 0;
      left: 0;
    }
    .card-body {
      padding: 12px;
    }
    .card-title {
      font-size: 0.85rem;
      font-weight: 700;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .card-desc {
      font-size: 0.7rem;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-top: 2px;
    }

    /* ───── EDITOR ───── */
    #editorSection { display: none; }
    .editor-container {
      background: var(--card);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
    }
    .editor-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }
    .editor-header h2 {
      font-size: 1.1rem;
      font-weight: 700;
    }
    .editor-header p {
      font-size: 0.7rem;
      color: var(--muted);
    }
    .btn-change {
      background: transparent;
      border: 1px solid var(--primary);
      color: var(--primary);
      padding: 4px 10px;
      border-radius: 10px;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
    }
    .canvas-wrap {
      position: relative;
      display: flex;
      justify-content: center;
      margin-bottom: 16px;
      touch-action: none;
    }
    #photoCanvas {
      display: block;
      border-radius: 12px;
      max-width: 100%;
      width: min(calc(100vw - 40px), 480px);
      height: min(calc(100vw - 40px), 480px);
      cursor: grab;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    #photoCanvas:active { cursor: grabbing; }

    .upload-btns {
      display: flex;
      gap: 10px;
      margin-bottom: 16px;
    }
    .upload-btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      background: var(--primary);
      color: #fff;
      padding: 10px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      text-align: center;
    }
    .upload-btn.purple { background: #8b5cf6; }

    /* Controls */
    .controls-panel {
      background: #f1f5f9;
      border-radius: 16px;
      padding: 16px;
      margin-bottom: 16px;
    }
    .zoom-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 16px;
    }
    .ctrl-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: #cbd5e1;
      color: #475569;
      font-size: 1rem;
      cursor: pointer;
      border: none;
      transition: background .15s;
    }
    .ctrl-btn:hover { background: #94a3b8; color: #fff; }
    
    input[type=range] {
      flex: 1;
      -webkit-appearance: none;
      appearance: none;
      height: 6px;
      border-radius: 3px;
      background: #cbd5e1;
      outline: none;
    }
    input[type=range]::-webkit-slider-thumb {
      -webkit-appearance: none;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--primary);
      cursor: pointer;
    }
    .pad-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 6px;
      justify-items: center;
    }

    .action-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: #10b981;
      color: #fff;
      font-weight: 700;
      padding: 12px;
      border-radius: 12px;
      border: none;
      font-size: 0.9rem;
      cursor: pointer;
      margin-bottom: 16px;
    }
    .action-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Share Section */
    .share-panel {
      background: #f1f5f9;
      border-radius: 16px;
      padding: 16px;
    }
    .share-title {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 12px;
    }
    .share-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .share-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 8px;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      color: #fff;
    }
    .share-btn.full { grid-column: span 2; background: #6366f1; }
    .share-btn.wa { background: #25d366; }
    .share-btn.ig { background: linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045); }
    .share-btn.fb { background: #1877f2; }
    .share-btn.tw { background: #1da1f2; }

    .link-row {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }
    .link-input {
      flex: 1;
      background: #fff;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.75rem;
      color: var(--text);
      outline: none;
    }

    /* ── BOTTOM NAV ── */
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
      flex: 1;
    }
    .bnav-item i { font-size: 1.3rem; }
    .bnav-label { font-size: 0.65rem; font-weight: 600; }

    #toast {
      position: fixed;
      bottom: calc(var(--bottom-h) + env(safe-area-inset-bottom) + 20px);
      left: 50%;
      transform: translateX(-50%);
      background: #1e293b;
      color: #fff;
      padding: 10px 20px;
      border-radius: 50px;
      font-size: 0.8rem;
      opacity: 0;
      transition: opacity 0.3s;
      z-index: 9999;
      pointer-events: none;
      white-space: nowrap;
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="app-header">
  <div class="header-top">
    <a href="siswa.php" class="back-btn"><i class="fas fa-arrow-left"></i> <span id="topGalleryTitle">Twibbon</span></a>
    <div id="topEditTitle" style="display:none;" class="page-title">Editor</div>
  </div>
</div>

<!-- Main Wrapper -->
<div class="main-wrap">

  <!-- GALLERY SECTION -->
  <section id="gallerySection">
    <?php if (empty($templates)): ?>
    <div style="background:var(--card); border-radius:var(--radius); padding:40px 20px; text-align:center; box-shadow:0 8px 30px rgba(0,0,0,0.06);">
      <i class="fas fa-images" style="font-size:4rem; color:var(--muted); margin-bottom:16px; opacity:0.5;"></i>
      <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:4px;">Belum ada template</h3>
      <p style="font-size:0.8rem; color:var(--muted);">Hubungi guru untuk menambahkan template twibbon.</p>
    </div>
    <?php else: ?>
    <div class="gallery-grid">
      <?php foreach ($templates as $t): ?>
      <div class="tpl-card <?= ($activeTpl && $activeTpl['id']==$t['id'] ? 'active' : '') ?>"
           data-id="<?= $t['id'] ?>"
           data-url="../../uploads/twibbon/<?= htmlspecialchars(rawurlencode($t['filename'])) ?>"
           data-title="<?= htmlspecialchars(addslashes($t['judul'])) ?>"
           data-share="<?= htmlspecialchars($baseUrl . '?id=' . $t['id']) ?>"
           onclick="openEditor(this)">
        <div class="checkered">
          <img src="../../uploads/twibbon/<?= htmlspecialchars(rawurlencode($t['filename'])) ?>" alt="<?= htmlspecialchars($t['judul']) ?>" loading="lazy">
        </div>
        <div class="card-body">
          <div class="card-title"><?= htmlspecialchars($t['judul']) ?></div>
          <?php if ($t['deskripsi']): ?><div class="card-desc"><?= htmlspecialchars($t['deskripsi']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- EDITOR SECTION -->
  <section id="editorSection">
    <div class="editor-container">
      <div class="editor-header">
        <div>
          <h2 id="editorTitle">Template</h2>
          <p>Drag foto untuk menggeser · Scroll untuk zoom</p>
        </div>
        <button onclick="backToGallery()" class="btn-change"><i class="fas fa-th-large"></i> Ganti</button>
      </div>

      <div class="canvas-wrap">
        <canvas id="photoCanvas" width="1080" height="1080"></canvas>
      </div>

      <div class="upload-btns">
        <label class="upload-btn">
          <i class="fas fa-image"></i> Pilih Foto
          <input type="file" id="photoInput" accept="image/*" style="display:none;">
        </label>
        <label class="upload-btn purple">
          <i class="fas fa-camera"></i> Ambil Foto
          <input type="file" id="cameraInput" accept="image/*" capture="user" style="display:none;">
        </label>
      </div>

      <div id="photoControls" class="controls-panel" style="display:none;">
        <div class="zoom-row">
          <button class="ctrl-btn" onclick="adjustZoom(-0.05)"><i class="fas fa-search-minus"></i></button>
          <input type="range" id="zoomRange" min="50" max="300" value="100" oninput="setZoom(this.value/100)">
          <button class="ctrl-btn" onclick="adjustZoom(0.05)"><i class="fas fa-search-plus"></i></button>
          <span id="zoomVal" style="font-size:0.75rem; color:var(--muted); font-weight:700; width:40px; text-align:right;">100%</span>
        </div>
        <div class="pad-grid">
          <div></div> <div></div>
          <button class="ctrl-btn" onclick="movePhoto(0,-15)"><i class="fas fa-arrow-up"></i></button>
          <div></div> <div></div>
          
          <button class="ctrl-btn" onclick="movePhoto(-15,0)"><i class="fas fa-arrow-left"></i></button>
          <button class="ctrl-btn" onclick="resetPhoto()"><i class="fas fa-expand-arrows-alt"></i></button>
          <div></div>
          <button class="ctrl-btn" onclick="movePhoto(15,0)"><i class="fas fa-arrow-right"></i></button>
          <div></div>
          
          <div></div> <div></div>
          <button class="ctrl-btn" onclick="movePhoto(0,15)"><i class="fas fa-arrow-down"></i></button>
          <div></div> <div></div>
        </div>
      </div>

      <button id="btnDownload" class="action-btn" disabled onclick="downloadResult()">
        <i class="fas fa-download"></i> Download Foto Twibbon
      </button>

      <div class="share-panel">
        <div class="share-title"><i class="fas fa-share-alt"></i> Bagikan</div>
        <div class="share-grid">
          <button onclick="shareNative()" class="share-btn full"><i class="fas fa-share-nodes"></i> Bagikan Foto Hasil</button>
          <button onclick="shareWhatsApp()" class="share-btn wa"><i class="fab fa-whatsapp"></i> WhatsApp</button>
          <button onclick="shareInstagram()" class="share-btn ig"><i class="fab fa-instagram"></i> Instagram</button>
          <button onclick="shareFacebook()" class="share-btn fb"><i class="fab fa-facebook-f"></i> Facebook</button>
          <button onclick="shareTwitter()" class="share-btn tw"><i class="fab fa-twitter"></i> Twitter</button>
        </div>
        <div class="link-row">
          <input id="shareLinkInput" type="text" readonly class="link-input">
          <button onclick="copyShareLink()" class="ctrl-btn" style="width:34px;height:34px;"><i class="fas fa-copy"></i></button>
        </div>
      </div>

    </div>
  </section>

</div>

<div id="toast"></div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
  <a href="siswa.php" class="bnav-item"><i class="fas fa-home"></i><span class="bnav-label">Beranda</span></a>
  <a href="presensi.php" class="bnav-item"><i class="fas fa-book-open"></i><span class="bnav-label">Studi</span></a>
  <a href="../../pengumuman.php" class="bnav-item"><i class="far fa-bell"></i><span class="bnav-label">Notifikasi</span></a>
  <a href="profil.php" class="bnav-item"><i class="far fa-user"></i><span class="bnav-label">Profil</span></a>
</nav>

<script>
const CANVAS_SIZE = 1080;
let canvas, ctx;
let frameImg   = null;
let userPhoto  = null;
let photoX     = 0, photoY = 0;
let photoScale = 1.0;
let dragging   = false, lastX = 0, lastY = 0;
let currentShareUrl  = '';
let currentTitle     = '';
let resultBlob       = null;

document.addEventListener('DOMContentLoaded', () => {
  canvas = document.getElementById('photoCanvas');
  ctx    = canvas.getContext('2d');
  drawPlaceholder();
  initDrag();
  initWheel();

  document.getElementById('photoInput').addEventListener('change', e => loadUserPhoto(e.target.files[0]));
  document.getElementById('cameraInput').addEventListener('change', e => loadUserPhoto(e.target.files[0]));

  const urlParams = new URLSearchParams(window.location.search);
  const paramId   = parseInt(urlParams.get('id')) || 0;
  if (paramId > 0) {
    const card = document.querySelector('.tpl-card[data-id="' + paramId + '"]');
    if (card) openEditor(card);
  }
});

function openEditor(card) {
  document.querySelectorAll('.tpl-card').forEach(c => c.classList.remove('active'));
  card.classList.add('active');
  const url = card.dataset.url;
  currentTitle = card.dataset.title;
  currentShareUrl = card.dataset.share;

  document.getElementById('editorTitle').textContent = currentTitle;
  document.getElementById('shareLinkInput').value = currentShareUrl;

  const id = card.dataset.id;
  window.history.replaceState({}, '', '?id=' + id);

  document.getElementById('gallerySection').style.display = 'none';
  document.getElementById('editorSection').style.display = 'block';
  document.getElementById('topGalleryTitle').style.display = 'none';
  document.getElementById('topEditTitle').style.display = 'block';

  frameImg = new Image();
  frameImg.crossOrigin = 'anonymous';
  frameImg.onload  = () => { redraw(); };
  frameImg.onerror = () => showToast('Gagal memuat template. Coba lagi.');
  frameImg.src = url;

  document.getElementById('btnDownload').disabled = true;
  resultBlob = null;
}

function backToGallery() {
  document.getElementById('gallerySection').style.display = 'block';
  document.getElementById('editorSection').style.display = 'none';
  document.getElementById('topGalleryTitle').style.display = 'inline';
  document.getElementById('topEditTitle').style.display = 'none';
  window.history.replaceState({}, '', 'twibbon.php');
}

function drawPlaceholder() {
  if (!ctx) return;
  ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
  ctx.fillStyle = '#e2e8f0';
  ctx.fillRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
  ctx.fillStyle = '#94a3b8';
  ctx.font = 'bold 48px system-ui';
  ctx.textAlign = 'center';
  ctx.fillText('Pilih Foto', CANVAS_SIZE/2, CANVAS_SIZE/2 - 20);
  ctx.font = '32px system-ui';
  ctx.fillText('Tekan tombol "Pilih Foto"', CANVAS_SIZE/2, CANVAS_SIZE/2 + 40);
}

function redraw() {
  ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
  ctx.fillStyle = '#e2e8f0';
  ctx.fillRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);

  if (userPhoto) {
    ctx.save();
    const pw = userPhoto.naturalWidth  * photoScale;
    const ph = userPhoto.naturalHeight * photoScale;
    ctx.drawImage(userPhoto, photoX - pw/2, photoY - ph/2, pw, ph);
    ctx.restore();
  }

  if (frameImg && frameImg.complete && frameImg.naturalWidth > 0) {
    ctx.drawImage(frameImg, 0, 0, CANVAS_SIZE, CANVAS_SIZE);
  }

  if (userPhoto && frameImg) {
    document.getElementById('btnDownload').disabled = false;
  }
}

function loadUserPhoto(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = new Image();
    img.onload = () => {
      userPhoto = img;
      const rw = CANVAS_SIZE / img.naturalWidth;
      const rh = CANVAS_SIZE / img.naturalHeight;
      photoScale = Math.max(rw, rh);
      photoX = CANVAS_SIZE / 2;
      photoY = CANVAS_SIZE / 2;
      document.getElementById('zoomRange').value = 100;
      document.getElementById('zoomVal').textContent = '100%';
      document.getElementById('photoControls').style.display = 'block';
      redraw();
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function initDrag() {
  const c = canvas;
  c.addEventListener('mousedown', e => { dragging=true; lastX=e.clientX; lastY=e.clientY; });
  window.addEventListener('mousemove', e => {
    if (!dragging || !userPhoto) return;
    const rect = c.getBoundingClientRect();
    const scale = CANVAS_SIZE / rect.width;
    photoX += (e.clientX - lastX) * scale;
    photoY += (e.clientY - lastY) * scale;
    lastX = e.clientX; lastY = e.clientY;
    redraw();
  });
  window.addEventListener('mouseup', () => dragging = false);

  let prevDist = null;
  c.addEventListener('touchstart', e => {
    if (e.touches.length === 1) {
      dragging = true;
      lastX = e.touches[0].clientX; lastY = e.touches[0].clientY;
    }
    prevDist = null;
    e.preventDefault();
  }, {passive:false});

  c.addEventListener('touchmove', e => {
    e.preventDefault();
    if (e.touches.length === 2) {
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      const dist = Math.sqrt(dx*dx + dy*dy);
      if (prevDist !== null) {
        const delta = (dist - prevDist) / 200;
        setZoom(photoScale + delta, false);
      }
      prevDist = dist;
      dragging = false;
    } else if (e.touches.length === 1 && dragging && userPhoto) {
      const rect = canvas.getBoundingClientRect();
      const scale = CANVAS_SIZE / rect.width;
      photoX += (e.touches[0].clientX - lastX) * scale;
      photoY += (e.touches[0].clientY - lastY) * scale;
      lastX = e.touches[0].clientX; lastY = e.touches[0].clientY;
      redraw();
    }
  }, {passive:false});

  c.addEventListener('touchend', () => { dragging=false; prevDist=null; });
}

function initWheel() {
  canvas.addEventListener('wheel', e => {
    e.preventDefault();
    const delta = e.deltaY < 0 ? 0.05 : -0.05;
    adjustZoom(delta);
  }, {passive:false});
}

function adjustZoom(delta) {
  if (!userPhoto) return;
  setZoom(photoScale + delta);
}

function setZoom(val, updateSlider = true) {
  photoScale = Math.min(Math.max(val, 0.1), 5);
  redraw();
  if (updateSlider) {
    document.getElementById('zoomRange').value = Math.round(photoScale * 100);
    document.getElementById('zoomVal').textContent = Math.round(photoScale*100) + '%';
  }
}

function movePhoto(dx, dy) {
  if (!userPhoto) return;
  photoX += dx; photoY += dy;
  redraw();
}

function resetPhoto() {
  if (!userPhoto) return;
  const rw = CANVAS_SIZE / userPhoto.naturalWidth;
  const rh = CANVAS_SIZE / userPhoto.naturalHeight;
  photoScale = Math.max(rw, rh);
  photoX = CANVAS_SIZE / 2;
  photoY = CANVAS_SIZE / 2;
  document.getElementById('zoomRange').value = 100;
  document.getElementById('zoomVal').textContent = '100%';
  redraw();
}

function downloadResult() {
  canvas.toBlob(blob => {
    resultBlob = blob;
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'twibbon_' + currentTitle.replace(/[^a-zA-Z0-9]/g,'_') + '.png';
    a.click();
    URL.revokeObjectURL(url);
    showToast('Foto berhasil didownload!');
  }, 'image/png');
}

function shareNative() {
  canvas.toBlob(async blob => {
    const file = new File([blob], 'twibbon.png', {type:'image/png'});
    if (navigator.share && navigator.canShare && navigator.canShare({files:[file]})) {
      try {
        await navigator.share({
          title: 'Twibbon – ' + currentTitle,
          text: 'Lihat twibbon saya! 🎉',
          files: [file]
        });
      } catch(e) {
        if (e.name !== 'AbortError') shareByLink();
      }
    } else {
      downloadResult();
      showToast('Download foto lalu bagikan ke medsos!');
    }
  }, 'image/png');
}

function shareWhatsApp() {
  const text = encodeURIComponent('🎉 ' + currentTitle + ' — Buat twibbon kamu juga: ' + currentShareUrl);
  window.open('https://wa.me/?text=' + text, '_blank');
}

function shareInstagram() {
  downloadResult();
  showToast('Download foto → buka Instagram → bagikan!');
}

function shareFacebook() {
  window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentShareUrl), '_blank');
}

function shareTwitter() {
  const text = encodeURIComponent('🎉 ' + currentTitle + ' ' + currentShareUrl);
  window.open('https://twitter.com/intent/tweet?text=' + text, '_blank');
}

function copyShareLink() {
  const val = document.getElementById('shareLinkInput').value;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(val).then(() => showToast('Link disalin!'));
  } else {
    const el = document.createElement('textarea');
    el.value = val; document.body.appendChild(el); el.select();
    document.execCommand('copy'); document.body.removeChild(el);
    showToast('Link disalin!');
  }
}

let toastTimer = null;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.opacity = '1';
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.style.opacity = '0', 2500);
}
</script>
</body>
</html>
