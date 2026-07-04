<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Auth check — allow siswa (3), guru (2), admin (1)
if (!isset($_SESSION['no_induk'])) {
  header('Location: index.php?haruslogin');
  exit;
}

require_once __DIR__ . '/koneksi.php';
if (!function_exists('data_lembaga')) {
  require_once __DIR__ . '/functions.php';
}

date_default_timezone_set('Asia/Jakarta');
$today    = date('Y-m-d');
$hakAkses = (int)($_SESSION['hak_akses'] ?? 0);
$noInduk  = $_SESSION['no_induk'] ?? '';
$kelas    = $_SESSION['kelas']    ?? '';
$namaUser = $_SESSION['nama']     ?? $noInduk;
$lembaga  = function_exists('data_lembaga') ? data_lembaga() : [];

// Back-link per role
if ($hakAkses === 3)      $backLink = 'pages/siswa/siswa.php';
elseif ($hakAkses === 2)  $backLink = 'pages/guru/guru.php';
else                      $backLink = 'home.php';

// Extract tingkat from kelas (e.g. "XI IPA 1" → "XI")
$tingkat = strtok(trim($kelas), ' ');

// Ensure table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  penting TINYINT(1) DEFAULT 0,
  mulai DATE NOT NULL,
  selesai DATE NOT NULL,
  target_scope ENUM('SEMUA','KELAS','TINGKAT','GURU') DEFAULT 'SEMUA',
  target_value VARCHAR(100) DEFAULT NULL,
  lampiran VARCHAR(255) DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4");

$noIndukEsc = mysqli_real_escape_string($conn, $noInduk);
$kelasEsc   = mysqli_real_escape_string($conn, $kelas);
$tingkatEsc = mysqli_real_escape_string($conn, $tingkat);

if ($hakAkses === 1) {
  // Admin: sees all active
  $whereScope = "1=1";
} elseif ($hakAkses === 2) {
  // Guru: SEMUA + GURU scope
  $whereScope = "(p.target_scope = 'SEMUA' OR p.target_scope = 'GURU')";
} else {
  // Siswa: SEMUA + matching KELAS + matching TINGKAT
  $whereScope = "(p.target_scope = 'SEMUA'
                    OR (p.target_scope = 'KELAS'   AND p.target_value = '$kelasEsc')
                    OR (p.target_scope = 'TINGKAT' AND p.target_value = '$tingkatEsc'))";
}

$sql = "SELECT p.*, COALESCE(g.nama_guru, p.created_by) AS nama_pengirim
        FROM tbl_pengumuman p
        LEFT JOIN tbl_guru g ON g.no_induk = p.created_by
        WHERE p.mulai <= '$today' AND p.selesai >= '$today'
          AND $whereScope
        ORDER BY p.penting DESC, p.created_at DESC";

$result = mysqli_query($conn, $sql);
$pengumumanList = [];
if ($result) while ($row = mysqli_fetch_assoc($result)) $pengumumanList[] = $row;

$totalPenting = 0;
foreach ($pengumumanList as $p) if ($p['penting']) $totalPenting++;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengumuman – <?= htmlspecialchars($lembaga['nama_sekolah'] ?? 'Jurnal Digital') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: #f1f5f9;
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    }

    .card-ann {
      transition: transform .15s, box-shadow .15s;
    }

    .card-ann:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px -6px rgba(0, 0, 0, .13);
    }

    .prose-isi {
      white-space: pre-line;
      line-height: 1.7;
    }

    details>summary {
      list-style: none;
    }

    details>summary::-webkit-details-marker {
      display: none;
    }

    details[open] .chevron {
      transform: rotate(180deg);
    }

    .chevron {
      transition: transform .2s;
    }
  </style>
</head>

<body class="min-h-screen pb-10">

  <!-- Header -->
  <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 pt-10 pb-16">
    <div class="max-w-2xl mx-auto">
      <div class="flex items-center justify-between mb-2">
        <a href="<?= $backLink ?>" class="flex items-center gap-1 text-blue-100 hover:text-white text-sm transition">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <?php if ($hakAkses <= 2): ?>
          <a href="<?= $hakAkses === 2 ? 'pengumuman-guru.php' : 'home.php?page=pengumuman' ?>"
            class="flex items-center gap-1 bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-full transition">
            <i class="fas fa-edit"></i> Kelola Pengumuman
          </a>
        <?php endif; ?>
      </div>
      <h1 class="text-2xl font-bold mt-4"><i class="fas fa-bullhorn mr-2"></i>Pengumuman</h1>
      <p class="text-blue-200 text-sm mt-1">
        <?= htmlspecialchars($namaUser) ?>
        <?php if ($kelas): ?> &middot; Kelas <?= htmlspecialchars($kelas) ?><?php endif; ?>
          &middot; <?= date('d F Y') ?>
      </p>
    </div>
  </div>

  <!-- Stats bar -->
  <div class="max-w-2xl mx-auto px-4 -mt-8 mb-4">
    <div class="bg-white rounded-2xl shadow-md p-4 flex gap-4 flex-wrap items-center">
      <div class="flex items-center gap-2">
        <span class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm"><?= count($pengumumanList) ?></span>
        <span class="text-sm text-gray-600">Pengumuman Aktif</span>
      </div>
      <?php if ($totalPenting > 0): ?>
        <div class="flex items-center gap-2">
          <span class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-sm"><?= $totalPenting ?></span>
          <span class="text-sm text-gray-600">Penting</span>
        </div>
      <?php endif; ?>
      <div class="ml-auto text-xs text-gray-400">
        Hari ini: <?= date('d M Y') ?>
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="max-w-2xl mx-auto px-4 space-y-3">

    <?php if (empty($pengumumanList)): ?>
      <div class="bg-white rounded-2xl shadow p-10 text-center">
        <i class="fas fa-inbox text-gray-300 text-5xl mb-4 block"></i>
        <p class="text-gray-500 font-medium">Tidak ada pengumuman aktif saat ini.</p>
        <p class="text-gray-400 text-sm mt-1">Pengumuman akan muncul sesuai jadwal yang ditetapkan.</p>
      </div>
    <?php else: ?>
      <?php foreach ($pengumumanList as $p):
        $isPenting = (bool)$p['penting'];
        $hasLampiran = !empty($p['lampiran']);
        $namaPengirim = !empty($p['nama_pengirim']) ? $p['nama_pengirim'] : 'Administrator';
        $scopeVal = isset($p['target_scope']) ? $p['target_scope'] : 'SEMUA';
        if ($scopeVal === 'KELAS') {
          $scopeLabel = 'Kelas ' . (isset($p['target_value']) ? $p['target_value'] : '');
        } elseif ($scopeVal === 'TINGKAT') {
          $scopeLabel = 'Tingkat ' . (isset($p['target_value']) ? $p['target_value'] : '');
        } elseif ($scopeVal === 'GURU') {
          $scopeLabel = 'Khusus Guru';
        } else {
          $scopeLabel = 'Semua';
        }
      ?>
        <details class="bg-white rounded-2xl shadow card-ann overflow-hidden" <?= $isPenting ? 'open' : '' ?>>
          <summary class="flex items-start gap-3 p-4 cursor-pointer select-none">
            <div class="flex-shrink-0 mt-0.5">
              <?php if ($isPenting): ?>
                <span class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-500 text-lg">
                  <i class="fas fa-exclamation-circle"></i>
                </span>
              <?php else: ?>
                <span class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-400 text-lg">
                  <i class="fas fa-bullhorn"></i>
                </span>
              <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 flex-wrap">
                <h2 class="font-semibold text-gray-800 text-base leading-snug">
                  <?php if ($isPenting): ?>
                    <span class="inline-block text-xs font-bold text-white bg-red-500 rounded px-1.5 py-0.5 mr-1 align-middle">PENTING</span>
                  <?php endif; ?>
                  <?= htmlspecialchars($p['judul']) ?>
                </h2>
                <i class="fas fa-chevron-down chevron text-gray-400 text-sm flex-shrink-0 mt-1"></i>
              </div>
              <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1.5 text-xs text-gray-500">
                <span><i class="fas fa-user mr-1 text-gray-400"></i><?= htmlspecialchars($namaPengirim) ?></span>
                <span><i class="fas fa-calendar mr-1 text-gray-400"></i>
                  <?= date('d M Y', strtotime($p['mulai'])) ?>
                  <?= $p['mulai'] !== $p['selesai'] ? ' – ' . date('d M Y', strtotime($p['selesai'])) : '' ?>
                </span>
                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded px-1.5 py-0.5">
                  <i class="fas fa-users text-xs"></i> <?= htmlspecialchars($scopeLabel) ?>
                </span>
              </div>
            </div>
          </summary>
          <div class="px-4 pb-4 border-t border-gray-100 pt-3">
            <div class="prose-isi text-gray-700 text-sm"><?= nl2br(htmlspecialchars($p['isi'])) ?></div>
            <?php if ($hasLampiran): ?>
              <a href="materi/<?= htmlspecialchars($p['lampiran']) ?>" target="_blank"
                class="inline-flex items-center gap-2 mt-3 bg-red-50 hover:bg-red-100 text-red-600 font-medium text-sm px-3 py-2 rounded-lg transition">
                <i class="fas fa-file-pdf"></i> Unduh Lampiran PDF
              </a>
            <?php endif; ?>
            <div class="mt-3 text-xs text-gray-400">
              Diposting: <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
            </div>
          </div>
        </details>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

</body>

</html>