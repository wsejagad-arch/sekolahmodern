<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../auth_helper.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;padding:24px;color:#991b1b;">Koneksi database tidak tersedia.</div>';
    exit;
}

function wks_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function wks_normalize_role(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/\s+/', ' ', $value);
    return $value ?? '';
}

function wks_valid_url(string $value): bool
{
    if ($value === '') {
        return true;
    }
    return (bool)filter_var($value, FILTER_VALIDATE_URL);
}

function wks_team_config(): array
{
    return [
        'kurikulum' => [
            'name' => 'WKS Kurikulum',
            'icon' => 'bi-journal-bookmark-fill',
            'accent' => '#0f766e',
            'summary' => 'Program akademik, perangkat ajar, jadwal pembelajaran, asesmen, dan pengembangan kurikulum.',
        ],
        'kesiswaan' => [
            'name' => 'WKS Kesiswaan',
            'icon' => 'bi-people-fill',
            'accent' => '#2563eb',
            'summary' => 'Program pembinaan siswa, ketertiban, organisasi, prestasi, dan layanan karakter.',
        ],
        'humas' => [
            'name' => 'WKS Humas',
            'icon' => 'bi-broadcast-pin',
            'accent' => '#dc2626',
            'summary' => 'Publikasi sekolah, kemitraan, dokumentasi kegiatan, dan komunikasi eksternal.',
        ],
        'sarpras' => [
            'name' => 'WKS Sarpras',
            'icon' => 'bi-tools',
            'accent' => '#d97706',
            'summary' => 'Inventaris, perawatan fasilitas, ruang belajar, dan kebutuhan sarana prasarana.',
        ],
    ];
}

function wks_create_tables(mysqli $conn): void
{
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_wks_microsite (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            unit VARCHAR(30) NOT NULL,
            title VARCHAR(160) NOT NULL,
            description TEXT DEFAULT NULL,
            microsite_url VARCHAR(500) DEFAULT NULL,
            folder_url VARCHAR(500) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by VARCHAR(50) DEFAULT NULL,
            updated_by VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_unit_active (unit, is_active),
            KEY idx_sort (sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_wks_folder (
            id_folder INT UNSIGNED NOT NULL AUTO_INCREMENT,
            unit VARCHAR(30) NOT NULL,
            parent_id INT UNSIGNED DEFAULT NULL,
            folder_name VARCHAR(180) NOT NULL,
            folder_url VARCHAR(500) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by VARCHAR(50) DEFAULT NULL,
            updated_by VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id_folder),
            KEY idx_unit_active (unit, is_active),
            KEY idx_parent (parent_id),
            KEY idx_sort (sort_order, id_folder),
            CONSTRAINT fk_wks_folder_parent
                FOREIGN KEY (parent_id) REFERENCES tbl_wks_folder (id_folder)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_wks_file (
            id_file INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_folder INT UNSIGNED DEFAULT NULL,
            unit VARCHAR(30) NOT NULL,
            file_title VARCHAR(180) NOT NULL,
            file_url VARCHAR(500) DEFAULT NULL,
            file_type VARCHAR(60) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by VARCHAR(50) DEFAULT NULL,
            updated_by VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id_file),
            KEY idx_folder (id_folder),
            KEY idx_unit_active (unit, is_active),
            KEY idx_sort (sort_order, id_file),
            CONSTRAINT fk_wks_file_folder
                FOREIGN KEY (id_folder) REFERENCES tbl_wks_folder (id_folder)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function wks_seed_default(mysqli $conn, array $teams, string $nip): void
{
    foreach ($teams as $unit => $team) {
        $unitEsc = mysqli_real_escape_string($conn, $unit);
        $qExisting = @mysqli_query($conn, "SELECT id FROM tbl_wks_microsite WHERE unit='$unitEsc' LIMIT 1");
        if ($qExisting && mysqli_num_rows($qExisting) > 0) {
            continue;
        }

        $titleEsc = mysqli_real_escape_string($conn, $team['name']);
        $descEsc = mysqli_real_escape_string($conn, $team['summary']);
        $nipEsc = mysqli_real_escape_string($conn, $nip);
        @mysqli_query($conn, "
            INSERT INTO tbl_wks_microsite
                (unit, title, description, microsite_url, folder_url, sort_order, created_by, updated_by)
            VALUES
                ('$unitEsc', '$titleEsc', '$descEsc', '', '', 10, '$nipEsc', '$nipEsc')
        ");
    }
}

$nip = (string)($_SESSION['no_induk'] ?? '');
$nipEsc = mysqli_real_escape_string($conn, $nip);
$qGuru = @mysqli_query($conn, "SELECT no_induk, nama_guru, jabatan, foto FROM tbl_guru WHERE no_induk='$nipEsc' LIMIT 1");
$guru = $qGuru ? mysqli_fetch_assoc($qGuru) : null;
$jabatan = (string)($guru['jabatan'] ?? '');
$normalizedRole = wks_normalize_role($jabatan);
$canManage = in_array($normalizedRole, ['wks kurikulum', 'tim wks kurikulum'], true);

$kelasAmpu = [];
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND kelas <> ''");
while ($qKelas && ($rowKelas = mysqli_fetch_assoc($qKelas))) {
    $kelasAmpu[] = (string)$rowKelas['kelas'];
}
$kelasDetailUrl = 'data-siswa';
if (count($kelasAmpu) === 1) {
    $kelasDetailUrl .= '?kelas=' . rawurlencode($kelasAmpu[0]);
}

$teams = wks_team_config();
wks_create_tables($conn);
wks_seed_default($conn, $teams, $nip);

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        $flash = 'Akses ditolak. Hanya WKS Kurikulum atau Tim WKS Kurikulum yang dapat mengelola microsite.';
        $flashType = 'danger';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        $unit = (string)($_POST['unit'] ?? '');
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $micrositeUrl = trim((string)($_POST['microsite_url'] ?? ''));
        $folderUrl = trim((string)($_POST['folder_url'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'delete' && $id > 0) {
            $ok = @mysqli_query($conn, "DELETE FROM tbl_wks_microsite WHERE id=$id LIMIT 1");
            $flash = $ok ? 'Microsite berhasil dihapus.' : 'Gagal menghapus microsite.';
            $flashType = $ok ? 'success' : 'danger';
        } elseif (!isset($teams[$unit])) {
            $flash = 'Pilih bidang WKS yang valid.';
            $flashType = 'danger';
        } elseif ($title === '') {
            $flash = 'Judul microsite wajib diisi.';
            $flashType = 'danger';
        } elseif (!wks_valid_url($micrositeUrl) || !wks_valid_url($folderUrl)) {
            $flash = 'Link microsite dan folder harus berupa URL valid, misalnya https://...';
            $flashType = 'danger';
        } else {
            $unitEsc = mysqli_real_escape_string($conn, $unit);
            $titleEsc = mysqli_real_escape_string($conn, $title);
            $descriptionEsc = mysqli_real_escape_string($conn, $description);
            $micrositeEsc = mysqli_real_escape_string($conn, $micrositeUrl);
            $folderEsc = mysqli_real_escape_string($conn, $folderUrl);

            if ($action === 'update' && $id > 0) {
                $ok = @mysqli_query($conn, "
                    UPDATE tbl_wks_microsite
                    SET unit='$unitEsc',
                        title='$titleEsc',
                        description='$descriptionEsc',
                        microsite_url='$micrositeEsc',
                        folder_url='$folderEsc',
                        sort_order=$sortOrder,
                        is_active=$isActive,
                        updated_by='$nipEsc'
                    WHERE id=$id
                    LIMIT 1
                ");
                $flash = $ok ? 'Microsite berhasil diperbarui.' : 'Gagal memperbarui microsite.';
                $flashType = $ok ? 'success' : 'danger';
            } else {
                $ok = @mysqli_query($conn, "
                    INSERT INTO tbl_wks_microsite
                        (unit, title, description, microsite_url, folder_url, sort_order, is_active, created_by, updated_by)
                    VALUES
                        ('$unitEsc', '$titleEsc', '$descriptionEsc', '$micrositeEsc', '$folderEsc', $sortOrder, $isActive, '$nipEsc', '$nipEsc')
                ");
                $flash = $ok ? 'Microsite baru berhasil ditambahkan.' : 'Gagal menambahkan microsite.';
                $flashType = $ok ? 'success' : 'danger';
            }
        }
    }
}

$itemsByUnit = [];
foreach (array_keys($teams) as $unit) {
    $itemsByUnit[$unit] = [];
}

$whereActive = $canManage ? '1=1' : 'is_active=1';
$qItems = @mysqli_query($conn, "
    SELECT *
    FROM tbl_wks_microsite
    WHERE $whereActive
    ORDER BY FIELD(unit, 'kurikulum', 'kesiswaan', 'humas', 'sarpras'), sort_order ASC, id ASC
");
while ($qItems && ($row = mysqli_fetch_assoc($qItems))) {
    $unit = (string)$row['unit'];
    if (isset($itemsByUnit[$unit])) {
        $itemsByUnit[$unit][] = $row;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu WKS - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --bg:#f5f7fb; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --brand:#0f766e; }
        body { margin:0; font-family:"Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color:var(--ink); background:linear-gradient(135deg,#ecfeff,#f8fafc 48%,#fff7ed); padding-bottom:72px; }
        .shell { max-width:1280px; margin:0 auto; padding:24px; }
        .hero { background:linear-gradient(135deg,#0f172a,#0f766e); color:#fff; border-radius:20px; padding:24px; box-shadow:0 18px 45px rgba(15,23,42,.16); }
        .hero a { color:rgba(255,255,255,.82); text-decoration:none; font-weight:800; }
        .hero p { color:rgba(255,255,255,.72); max-width:760px; }
        .manager-badge { display:inline-flex; align-items:center; gap:8px; border-radius:999px; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.24); padding:8px 12px; font-size:12px; font-weight:800; }
        .team-nav { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin:16px 0; }
        .team-chip { background:#fff; border:1px solid var(--line); border-radius:14px; padding:14px; text-decoration:none; color:var(--ink); display:flex; align-items:center; gap:12px; box-shadow:0 8px 24px rgba(15,23,42,.06); transition:.18s ease; }
        .team-chip:hover, .team-chip.is-active { transform:translateY(-1px); border-color:#0f766e; box-shadow:0 14px 30px rgba(15,118,110,.14); color:var(--ink); }
        .team-chip i { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; color:#fff; }
        .team-chip strong { display:block; font-size:14px; }
        .team-chip span { display:block; color:var(--muted); font-size:12px; }
        .panel { background:rgba(255,255,255,.94); border:1px solid rgba(226,232,240,.9); border-radius:18px; box-shadow:0 12px 32px rgba(15,23,42,.07); }
        .panel-pad { padding:18px; }
        .team-section { display:none; scroll-margin-top:20px; margin-top:16px; overflow:hidden; }
        .team-section.is-active { display:block; }
        .team-head { color:#fff; padding:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
        .team-head i { width:46px; height:46px; display:grid; place-items:center; border-radius:14px; background:rgba(255,255,255,.18); font-size:24px; }
        .microsite-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; padding:18px; }
        .site-card { border:1px solid var(--line); border-radius:14px; background:#fff; padding:16px; min-height:180px; display:flex; flex-direction:column; gap:12px; }
        .site-title { font-weight:900; font-size:17px; }
        .site-desc { color:#475569; font-size:13px; line-height:1.55; flex:1; }
        .site-actions { display:flex; flex-wrap:wrap; gap:8px; }
        .btn-soft { border:1px solid var(--line); background:#f8fafc; color:#0f172a; font-weight:800; border-radius:10px; padding:9px 12px; text-decoration:none; display:inline-flex; gap:7px; align-items:center; }
        .btn-soft.primary { background:#0f766e; border-color:#0f766e; color:#fff; }
        .btn-soft.disabled { color:#94a3b8; pointer-events:none; }
        .empty { padding:28px; text-align:center; color:var(--muted); border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc; }
        .manage-form { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .manage-form .wide { grid-column:1 / -1; }
        .form-control, .form-select { border-radius:12px; border-color:#dbe3ee; }
        .bottom-nav-wrap { position:fixed; bottom:0; left:0; right:0; z-index:1000; padding:12px 16px 20px; }
        .bottom-nav {
            max-width:440px; margin:0 auto; background:rgba(255,255,255,.9); backdrop-filter:blur(20px);
            border-radius:35px; padding:10px 12px; display:flex; justify-content:space-around; align-items:center;
            box-shadow:0 -10px 40px rgba(0,0,0,.08); border:1px solid rgba(255,255,255,.5);
            font-family:"Poppins", sans-serif;
        }
        .bottom-nav .nav-link {
            text-decoration:none; color:#94a3b8; font-size:10px; font-weight:600;
            display:flex; flex-direction:column; align-items:center; gap:4px; padding:0;
            font-family:"Poppins", sans-serif;
        }
        .bottom-nav .nav-link i { font-size:20px; }
        .bottom-nav .nav-link.active { color:var(--brand); }
        .nav-center {
            width:68px; height:68px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#4f46e5);
            margin-top:-45px; display:grid; place-items:center; color:#fff; font-size:34px;
            box-shadow:0 10px 25px rgba(79,70,229,.4); border:5px solid #f8fafc; text-decoration:none;
        }
        .wks-picker-empty { padding:28px; text-align:center; color:var(--muted); border:1px dashed #cbd5e1; border-radius:18px; background:rgba(255,255,255,.75); }
        @media (max-width:1100px) { .team-nav { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:900px) { .microsite-grid, .manage-form { grid-template-columns:1fr; } .manage-form .wide { grid-column:auto; } }
        @media (max-width:640px) { .team-nav { grid-template-columns:1fr; } }
        @media (max-width:640px) { .shell { padding:16px; } .hero { padding:20px; } .team-head { flex-direction:column; } }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <a href="guru_2026"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mt-3">
            <div>
                <h1 class="mb-2">Menu WKS</h1>
                <p class="mb-0">Pusat microsite dan folder kerja WKS Kurikulum, Kesiswaan, Humas, dan Sarpras. Guru biasa dapat melihat tautan yang aktif, sedangkan pengelolaan dibuka untuk WKS Kurikulum dan Tim WKS Kurikulum.</p>
            </div>
            <div class="manager-badge">
                <i class="bi <?= $canManage ? 'bi-unlock-fill' : 'bi-lock-fill'; ?>"></i>
                <?= $canManage ? 'Mode pengelola' : 'Mode baca'; ?><?= $jabatan !== '' ? ' - ' . wks_h($jabatan) : ''; ?>
            </div>
        </div>
    </section>

    <?php if ($flash !== ''): ?>
        <div class="alert alert-<?= wks_h($flashType); ?> mt-3 mb-0"><?= wks_h($flash); ?></div>
    <?php endif; ?>

    <nav class="team-nav" aria-label="Navigasi bidang WKS">
        <?php foreach ($teams as $unit => $team): ?>
            <a class="team-chip" href="#wks-<?= wks_h($unit); ?>" data-wks-target="wks-<?= wks_h($unit); ?>" aria-controls="wks-<?= wks_h($unit); ?>" aria-expanded="false">
                <i class="bi <?= wks_h($team['icon']); ?>" style="background:<?= wks_h($team['accent']); ?>"></i>
                <div>
                    <strong><?= wks_h($team['name']); ?></strong>
                    <span><?= count($itemsByUnit[$unit]); ?> tautan aktif/tersimpan</span>
                </div>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="wks-picker-empty" id="wksPickerEmpty">
        Pilih salah satu bidang WKS di atas untuk menampilkan microsite dan folder timnya.
    </div>

    <?php if ($canManage): ?>
        <section class="panel panel-pad mb-3">
            <h5 class="fw-bold mb-1"><i class="bi bi-plus-circle-fill text-success"></i> Tambah Microsite WKS</h5>
            <p class="text-muted small mb-3">Gunakan form ini untuk menambahkan link microsite dan folder tim. Item nonaktif hanya terlihat oleh pengelola.</p>
            <form method="post" class="manage-form">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label fw-bold">Bidang WKS</label>
                    <select class="form-select" name="unit" required>
                        <?php foreach ($teams as $unit => $team): ?>
                            <option value="<?= wks_h($unit); ?>"><?= wks_h($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-bold">Urutan</label>
                    <input class="form-control" type="number" name="sort_order" value="10">
                </div>
                <div class="wide">
                    <label class="form-label fw-bold">Judul</label>
                    <input class="form-control" type="text" name="title" maxlength="160" required placeholder="Contoh: Microsite Program Kesiswaan">
                </div>
                <div class="wide">
                    <label class="form-label fw-bold">Deskripsi</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Ringkasan isi microsite atau folder kerja."></textarea>
                </div>
                <div>
                    <label class="form-label fw-bold">Link Microsite</label>
                    <input class="form-control" type="url" name="microsite_url" placeholder="https://...">
                </div>
                <div>
                    <label class="form-label fw-bold">Link Folder Tim</label>
                    <input class="form-control" type="url" name="folder_url" placeholder="https://drive.google.com/...">
                </div>
                <div class="wide form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newActive" checked>
                    <label class="form-check-label fw-bold" for="newActive">Tampilkan untuk semua guru</label>
                </div>
                <div class="wide">
                    <button class="btn btn-success fw-bold px-4" type="submit"><i class="bi bi-save"></i> Simpan Microsite</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <?php foreach ($teams as $unit => $team): ?>
        <section class="panel team-section" id="wks-<?= wks_h($unit); ?>" aria-hidden="true">
            <div class="team-head" style="background:linear-gradient(135deg,<?= wks_h($team['accent']); ?>,#0f172a);">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi <?= wks_h($team['icon']); ?>"></i>
                    <div>
                        <h2 class="h4 mb-1"><?= wks_h($team['name']); ?></h2>
                        <div style="color:rgba(255,255,255,.78);"><?= wks_h($team['summary']); ?></div>
                    </div>
                </div>
                <span class="badge rounded-pill text-bg-light"><?= count($itemsByUnit[$unit]); ?> item</span>
            </div>
            <div class="microsite-grid">
                <?php if (empty($itemsByUnit[$unit])): ?>
                    <div class="empty">Belum ada microsite aktif untuk bidang ini.</div>
                <?php endif; ?>

                <?php foreach ($itemsByUnit[$unit] as $item): ?>
                    <article class="site-card">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="site-title"><?= wks_h($item['title']); ?></div>
                            <?php if ($canManage && !(int)$item['is_active']): ?>
                                <span class="badge text-bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </div>
                        <div class="site-desc"><?= nl2br(wks_h($item['description'] ?: 'Belum ada deskripsi.')); ?></div>
                        <div class="site-actions">
                            <?php if ((string)$item['microsite_url'] !== ''): ?>
                                <a class="btn-soft primary" href="<?= wks_h($item['microsite_url']); ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Microsite</a>
                            <?php else: ?>
                                <span class="btn-soft disabled"><i class="bi bi-link-45deg"></i> Microsite kosong</span>
                            <?php endif; ?>

                            <?php if ((string)$item['folder_url'] !== ''): ?>
                                <a class="btn-soft" href="<?= wks_h($item['folder_url']); ?>" target="_blank" rel="noopener"><i class="bi bi-folder2-open"></i> Folder Tim</a>
                            <?php else: ?>
                                <span class="btn-soft disabled"><i class="bi bi-folder"></i> Folder kosong</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($canManage): ?>
                            <details class="mt-2">
                                <summary class="fw-bold text-success" style="cursor:pointer;">Edit item</summary>
                                <form method="post" class="manage-form mt-3">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= (int)$item['id']; ?>">
                                    <div>
                                        <label class="form-label fw-bold">Bidang</label>
                                        <select class="form-select" name="unit" required>
                                            <?php foreach ($teams as $optUnit => $optTeam): ?>
                                                <option value="<?= wks_h($optUnit); ?>" <?= $optUnit === $item['unit'] ? 'selected' : ''; ?>><?= wks_h($optTeam['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Urutan</label>
                                        <input class="form-control" type="number" name="sort_order" value="<?= (int)$item['sort_order']; ?>">
                                    </div>
                                    <div class="wide">
                                        <label class="form-label fw-bold">Judul</label>
                                        <input class="form-control" type="text" name="title" maxlength="160" value="<?= wks_h($item['title']); ?>" required>
                                    </div>
                                    <div class="wide">
                                        <label class="form-label fw-bold">Deskripsi</label>
                                        <textarea class="form-control" name="description" rows="3"><?= wks_h($item['description']); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Link Microsite</label>
                                        <input class="form-control" type="url" name="microsite_url" value="<?= wks_h($item['microsite_url']); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Link Folder Tim</label>
                                        <input class="form-control" type="url" name="folder_url" value="<?= wks_h($item['folder_url']); ?>">
                                    </div>
                                    <div class="wide form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active<?= (int)$item['id']; ?>" <?= (int)$item['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="active<?= (int)$item['id']; ?>">Tampilkan untuk semua guru</label>
                                    </div>
                                    <div class="wide d-flex flex-wrap gap-2">
                                        <button class="btn btn-success fw-bold" type="submit"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
                                    </div>
                                </form>
                                <form method="post" class="mt-2" onsubmit="return confirm('Hapus microsite ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$item['id']; ?>">
                                    <button class="btn btn-outline-danger btn-sm fw-bold" type="submit"><i class="bi bi-trash"></i> Hapus</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</main>

<div class="bottom-nav-wrap">
    <nav class="bottom-nav">
        <a href="guru_2026" class="nav-link"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
        <a href="<?= wks_h($kelasDetailUrl); ?>" class="nav-link"><i class="bi bi-journal-bookmark"></i><span>Kelas</span></a>
        <a href="guru_2026?open_jurnal=1" class="nav-center" aria-label="Input jurnal"><i class="bi bi-fingerprint"></i></a>
        <a href="guru_2026" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Tugas</span></a>
        <a href="profil-guru" class="nav-link">
            <div style="width:24px; height:24px; border-radius:50%; overflow:hidden; border:1.5px solid #cbd5e1; margin-bottom:2px; position:relative;">
                <?php if (!empty($guru['foto'])): ?>
                    <img src="../../foto/<?= wks_h($guru['foto']); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <?= get_guru_avatar_svg(get_guru_gender((string)($guru['no_induk'] ?? $nip), (string)($guru['nama_guru'] ?? 'Guru'))); ?>
                <?php endif; ?>
            </div>
            <span>Profil</span>
        </a>
    </nav>
</div>
<script>
    (function() {
        var chips = document.querySelectorAll('[data-wks-target]');
        var sections = document.querySelectorAll('.team-section');
        var emptyState = document.getElementById('wksPickerEmpty');

        function openWksPanel(targetId, updateHash) {
            var target = document.getElementById(targetId);
            if (!target) {
                return;
            }

            sections.forEach(function(section) {
                var isActive = section.id === targetId;
                section.classList.toggle('is-active', isActive);
                section.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            chips.forEach(function(chip) {
                var isActive = chip.getAttribute('data-wks-target') === targetId;
                chip.classList.toggle('is-active', isActive);
                chip.setAttribute('aria-expanded', isActive ? 'true' : 'false');
            });

            if (emptyState) {
                emptyState.style.display = 'none';
            }

            if (updateHash) {
                history.replaceState(null, '', '#' + targetId);
            }
        }

        chips.forEach(function(chip) {
            chip.addEventListener('click', function(event) {
                event.preventDefault();
                openWksPanel(chip.getAttribute('data-wks-target'), true);
            });
        });

        if (window.location.hash) {
            openWksPanel(window.location.hash.substring(1), false);
        }
    })();
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
