<?php
if (!isset($_SESSION['username'])) {
    header('location: index.php?haruslogin');
    exit;
} elseif ($hakakses != 1) { ?>
    <script>
        window.location = '404.html';
    </script>
<?php
    exit;
}

if (!function_exists('ds_table_exists')) {
    function ds_table_exists($conn, $tableName)
    {
        $safeTable = mysqli_real_escape_string($conn, $tableName);
        $result = @mysqli_query($conn, "SHOW TABLES LIKE '{$safeTable}'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('ds_school_year_label')) {
    function ds_school_year_label()
    {
        $currentYear = (int) date('Y');
        if ((int) date('n') >= 7) {
            return $currentYear . '/' . ($currentYear + 1);
        }
        return ($currentYear - 1) . '/' . $currentYear;
    }
}

include 'koneksi.php';

$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantSiswa = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? "s.id_sekolah={$tenantId}" : "1=1";
$tenantKelas = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_kelas', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantKelasAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_kelas', 'id_sekolah') ? "k.id_sekolah={$tenantId}" : "1=1";
$tenantWaliAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_wali_kelas', 'id_sekolah') ? "wk.id_sekolah={$tenantId}" : "1=1";
$tenantTa = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_thn_ajaran', 'id_sekolah') ? "WHERE id_sekolah={$tenantId}" : "";
$tenantPengaturan = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_pengaturan', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

$izinEditNotice = '';
$izinEditNoticeType = 'success';
$izinEditGlobal = 0;

if (!function_exists('ds_safe_redirect')) {
    function ds_safe_redirect($url)
    {
        $safeUrl = (string) $url;
        if (!headers_sent()) {
            header('Location: ' . $safeUrl);
        } else {
            echo '<script>window.location.href=' . json_encode($safeUrl) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($safeUrl, ENT_QUOTES) . '"></noscript>';
        }
        exit;
    }
}

// Gunakan pengaturan global: sekali buka, semua siswa dapat mengedit profil.
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
    kunci VARCHAR(60) PRIMARY KEY,
    nilai VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (function_exists('mt_add_school_column') && $conn instanceof mysqli) {
    mt_add_school_column($conn, 'tbl_pengaturan');
}
@mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai" . (strpos($tenantPengaturan, 'id_sekolah=') === 0 ? ",id_sekolah" : "") . ") VALUES ('izin_edit_profil','0'" . (strpos($tenantPengaturan, 'id_sekolah=') === 0 ? ",{$tenantId}" : "") . ")");

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['global_toggle_izin_edit'])) {
    $returnUrl = trim($_POST['_return_url'] ?? 'home.php?page=data-siswa');
    if ($returnUrl === '' || stripos($returnUrl, 'home.php?page=data-siswa') !== 0) {
        $returnUrl = 'home.php?page=data-siswa';
    }

    $izinBaruGlobal = ($_POST['izin_global_baru'] ?? '') === '1' ? '1' : '0';
    $qToggle = @mysqli_query($conn, "UPDATE tbl_pengaturan SET nilai='$izinBaruGlobal' WHERE kunci='izin_edit_profil' AND {$tenantPengaturan}");
    if ($qToggle) {
        $_SESSION['_izin_edit_notice'] = $izinBaruGlobal === '1'
            ? 'Izin edit profil global berhasil dibuka. Semua siswa kini dapat mengedit profil.'
            : 'Izin edit profil global berhasil dikunci. Semua siswa kini tidak dapat mengedit profil.';
        $_SESSION['_izin_edit_notice_type'] = 'success';
    } else {
        $_SESSION['_izin_edit_notice'] = 'Gagal mengubah izin edit profil global.';
        $_SESSION['_izin_edit_notice_type'] = 'danger';
    }

    ds_safe_redirect($returnUrl);
}

$izinEditNotice = $_SESSION['_izin_edit_notice'] ?? '';
$izinEditNoticeType = $_SESSION['_izin_edit_notice_type'] ?? 'success';
unset($_SESSION['_izin_edit_notice'], $_SESSION['_izin_edit_notice_type']);

$qIzinGlobal = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil' AND {$tenantPengaturan} LIMIT 1");
if ($qIzinGlobal && ($rIzinGlobal = mysqli_fetch_assoc($qIzinGlobal))) {
    $izinEditGlobal = ((string)($rIzinGlobal['nilai'] ?? '0') === '1') ? 1 : 0;
}

$hasKelasTable = ds_table_exists($conn, 'tbl_kelas');
$hasWaliTable = ds_table_exists($conn, 'tbl_wali_kelas');
$hasTaTable = ds_table_exists($conn, 'tbl_thn_ajaran');

$kelasFilter = trim($_GET['kelas'] ?? '');
$namaFilter = trim($_GET['nama'] ?? '');
$waliFilter = trim($_GET['wali'] ?? '');

$kelasFilterEsc = mysqli_real_escape_string($conn, $kelasFilter);
$namaFilterEsc = mysqli_real_escape_string($conn, $namaFilter);
$waliFilterEsc = mysqli_real_escape_string($conn, $waliFilter);

$tahunAjaran = ds_school_year_label();
if ($hasTaTable) {
    $resTa = @mysqli_query($conn, "SELECT tahun FROM tbl_thn_ajaran {$tenantTa} ORDER BY id_thn DESC LIMIT 1");
    if ($resTa && ($rowTa = mysqli_fetch_assoc($resTa)) && !empty($rowTa['tahun'])) {
        $tahunAjaran = $rowTa['tahun'];
    }
}

$kelasOptions = [];
if ($hasKelasTable) {
    $sqlKelas = @mysqli_query($conn, "SELECT id_kelas, kelas FROM tbl_kelas WHERE {$tenantKelas} ORDER BY kelas ASC");
    if ($sqlKelas) {
        while ($rowKelas = mysqli_fetch_assoc($sqlKelas)) {
            $kelasOptions[] = $rowKelas;
        }
    }
}

if (empty($kelasOptions)) {
    $sqlDistinct = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa s WHERE {$tenantSiswa} AND s.status = 'Aktif' AND kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
    if ($sqlDistinct) {
        while ($rowKelas = mysqli_fetch_assoc($sqlDistinct)) {
            $kelasOptions[] = [
                'id_kelas' => null,
                'kelas' => $rowKelas['kelas'],
            ];
        }
    }
}

$kelasHeader = 'Semua Kelas';
$waliHeader = 'Gabungan data';
if ($kelasFilter !== '') {
    $kelasHeader = $kelasFilter;
    if ($hasKelasTable && $hasWaliTable) {
        $sqlMeta = "SELECT k.kelas, COALESCE(NULLIF(wk.nama_wali, ''), '-') AS nama_wali
                    FROM tbl_kelas k
                    LEFT JOIN tbl_wali_kelas wk ON wk.id_kelas = k.id_kelas AND {$tenantWaliAlias}
                    WHERE {$tenantKelasAlias} AND TRIM(k.kelas) = '{$kelasFilterEsc}'
                    LIMIT 1";
        $resMeta = @mysqli_query($conn, $sqlMeta);
        if ($resMeta && ($rowMeta = mysqli_fetch_assoc($resMeta))) {
            $kelasHeader = $rowMeta['kelas'] ?: $kelasHeader;
            $waliHeader = $rowMeta['nama_wali'] ?: '-';
        }
    }
}

$joinKelas = $hasKelasTable ? "LEFT JOIN tbl_kelas k ON TRIM(k.kelas) = TRIM(s.kelas) AND {$tenantKelasAlias}" : '';
$joinWali = ($hasKelasTable && $hasWaliTable) ? "LEFT JOIN tbl_wali_kelas wk ON wk.id_kelas = k.id_kelas AND {$tenantWaliAlias}" : '';
$waliSelect = ($hasKelasTable && $hasWaliTable)
    ? "COALESCE(NULLIF(wk.nama_wali, ''), NULLIF(k.wali_kelas, ''), '-') AS nama_wali"
    : "'-' AS nama_wali";
$waliFilterExpr = ($hasKelasTable && $hasWaliTable)
    ? "COALESCE(NULLIF(wk.nama_wali, ''), NULLIF(k.wali_kelas, ''), '-')"
    : "'-'";

$conditions = [$tenantSiswa, "s.status = 'Aktif'"];
if ($kelasFilter !== '') {
    $conditions[] = "TRIM(s.kelas) = '{$kelasFilterEsc}'";
}
if ($namaFilter !== '') {
    $conditions[] = "s.nama_siswa LIKE '%{$namaFilterEsc}%'";
}
if ($waliFilter !== '') {
    $conditions[] = "{$waliFilterExpr} LIKE '%{$waliFilterEsc}%'";
}

$whereSql = '';
if (!empty($conditions)) {
    $whereSql = 'WHERE ' . implode(' AND ', $conditions);
}

$sqlData = "SELECT s.no_induk, s.nisn, s.nama_siswa, s.kelas, s.no_wa, {$waliSelect}
            FROM tbl_siswa s
            {$joinKelas}
            {$joinWali}
            {$whereSql}
            ORDER BY s.kelas ASC, s.nama_siswa ASC";

$resData = @mysqli_query($conn, $sqlData);
$rows = [];
if ($resData) {
    while ($row = mysqli_fetch_assoc($resData)) {
        $rows[] = $row;
    }
}
$totalRows = count($rows);
$totalKelas = count($kelasOptions);
$activeFilterLabel = $kelasFilter !== '' ? $kelasFilter : 'Semua kelas';
?>

<style>
    .student-roster-shell {
        --roster-primary: #2563eb;
        --roster-primary-dark: #1d4ed8;
        --roster-surface: #ffffff;
        --roster-muted: #64748b;
        --roster-border: #e2e8f0;
        --roster-bg: #f8fafc;
        --roster-soft: #eff6ff;
        --roster-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        color: #0f172a;
        width: 100%;
        max-width: 100%;
        min-height: calc(100vh - 210px);
        display: flex;
        flex-direction: column;
        padding-bottom: 1.25rem;
    }

    .student-roster-shell .hero-card {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 50%, #2563eb 100%);
        color: #fff;
        border: 0;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: var(--roster-shadow);
        position: relative;
        width: 100%;
    }

    .student-roster-shell .hero-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 38%), radial-gradient(circle at bottom left, rgba(255, 255, 255, 0.1), transparent 32%);
        pointer-events: none;
    }

    .student-roster-shell .hero-body {
        position: relative;
        z-index: 1;
        padding: 1.5rem;
    }

    .student-roster-shell .hero-title {
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem 0;
    }

    .student-roster-shell .hero-subtitle {
        margin: 0;
        color: rgba(255, 255, 255, 0.82);
        max-width: 56rem;
    }

    .student-roster-shell .metric-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.9rem;
        margin-top: 1.1rem;
    }

    .student-roster-shell .metric-card {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 18px;
        padding: 0.9rem 1rem;
        backdrop-filter: blur(10px);
    }

    .student-roster-shell .metric-label {
        display: block;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.72);
        margin-bottom: 0.35rem;
    }

    .student-roster-shell .metric-value {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .student-roster-shell .filter-card,
    .student-roster-shell .identity-card,
    .student-roster-shell .table-card {
        background: var(--roster-surface);
        border: 1px solid var(--roster-border);
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
    }

    .student-roster-shell .filter-card {
        padding: 1rem 1rem 0.85rem;
    }

    .student-roster-shell .section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: -0.01em;
        color: #0f172a;
    }

    .student-roster-shell .section-subtitle {
        margin: 0.2rem 0 0;
        color: var(--roster-muted);
        font-size: 0.875rem;
    }

    .student-roster-shell .input-modern,
    .student-roster-shell .select-modern {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #fff;
        padding: 0.72rem 0.9rem;
        transition: box-shadow 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }

    .student-roster-shell .input-modern:focus,
    .student-roster-shell .select-modern:focus {
        outline: none;
        border-color: var(--roster-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .student-roster-shell .identity-card {
        padding: 1rem;
        margin-top: 1rem;
    }

    .student-roster-shell .identity-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 0.9rem;
    }

    .student-roster-shell .identity-pill {
        background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 0.85rem 0.95rem;
        min-height: 86px;
    }

    .student-roster-shell .identity-pill .label {
        display: block;
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #3b82f6;
        margin-bottom: 0.35rem;
    }

    .student-roster-shell .identity-pill .value {
        font-size: 0.97rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
        word-break: break-word;
    }

    .student-roster-shell .table-card {
        margin-top: 1rem;
        overflow: hidden;
        width: 100%;
    }

    .student-roster-shell .table-headbar {
        padding: 1rem 1rem 0.5rem;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .student-roster-shell .table-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.5rem 0.8rem;
        border-radius: 999px;
        background: var(--roster-soft);
        color: var(--roster-primary-dark);
        font-weight: 700;
        font-size: 0.88rem;
    }

    .student-roster-shell .table-wrap {
        padding: 0 1rem 1rem;
        width: 100%;
    }

    .student-roster-shell table thead th {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a !important;
        font-size: 0.82rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .student-roster-shell table tbody tr:hover {
        background: #f8fbff;
    }

    .student-roster-shell .student-name {
        font-weight: 800;
        color: #0f172a;
    }

    .student-roster-shell .student-meta {
        display: block;
        font-size: 0.8rem;
        color: var(--roster-muted);
        margin-top: 0.12rem;
    }

    .student-roster-shell .table-responsive {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        overflow-x: auto;
        color: #16a34a;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }

    .student-roster-shell .empty-state {
        padding: 2.75rem 1rem;
        text-align: center;
        color: var(--roster-muted);
    }

    .student-roster-shell .empty-state i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 0.6rem;
        color: #cbd5e1;
    }

    .student-roster-shell .filter-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .student-roster-shell .mobile-student-list {
        display: none;
    }

    .student-roster-shell .mobile-student-card {
        background: #fff;
        border: 1px solid var(--roster-border);
        border-radius: 18px;
        padding: 0.95rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        margin-bottom: 0.9rem;
    }

    .student-roster-shell .mobile-student-card .card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.8rem;
    }

    .student-roster-shell .mobile-student-card .name {
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        margin-bottom: 0.15rem;
    }

    .student-roster-shell .mobile-student-card .meta {
        font-size: 0.82rem;
        color: var(--roster-muted);
    }

    .student-roster-shell .mobile-student-card .kv-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
        margin-bottom: 0.85rem;
    }

    .student-roster-shell .mobile-student-card .kv {
        background: #f8fbff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.65rem 0.75rem;
    }

    .student-roster-shell .mobile-student-card .kv .label {
        display: block;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #3b82f6;
        margin-bottom: 0.2rem;
    }

    .student-roster-shell .mobile-student-card .kv .value {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    .student-roster-shell .mobile-student-card .actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .student-roster-shell .mobile-student-card .actions .btn {
        flex: 1 1 140px;
        border-radius: 999px;
        font-weight: 700;
    }

    .student-roster-shell .btn-modern {
        border-radius: 999px;
        padding: 0.7rem 1rem;
        font-weight: 700;
        border: 0;
    }

    @media (max-width: 992px) {

        .student-roster-shell .metric-grid,
        .student-roster-shell .identity-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .student-roster-shell {
            min-height: calc(100vh - 190px);
        }
    }

    @media (max-width: 576px) {

        .student-roster-shell {
            min-height: calc(100vh - 165px);
            padding-bottom: 0.75rem;
        }

        .student-roster-shell .hero-body,
        .student-roster-shell .filter-card,
        .student-roster-shell .identity-card,
        .student-roster-shell .table-headbar,
        .student-roster-shell .table-wrap {
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .student-roster-shell .metric-grid,
        .student-roster-shell .identity-grid {
            grid-template-columns: 1fr;
        }

        .student-roster-shell .filter-actions {
            width: 100%;
        }

        .student-roster-shell .filter-actions .btn-modern,
        .student-roster-shell .filter-actions .btn {
            width: 100%;
        }

        .student-roster-shell .table-responsive {
            border-radius: 14px;
        }

        .student-roster-shell table {
            min-width: 820px;
        }

        .student-roster-shell .table-headbar {
            align-items: flex-start;
        }

        .student-roster-shell .table-wrap {
            display: block;
        }

        .student-roster-shell .mobile-student-list {
            display: block;
            padding: 0 0.95rem 1rem;
        }

        .student-roster-shell .mobile-student-card .kv-grid {
            grid-template-columns: 1fr;
        }

        .student-roster-shell .mobile-student-card .actions .btn {
            width: 100%;
            flex-basis: 100%;
        }

        .student-roster-shell .table-badge,
        .student-roster-shell .btn-modern,
        .student-roster-shell .filter-actions .btn,
        .student-roster-shell .filter-actions .btn-modern {
            width: 100%;
        }
    }

    @media (max-width: 420px) {
        .student-roster-shell .hero-title {
            font-size: 1.2rem;
        }

        .student-roster-shell .hero-subtitle {
            font-size: 0.92rem;
        }

        .student-roster-shell .table-badge {
            width: 100%;
            justify-content: center;
        }

        .student-roster-shell .hero-card {
            border-radius: 18px;
        }

        .student-roster-shell .identity-pill {
            min-height: unset;
        }
    }
</style>

<div class="container-fluid student-roster-shell px-2 px-md-3 px-lg-4">
    <div class="hero-card mb-4">
        <div class="hero-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between" style="gap: 1rem;">
                <div>
                    <h2 class="hero-title">Data Siswa Per Kelas</h2>
                    <p class="hero-subtitle">Tabel siswa global dan per kelas dengan filter kelas, nama siswa, serta nama wali kelas. Tampilan ini dibuat ringkas agar cepat dipakai untuk cek identitas kelas.</p>
                </div>
                <div class="filter-actions">
                    <a href="?page=tambah-siswa" class="btn btn-light btn-modern text-primary"><i class="fas fa-plus mr-1"></i> Tambah Data</a>
                    <a href="export-siswa.php" class="btn btn-success btn-modern"><i class="fas fa-file-excel mr-1"></i> Export</a>
                    <a href="?page=import-siswa" class="btn btn-warning btn-modern text-white"><i class="fas fa-file-upload mr-1"></i> Import</a>
                </div>
            </div>

            <div class="metric-grid">
                <div class="metric-card">
                    <span class="metric-label">Header</span>
                    <div class="metric-value"><?php echo htmlspecialchars($kelasFilter === '' ? 'Data Global' : 'Kelas Terpilih'); ?></div>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Kelas</span>
                    <div class="metric-value"><?php echo htmlspecialchars($activeFilterLabel); ?></div>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Tahun Ajaran</span>
                    <div class="metric-value"><?php echo htmlspecialchars($tahunAjaran); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-card mb-3">
        <?php if ($izinEditNotice !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($izinEditNoticeType); ?> mb-3" role="alert">
                <?php echo htmlspecialchars($izinEditNotice); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap:.75rem;">
            <div class="small text-muted">
                Izin edit profil diterapkan secara global ke seluruh siswa.
            </div>
            <form method="post" action="home.php?page=data-siswa" class="mb-0 w-100 w-md-auto">
                <input type="hidden" name="global_toggle_izin_edit" value="1">
                <input type="hidden" name="izin_global_baru" value="<?php echo $izinEditGlobal === 1 ? '0' : '1'; ?>">
                <input type="hidden" name="_return_url" value="home.php?page=data-siswa&kelas=<?php echo urlencode($kelasFilter); ?>&nama=<?php echo urlencode($namaFilter); ?>&wali=<?php echo urlencode($waliFilter); ?>">
                <?php if ($izinEditGlobal === 1): ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger" id="global-izin-edit">
                        <i class="fas fa-lock mr-1"></i>Kunci Global Edit Profil
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-sm btn-outline-success" id="global-izin-edit">
                        <i class="fas fa-unlock mr-1"></i>Buka Global Edit Profil
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <div class="d-flex flex-wrap align-items-end justify-content-between mb-3" style="gap: 1rem;">
            <div>
                <h3 class="section-title">Filter Data</h3>
                <p class="section-subtitle">Pilih kelas, cari nama siswa, atau cari nama wali kelas.</p>
            </div>
            <span class="table-badge"><i class="fas fa-layer-group"></i> <?php echo (int) $totalRows; ?> data tampil</span>
        </div>

        <form method="get" action="home.php" class="mb-0">
            <input type="hidden" name="page" value="data-siswa">
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <label class="form-label font-weight-bold text-dark">Kelas</label>
                    <select name="kelas" class="select-modern">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelasOptions as $kelasItem): ?>
                            <option value="<?php echo htmlspecialchars($kelasItem['kelas']); ?>" <?php echo $kelasFilter === $kelasItem['kelas'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kelasItem['kelas']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4 mb-3">
                    <label class="form-label font-weight-bold text-dark">Nama Siswa</label>
                    <input type="text" name="nama" class="input-modern" value="<?php echo htmlspecialchars($namaFilter); ?>" placeholder="Cari nama siswa...">
                </div>
                <div class="col-lg-4 mb-3">
                    <label class="form-label font-weight-bold text-dark">Nama Wali Kelas</label>
                    <input type="text" name="wali" class="input-modern" value="<?php echo htmlspecialchars($waliFilter); ?>" placeholder="Cari nama wali kelas...">
                </div>
            </div>
            <div class="filter-actions mt-1">
                <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-search mr-1"></i> Terapkan Filter</button>
                <a href="home.php?page=data-siswa" class="btn btn-outline-secondary btn-modern"><i class="fas fa-undo mr-1"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="identity-card">
        <div class="d-flex flex-wrap align-items-start justify-content-between" style="gap: 1rem;">
            <div>
                <h3 class="section-title">Identitas Kelas</h3>
                <p class="section-subtitle">Header ringkas untuk membantu identifikasi data yang sedang dibuka.</p>
            </div>
            <span class="badge badge-primary p-2 px-3">Wali Kelas: <?php echo htmlspecialchars($waliHeader); ?></span>
        </div>

        <div class="identity-grid">
            <div class="identity-pill">
                <span class="label">Header</span>
                <div class="value"><?php echo htmlspecialchars($kelasFilter === '' ? 'Global semua kelas' : 'Kelas terpilih'); ?></div>
            </div>
            <div class="identity-pill">
                <span class="label">Kelas</span>
                <div class="value"><?php echo htmlspecialchars($kelasHeader); ?></div>
            </div>
            <div class="identity-pill">
                <span class="label">Tahun Ajaran</span>
                <div class="value"><?php echo htmlspecialchars($tahunAjaran); ?></div>
            </div>
            <div class="identity-pill">
                <span class="label">Nama Wali Kelas</span>
                <div class="value"><?php echo htmlspecialchars($waliHeader); ?></div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-headbar">
            <div>
                <h3 class="section-title">Tabel Data Siswa</h3>
                <p class="section-subtitle">Kolom utama yang ditampilkan: nomor, NIS, nama siswa, kelas, dan nomor HP.</p>
            </div>
            <span class="table-badge"><i class="fas fa-user-graduate"></i> <?php echo (int) $totalRows; ?> siswa</span>
        </div>

        <div class="table-wrap table-responsive">
            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th style="min-width:140px;">NIS</th>
                        <th style="min-width:240px;">Nama Siswa</th>
                        <th style="min-width:160px;">Kelas</th>
                        <th style="min-width:170px;">Nomor HP</th>
                        <th style="min-width:130px;">Izin Edit</th>
                        <th style="min-width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <div class="font-weight-bold text-dark mb-1">Tidak ada data siswa yang cocok</div>
                                    <div>Coba ubah filter kelas, nama siswa, atau nama wali kelas.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        $returnUrl = 'home.php?page=data-siswa';
                        if ($kelasFilter !== '' || $namaFilter !== '' || $waliFilter !== '') {
                            $returnUrl .= '&kelas=' . urlencode($kelasFilter) . '&nama=' . urlencode($namaFilter) . '&wali=' . urlencode($waliFilter);
                        }
                        foreach ($rows as $data): ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($data['no_induk']); ?></td>
                                <td>
                                    <div class="student-name"><?php echo htmlspecialchars($data['nama_siswa']); ?></div>
                                    <?php if (!empty($data['nisn'])): ?>
                                        <span class="student-meta">NISN: <?php echo htmlspecialchars($data['nisn']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-pill badge-info px-3 py-2"><?php echo htmlspecialchars($data['kelas'] ?? '-'); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($data['no_wa'])): ?>
                                        <?php $cleanHp = preg_replace('/\D/', '', (string) $data['no_wa']); ?>
                                        <?php if ($cleanHp !== ''): ?>
                                            <a class="hp-link" href="https://wa.me/62<?php echo ltrim($cleanHp, '0'); ?>" target="_blank" rel="noopener">
                                                <i class="fab fa-whatsapp mr-1"></i><?php echo htmlspecialchars($data['no_wa']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($izinEditGlobal === 1): ?>
                                        <span class="badge badge-pill badge-success px-3 py-2">Dibuka</span>
                                    <?php else: ?>
                                        <span class="badge badge-pill badge-secondary px-3 py-2">Dikunci</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="detail-profil-siswa.php?no_induk=<?php echo urlencode($data['no_induk']); ?>">
                                        <i class="fas fa-id-card mr-1"></i> Profil
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-student-list">
            <?php if (empty($rows)): ?>
                <div class="mobile-student-card">
                    <div class="empty-state py-3 m-0">
                        <i class="fas fa-folder-open"></i>
                        <div class="font-weight-bold text-dark mb-1">Tidak ada data siswa yang cocok</div>
                        <div>Coba ubah filter kelas, nama siswa, atau nama wali kelas.</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($rows as $index => $data): ?>
                    <div class="mobile-student-card">
                        <div class="card-head">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($data['nama_siswa']); ?></div>
                                <div class="meta">NIS: <?php echo htmlspecialchars($data['no_induk']); ?></div>
                                <?php if (!empty($data['nisn'])): ?>
                                    <div class="meta">NISN: <?php echo htmlspecialchars($data['nisn']); ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="badge badge-pill badge-info px-3 py-2"><?php echo htmlspecialchars($data['kelas'] ?? '-'); ?></span>
                        </div>

                        <div class="kv-grid">
                            <div class="kv">
                                <span class="label">Nomor HP</span>
                                <span class="value">
                                    <?php if (!empty($data['no_wa'])): ?>
                                        <?php $cleanHp = preg_replace('/\D/', '', (string) $data['no_wa']); ?>
                                        <?php if ($cleanHp !== ''): ?>
                                            <a class="hp-link" href="https://wa.me/62<?php echo ltrim($cleanHp, '0'); ?>" target="_blank" rel="noopener">
                                                <?php echo htmlspecialchars($data['no_wa']); ?>
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="kv">
                                <span class="label">Izin Edit</span>
                                <span class="value">
                                    <?php if ($izinEditGlobal === 1): ?>
                                        <span class="badge badge-pill badge-success px-2 py-1">Dibuka</span>
                                    <?php else: ?>
                                        <span class="badge badge-pill badge-secondary px-2 py-1">Dikunci</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="actions">
                            <a class="btn btn-outline-primary btn-sm" href="detail-profil-siswa.php?no_induk=<?php echo urlencode($data['no_induk']); ?>">
                                <i class="fas fa-id-card mr-1"></i> Profil
                            </a>
                            <a class="btn btn-outline-secondary btn-sm" href="?page=data-siswa&kelas=<?php echo urlencode($kelasFilter); ?>&nama=<?php echo urlencode($namaFilter); ?>&wali=<?php echo urlencode($waliFilter); ?>">
                                <i class="fas fa-sync-alt mr-1"></i> Reload
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
