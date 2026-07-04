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
$baseUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'s':'') . '://' . $_SERVER['HTTP_HOST'] . '/jurnal/pages/siswa/twibbon.php';
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

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *{box-sizing:border-box;}
    body{background:#0f172a;font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;overflow-x:hidden;}
    /* ───── GALLERY ───── */
    #gallerySection{background:#0f172a;min-height:100vh;}
    .tpl-card{cursor:pointer;transition:transform .2s,box-shadow .2s;border:3px solid transparent;}
    .tpl-card:hover{transform:scale(1.03);box-shadow:0 0 0 3px #f9a8d4;}
    .tpl-card.active{border-color:#ec4899;box-shadow:0 0 0 3px #ec4899;}
    .checkered{background-image:linear-gradient(45deg,#555 25%,transparent 25%),linear-gradient(-45deg,#555 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#555 75%),linear-gradient(-45deg,transparent 75%,#555 75%);background-size:10px 10px;background-position:0 0,0 5px,5px -5px,-5px 0px;background-color:#444;}
    /* ───── EDITOR ───── */
    #editorSection{display:none;background:#0f172a;min-height:100vh;}
    #canvasWrap{position:relative;display:inline-block;touch-action:none;}
    #photoCanvas{display:block;border-radius:12px;max-width:100%;cursor:grab;}
    #photoCanvas:active{cursor:grabbing;}
    /* Controls */
    .ctrl-btn{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:10px;background:#1e293b;color:#e2e8f0;font-size:1.1rem;cursor:pointer;transition:background .15s;border:none;}
    .ctrl-btn:hover{background:#334155;}
    /* Share buttons */
    .share-btn{display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;transition:filter .15s;}
    .share-btn:hover{filter:brightness(1.1);}
    /* Range slider */
    input[type=range]{-webkit-appearance:none;appearance:none;height:6px;border-radius:3px;background:#334155;outline:none;}
    input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;width:18px;height:18px;border-radius:50%;background:#ec4899;cursor:pointer;}
    /* Toast */
    #toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:50px;font-size:.8rem;opacity:0;transition:opacity .3s;z-index:9999;pointer-events:none;white-space:nowrap;}
  </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     TOP BAR
══════════════════════════════════════════ -->
<div class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-4 py-3 bg-black/60 backdrop-blur-sm">
  <a href="siswa.php" class="text-white/70 hover:text-white text-sm flex items-center gap-1 transition">
    <i class="fas fa-arrow-left"></i> <span class="hidden sm:inline">Kembali</span>
  </a>
  <h1 class="text-white font-bold text-sm sm:text-base tracking-wide flex items-center gap-2">
    <i class="fas fa-camera-retro text-pink-400"></i> Twibbon Creator
  </h1>
  <div id="topEdit" class="hidden">
    <button onclick="backToGallery()" class="text-white/70 hover:text-white text-sm flex items-center gap-1 transition">
      <i class="fas fa-th-large"></i> <span class="hidden sm:inline">Galeri</span>
    </button>
  </div>
  <div id="topGallery">
    <span class="text-white/50 text-xs"><?= htmlspecialchars($namaSiswa) ?></span>
  </div>
</div>

<!-- ══════════════════════════════════════════
     GALLERY SECTION
══════════════════════════════════════════ -->
<section id="gallerySection" class="pt-16 pb-10 px-4">
  <div class="max-w-2xl mx-auto">

    <div class="text-center py-8">
      <h2 class="text-white text-2xl font-bold mb-1">Pilih Template Twibbon</h2>
      <p class="text-white/50 text-sm">Klik template untuk mulai membuat foto</p>
    </div>

    <?php if (empty($templates)): ?>
    <div class="text-center py-16">
      <i class="fas fa-images text-white/20 text-6xl mb-4 block"></i>
      <p class="text-white/50">Belum ada template tersedia.</p>
      <p class="text-white/30 text-sm mt-1">Hubungi guru untuk menambahkan template.</p>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
      <?php foreach ($templates as $t): ?>
      <div class="tpl-card rounded-2xl overflow-hidden <?= ($activeTpl && $activeTpl['id']==$t['id'] ? 'active' : '') ?>"
           data-id="<?= $t['id'] ?>"
           data-url="../../uploads/twibbon/<?= htmlspecialchars(rawurlencode($t['filename'])) ?>"
           data-title="<?= htmlspecialchars(addslashes($t['judul'])) ?>"
           data-share="<?= htmlspecialchars($baseUrl . '?id=' . $t['id']) ?>"
           onclick="openEditor(this)">
        <div class="checkered aspect-square">
          <img src="../../uploads/twibbon/<?= htmlspecialchars(rawurlencode($t['filename'])) ?>"
               alt="<?= htmlspecialchars($t['judul']) ?>"
               class="w-full h-full object-contain" loading="lazy">
        </div>
        <div class="bg-gray-900/80 p-2.5">
          <p class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($t['judul']) ?></p>
          <?php if ($t['deskripsi']): ?>
          <p class="text-white/40 text-xs truncate mt-0.5"><?= htmlspecialchars($t['deskripsi']) ?></p>
          <?php endif; ?>
          <p class="text-white/30 text-xs mt-1"><?= htmlspecialchars($t['nama_pembuat'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</section>

<!-- ══════════════════════════════════════════
     EDITOR SECTION
══════════════════════════════════════════ -->
<section id="editorSection" class="pt-16 pb-10 px-4">
  <div class="max-w-lg mx-auto">

    <!-- Title -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-white font-bold text-lg" id="editorTitle">Template</h2>
        <p class="text-white/40 text-xs">Drag foto untuk menggeser · Scroll untuk zoom</p>
      </div>
      <button onclick="backToGallery()" class="text-white/50 hover:text-white text-sm transition flex items-center gap-1">
        <i class="fas fa-th-large"></i> Ganti
      </button>
    </div>

    <!-- Canvas -->
    <div class="flex justify-center mb-4">
      <div id="canvasWrap">
        <canvas id="photoCanvas" width="1080" height="1080"
                style="width:min(calc(100vw - 2rem), 480px); height:min(calc(100vw - 2rem), 480px); border-radius:12px;">
        </canvas>
      </div>
    </div>

    <!-- Upload photo -->
    <div class="flex gap-3 mb-4">
      <label for="photoInput"
             class="flex-1 flex items-center justify-center gap-2 bg-pink-600 hover:bg-pink-700 text-white font-semibold py-3 rounded-xl cursor-pointer transition text-sm">
        <i class="fas fa-image"></i> Pilih Foto
        <input type="file" id="photoInput" accept="image/*" class="hidden">
      </label>
      <label for="cameraInput"
             class="flex-1 flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-semibold py-3 rounded-xl cursor-pointer transition text-sm">
        <i class="fas fa-camera"></i> Ambil Foto
        <input type="file" id="cameraInput" accept="image/*" capture="user" class="hidden">
      </label>
    </div>

    <!-- Zoom & Position controls -->
    <div id="photoControls" class="hidden bg-slate-800 rounded-2xl p-4 mb-4 space-y-4">
      <div class="flex items-center gap-3">
        <button class="ctrl-btn" onclick="adjustZoom(-0.05)"><i class="fas fa-search-minus"></i></button>
        <input type="range" id="zoomRange" min="50" max="300" value="100" class="flex-1" oninput="setZoom(this.value/100)">
        <button class="ctrl-btn" onclick="adjustZoom(0.05)"><i class="fas fa-search-plus"></i></button>
        <span id="zoomVal" class="text-white/70 text-xs w-8 text-right">100%</span>
      </div>

      <div class="grid grid-cols-5 gap-2">
        <div></div>
        <div></div>
        <button class="ctrl-btn mx-auto" onclick="movePhoto(0,-15)"><i class="fas fa-arrow-up"></i></button>
        <div></div>
        <div></div>

        <button class="ctrl-btn mx-auto" onclick="movePhoto(-15,0)"><i class="fas fa-arrow-left"></i></button>
        <button class="ctrl-btn mx-auto" onclick="resetPhoto()"><i class="fas fa-expand-arrows-alt"></i></button>
        <div></div>
        <button class="ctrl-btn mx-auto" onclick="movePhoto(15,0)"><i class="fas fa-arrow-right"></i></button>
        <div></div>

        <div></div>
        <div></div>
        <button class="ctrl-btn mx-auto" onclick="movePhoto(0,15)"><i class="fas fa-arrow-down"></i></button>
        <div></div>
        <div></div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="space-y-3">
      <!-- Download -->
      <button id="btnDownload"
              class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              disabled onclick="downloadResult()">
        <i class="fas fa-download"></i> Download Foto Twibbon
      </button>

      <!-- Share -->
      <div class="bg-slate-800 rounded-2xl p-4">
        <p class="text-white/60 text-xs font-semibold mb-3 uppercase tracking-wide"><i class="fas fa-share-alt mr-1"></i>Bagikan</p>
        <div class="grid grid-cols-2 gap-2">
          <!-- Share Result image (downloaded) -->
          <button onclick="shareNative()" id="btnShareNative"
                  class="share-btn text-white justify-center col-span-2" style="background:#6366f1;">
            <i class="fas fa-share-nodes"></i> Bagikan Foto Hasil
          </button>
          <!-- Share link per platform -->
          <button onclick="shareWhatsApp()" class="share-btn text-white" style="background:#25d366;">
            <i class="fab fa-whatsapp"></i> WhatsApp
          </button>
          <button onclick="shareInstagram()" class="share-btn text-white" style="background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045);">
            <i class="fab fa-instagram"></i> Instagram
          </button>
          <button onclick="shareFacebook()" class="share-btn text-white" style="background:#1877f2;">
            <i class="fab fa-facebook"></i> Facebook
          </button>
          <button onclick="shareTwitter()" class="share-btn text-white" style="background:#1da1f2;">
            <i class="fab fa-twitter"></i> X / Twitter
          </button>
        </div>

        <!-- Copy link -->
        <div class="flex gap-2 mt-3">
          <input id="shareLinkInput" type="text" readonly
                 class="flex-1 bg-slate-700 text-white/70 text-xs px-3 py-2 rounded-lg outline-none border border-slate-600 truncate">
          <button onclick="copyShareLink()" class="ctrl-btn flex-shrink-0" title="Salin link">
            <i class="fas fa-copy text-sm"></i>
          </button>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Toast -->
<div id="toast"></div>

<script>
// ═══════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════
const CANVAS_SIZE = 1080;
let canvas, ctx;
let frameImg   = null;  // the twibbon frame
let userPhoto  = null;  // the user's photo
let photoX     = 0, photoY = 0; // center of photo on canvas
let photoScale = 1.0;
let dragging   = false, lastX = 0, lastY = 0;
let currentShareUrl  = '';
let currentTitle     = '';
let resultBlob       = null;

// ═══════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  canvas = document.getElementById('photoCanvas');
  ctx    = canvas.getContext('2d');

  drawPlaceholder();
  initDrag();
  initWheel();

  // Photo file inputs
  document.getElementById('photoInput').addEventListener('change', e => loadUserPhoto(e.target.files[0]));
  document.getElementById('cameraInput').addEventListener('change', e => loadUserPhoto(e.target.files[0]));

  // Auto-open from URL param
  const urlParams = new URLSearchParams(window.location.search);
  const paramId   = parseInt(urlParams.get('id')) || 0;
  if (paramId > 0) {
    const card = document.querySelector('.tpl-card[data-id="' + paramId + '"]');
    if (card) openEditor(card);
  }
});

// ═══════════════════════════════════════════════
// GALLERY ↔ EDITOR NAVIGATION
// ═══════════════════════════════════════════════
function openEditor(card) {
  // Highlight
  document.querySelectorAll('.tpl-card').forEach(c => c.classList.remove('active'));
  card.classList.add('active');

  const url      = card.dataset.url;
  currentTitle   = card.dataset.title;
  currentShareUrl = card.dataset.share;

  document.getElementById('editorTitle').textContent = currentTitle;
  document.getElementById('shareLinkInput').value    = currentShareUrl;

  // Update browser URL (no reload)
  const id = card.dataset.id;
  window.history.replaceState({}, '', '?id=' + id);

  // Show editor
  document.getElementById('gallerySection').style.display = 'none';
  document.getElementById('editorSection').style.display  = 'block';
  document.getElementById('topEdit').classList.remove('hidden');
  document.getElementById('topGallery').classList.add('hidden');

  // Load frame
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
  document.getElementById('editorSection').style.display  = 'none';
  document.getElementById('topEdit').classList.add('hidden');
  document.getElementById('topGallery').classList.remove('hidden');
  window.history.replaceState({}, '', 'twibbon.php');
}

// ═══════════════════════════════════════════════
// CANVAS DRAWING
// ═══════════════════════════════════════════════
function drawPlaceholder() {
  if (!ctx) return;
  ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
  ctx.fillStyle = '#1e293b';
  ctx.fillRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
  ctx.fillStyle = '#334155';
  ctx.font = 'bold 48px system-ui';
  ctx.textAlign = 'center';
  ctx.fillText('Pilih Foto', CANVAS_SIZE/2, CANVAS_SIZE/2 - 20);
  ctx.font = '32px system-ui';
  ctx.fillStyle = '#475569';
  ctx.fillText('Tekan tombol "Pilih Foto"', CANVAS_SIZE/2, CANVAS_SIZE/2 + 40);
}

function redraw() {
  ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
  ctx.fillStyle = '#1e293b';
  ctx.fillRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);

  // Draw user photo behind frame
  if (userPhoto) {
    ctx.save();
    const pw = userPhoto.naturalWidth  * photoScale;
    const ph = userPhoto.naturalHeight * photoScale;
    ctx.drawImage(userPhoto, photoX - pw/2, photoY - ph/2, pw, ph);
    ctx.restore();
  }

  // Draw frame on top
  if (frameImg && frameImg.complete && frameImg.naturalWidth > 0) {
    ctx.drawImage(frameImg, 0, 0, CANVAS_SIZE, CANVAS_SIZE);
  }

  // If both loaded, enable download
  if (userPhoto && frameImg) {
    document.getElementById('btnDownload').disabled = false;
  }
}

// ═══════════════════════════════════════════════
// LOAD USER PHOTO
// ═══════════════════════════════════════════════
function loadUserPhoto(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = new Image();
    img.onload = () => {
      userPhoto  = img;
      // Fit photo to canvas initially (cover)
      const rw = CANVAS_SIZE / img.naturalWidth;
      const rh = CANVAS_SIZE / img.naturalHeight;
      photoScale = Math.max(rw, rh);
      photoX     = CANVAS_SIZE / 2;
      photoY     = CANVAS_SIZE / 2;
      document.getElementById('zoomRange').value = 100;
      document.getElementById('zoomVal').textContent = '100%';
      document.getElementById('photoControls').classList.remove('hidden');
      redraw();
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

// ═══════════════════════════════════════════════
// DRAG / TOUCH / PINCH
// ═══════════════════════════════════════════════
function initDrag() {
  const c = canvas;
  // Mouse
  c.addEventListener('mousedown', e => { dragging=true; lastX=e.clientX; lastY=e.clientY; });
  window.addEventListener('mousemove', e => {
    if (!dragging || !userPhoto) return;
    const rect  = c.getBoundingClientRect();
    const scale = CANVAS_SIZE / rect.width;
    photoX += (e.clientX - lastX) * scale;
    photoY += (e.clientY - lastY) * scale;
    lastX = e.clientX; lastY = e.clientY;
    redraw();
  });
  window.addEventListener('mouseup', () => dragging = false);

  // Touch (single & pinch)
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
      // Pinch zoom
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
      const rect  = canvas.getBoundingClientRect();
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

// ═══════════════════════════════════════════════
// ZOOM & MOVE
// ═══════════════════════════════════════════════
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

// ═══════════════════════════════════════════════
// DOWNLOAD
// ═══════════════════════════════════════════════
function downloadResult() {
  canvas.toBlob(blob => {
    resultBlob = blob;
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'twibbon_' + currentTitle.replace(/[^a-zA-Z0-9]/g,'_') + '.png';
    a.click();
    URL.revokeObjectURL(url);
    showToast('Foto berhasil didownload!');
  }, 'image/png');
}

// ═══════════════════════════════════════════════
// SHARE
// ═══════════════════════════════════════════════
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
      // Fallback: just download + show toast
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
  // Instagram doesn't support direct URL share; download first
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

// ═══════════════════════════════════════════════
// TOAST
// ═══════════════════════════════════════════════
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
