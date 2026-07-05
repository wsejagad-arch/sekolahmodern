<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["no_induk"])) { header("location: ../../index.php?haruslogin"); exit; }
if($_SESSION['hak_akses'] != 2) { echo '<script>window.location="../../404.html";</script>'; exit; }
include '../../koneksi.php';
include '../../functions.php';
date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, (string)$nipguru);
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantMapelAmpu = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantMapelAmpuAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "m.id_sekolah={$tenantId}" : "1=1";
$tenantKelasAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_kelas', 'id_sekolah') ? "k.id_sekolah={$tenantId}" : "1=1";
$tenantWaliAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_wali_kelas', 'id_sekolah') ? "wk.id_sekolah={$tenantId}" : "1=1";
$tenantPenilaianAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_penilaian_item', 'id_sekolah') ? "pi.id_sekolah={$tenantId}" : "1=1";
$tenantSiswa = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

function gn_table_exists(mysqli $conn, string $table): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '$tableEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function gn_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function gn_sql_in(mysqli $conn, array $values): string
{
    $safe = [];
    foreach ($values as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            $safe[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
        }
    }
    return $safe ? implode(',', array_unique($safe)) : "''";
}

// Pastikan tabel ada (untuk instalasi lama)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_penilaian_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  id_mapel INT NOT NULL,
  kelas VARCHAR(50) NOT NULL,
  mapel VARCHAR(100) NOT NULL,
  no_induk_guru VARCHAR(50) NOT NULL,
  kode_penilaian VARCHAR(20) NOT NULL,
  materi VARCHAR(255) NOT NULL,
  UNIQUE KEY uniq_item (tanggal, id_mapel, kode_penilaian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_item INT NOT NULL,
  no_induk_siswa VARCHAR(50) NOT NULL,
  nilai FLOAT DEFAULT 0,
  UNIQUE KEY uniq_nilai_item (id_item, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? '');
$kelas = trim((string)($_GET['kelas'] ?? ''));
$idmapel = (int)($_GET['idmapel'] ?? 0);
$scope = (string)($_GET['scope'] ?? 'own');

$ownKelasList = [];
$qOwnKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE {$tenantMapelAmpu} AND no_induk='$nipEsc' AND kelas <> '' ORDER BY kelas ASC");
while ($qOwnKelas && ($row = mysqli_fetch_assoc($qOwnKelas))) {
    $ownKelasList[(string)$row['kelas']] = (string)$row['kelas'];
}

$waliKelasList = [];
if (gn_table_exists($conn, 'tbl_wali_kelas') && gn_table_exists($conn, 'tbl_kelas')) {
    $qWali = mysqli_query(
        $conn,
        "SELECT DISTINCT k.kelas
         FROM tbl_wali_kelas wk
         JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
         WHERE {$tenantWaliAlias} AND {$tenantKelasAlias} AND wk.nip_wali='$nipEsc' AND k.kelas <> ''
         ORDER BY k.kelas ASC"
    );
    while ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
        $waliKelasList[(string)$row['kelas']] = (string)$row['kelas'];
    }
}
if (gn_table_exists($conn, 'tbl_kelas') && gn_column_exists($conn, 'tbl_kelas', 'nip_wali')) {
    $qWaliLegacy = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE " . (function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_kelas', 'id_sekolah') ? "id_sekolah={$tenantId} AND " : "") . "nip_wali='$nipEsc' AND kelas <> '' ORDER BY kelas ASC");
    while ($qWaliLegacy && ($row = mysqli_fetch_assoc($qWaliLegacy))) {
        $waliKelasList[(string)$row['kelas']] = (string)$row['kelas'];
    }
}

$isWaliKelas = !empty($waliKelasList);
if (!$isWaliKelas || $scope !== 'wali') {
    $scope = 'own';
}

$availableKelasList = $scope === 'wali' ? $waliKelasList : $ownKelasList;
if ($kelas !== '' && !isset($availableKelasList[$kelas])) {
    $kelas = '';
}
$kelasEsc = mysqli_real_escape_string($conn, $kelas);

// Ambil daftar pertemuan untuk guru ini (dengan filter opsional)
$whereParts = [];
$whereParts[] = $tenantPenilaianAlias;
if ($tanggal !== '') { $whereParts[] = "pi.tanggal='".$tanggal."'"; }
if ($idmapel > 0) { $whereParts[] = "pi.id_mapel=".$idmapel; }

if ($scope === 'wali') {
    $waliIn = gn_sql_in($conn, array_values($waliKelasList));
    $whereParts[] = "pi.kelas IN ($waliIn)";
    if ($kelas !== '') {
        $whereParts[] = "pi.kelas='$kelasEsc'";
    }
} else {
    $whereParts[] = "pi.no_induk_guru='$nipEsc'";
    if ($kelas !== '') {
        $whereParts[] = "pi.kelas='$kelasEsc'";
    }
}

$isFilterActive = isset($_GET['filter']);

$where = ' WHERE ' . implode(' AND ', $whereParts);
if ($isFilterActive) {
    $pertemuan = mysqli_query(
        $conn,
        "SELECT pi.tanggal, pi.id_mapel, pi.kelas, pi.mapel, pi.no_induk_guru, MAX(g.nama_guru) AS nama_guru
         FROM tbl_penilaian_item pi
         LEFT JOIN tbl_guru g ON g.no_induk = pi.no_induk_guru
         $where
         GROUP BY pi.tanggal, pi.id_mapel, pi.kelas, pi.mapel, pi.no_induk_guru
         ORDER BY pi.tanggal DESC, pi.kelas ASC, pi.mapel ASC"
    );
} else {
    $pertemuan = false;
}

// Data untuk filter dropdown
$kelasOptions = array_values($availableKelasList);
$mapelSql = $scope === 'wali'
    ? "SELECT DISTINCT pi.id_mapel, pi.mapel AS nama_mapel, pi.kelas, pi.no_induk_guru
       FROM tbl_penilaian_item pi
       WHERE {$tenantPenilaianAlias} AND pi.kelas IN (" . gn_sql_in($conn, array_values($waliKelasList)) . ")" . ($kelas !== '' ? " AND pi.kelas='$kelasEsc'" : "") . "
       ORDER BY pi.mapel ASC, pi.kelas ASC"
    : "SELECT DISTINCT id_mapel, nama_mapel, kelas, no_induk AS no_induk_guru
       FROM tbl_mapel_ampu
       WHERE {$tenantMapelAmpu} AND no_induk='$nipEsc'" . ($kelas !== '' ? " AND kelas='$kelasEsc'" : "") . "
       ORDER BY nama_mapel ASC, kelas ASC";
$mapelOpts = mysqli_query($conn, $mapelSql);
$mapelOptions = [];
while ($mapelOpts && ($mo = mysqli_fetch_assoc($mapelOpts))) {
    $mapelOptions[] = $mo;
}

// Data untuk bagian Input Nilai Siswa. Input hanya memakai mapel yang guru ampu sendiri.
$inputMapelOptions = [];
$inputMapelSql = "SELECT id_mapel, nama_mapel, kelas
                  FROM tbl_mapel_ampu
                  WHERE {$tenantMapelAmpu} AND no_induk='$nipEsc'" . ($kelas !== '' ? " AND kelas='$kelasEsc'" : "") . "
                  ORDER BY kelas ASC, nama_mapel ASC";
$inputMapelResult = mysqli_query($conn, $inputMapelSql);
while ($inputMapelResult && ($row = mysqli_fetch_assoc($inputMapelResult))) {
    $inputMapelOptions[] = $row;
}

$inputIdMapel = (int)($_GET['input_idmapel'] ?? ($idmapel > 0 ? $idmapel : 0));
$validInputIds = array_map(static fn($row) => (int)$row['id_mapel'], $inputMapelOptions);
if (!$inputIdMapel || !in_array($inputIdMapel, $validInputIds, true)) {
    $inputIdMapel = $validInputIds[0] ?? 0;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nilai Siswa</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
  <style>
    body { background: #ebf1f6; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
    .page-header {
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      color: #fff;
      padding: 2rem 1rem;
      border-radius: 0 0 24px 24px;
      box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
      margin-bottom: 2rem;
    }
    .filter-card {
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      background: #ffffff;
    }
    .input-score-card {
      border: 1px solid #dbeafe;
      border-radius: 16px;
      background: linear-gradient(135deg, #ffffff, #eff6ff);
      box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
    }
    .input-score-icon {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      background: linear-gradient(135deg, #2563eb, #14b8a6);
      box-shadow: 0 8px 16px rgba(37, 99, 235, 0.22);
      flex: 0 0 auto;
    }
    .meeting-card { 
      border: 1px solid #e2e8f0; 
      border-radius: 16px; 
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); 
      overflow: hidden;
    }
    .meeting-card .card-header { 
      border-bottom: 1px solid #e2e8f0; 
      background: #f8fafc; 
      padding: 1rem 1.25rem;
    }
    .btn-primary {
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      border: none;
      box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }
    .badge-pill { border-radius: 999px; }
    .table-container {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .custom-table {
      width: 100%;
      margin-bottom: 0;
      border-collapse: separate;
      border-spacing: 0;
    }
    .custom-table thead th {
      background: #f8fafc;
      color: #64748b;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
      padding: 1rem 1.25rem;
      border-bottom: 2px solid #e2e8f0;
      white-space: nowrap;
      vertical-align: middle;
    }
    .custom-table tbody td {
      padding: 1rem 1.25rem;
      vertical-align: middle;
      border-bottom: 1px solid #e2e8f0;
      color: #334155;
      font-size: 0.875rem;
      font-weight: 500;
      white-space: nowrap;
    }
    .custom-table tbody tr:hover td {
      background-color: #f1f5f9;
    }
    .custom-table tbody tr:last-child td {
      border-bottom: none;
    }
    .th-item { position: relative; padding-right: 2.5rem !important; }
    .th-item .btn-del-item { position:absolute; top:50%; right:10px; transform:translateY(-50%); padding:0.25rem 0.4rem; font-size:0.8rem; border-radius:6px; }
    .cell-value { font-weight: 700; color: #0f172a; }
    /* Make the first column sticky */
    .custom-table th:first-child,
    .custom-table td:first-child {
      position: sticky;
      left: 0;
      z-index: 2;
    }
    .custom-table thead th:first-child {
      background-color: #f8fafc;
      z-index: 3;
      box-shadow: inset -1px 0 0 #e2e8f0;
    }
    .custom-table tbody td:first-child {
      background-color: #ffffff;
      font-weight: 600;
      box-shadow: inset -1px 0 0 #e2e8f0;
    }
    .custom-table tbody tr:hover td:first-child {
      background-color: #f1f5f9;
    }
  </style>
</head>
<body>
<?php include 'guru_sidebar_shared.php'; ?>

<div class="app-shell" style="grid-template-columns: 1fr; padding-right: 24px;">
  <div class="desktop-center-column">

    <div class="welcome-banner-premium mb-4">
        <div class="banner-content">
            <div class="banner-text">
                <h2 class="animate-fade-in" style="font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.5px;">Nilai Siswa 📊</h2>
                <p class="banner-subtitle" style="font-size:1.05rem;opacity:0.9;"><?= $scope === 'wali' ? 'Pantau nilai lintas mapel kelas wali Anda.' : 'Rekap dan kelola penilaian mata pelajaran yang Anda ampu.'; ?></p>
            </div>
            <div class="banner-actions">
                <a href="../../home.php" class="btn-premium-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
            </div>
        </div>
        <div class="banner-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

<div class="pb-4">
  <div class="card filter-card mb-3">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get">
        <input type="hidden" name="filter" value="1">
        <?php if ($isWaliKelas) { ?>
        <div class="col-12 col-md-3">
          <label class="form-label">Cakupan Nilai</label>
          <select name="scope" class="form-select" onchange="this.form.submit()">
            <option value="own" <?= $scope === 'own' ? 'selected' : ''; ?>>Mapel Saya</option>
            <option value="wali" <?= $scope === 'wali' ? 'selected' : ''; ?>>Kelas Wali</option>
          </select>
        </div>
        <?php } else { ?>
          <input type="hidden" name="scope" value="own">
        <?php } ?>
        <div class="col-12 col-md-<?= $isWaliKelas ? '2' : '3'; ?>">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>" class="form-control" />
        </div>
        <div class="col-12 col-md-<?= $isWaliKelas ? '2' : '3'; ?>">
          <label class="form-label">Kelas</label>
          <select name="kelas" class="form-select">
            <option value="">Semua Kelas</option>
            <?php foreach($kelasOptions as $k) { ?>
              <option value="<?= htmlspecialchars($k); ?>" <?= $kelas === $k ? 'selected' : '' ?>><?= htmlspecialchars($k); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-md-<?= $isWaliKelas ? '3' : '4'; ?>">
          <label class="form-label">Mapel</label>
          <select name="idmapel" class="form-select">
            <option value="">Semua Mapel</option>
            <?php foreach($mapelOptions as $mo) { ?>
              <option value="<?= (int)$mo['id_mapel']; ?>" <?= $idmapel === (int)$mo['id_mapel'] ? 'selected' : '' ?>><?= htmlspecialchars($mo['nama_mapel'].' ('.$mo['kelas'].')'); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <div class="d-grid gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
            <a href="nilai.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card input-score-card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-12 col-lg-5">
          <div class="d-flex align-items-center gap-3">
            <span class="input-score-icon"><i class="bi bi-pencil-square fs-4"></i></span>
            <div>
              <h6 class="mb-1 fw-bold text-dark">Input Nilai Siswa</h6>
              <div class="text-muted small">Pilih mapel sesuai kelas, lalu buka form input nilai siswa.</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-7">
          <form class="row g-2 align-items-end" method="get">
            <?php if ($isFilterActive) { ?>
              <input type="hidden" name="filter" value="1">
            <?php } ?>
            <input type="hidden" name="scope" value="<?= htmlspecialchars($scope); ?>">
            <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>">
            <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas); ?>">
            <input type="hidden" name="idmapel" value="<?= $idmapel > 0 ? (int)$idmapel : ''; ?>">
            <div class="col-12 col-md">
              <label class="form-label small fw-semibold">Mapel/Kelas</label>
              <select name="input_idmapel" class="form-select" onchange="this.form.submit()" <?= empty($inputMapelOptions) ? 'disabled' : ''; ?>>
                <?php if (empty($inputMapelOptions)) { ?>
                  <option value="">Belum ada mapel yang bisa diinput</option>
                <?php } else { ?>
                  <?php foreach ($inputMapelOptions as $imo) { ?>
                    <option value="<?= (int)$imo['id_mapel']; ?>" <?= $inputIdMapel === (int)$imo['id_mapel'] ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($imo['nama_mapel'] . ' - ' . $imo['kelas']); ?>
                    </option>
                  <?php } ?>
                <?php } ?>
              </select>
            </div>
            <div class="col-12 col-md-auto d-grid">
              <?php if ($inputIdMapel > 0) { ?>
                <a class="btn btn-success" href="inputnilai?getDetail=<?= (int)$inputIdMapel; ?>">
                  <i class="bi bi-pencil-square"></i> Input Nilai
                </a>
              <?php } else { ?>
                <button class="btn btn-secondary" type="button" disabled>
                  <i class="bi bi-pencil-square"></i> Input Nilai
                </button>
              <?php } ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="alert <?= $scope === 'wali' ? 'alert-info' : 'alert-secondary'; ?> py-2 small">
    <?php if ($scope === 'wali') { ?>
      Mode kelas wali aktif: Anda dapat memantau nilai semua mapel pada kelas wali yang dipilih. Nilai milik guru lain ditampilkan hanya untuk pemantauan.
    <?php } else { ?>
      Mode mapel saya: halaman hanya menampilkan nilai dari mapel yang Anda ampu.
    <?php } ?>
  </div>

  <?php if (!$isFilterActive) { ?>
    <div class="text-center py-5 empty-state" style="background:#fff; border-radius:16px; border:1px dashed #cbd5e1;">
      <div style="width:80px; height:80px; background:#eff6ff; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:#4f46e5;">
        <i class="bi bi-funnel fs-1"></i>
      </div>
      <h5 class="fw-bold text-dark">Gunakan Filter untuk Menampilkan Data</h5>
      <p class="text-muted mb-0">Silakan pilih cakupan, tanggal, kelas, atau mapel pada form di atas lalu klik "Terapkan".</p>
    </div>
  <?php } elseif (!$pertemuan) { ?>
    <div class="alert alert-danger shadow-sm border-0 rounded-4">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> Gagal memuat data nilai: <?= htmlspecialchars(mysqli_error($conn)); ?>
    </div>
  <?php } elseif (mysqli_num_rows($pertemuan) === 0) { ?>
    <div class="text-center py-5 empty-state" style="background:#fff; border-radius:16px; border:1px dashed #cbd5e1;">
      <div style="width:80px; height:80px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:#ef4444;">
        <i class="bi bi-clipboard-x fs-1"></i>
      </div>
      <h5 class="fw-bold text-dark">Belum ada penilaian</h5>
      <p class="text-muted mb-0">Tidak ada data nilai yang sesuai dengan filter yang Anda pilih.</p>
    </div>
  <?php } ?>

  <?php while ($pertemuan && ($p = mysqli_fetch_assoc($pertemuan))) {
    $tgl = $p['tanggal'];
    $idm = (int)$p['id_mapel'];
    $kls = $p['kelas'];
    $mpl = $p['mapel'];
    $guruMapel = $p['nama_guru'] ?: $p['no_induk_guru'];
    $canManageMeeting = (string)$p['no_induk_guru'] === (string)$nipguru;
    $ownerEsc = mysqli_real_escape_string($conn, (string)$p['no_induk_guru']);
    $printOwner = rawurlencode((string)$p['no_induk_guru']);
    $printTanggal = rawurlencode((string)$tgl);
    $printKelas = rawurlencode((string)$kls);
    $classPrintUrl = "download_nilai_kelas_pdf.php?tanggal={$printTanggal}&idmapel={$idm}&kelas={$printKelas}&owner={$printOwner}";

    // Ambil items untuk pertemuan ini
    $items = mysqli_query($conn, "SELECT * FROM tbl_penilaian_item WHERE " . (function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_penilaian_item', 'id_sekolah') ? "id_sekolah={$tenantId} AND " : "") . "tanggal='".$tgl."' AND id_mapel=".$idm." AND no_induk_guru='".$ownerEsc."' ORDER BY id ASC");
    $itemList = [];
    while ($it = mysqli_fetch_assoc($items)) { $itemList[] = $it; }

    // Ambil siswa kelas tsb dan simpan dalam array
    $siswaQuery = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE {$tenantSiswa} AND kelas='".mysqli_real_escape_string($conn,$kls)."' AND status='Aktif' ORDER BY nama_siswa ASC");
    $siswaList = [];
    while ($s = mysqli_fetch_assoc($siswaQuery)) { $siswaList[] = $s; }

    // Ambil nilai existing untuk semua item
    $nilaiMap = [];
    if (count($itemList) > 0) {
      $ids = array_map(function($x){ return (int)$x['id']; }, $itemList);
      $idStr = implode(',', $ids);
      $qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_item IN (".$idStr.")");
      while ($nv = mysqli_fetch_assoc($qNil)) {
        $nilaiMap[$nv['id_item']][$nv['no_induk_siswa']] = $nv['nilai'];
      }
    }
  ?>
  <div class="card meeting-card mb-4">
    <div class="card-header bg-white">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2">Kelas <?= htmlspecialchars($kls); ?></span>
          <span class="fw-semibold"><?= htmlspecialchars($mpl); ?></span>
          <?php if ($scope === 'wali') { ?>
            <span class="badge bg-light text-dark border ms-2"><i class="bi bi-person"></i> <?= htmlspecialchars($guruMapel); ?></span>
          <?php } ?>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
          <?php $jmlItem = count($itemList); if ($jmlItem>0) { ?>
            <span class="badge rounded-pill text-bg-secondary"><?= $jmlItem; ?> Item</span>
          <?php } ?>
          <span class="text-muted small"><i class="bi bi-calendar3"></i> <?= htmlspecialchars($tgl); ?></span>
          <?php if (count($itemList) > 0) { ?>
            <a href="<?= htmlspecialchars($classPrintUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-success" target="_blank">
              <i class="bi bi-printer"></i> Cetak Kelas
            </a>
          <?php } ?>
          <?php if ($canManageMeeting) { ?>
            <a href="download_nilai_kelas_pdf.php?tanggal=<?= htmlspecialchars($tgl); ?>&idmapel=<?= (int)$idm; ?>&kelas=<?= htmlspecialchars($kls); ?>" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> Download PDF Kelas</a>
            <button class="btn btn-sm btn-outline-danger btn-mass-clear" data-tanggal="<?= htmlspecialchars($tgl); ?>" data-idmapel="<?= (int)$idm; ?>" data-kelas="<?= htmlspecialchars($kls); ?>"><i class="bi bi-trash3"></i> Bersihkan Nilai</button>
          <?php } ?>
        </div>
      </div>
    </div>
    <div class="card-body">
      <?php if (count($itemList) === 0) { ?>
        <div class="alert alert-secondary d-flex align-items-center"><i class="bi bi-info-circle me-2"></i> Belum ada kolom penilaian ditambahkan untuk pertemuan ini.</div>
      <?php } else { ?>
        <div class="print-toolbar mb-3 p-3 rounded border bg-light">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
              <label class="form-label small fw-semibold mb-1">Cetak Laporan</label>
              <div class="d-grid">
                <a href="<?= htmlspecialchars($classPrintUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-success btn-sm" target="_blank">
                  <i class="bi bi-file-earmark-pdf"></i> Per Kelas
                </a>
              </div>
            </div>
            <div class="col-12 col-md-5">
              <label class="form-label small fw-semibold mb-1">Pilih Siswa</label>
              <select class="form-select form-select-sm report-student-select">
                <option value="">Pilih siswa untuk laporan individu</option>
                <?php foreach ($siswaList as $sOpt) { ?>
                  <option value="<?= htmlspecialchars($sOpt['no_induk'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($sOpt['nama_siswa']); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-12 col-md-3">
              <button class="btn btn-outline-primary btn-sm w-100 btn-print-student"
                      type="button"
                      data-tanggal="<?= htmlspecialchars($tgl, ENT_QUOTES, 'UTF-8'); ?>"
                      data-idmapel="<?= (int)$idm; ?>"
                      data-kelas="<?= htmlspecialchars($kls, ENT_QUOTES, 'UTF-8'); ?>"
                      data-owner="<?= htmlspecialchars((string)$p['no_induk_guru'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-person-lines-fill"></i> Per Individu
              </button>
            </div>
          </div>
        </div>
        <div class="table-container mb-0" style="border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: #fff;">
          <div class="table-responsive" style="margin: 0; border: none; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table custom-table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="min-width: 200px;">Nama Siswa</th>
                <?php foreach ($itemList as $it) { ?>
                  <th class="text-center th-item" data-item-id="<?= (int)$it['id']; ?>">
                    <div class="fw-semibold"><?= htmlspecialchars($it['kode_penilaian']); ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($it['materi']); ?></div>
                    <?php if ($canManageMeeting) { ?>
                      <button type="button" class="btn btn-sm btn-outline-danger btn-del-item" title="Hapus kolom" data-item-id="<?= (int)$it['id']; ?>">
                        <i class="bi bi-x"></i>
                      </button>
                    <?php } ?>
                  </th>
                <?php } ?>
                <th class="text-center">Rata-rata UH</th>
                <th class="text-center">Rata-rata ASAS/ASAT</th>
                <?php if ($canManageMeeting) { ?>
                  <th class="text-center">Aksi</th>
                  <th class="text-center">Download</th>
                <?php } ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($siswaList as $s) { $ni = $s['no_induk']; ?>
                <tr>
                  <td><?= htmlspecialchars($s['nama_siswa']); ?></td>
                  <?php
                    $uhSum=0; $uhCnt=0; $asSum=0; $asCnt=0;
                    foreach ($itemList as $it) {
                      $valRaw = $nilaiMap[$it['id']][$ni] ?? '';
                      $display = ($valRaw === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($valRaw));
                      // tampilkan sel
                      echo '<td class="text-center"><span class="cell-value" data-item-id="'.(int)$it['id'].'" data-nis="'.htmlspecialchars($ni).'">'.$display.'</span></td>';
                      // akumulasi rata-rata
                      $kode = strtoupper($it['kode_penilaian']);
                      if ($valRaw !== '' && is_numeric($valRaw)) {
                        $num = (float)$valRaw;
                        if (strpos($kode, 'UH') === 0) { $uhSum += $num; $uhCnt++; }
                        if ($kode === 'ASAS' || $kode === 'ASAT') { $asSum += $num; $asCnt++; }
                      }
                    }
                    $avgUH = $uhCnt ? round($uhSum / $uhCnt, 2) : '';
                    $avgAS = $asCnt ? round($asSum / $asCnt, 2) : '';
                  ?>
                  <td class="text-center fw-bold text-primary"><?= $avgUH === '' ? '<span class="text-muted fw-normal">-</span>' : htmlspecialchars($avgUH); ?></td>
                  <td class="text-center fw-bold text-primary"><?= $avgAS === '' ? '<span class="text-muted fw-normal">-</span>' : htmlspecialchars($avgAS); ?></td>
                  <?php if ($canManageMeeting) { ?>
                    <td class="text-center">
                      <button class="btn btn-sm btn-light border btn-edit-row shadow-sm text-primary fw-semibold" style="border-radius: 8px;" data-nis="<?= htmlspecialchars($ni); ?>" data-tanggal="<?= htmlspecialchars($tgl); ?>" data-idmapel="<?= (int)$idm; ?>">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                      </button>
                    </td>
                    <td class="text-center">
                      <a href="download_nilai_pdf.php?nis=<?= urlencode($ni); ?>&tanggal=<?= urlencode($tgl); ?>&idmapel=<?= (int)$idm; ?>&kelas=<?= urlencode($kls); ?>" class="btn btn-sm btn-danger shadow-sm fw-semibold" style="border-radius: 8px;" target="_blank" title="NIS: <?= htmlspecialchars($ni); ?>">
                        <i class="bi bi-file-pdf me-1"></i>PDF
                      </a>
                    </td>
                  <?php } ?>
                </tr>
              <?php } ?>
            </tbody>
          </table>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
  <?php } // end while pertemuan ?>
</div>

<!-- Modal Edit Nilai Per Orang -->
<div class="modal fade" id="modalEditNilai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Nilai Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditOrang">
          <input type="hidden" name="tanggal" />
          <input type="hidden" name="idmapel" />
          <input type="hidden" name="no_induk_siswa" />
          <div class="row" id="listInputNilai"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnSimpanOrang">Simpan</button>
      </div>
    </div>
  </div>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (function(){
    function reloadPage(){ window.location.reload(); }
    // Hapus kolom nilai
    $(document).on('click', '.btn-del-item', function(){
      const id = $(this).data('item-id');
      showConfirm('Hapus kolom penilaian ini beserta semua nilainya?').then(function(ok){
        if (!ok) return;
        $.post('hapus_item_penilaian.php', { id_item: id }, function(res){
          reloadPage();
        }).fail(function(){ showToast('Gagal menghapus kolom', 'error'); });
      });
    });

    // Bersihkan nilai masal (per pertemuan)
    $(document).on('click', '.btn-mass-clear', function(){
      const tanggal = $(this).data('tanggal');
      const idmapel = $(this).data('idmapel');
      const kelas = $(this).data('kelas');
      showConfirm('Hapus semua nilai pada pertemuan ini?').then(function(ok){
        if (!ok) return;
        $.post('hapus_nilai_masal.php', { tanggal, idmapel, kelas }, function(){
          reloadPage();
        }).fail(function(){ showToast('Gagal menghapus nilai', 'error'); });
      });
    });

    // Edit nilai per orang: rakit form berdasarkan header kolom dan nilai sel di baris tsb
    $(document).on('click', '.btn-edit-row', function(){
      const btn = $(this);
      const tr = btn.closest('tr');
      const modal = new bootstrap.Modal(document.getElementById('modalEditNilai'));
      const nis = btn.data('nis');
      const tanggal = btn.data('tanggal');
      const idmapel = btn.data('idmapel');
      const ths = btn.closest('.card').find('thead th.th-item');
      const inputs = [];
      ths.each(function(index){
        const th = $(this);
        const itemId = th.data('item-id');
        const code = th.find('.fw-semibold').text().trim();
        const materi = th.find('.text-muted.small').text().trim();
        const td = tr.find('td').eq(1+index); // kolom 0 = Nama Siswa
        let val = td.find('.cell-value').text().trim();
        if (val === '-' || val === '') val = '';
        inputs.push(`
          <div class="col-12 col-md-6 mb-3">
            <label class="form-label">${code} <small class="text-muted d-block">${materi}</small></label>
            <input type="number" name="nilai[${itemId}]" class="form-control" min="0" max="100" step="1" value="${val}">
          </div>`);
      });
      $('#formEditOrang [name=tanggal]').val(tanggal);
      $('#formEditOrang [name=idmapel]').val(idmapel);
      $('#formEditOrang [name=no_induk_siswa]').val(nis);
      $('#listInputNilai').html(inputs.join(''));
      modal.show();
    });

    // Simpan nilai per orang (batch)
    $('#btnSimpanOrang').on('click', function(){
      const form = $('#formEditOrang');
      const btn = $(this);
      btn.prop('disabled', true).text('Menyimpan...');
      $.post('update_nilai_perorang.php', form.serialize(), function(){
        // Setelah simpan, reload untuk sinkron tampilan
        window.location.reload();
      }).fail(function(){
        alert('Gagal menyimpan');
        btn.prop('disabled', false).text('Simpan');
      });
    });

    $(document).on('click', '.btn-print-student', function(){
      const btn = $(this);
      const wrap = btn.closest('.print-toolbar');
      const nis = wrap.find('.report-student-select').val();
      if (!nis) {
        alert('Pilih siswa terlebih dahulu.');
        return;
      }
      const params = new URLSearchParams({
        nis: nis,
        tanggal: btn.data('tanggal'),
        idmapel: btn.data('idmapel'),
        kelas: btn.data('kelas'),
        owner: btn.data('owner')
      });
      window.open('download_nilai_pdf.php?' + params.toString(), '_blank');
    });
  })();
</script>
  </div> <!-- end desktop-center-column -->
</div> <!-- end app-shell -->

<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
