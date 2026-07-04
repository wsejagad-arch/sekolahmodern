<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

if (!isset($_SESSION['no_induk'])) {
    header('location: ../../index.php?haruslogin');
    exit;
}

if (!isset($_SESSION['hak_akses']) || (int) $_SESSION['hak_akses'] !== 2) {
    echo '<script>window.location="../../404.html";</script>';
    exit;
}

$nipGuru = (string) $_SESSION['no_induk'];
$namaGuru = $_SESSION['nama_guru'] ?? ($_SESSION['nama'] ?? 'Guru');

function guru_ds_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function guru_ds_table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $result = @mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    return $result && mysqli_num_rows($result) > 0;
}

function guru_ds_column_exists(mysqli $conn, string $table, string $column): bool
{
    $safeTable = mysqli_real_escape_string($conn, $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = @mysqli_query($conn, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result && mysqli_num_rows($result) > 0;
}

$kelasOptions = [];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);

if (guru_ds_table_exists($conn, 'tbl_mapel_ampu')) {
    $idSekolah = mt_current_school_id();
    $qKelasAjar = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE id_sekolah=$idSekolah AND no_induk='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC");
    while ($qKelasAjar && ($row = mysqli_fetch_assoc($qKelasAjar))) {
        $kelasOptions[$row['kelas']] = $row['kelas'];
    }
}

if (guru_ds_table_exists($conn, 'tbl_wali_kelas') && guru_ds_table_exists($conn, 'tbl_kelas')) {
    $idSekolah = mt_current_school_id();
    $qKelasWali = @mysqli_query($conn, "SELECT DISTINCT k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas WHERE wk.id_sekolah=$idSekolah AND k.id_sekolah=$idSekolah AND wk.nip_wali='{$nipEsc}' AND k.kelas <> '' ORDER BY k.kelas ASC");
    while ($qKelasWali && ($row = mysqli_fetch_assoc($qKelasWali))) {
        $kelasOptions[$row['kelas']] = $row['kelas'];
    }
}

ksort($kelasOptions, SORT_NATURAL | SORT_FLAG_CASE);

$kelasFilter = trim((string) ($_GET['kelas'] ?? ''));
$namaFilter = trim((string) ($_GET['nama'] ?? ''));

if ($kelasFilter !== '' && !isset($kelasOptions[$kelasFilter])) {
    $kelasFilter = '';
}

$hasKelasFilter = $kelasFilter !== '';
$students = [];
$mapelActions = [];
$selectedMapelAction = (int) ($_GET['idmapel'] ?? 0);
$where = [];

if (empty($kelasOptions)) {
    $where[] = '1=0';
}

if ($hasKelasFilter) {
    $where[] = "s.kelas='" . mysqli_real_escape_string($conn, $kelasFilter) . "'";
}

if ($namaFilter !== '') {
    $namaEsc = mysqli_real_escape_string($conn, $namaFilter);
    $where[] = "(s.nama_siswa LIKE '%{$namaEsc}%' OR s.no_induk LIKE '%{$namaEsc}%' OR s.nisn LIKE '%{$namaEsc}%')";
}

if (guru_ds_column_exists($conn, 'tbl_siswa', 'status')) {
    $where[] = "s.status='Aktif'";
}
$idSekolah = mt_current_school_id();
$where[] = "s.id_sekolah=$idSekolah";

$phoneSelect = guru_ds_column_exists($conn, 'tbl_siswa', 'no_wa') ? 's.no_wa' : "'' AS no_wa";
$nisnSelect = guru_ds_column_exists($conn, 'tbl_siswa', 'nisn') ? 's.nisn' : "'' AS nisn";

if ($hasKelasFilter && !empty($kelasOptions)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT s.no_induk, {$nisnSelect}, s.nama_siswa, s.kelas, {$phoneSelect}
            FROM tbl_siswa s
            {$whereSql}
            ORDER BY s.kelas ASC, s.nama_siswa ASC";
    $qStudents = @mysqli_query($conn, $sql);
    while ($qStudents && ($row = mysqli_fetch_assoc($qStudents))) {
        $students[] = $row;
    }

    if (guru_ds_table_exists($conn, 'tbl_mapel_ampu')) {
        $idSekolah = mt_current_school_id();
        $kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
        $qMapelActions = @mysqli_query(
            $conn,
            "SELECT id_mapel, nama_mapel, kelas, thn_ajaran
             FROM tbl_mapel_ampu
             WHERE id_sekolah=$idSekolah AND no_induk='{$nipEsc}' AND kelas='{$kelasEsc}'
             ORDER BY nama_mapel ASC"
        );
        while ($qMapelActions && ($row = mysqli_fetch_assoc($qMapelActions))) {
            $mapelActions[] = $row;
        }
    }

    if (!empty($mapelActions)) {
        $hasSelectedMapel = false;
        foreach ($mapelActions as $mapelAction) {
            if ((int) $mapelAction['id_mapel'] === $selectedMapelAction) {
                $hasSelectedMapel = true;
                break;
            }
        }
        if (!$hasSelectedMapel) {
            $selectedMapelAction = (int) $mapelActions[0]['id_mapel'];
        }
    } else {
        $selectedMapelAction = 0;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Siswa - SIMANIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary:#4f46e5;
            --primary-light:#6366f1;
            --bg:#f8fafc;
            --text:#0f172a;
            --muted:#64748b;
            --border:#e2e8f0;
            --shadow:0 18px 42px rgba(15,23,42,.08);
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            font-family:"Poppins", sans-serif;
            font-weight:400;
            background:
                radial-gradient(circle at top right,
                    rgba(223,255,154,0.35) 0%,
                    transparent 35%),
                radial-gradient(circle at bottom left,
                    rgba(0,107,47,0.35) 0%,
                    transparent 35%),
                linear-gradient(
                    135deg,
                    rgba(11,122,50,0.75),
                    rgba(126,217,87,0.55),
                    rgba(217,255,159,0.45)
                );
            background-attachment: fixed;
            color:var(--text);
            padding-bottom:112px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Beautiful Green App Background Overlays */
        .background {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: -10;
            pointer-events: none;
            backdrop-filter: blur(4px);
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(10px);
        }

        .shape.one {
            width: 420px;
            height: 420px;
            background: rgba(47,168,79,0.35);
            top: -120px;
            left: -130px;
        }

        .shape.two {
            width: 520px;
            height: 520px;
            background: rgba(184,240,106,0.28);
            top: -180px;
            right: -160px;
        }

        .shape.three {
            width: 620px;
            height: 620px;
            background: rgba(13,111,45,0.38);
            bottom: -230px;
            right: -190px;
        }

        .shape.four {
            width: 460px;
            height: 460px;
            background: rgba(105,201,74,0.25);
            bottom: -120px;
            left: -160px;
        }

        .wave {
            position: absolute;
            width: 100%;
            height: 100%;
            background:
                repeating-radial-gradient(
                    ellipse at bottom right,
                    transparent 0 12px,
                    rgba(255,255,255,0.08) 13px 14px
                );
            opacity: 0.2;
        }

        .dots {
            position: absolute;
            width: 220px;
            height: 300px;
            background-image:
                radial-gradient(
                    rgba(255,255,255,0.18) 3px,
                    transparent 3px
                );
            background-size: 22px 22px;
            right: 30px;
            top: 90px;
        }
        .page-shell { max-width:1180px; margin:0 auto; padding:24px; }
        .topbar {
            display:flex;
            justify-content:space-between;
            gap:16px;
            align-items:flex-end;
            margin-bottom:18px;
        }
        .page-title {
            font-size:1.75rem;
            font-weight:600;
            letter-spacing:0;
        }
        .page-eyebrow {
            color:var(--muted);
            font-size:.86rem;
            font-weight:400;
        }
        .summary-chip {
            background:#fff;
            border:1px solid var(--border);
            border-radius:16px;
            padding:12px 16px;
            min-width:150px;
            box-shadow:0 10px 24px rgba(15,23,42,.05);
        }
        .summary-count { font-weight:600; }
        .panel {
            background:rgba(255,255,255,.94);
            border:1px solid var(--border);
            border-radius:18px;
            box-shadow:var(--shadow);
            overflow:hidden;
        }
        .panel-pad { padding:18px; }
        .filter-panel { margin-bottom:16px; }
        .nilai-action-panel { margin-bottom:16px; }
        .nilai-action-grid {
            display:grid;
            grid-template-columns:minmax(0, 1fr) auto auto;
            gap:12px;
            align-items:end;
        }
        .nilai-action-title {
            font-weight:600;
            color:var(--text);
        }
        .form-label { color:#334155; font-size:.86rem; font-weight:500; }
        .form-select,
        .form-control {
            border-radius:12px;
            border-color:var(--border);
            min-height:44px;
        }
        .btn {
            border-radius:12px;
            min-height:44px;
            font-weight:500;
        }
        .btn-primary {
            background:var(--primary);
            border-color:var(--primary);
        }
        .table { --bs-table-hover-bg:#f8fafc; }
        .table th {
            white-space:nowrap;
            color:var(--muted);
            font-size:.74rem;
            text-transform:uppercase;
            letter-spacing:.04em;
            background:#f8fafc;
            border-bottom:1px solid var(--border);
            font-weight:500;
        }
        .table td { vertical-align:middle; padding:14px 12px; }
        .student-name { font-weight:500; color:#172033; }
        .kelas-badge {
            background:#eef2ff;
            color:#4338ca;
            border:1px solid #c7d2fe;
            border-radius:999px;
            padding:5px 10px;
            font-weight:500;
            font-size:.76rem;
        }
        .empty-state {
            padding:52px 18px;
            text-align:center;
            color:var(--muted);
        }
        .empty-icon {
            width:54px;
            height:54px;
            border-radius:18px;
            display:grid;
            place-items:center;
            margin:0 auto 14px;
            background:#eef2ff;
            color:var(--primary);
            font-size:1.45rem;
        }
        .empty-title { font-weight:600; color:#334155; }
        .bottom-nav-wrap { position:fixed; bottom:0; left:0; right:0; z-index:1000; padding:12px 16px 20px; }
        .bottom-nav {
            max-width:440px;
            margin:0 auto;
            background:rgba(255,255,255,.92);
            backdrop-filter:blur(20px);
            border-radius:35px;
            padding:10px 12px;
            display:flex;
            justify-content:space-around;
            align-items:center;
            box-shadow:0 -10px 40px rgba(0,0,0,.08);
            border:1px solid rgba(255,255,255,.55);
            font-family:"Poppins", sans-serif;
        }
        .nav-link {
            text-decoration:none;
            color:#94a3b8;
            font-size:10px;
            font-weight:500;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:4px;
            font-family:"Poppins", sans-serif;
        }
        .nav-link i { font-size:20px; }
        .nav-link.active { color:var(--primary); }
        .nav-center {
            width:68px;
            height:68px;
            border-radius:50%;
            background:linear-gradient(135deg, var(--primary-light), var(--primary));
            margin-top:-45px;
            display:grid;
            place-items:center;
            color:#fff;
            font-size:34px;
            box-shadow:0 10px 25px rgba(79,70,229,.4);
            border:5px solid #f8fafc;
            text-decoration:none;
        }
        @media (max-width: 767px) {
            .page-shell { padding:16px; }
            .topbar { align-items:flex-start; flex-direction:column; }
            .summary-chip { width:100%; display:flex; justify-content:space-between; align-items:center; }
            .panel-pad { padding:14px; }
            .nilai-action-grid { grid-template-columns:1fr; }
            .table td { padding:12px 10px; }
        }
    </style>
</head>
<body>

  <div class="background">
    <div class="shape one"></div>
    <div class="shape two"></div>
    <div class="shape three"></div>
    <div class="shape four"></div>
    <div class="wave"></div>
    <div class="dots"></div>
  </div>
<main class="page-shell">
    <div class="topbar">
        <div>
            <a href="guru_legacy" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h1 class="page-title mb-1">Data Siswa</h1>
            <div class="page-eyebrow">Pilih kelas yang diampu oleh <?= guru_ds_h($namaGuru); ?></div>
        </div>
        <div class="summary-chip text-md-end">
            <div class="summary-count"><?= $hasKelasFilter ? count($students) : 0; ?> siswa</div>
            <div class="small text-muted"><?= count($kelasOptions); ?> kelas tersedia</div>
        </div>
    </div>

    <section class="panel panel-pad filter-panel">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" name="kelas" id="kelas">
                    <option value="">Pilih kelas terlebih dahulu</option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= guru_ds_h($kelas); ?>" <?= $kelasFilter === $kelas ? 'selected' : ''; ?>>
                            <?= guru_ds_h($kelas); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="nama">Cari siswa</label>
                <input class="form-control" type="search" name="nama" id="nama" value="<?= guru_ds_h($namaFilter); ?>" placeholder="Nama, NIS, atau NISN">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit"><i class="bi bi-search"></i> Cari</button>
                <a class="btn btn-outline-secondary" href="data-siswa" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </section>

    <?php if ($hasKelasFilter): ?>
        <section class="panel panel-pad nilai-action-panel">
            <form method="get" class="nilai-action-grid">
                <input type="hidden" name="kelas" value="<?= guru_ds_h($kelasFilter); ?>">
                <?php if ($namaFilter !== ''): ?>
                    <input type="hidden" name="nama" value="<?= guru_ds_h($namaFilter); ?>">
                <?php endif; ?>
                <div>
                    <div class="nilai-action-title mb-1"><i class="bi bi-clipboard-data me-1"></i> Nilai Siswa Kelas <?= guru_ds_h($kelasFilter); ?></div>
                    <label class="form-label" for="idmapel">Mata Pelajaran</label>
                    <select class="form-select" name="idmapel" id="idmapel" onchange="this.form.submit()" <?= empty($mapelActions) ? 'disabled' : ''; ?>>
                        <?php if (empty($mapelActions)): ?>
                            <option value="">Belum ada mapel yang Anda ampu di kelas ini</option>
                        <?php else: ?>
                            <?php foreach ($mapelActions as $mapelAction): ?>
                                <option value="<?= (int) $mapelAction['id_mapel']; ?>" <?= $selectedMapelAction === (int) $mapelAction['id_mapel'] ? 'selected' : ''; ?>>
                                    <?= guru_ds_h($mapelAction['nama_mapel']); ?><?= !empty($mapelAction['thn_ajaran']) ? ' - ' . guru_ds_h($mapelAction['thn_ajaran']) : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if ($selectedMapelAction > 0): ?>
                    <a class="btn btn-success" href="inputnilai?getDetail=<?= (int) $selectedMapelAction; ?>">
                        <i class="bi bi-pencil-square"></i> Input Nilai
                    </a>
                    <a class="btn btn-outline-primary" target="_blank" href="cetak-nilai-kelas?kelas=<?= rawurlencode($kelasFilter); ?>&idmapel=<?= (int) $selectedMapelAction; ?>">
                        <i class="bi bi-printer"></i> Cetak
                    </a>
                <?php else: ?>
                    <button class="btn btn-success" type="button" disabled><i class="bi bi-pencil-square"></i> Input Nilai</button>
                    <button class="btn btn-outline-primary" type="button" disabled><i class="bi bi-printer"></i> Cetak</button>
                <?php endif; ?>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <?php if (empty($kelasOptions)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                <div class="empty-title mb-1">Belum ada kelas terkait</div>
                <div>Guru ini belum memiliki kelas mengajar atau wali kelas di database.</div>
            </div>
        <?php elseif (!$hasKelasFilter): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-funnel"></i></div>
                <div class="empty-title mb-1">Belum ada kelas dipilih</div>
                <div>Pilih salah satu kelas di filter untuk melihat daftar siswa.</div>
            </div>
        <?php elseif (empty($students)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-search"></i></div>
                <div class="empty-title mb-1">Data siswa tidak ditemukan</div>
                <div>Coba ubah filter kelas atau kata kunci pencarian.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:64px;">No</th>
                        <th>Nama</th>
                        <th>No Induk</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>No WA</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td class="student-name"><?= guru_ds_h($student['nama_siswa'] ?? ''); ?></td>
                            <td><?= guru_ds_h($student['no_induk'] ?? ''); ?></td>
                            <td><?= guru_ds_h($student['nisn'] ?? ''); ?></td>
                            <td><span class="kelas-badge"><?= guru_ds_h($student['kelas'] ?? ''); ?></span></td>
                            <td><?= guru_ds_h($student['no_wa'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<div class="bottom-nav-wrap">
    <nav class="bottom-nav" aria-label="Navigasi guru">
        <a href="guru_legacy" class="nav-link"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
        <a href="data-siswa" class="nav-link active"><i class="bi bi-journal-bookmark"></i><span>Kelas</span></a>
        <a href="guru_legacy?open_jurnal=1" class="nav-center" aria-label="Input jurnal"><i class="bi bi-fingerprint"></i></a>
        <a href="inputtugas" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Tugas</span></a>
        <a href="profil-guru" class="nav-link"><i class="bi bi-person-fill"></i><span>Profil</span></a>
    </nav>
</div>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
