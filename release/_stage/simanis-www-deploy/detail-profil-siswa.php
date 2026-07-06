<?php
require_once __DIR__ . '/bootstrap.php';
require_admin();

date_default_timezone_set('Asia/Jakarta');

if (!function_exists('detail_profile_table_exists')) {
    function detail_profile_table_exists($conn, $tableName)
    {
        $safeTable = mysqli_real_escape_string($conn, $tableName);
        $result = @mysqli_query($conn, "SHOW TABLES LIKE '{$safeTable}'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('detail_profile_column_exists')) {
    function detail_profile_column_exists($conn, $tableName, $columnName)
    {
        static $cache = [];
        $cacheKey = $tableName . '.' . $columnName;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $safeColumn = mysqli_real_escape_string($conn, $columnName);
        $result = @mysqli_query($conn, "SHOW COLUMNS FROM {$tableName} LIKE '{$safeColumn}'");
        $cache[$cacheKey] = $result && mysqli_num_rows($result) > 0;
        return $cache[$cacheKey];
    }
}

if (!function_exists('detail_profile_label')) {
    function detail_profile_label($column)
    {
        $map = [
            'no_induk' => 'No. Induk / NIS',
            'nama_siswa' => 'Nama Siswa',
            'nisn' => 'NISN',
            'kelas' => 'Kelas',
            'status' => 'Status',
            'jabatan' => 'Jabatan',
            'alamat' => 'Alamat',
            'lat' => 'Latitude',
            'lng' => 'Longitude',
            'no_wa' => 'No. WhatsApp',
            'nama_darurat' => 'Nama Keluarga Darurat',
            'no_darurat' => 'No. HP Keluarga',
        ];

        if (isset($map[$column])) {
            return $map[$column];
        }

        return ucwords(str_replace(['_', '-'], ' ', $column));
    }
}

if (!function_exists('detail_profile_value')) {
    function detail_profile_value($column, $value)
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($column === 'alamat') {
            return nl2br(htmlspecialchars((string) $value));
        }

        return htmlspecialchars((string) $value);
    }
}

if (!function_exists('detail_profile_whatsapp_link')) {
    function detail_profile_whatsapp_link($number)
    {
        $clean = preg_replace('/\D/', '', (string) $number);
        if ($clean === '') {
            return '';
        }

        return 'https://wa.me/62' . ltrim($clean, '0');
    }
}

$lembaga = function_exists('data_lembaga') ? data_lembaga() : [];
$noInduk = trim((string) ($_GET['no_induk'] ?? $_POST['no_induk'] ?? ''));

if ($noInduk === '') {
    echo 'Parameter tidak valid.';
    exit;
}

$noIndukEsc = mysqli_real_escape_string($conn, $noInduk);

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
  kunci VARCHAR(60) PRIMARY KEY,
  nilai VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai) VALUES ('izin_edit_profil','0')");

$izinEditGlobal = 0;
$qIzinGlobal = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil' LIMIT 1");
if ($qIzinGlobal && ($rowIzinGlobal = mysqli_fetch_assoc($qIzinGlobal))) {
    $izinEditGlobal = ((string) ($rowIzinGlobal['nilai'] ?? '0') === '1') ? 1 : 0;
}

$editMode = true;
$notice = $_SESSION['_detail_profile_notice'] ?? '';
$noticeType = $_SESSION['_detail_profile_notice_type'] ?? 'info';
unset($_SESSION['_detail_profile_notice'], $_SESSION['_detail_profile_notice_type']);

if (!detail_profile_table_exists($conn, 'tbl_siswa')) {
    echo 'Tabel siswa tidak ditemukan.';
    exit;
}

$schemaCols = [];
$resCols = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_siswa');
if ($resCols) {
    while ($rowCol = mysqli_fetch_assoc($resCols)) {
        $schemaCols[] = $rowCol['Field'];
    }
}

$migrateCols = [
    'nisn' => "ALTER TABLE tbl_siswa ADD COLUMN nisn VARCHAR(20) DEFAULT NULL AFTER no_induk",
    'alamat' => "ALTER TABLE tbl_siswa ADD COLUMN alamat TEXT DEFAULT NULL",
    'lat' => "ALTER TABLE tbl_siswa ADD COLUMN lat VARCHAR(30) DEFAULT NULL",
    'lng' => "ALTER TABLE tbl_siswa ADD COLUMN lng VARCHAR(30) DEFAULT NULL",
    'no_wa' => "ALTER TABLE tbl_siswa ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL",
    'no_darurat' => "ALTER TABLE tbl_siswa ADD COLUMN no_darurat VARCHAR(20) DEFAULT NULL",
    'nama_darurat' => "ALTER TABLE tbl_siswa ADD COLUMN nama_darurat VARCHAR(100) DEFAULT NULL",
];

foreach ($migrateCols as $column => $sqlAlter) {
    if (!in_array($column, $schemaCols, true)) {
        @mysqli_query($conn, $sqlAlter);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_save_admin_profile'])) {
    $fieldsToUpdate = [
        'nama_siswa' => trim((string) ($_POST['nama_siswa'] ?? '')),
        'nisn' => trim((string) ($_POST['nisn'] ?? '')),
        'kelas' => trim((string) ($_POST['kelas'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? '')),
        'jabatan' => trim((string) ($_POST['jabatan'] ?? '')),
        'alamat' => trim((string) ($_POST['alamat'] ?? '')),
        'lat' => trim((string) ($_POST['lat'] ?? '')),
        'lng' => trim((string) ($_POST['lng'] ?? '')),
        'no_wa' => trim((string) ($_POST['no_wa'] ?? '')),
        'nama_darurat' => trim((string) ($_POST['nama_darurat'] ?? '')),
        'no_darurat' => trim((string) ($_POST['no_darurat'] ?? '')),
    ];

    $updateParts = [];
    foreach ($fieldsToUpdate as $field => $value) {
        if (detail_profile_column_exists($conn, 'tbl_siswa', $field)) {
            $safeValue = mysqli_real_escape_string($conn, $value);
            $updateParts[] = "{$field}='{$safeValue}'";
        }
    }

    if (!empty($updateParts)) {
        $sqlUpdate = "UPDATE tbl_siswa SET " . implode(', ', $updateParts) . " WHERE no_induk='$noIndukEsc' LIMIT 1";
        if (@mysqli_query($conn, $sqlUpdate)) {
            $_SESSION['_detail_profile_notice'] = 'Data siswa berhasil diperbarui oleh admin.';
            $_SESSION['_detail_profile_notice_type'] = 'success';
        } else {
            $_SESSION['_detail_profile_notice'] = 'Gagal memperbarui data siswa.';
            $_SESSION['_detail_profile_notice_type'] = 'danger';
        }
    } else {
        $_SESSION['_detail_profile_notice'] = 'Tidak ada kolom yang bisa diperbarui.';
        $_SESSION['_detail_profile_notice_type'] = 'warning';
    }

    header('Location: detail-profil-siswa.php?no_induk=' . urlencode($noInduk) . '&edit=1');
    exit;
}

$studentQuery = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$noIndukEsc' LIMIT 1");
$student = $studentQuery ? mysqli_fetch_assoc($studentQuery) : null;

if (!$student) {
    echo 'Siswa tidak ditemukan.';
    exit;
}

$displayColumns = [];
foreach ($student as $column => $value) {
    if (in_array($column, ['password', 'token', 'remember_token'], true)) {
        continue;
    }
    $displayColumns[] = $column;
}

$editableFields = [
    'nama_siswa' => ['label' => 'Nama Siswa', 'type' => 'text'],
    'nisn' => ['label' => 'NISN', 'type' => 'text'],
    'kelas' => ['label' => 'Kelas', 'type' => 'text'],
    'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']],
    'jabatan' => ['label' => 'Jabatan', 'type' => 'text'],
    'alamat' => ['label' => 'Alamat', 'type' => 'textarea'],
    'lat' => ['label' => 'Latitude', 'type' => 'text'],
    'lng' => ['label' => 'Longitude', 'type' => 'text'],
    'no_wa' => ['label' => 'No. WhatsApp', 'type' => 'text'],
    'nama_darurat' => ['label' => 'Nama Keluarga Darurat', 'type' => 'text'],
    'no_darurat' => ['label' => 'No. HP Keluarga', 'type' => 'text'],
];

foreach ($editableFields as $field => $meta) {
    if (!detail_profile_column_exists($conn, 'tbl_siswa', $field)) {
        unset($editableFields[$field]);
    }
}
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include 'topbar.php'; ?>

        <div class="container-fluid profile-detail-shell d-flex flex-column">
            <style>
                .profile-detail-shell {
                    --detail-bg: #f8fafc;
                    --detail-surface: #ffffff;
                    --detail-border: #e2e8f0;
                    --detail-muted: #64748b;
                    --detail-primary: #2563eb;
                    --detail-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
                    width: 100%;
                    gap: 1rem;
                    padding: 0 0.75rem 1.5rem;
                    /* max-width removed */
                    min-height: calc(100vh - 190px);
                }

                body {
                    background: var(--detail-bg);
                }

                #content-wrapper {
                    min-height: 100vh;
                }

                #content {
                    display: flex;
                    flex: 1 0 auto;
                    flex-direction: column;
                }

                .profile-card,
                .profile-table-card,
                .profile-edit-card {
                    background: var(--detail-surface);
                    border: 1px solid var(--detail-border);
                    border-radius: 20px;
                    box-shadow: var(--detail-shadow);
                }

                .profile-hero {
                    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #4f46e5 100%);
                    color: #fff;
                    border-radius: 20px;
                    padding: 1.35rem;
                    box-shadow: var(--detail-shadow);
                }

                .student-avatar {
                    width: 76px;
                    height: 76px;
                    border-radius: 999px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(255, 255, 255, 0.18);
                    border: 2px solid rgba(255, 255, 255, 0.35);
                    font-size: 2rem;
                    font-weight: 800;
                    flex: 0 0 auto;
                }

                .profile-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.35rem;
                    margin-top: 0.45rem;
                }

                .detail-label {
                    font-size: 0.72rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.06em;
                    color: var(--detail-muted);
                    margin-bottom: 0.25rem;
                }

                .detail-value {
                    font-size: 0.98rem;
                    font-weight: 600;
                    color: #0f172a;
                    word-break: break-word;
                }

                .status-banner {
                    display: flex;
                    align-items: flex-start;
                    gap: 0.85rem;
                }

                .status-banner .status-icon {
                    width: 2.35rem;
                    height: 2.35rem;
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    flex: 0 0 auto;
                    color: #fff;
                }

                .status-banner .status-copy {
                    flex: 1;
                    min-width: 0;
                }

                .status-banner .status-title {
                    font-weight: 800;
                    margin: 0 0 0.1rem 0;
                    color: #0f172a;
                }

                .status-banner .status-text {
                    margin: 0;
                    color: var(--detail-muted);
                    font-size: 0.9rem;
                    line-height: 1.45;
                }

                .detail-action-row {
                    display: flex;
                    gap: 0.5rem;
                    flex-wrap: wrap;
                    align-items: center;
                }

                .detail-badges {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.35rem;
                    margin-top: 0.4rem;
                }

                .info-note {
                    font-size: 0.82rem;
                    color: var(--detail-muted);
                    margin-top: 0.35rem;
                }

                .detail-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 0.85rem;
                }

                .detail-pill {
                    background: #f8fbff;
                    border: 1px solid #e2e8f0;
                    border-radius: 16px;
                    padding: 0.85rem 0.9rem;
                }

                .detail-table-wrap {
                    overflow: auto;
                    border-radius: 16px;
                    border: 1px solid var(--detail-border);
                }

                .detail-table {
                    margin-bottom: 0;
                }

                .detail-table th {
                    width: 220px;
                    background: #f8fafc;
                    white-space: nowrap;
                }

                .form-section-title {
                    font-size: 1rem;
                    font-weight: 800;
                    color: #0f172a;
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .profile-input,
                .profile-select,
                .profile-textarea {
                    border-radius: 0.9rem;
                    border: 1.5px solid #dbe4f0;
                    padding: 0.7rem 0.9rem;
                }

                .profile-input:focus,
                .profile-select:focus,
                .profile-textarea:focus {
                    border-color: var(--detail-primary);
                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
                }

                .readonly-box {
                    background: #f8fafc;
                    border: 1px dashed #cbd5e1;
                    border-radius: 0.9rem;
                    padding: 0.7rem 0.9rem;
                }

                .sticky-footer {
                    margin-top: auto;
                }

                @media (max-width: 767.98px) {
                    .profile-detail-shell {
                        max-width: 100% !important;
                        padding-left: 0.75rem;
                        padding-right: 0.75rem;
                        min-height: calc(100vh - 160px);
                    }

                    .profile-hero,
                    .profile-card,
                    .profile-table-card,
                    .profile-edit-card {
                        border-radius: 16px;
                    }

                    .detail-grid {
                        grid-template-columns: 1fr;
                    }

                    .detail-table th {
                        width: 140px;
                    }

                    .status-banner {
                        padding: 0.1rem 0;
                    }

                    .detail-action-row .btn {
                        width: 100%;
                    }
                }
            </style>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <a href="home.php?page=data-siswa" class="btn btn-sm btn-outline-secondary rounded-pill">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <h5 class="mb-0 fw-bold text-dark">Detail Profil Siswa</h5>
                </div>
                <div class="detail-action-row">
                    <?php if ($editMode): ?>
                        
                    </div>

                    <form method="post" action="detail-profil-siswa.php?no_induk=<?php echo urlencode($noInduk); ?>&edit=1">
                        <input type="hidden" name="_save_admin_profile" value="1">
                        <input type="hidden" name="no_induk" value="<?php echo htmlspecialchars($noInduk); ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="detail-label">No. Induk / NIS</label>
                                <div class="readonly-box"><?php echo htmlspecialchars((string) ($student['no_induk'] ?? '—')); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="detail-label">Nama Siswa</label>
                                <input type="text" name="nama_siswa" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['nama_siswa'] ?? '')); ?>">
                            </div>

                            <?php if (detail_profile_column_exists($conn, 'tbl_siswa', 'nisn')): ?>
                                <div class="col-md-6">
                                    <label class="detail-label">NISN</label>
                                    <input type="text" name="nisn" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['nisn'] ?? '')); ?>" placeholder="Masukkan NISN">
                                </div>
                            <?php endif; ?>

                            <?php if (detail_profile_column_exists($conn, 'tbl_siswa', 'kelas')): ?>
                                <div class="col-md-6">
                                    <label class="detail-label">Kelas</label>
                                    <input type="text" name="kelas" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['kelas'] ?? '')); ?>" placeholder="Contoh: X IPA 1">
                                </div>
                            <?php endif; ?>

                            <?php if (detail_profile_column_exists($conn, 'tbl_siswa', 'status')): ?>
                                <div class="col-md-6">
                                    <label class="detail-label">Status</label>
                                    <select name="status" class="form-select profile-select">
                                        <?php $currentStatus = (string) ($student['status'] ?? ''); ?>
                                        <option value="aktif" <?php echo strtolower($currentStatus) === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="nonaktif" <?php echo strtolower($currentStatus) === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php if (detail_profile_column_exists($conn, 'tbl_siswa', 'jabatan')): ?>
                                <div class="col-md-6">
                                    <label class="detail-label">Jabatan</label>
                                    <input type="text" name="jabatan" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['jabatan'] ?? '')); ?>">
                                </div>
                            <?php endif; ?>

                            <?php if (detail_profile_column_exists($conn, 'tbl_siswa', 'alamat')): ?>
                                <div class="col-12">
                                    <label class="detail-label">Alamat</label>
                                    <textarea name="alamat" rows="3" class="form-control profile-textarea" placeholder="Alamat lengkap siswa"><?php echo htmlspecialchars((string) ($student['alamat'] ?? '')); ?></textarea>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="detail-label">Latitude</label>
                                <input type="text" name="lat" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['lat'] ?? '')); ?>" placeholder="-6.200000">
                            </div>
                            <div class="col-md-6">
                                <label class="detail-label">Longitude</label>
                                <input type="text" name="lng" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['lng'] ?? '')); ?>" placeholder="106.816666">
                            </div>

                            <div class="col-md-6">
                                <label class="detail-label">No. WhatsApp Siswa</label>
                                <input type="text" name="no_wa" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['no_wa'] ?? '')); ?>" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="detail-label">Nama Keluarga Darurat</label>
                                <input type="text" name="nama_darurat" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['nama_darurat'] ?? '')); ?>" placeholder="Nama wali / keluarga">
                            </div>

                            <div class="col-md-6">
                                <label class="detail-label">No. HP Keluarga</label>
                                <input type="text" name="no_darurat" class="form-control profile-input" value="<?php echo htmlspecialchars((string) ($student['no_darurat'] ?? '')); ?>" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>

                        <div class="detail-action-row mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                            <a href="detail-profil-siswa.php?no_induk=<?php echo urlencode($noInduk); ?>" class="btn btn-outline-secondary rounded-pill px-4">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
<?php include 'footer.php'; ?>