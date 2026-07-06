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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
    <style>
        body { margin:0; font-family:"Plus Jakarta Sans", system-ui, sans-serif; background:#ebf1f6; color:#0f172a; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px 14px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
        .form-label { font-weight: 700; color: #1e293b; font-size: 0.9rem; }
        .table-modern th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background: #f8fafc; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; }
        .table-modern td { vertical-align: middle; padding: 16px; border-bottom: 1px solid #f1f5f9; }
        .table-modern tbody tr { transition: all 0.2s; }
        .table-modern tbody tr:hover { background: #f8fafc; }
        .kelas-badge { background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; border-radius:999px; padding:5px 10px; font-weight:700; font-size:0.76rem; }
        .empty-state { padding:52px 18px; text-align:center; color:#64748b; }
        .empty-icon { width:54px; height:54px; border-radius:18px; display:grid; place-items:center; margin:0 auto 14px; background:#eef2ff; color:#3c58b9; font-size:1.45rem; }
        .empty-title { font-weight:700; color:#1e293b; }
        .mobile-nav { position:fixed; left:0; right:0; bottom:0; background:rgba(255,255,255,.94); border-top:1px solid #e2e8f0; backdrop-filter:blur(16px); padding:10px 16px; display:flex; justify-content:center; gap:22px; z-index:20; }
        .mobile-nav a { color:#64748b; text-decoration:none; font-size:12px; font-weight:800; display:flex; flex-direction:column; align-items:center; }
        .mobile-nav i { font-size:20px; }
        @media (min-width: 768px) {
            .mobile-nav, .guru-common-footer-wrap, .bottom-nav-wrap { display: none !important; padding-bottom: 0 !important; }
            body { padding-bottom: 0 !important; }
            .data-siswa-shell { grid-template-columns: 1fr !important; padding-right: 24px !important; }
        }
    </style>
</head>
<body>

<?php include 'guru_sidebar_shared.php'; ?>

<div class="app-shell data-siswa-shell">
    <div class="desktop-center-column">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner-premium mb-4">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 class="animate-fade-in" style="font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.5px;">Data Siswa 🧑‍🎓</h2>
                    <p class="banner-subtitle" style="font-size:1.05rem;opacity:0.9;">Pilih kelas yang diampu oleh <?= guru_ds_h($namaGuru); ?> untuk melihat daftar siswa atau mencetak nilai.</p>
                </div>
                <div class="banner-actions d-none d-md-block">
                    <a href="../../home.php" class="btn-premium-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                </div>
            </div>
            <div class="banner-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

        <div class="premium-card mb-4">
            <div class="card-header-flex">
                <h3 class="card-title-modern"><i class="bi bi-funnel text-primary me-2"></i> Filter Pencarian</h3>
                <span class="badge-subtle"><?= $hasKelasFilter ? count($students) : 0; ?> Siswa, <?= count($kelasOptions); ?> Kelas Tersedia</span>
            </div>
            
            <form method="get" class="row g-3 align-items-end">
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
                    <button class="btn btn-primary flex-fill fw-bold" style="padding:10px; border-radius:12px;" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <a class="btn btn-outline-secondary fw-bold" style="padding:10px; border-radius:12px;" href="data-siswa" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>

        <?php if ($hasKelasFilter): ?>
            <div class="premium-card mb-4">
                <div class="card-header-flex">
                    <h3 class="card-title-modern"><i class="bi bi-clipboard-data text-primary me-2"></i> Nilai Kelas <?= guru_ds_h($kelasFilter); ?></h3>
                </div>
                <form method="get" class="row g-3 align-items-end">
                    <input type="hidden" name="kelas" value="<?= guru_ds_h($kelasFilter); ?>">
                    <?php if ($namaFilter !== ''): ?>
                        <input type="hidden" name="nama" value="<?= guru_ds_h($namaFilter); ?>">
                    <?php endif; ?>
                    
                    <div class="col-md-8">
                        <label class="form-label" for="idmapel">Mata Pelajaran yang Diampu</label>
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
                    
                    <div class="col-md-4 d-flex gap-2">
                        <?php if ($selectedMapelAction > 0): ?>
                            <a class="btn btn-success flex-fill fw-bold" style="padding:10px; border-radius:12px;" href="inputnilai?getDetail=<?= (int) $selectedMapelAction; ?>">
                                <i class="bi bi-pencil-square"></i> Input Nilai
                            </a>
                            <a class="btn btn-outline-primary fw-bold" style="padding:10px; border-radius:12px;" target="_blank" href="cetak-nilai-kelas?kelas=<?= rawurlencode($kelasFilter); ?>&idmapel=<?= (int) $selectedMapelAction; ?>">
                                <i class="bi bi-printer"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary flex-fill fw-bold" type="button" disabled style="padding:10px; border-radius:12px;"><i class="bi bi-pencil-square"></i> Input Nilai</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="premium-card">
            <div class="card-header-flex">
                <h3 class="card-title-modern"><i class="bi bi-person-lines-fill text-primary me-2"></i> Daftar Siswa</h3>
            </div>
            
            <?php if (empty($kelasOptions)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                    <div class="empty-title mb-1">Belum ada kelas terkait</div>
                    <div>Anda belum memiliki kelas mengajar atau wali kelas di database.</div>
                </div>
            <?php elseif (!$hasKelasFilter): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-funnel"></i></div>
                    <div class="empty-title mb-1">Pilih Kelas</div>
                    <div>Gunakan filter di atas untuk melihat daftar siswa di kelas tertentu.</div>
                </div>
            <?php elseif (empty($students)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-search"></i></div>
                    <div class="empty-title mb-1">Siswa tidak ditemukan</div>
                    <div>Tidak ada data siswa yang cocok dengan kriteria pencarian Anda.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="border-radius:12px; border:1px solid #f1f5f9;">
                    <table class="table table-modern mb-0">
                        <thead>
                        <tr>
                            <th style="width:64px;">No</th>
                            <th>Nama Siswa</th>
                            <th>No Induk / NISN</th>
                            <th>Kelas</th>
                            <th>No WA</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?= $index + 1; ?></td>
                                <td>
                                    <div class="fw-bold" style="color:#0f172a;"><?= guru_ds_h($student['nama_siswa'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= guru_ds_h($student['no_induk'] ?? ''); ?></div>
                                    <div style="font-size:0.75rem; color:#94a3b8;"><?= guru_ds_h($student['nisn'] ?? '-'); ?></div>
                                </td>
                                <td><span class="kelas-badge"><?= guru_ds_h($student['kelas'] ?? ''); ?></span></td>
                                <td><span class="text-muted"><i class="bi bi-telephone me-1"></i> <?= guru_ds_h($student['no_wa'] ?: '-'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>


<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>

