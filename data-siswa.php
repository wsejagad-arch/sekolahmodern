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
    $qToggle = @mysqli_query($conn, "UPDATE tbl_pengaturan SET nilai='$izinBaruGlobal' WHERE kunci='izin_edit_profil' AND ({$tenantPengaturan} OR id_sekolah IS NULL OR id_sekolah=0)");
    if ($qToggle) {
        $_SESSION['_izin_edit_notice'] = $izinBaruGlobal === '1'
            ? 'Izin edit profil global berhasil dibuka. Semua siswa kini dapat mengedit profil.'
            : 'Izin edit profil global berhasil dikunci. Semua siswa kini tidak dapat mengedit profil.';
        $_SESSION['_izin_edit_notice_type'] = 'success';
        
        // Memastikan cache di refresh dengan me-reload halaman
        header("Location: " . $returnUrl);
        exit;
    }
}

$izinEditNotice = $_SESSION['_izin_edit_notice'] ?? '';
$izinEditNoticeType = $_SESSION['_izin_edit_notice_type'] ?? 'success';
unset($_SESSION['_izin_edit_notice'], $_SESSION['_izin_edit_notice_type']);

$qIzinGlobal = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil' AND ({$tenantPengaturan} OR id_sekolah IS NULL OR id_sekolah=0) ORDER BY id_sekolah DESC LIMIT 1");
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


<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Siswa</h1>
        <div>
            <a href="?page=tambah-siswa" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Data
            </a>
            <a href="export-siswa.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Data
            </a>
            <a href="?page=import-siswa" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-file-upload fa-sm text-white-50"></i> Import Data
            </a>
        </div>
    </div>

    <!-- Alert / Notice -->
    <?php if ($izinEditNotice !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($izinEditNoticeType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($izinEditNotice); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-9 mb-3 mb-md-0">
                    <form method="get" action="home.php" class="form-row">
                        <input type="hidden" name="page" value="data-siswa">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <select name="kelas" class="form-control">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelasOptions as $kelasItem): ?>
                                    <option value="<?= htmlspecialchars($kelasItem['kelas']); ?>" <?= $kelasFilter === $kelasItem['kelas'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($kelasItem['kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($namaFilter); ?>" placeholder="Cari nama...">
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <input type="text" name="wali" class="form-control" value="<?= htmlspecialchars($waliFilter); ?>" placeholder="Cari wali kelas...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Filter</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-3 text-md-right">
                    <form method="post" action="home.php?page=data-siswa" class="mb-0">
                        <input type="hidden" name="global_toggle_izin_edit" value="1">
                        <input type="hidden" name="izin_global_baru" value="<?= $izinEditGlobal === 1 ? '0' : '1'; ?>">
                        <input type="hidden" name="_return_url" value="home.php?page=data-siswa&kelas=<?= urlencode($kelasFilter); ?>&nama=<?= urlencode($namaFilter); ?>&wali=<?= urlencode($waliFilter); ?>">
                        <?php if ($izinEditGlobal === 1): ?>
                            <button type="submit" class="btn btn-danger btn-sm shadow-sm" title="Kunci izin edit profil global">
                                <i class="fas fa-lock mr-1"></i>Kunci Profil
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success btn-sm shadow-sm" title="Buka izin edit profil global">
                                <i class="fas fa-unlock mr-1"></i>Buka Profil
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="mt-3 text-muted small">
                <strong>Info:</strong> Tahun Ajaran: <?= htmlspecialchars($tahunAjaran); ?> | Wali Kelas: <?= htmlspecialchars($waliHeader); ?>
            </div>
        </div>
    </div>

    <!-- Main Data Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Data Siswa <?= htmlspecialchars($kelasHeader); ?></h6>
            <span class="badge badge-primary px-2 py-1"><?= (int) $totalRows; ?> Data</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px;">NO.</th>
                            <th>NIS</th>
                            <th>NAMA SISWA</th>
                            <th class="text-center">KELAS</th>
                            <th>NO. HP</th>
                            <th class="text-center">IZIN EDIT</th>
                            <th class="text-center" style="min-width:140px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                                        <div>Tidak ada data siswa yang cocok dengan filter</div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($rows as $data): 
                            ?>
                                <tr>
                                    <td class="text-center align-middle"><?= $no++; ?></td>
                                    <td class="align-middle"><?= htmlspecialchars($data['no_induk']); ?></td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-dark"><?= htmlspecialchars($data['nama_siswa']); ?></div>
                                        <?php if (!empty($data['nisn'])): ?>
                                            <div class="small text-muted">NISN: <?= htmlspecialchars($data['nisn']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-info px-2 py-1"><?= htmlspecialchars($data['kelas'] ?? '-'); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if (!empty($data['no_wa'])): ?>
                                            <?php $cleanHp = preg_replace('/\D/', '', (string) $data['no_wa']); ?>
                                            <?php if ($cleanHp !== ''): ?>
                                                <a href="https://wa.me/62<?= ltrim($cleanHp, '0'); ?>" target="_blank" class="text-success text-decoration-none">
                                                    <i class="fab fa-whatsapp mr-1"></i><?= htmlspecialchars($data['no_wa']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if ($izinEditGlobal === 1): ?>
                                            <span class="badge badge-success px-2 py-1">Dibuka</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary px-2 py-1">Dikunci</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <a class="btn btn-sm btn-circle btn-primary" href="detail-profil-siswa.php?no_induk=<?= urlencode($data['no_induk']); ?>" title="Lihat Profil"><i class="fas fa-info"></i></a>
                                        <a class="btn btn-sm btn-circle btn-info" href="?page=edit-siswa&no_induk=<?= urlencode($data['no_induk']); ?>" title="Edit Data"><i class="fas fa-edit"></i></a>
                                        <?php
                                            $return_url_siswa = '?page=data-siswa&kelas=' . urlencode($kelasFilter) . '&nama=' . urlencode($namaFilter) . '&wali=' . urlencode($waliFilter);
                                        ?>
                                        <a href="delete-siswa.php?no_induk=<?= urlencode($data['no_induk']); ?>&return_url=<?= urlencode($return_url_siswa); ?>"
                                           class="btn btn-sm btn-circle btn-danger"
                                           title="Hapus Data"
                                           onclick="return confirm('Yakin ingin menghapus data siswa <?= htmlspecialchars($data['nama_siswa'], ENT_QUOTES); ?> (NIS: <?= htmlspecialchars($data['no_induk'], ENT_QUOTES); ?>)? Semua data terkait akan ikut terhapus!');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


