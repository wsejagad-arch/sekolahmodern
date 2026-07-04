<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk'])) {
    header('Location: ../../index.php?haruslogin');
    exit;
}
if ($_SESSION['hak_akses'] != 3) {
    header('Location: ../../403.php');
    exit;
}

require_once '../../koneksi.php';
require_once '../../functions.php';

date_default_timezone_set('Asia/Jakarta');

$nisSiswa  = $_SESSION['no_induk'];
$kelas     = $_SESSION['kelas'] ?? '';
$namaSiswa = $_SESSION['nama']  ?? $nisSiswa;
$lembaga   = function_exists('data_lembaga') ? data_lembaga() : [];

// Pastikan tabel ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pelanggaran_siswa (
  id_pelanggaran int(11) NOT NULL AUTO_INCREMENT,
  no_induk varchar(25) NOT NULL,
  nama_siswa varchar(150) NOT NULL,
  kelas varchar(50) NOT NULL,
  tanggal_pelanggaran date NOT NULL,
  kategori_pelanggaran enum('Berat','Sedang','Ringan') NOT NULL,
  jenis_pelanggaran varchar(100) NOT NULL,
  deskripsi_pelanggaran text,
  tindakan_yang_diambil text,
  no_induk_guru varchar(25) NOT NULL,
  nama_guru varchar(150) NOT NULL,
  status_pelanggaran enum('Aktif','Diselesaikan','Follow Up') DEFAULT 'Aktif',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pelanggaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$nisEsc = mysqli_real_escape_string($conn, $nisSiswa);

// Filter tahun (default: tahun ajaran ini — Juli s/d Juni)
$bulanNow = (int)date('m');
$tahunNow = (int)date('Y');
$tahunAwal = ($bulanNow >= 7) ? $tahunNow : $tahunNow - 1;
$filterTahun = $_GET['tahun'] ?? "$tahunAwal";
$filterTahunEsc = mysqli_real_escape_string($conn, $filterTahun);

// Ambil data pelanggaran siswa
$sql = "SELECT * FROM tbl_pelanggaran_siswa
        WHERE no_induk = '$nisEsc'
          AND YEAR(tanggal_pelanggaran) = '$filterTahunEsc'
        ORDER BY tanggal_pelanggaran DESC";
$result = mysqli_query($conn, $sql);
$pelanggaranList = [];
if ($result) while ($r = mysqli_fetch_assoc($result)) $pelanggaranList[] = $r;

// Statistik
$totalBerat  = 0; $totalSedang = 0; $totalRingan = 0;
$totalAktif  = 0; $totalSelesai = 0;
foreach ($pelanggaranList as $p) {
    if ($p['kategori_pelanggaran'] === 'Berat')  $totalBerat++;
    elseif ($p['kategori_pelanggaran'] === 'Sedang') $totalSedang++;
    else $totalRingan++;
    if ($p['status_pelanggaran'] === 'Aktif' || $p['status_pelanggaran'] === 'Follow Up')
        $totalAktif++;
    else $totalSelesai++;
}
$totalAll = count($pelanggaranList);

// Pilihan tahun tersedia
$tahunList = [];
$qTh = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_pelanggaran) AS th FROM tbl_pelanggaran_siswa WHERE no_induk='$nisEsc' ORDER BY th DESC");
if ($qTh) while ($r = mysqli_fetch_assoc($qTh)) $tahunList[] = $r['th'];
if (empty($tahunList)) $tahunList = [$tahunAwal];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pelanggaran – <?= htmlspecialchars($namaSiswa) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background:#f1f5f9; font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif; }
    .card-item { transition: transform .15s, box-shadow .15s; }
    .card-item:hover { transform: translateY(-1px); box-shadow: 0 8px 24px -6px rgba(0,0,0,.12); }
  </style>
</head>
<body class="min-h-screen pb-12">

  <!-- Header gradient -->
  <div class="bg-gradient-to-r from-red-600 to-rose-500 text-white px-4 pt-10 pb-16">
    <div class="max-w-2xl mx-auto">
      <a href="siswa.php" class="flex items-center gap-1 text-red-100 hover:text-white text-sm mb-4 transition">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <h1 class="text-2xl font-bold"><i class="fas fa-exclamation-triangle mr-2"></i>Riwayat Pelanggaran</h1>
      <p class="text-red-200 text-sm mt-1"><?= htmlspecialchars($namaSiswa) ?> &middot; Kelas <?= htmlspecialchars($kelas) ?></p>
    </div>
  </div>

  <!-- Stats + filter -->
  <div class="max-w-2xl mx-auto px-4 -mt-8 mb-4">
    <div class="bg-white rounded-2xl shadow-md p-4">
      <!-- Stats -->
      <div class="grid grid-cols-4 gap-3 mb-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-800"><?= $totalAll ?></div>
          <div class="text-xs text-gray-500 mt-0.5">Total</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-red-600"><?= $totalBerat ?></div>
          <div class="text-xs text-gray-500 mt-0.5">Berat</div>
        </div>
        <div class="text-2xl text-center">
          <div class="text-2xl font-bold text-yellow-500"><?= $totalSedang ?></div>
          <div class="text-xs text-gray-500 mt-0.5">Sedang</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600"><?= $totalRingan ?></div>
          <div class="text-xs text-gray-500 mt-0.5">Ringan</div>
        </div>
      </div>
      <!-- Progress bar kategori -->
      <?php if ($totalAll > 0): ?>
      <div class="flex h-2 rounded-full overflow-hidden mb-4 gap-0.5">
        <?php if ($totalBerat): ?><div class="bg-red-500" style="width:<?= round($totalBerat/$totalAll*100) ?>%"></div><?php endif; ?>
        <?php if ($totalSedang): ?><div class="bg-yellow-400" style="width:<?= round($totalSedang/$totalAll*100) ?>%"></div><?php endif; ?>
        <?php if ($totalRingan): ?><div class="bg-green-400" style="width:<?= round($totalRingan/$totalAll*100) ?>%"></div><?php endif; ?>
      </div>
      <?php endif; ?>
      <!-- Filter tahun -->
      <form method="get" class="flex items-center gap-2">
        <label class="text-xs text-gray-500 font-medium whitespace-nowrap">Tahun:</label>
        <select name="tahun" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-red-300">
          <?php foreach ($tahunList as $th): ?>
            <option value="<?= $th ?>" <?= $th == $filterTahun ? 'selected' : '' ?>><?= $th ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($totalAktif > 0): ?>
        <span class="ml-auto inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
          <i class="fas fa-circle text-red-500 text-[8px]"></i> <?= $totalAktif ?> Belum selesai
        </span>
        <?php else: ?>
        <span class="ml-auto text-xs text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>Semua selesai</span>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="max-w-2xl mx-auto px-4 space-y-3">

    <?php if (empty($pelanggaranList)): ?>
    <div class="bg-white rounded-2xl shadow p-10 text-center">
      <i class="fas fa-check-circle text-green-300 text-5xl mb-4 block"></i>
      <p class="text-gray-500 font-medium">Tidak ada catatan pelanggaran pada tahun <?= htmlspecialchars($filterTahun) ?>.</p>
      <p class="text-gray-400 text-sm mt-1">Pertahankan perilaku baik Anda!</p>
    </div>
    <?php else: ?>

    <?php foreach ($pelanggaranList as $p):
      // Badge warna kategori
      $kat = isset($p['kategori_pelanggaran']) ? $p['kategori_pelanggaran'] : '';
      if ($kat === 'Berat') {
          $bgKat = 'bg-red-100'; $textKat = 'text-red-700'; $iconKat = 'fa-exclamation-circle';
      } elseif ($kat === 'Sedang') {
          $bgKat = 'bg-yellow-100'; $textKat = 'text-yellow-700'; $iconKat = 'fa-exclamation-triangle';
      } else {
          $bgKat = 'bg-green-100'; $textKat = 'text-green-700'; $iconKat = 'fa-info-circle';
      }
      // Badge status
      $stat = isset($p['status_pelanggaran']) ? $p['status_pelanggaran'] : '';
      if ($stat === 'Diselesaikan') {
          $bgStat = 'bg-green-100'; $textStat = 'text-green-700';
      } elseif ($stat === 'Follow Up') {
          $bgStat = 'bg-blue-100'; $textStat = 'text-blue-700';
      } else {
          $bgStat = 'bg-red-100'; $textStat = 'text-red-700';
      }
      $tgl = date('d M Y', strtotime($p['tanggal_pelanggaran']));
    ?>
    <div class="bg-white rounded-2xl shadow card-item overflow-hidden">
      <!-- Card header -->
      <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
        <span class="w-10 h-10 rounded-full <?= $bgKat ?> flex items-center justify-center flex-shrink-0">
          <i class="fas <?= $iconKat ?> <?= $textKat ?> text-lg"></i>
        </span>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($p['jenis_pelanggaran']) ?></span>
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $bgKat ?> <?= $textKat ?>"><?= $p['kategori_pelanggaran'] ?></span>
          </div>
          <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-2 flex-wrap">
            <span><i class="fas fa-calendar-alt mr-1"></i><?= $tgl ?></span>
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded <?= $bgStat ?> <?= $textStat ?> font-medium">
              <?= htmlspecialchars($p['status_pelanggaran']) ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Card body -->
      <div class="px-4 py-3 space-y-2">
        <?php if (!empty($p['deskripsi_pelanggaran'])): ?>
        <div>
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1"><i class="fas fa-align-left mr-1"></i>Keterangan</p>
          <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($p['deskripsi_pelanggaran'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($p['tindakan_yang_diambil'])): ?>
        <div class="bg-blue-50 rounded-lg px-3 py-2">
          <p class="text-xs font-semibold text-blue-600 mb-1"><i class="fas fa-gavel mr-1"></i>Tindakan yang Diambil</p>
          <p class="text-sm text-blue-800"><?= nl2br(htmlspecialchars($p['tindakan_yang_diambil'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-between text-xs text-gray-400 pt-1 border-t border-gray-100">
          <span><i class="fas fa-user-tie mr-1"></i>Dicatat oleh: <?= htmlspecialchars($p['nama_guru']) ?></span>
          <span><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Keterangan kategori -->
    <div class="bg-white rounded-2xl shadow p-4 text-xs text-gray-500">
      <p class="font-semibold text-gray-600 mb-2"><i class="fas fa-info-circle mr-1 text-blue-400"></i>Keterangan Kategori</p>
      <div class="flex flex-wrap gap-3">
        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span><strong class="text-red-700">Berat</strong>: Pelanggaran serius</span>
        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span><strong class="text-yellow-700">Sedang</strong>: Perlu perhatian</span>
        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block"></span><strong class="text-green-700">Ringan</strong>: Pelanggaran minor</span>
      </div>
    </div>

    <?php endif; ?>
  </div>

</body>
</html>
