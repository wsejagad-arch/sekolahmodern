<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';

$nipGuru = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantMapelAmpu = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantTugas = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_tugas', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$kelasFilter = trim((string)($_GET['kelas'] ?? ''));
$mapelFilter = trim((string)($_GET['mapel'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? 'aktif'));

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    id_mapel INT NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    mapel VARCHAR(100) NOT NULL,
    no_induk_guru VARCHAR(50) NOT NULL,
    judul_tugas VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    link_tugas TEXT NULL,
    file_tugas VARCHAR(255) NULL,
    tanggal_pengumpulan DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('aktif','selesai','dihapus') DEFAULT 'aktif',
    INDEX (tanggal, id_mapel),
    INDEX (no_induk_guru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

foreach (['link_tugas' => 'TEXT NULL AFTER deskripsi', 'file_tugas' => 'VARCHAR(255) NULL AFTER link_tugas'] as $column => $definition) {
    $colEsc = mysqli_real_escape_string($conn, $column);
    $qCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_tugas LIKE '{$colEsc}'");
    if (!$qCol || mysqli_num_rows($qCol) === 0) {
        mysqli_query($conn, "ALTER TABLE tbl_tugas ADD COLUMN {$column} {$definition}");
    }
}

function ht_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$kelasOptions = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE {$tenantMapelAmpu} AND no_induk='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC");
while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
    $kelasOptions[] = (string)$row['kelas'];
}

$mapelOptions = [];
$qMapel = mysqli_query($conn, "SELECT DISTINCT nama_mapel FROM tbl_mapel_ampu WHERE {$tenantMapelAmpu} AND no_induk='{$nipEsc}' ORDER BY nama_mapel ASC");
while ($qMapel && ($row = mysqli_fetch_assoc($qMapel))) {
    $mapelOptions[] = (string)$row['nama_mapel'];
}

$where = [$tenantTugas, "no_induk_guru='{$nipEsc}'"];
if ($kelasFilter !== '') {
    $where[] = "kelas='" . mysqli_real_escape_string($conn, $kelasFilter) . "'";
}
if ($mapelFilter !== '') {
    $where[] = "mapel='" . mysqli_real_escape_string($conn, $mapelFilter) . "'";
}
if (in_array($statusFilter, ['aktif', 'selesai', 'dihapus'], true)) {
    $where[] = "status='" . mysqli_real_escape_string($conn, $statusFilter) . "'";
} else {
    $statusFilter = '';
    $where[] = "status<>'dihapus'";
}

$tasks = [];
$qTasks = mysqli_query($conn, "SELECT * FROM tbl_tugas WHERE " . implode(' AND ', $where) . " ORDER BY COALESCE(tanggal_pengumpulan, tanggal) DESC, id DESC LIMIT 200");
while ($qTasks && ($row = mysqli_fetch_assoc($qTasks))) {
    $tasks[] = $row;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tugas - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { min-height:100vh; margin:0; background:linear-gradient(135deg,#ecfdf5,#eef2ff); color:#0f172a; padding-bottom:120px; font-family:"Segoe UI", Arial, sans-serif; }
        .shell { max-width:1100px; margin:0 auto; padding:22px; }
        .hero { background:linear-gradient(135deg,#0f766e,#2563eb); color:#fff; border-radius:24px; padding:24px; box-shadow:0 20px 50px rgba(15,23,42,.18); }
        .panel { background:rgba(255,255,255,.94); border:1px solid #dbeafe; border-radius:18px; box-shadow:0 14px 34px rgba(15,23,42,.08); }
        .task-card { padding:16px; display:flex; gap:14px; justify-content:space-between; align-items:flex-start; }
        .task-icon { width:46px; height:46px; border-radius:15px; display:grid; place-items:center; background:#dcfce7; color:#15803d; font-size:22px; flex:0 0 auto; }
        .task-title { font-weight:800; color:#0f172a; margin:0; }
        .task-meta { color:#64748b; font-size:.84rem; display:flex; flex-wrap:wrap; gap:8px; margin-top:6px; }
        .badge-soft { background:#f8fafc; border:1px solid #e2e8f0; color:#475569; }
        @media (max-width: 640px) { .shell { padding:14px; } .task-card { flex-direction:column; } }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero mb-3">
        <a href="../../home.php" class="text-white-50 text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <h1 class="mt-3 mb-1">Tugas Siswa</h1>
        <p class="mb-0 text-white-50">Kelola dan pantau tugas yang sudah diberikan ke kelas.</p>
    </section>

    <section class="panel p-3 p-md-4 mb-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= ht_h($kelas); ?>" <?= $kelasFilter === $kelas ? 'selected' : ''; ?>><?= ht_h($kelas); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Mapel</label>
                <select name="mapel" class="form-select">
                    <option value="">Semua Mapel</option>
                    <?php foreach ($mapelOptions as $mapel): ?>
                        <option value="<?= ht_h($mapel); ?>" <?= $mapelFilter === $mapel ? 'selected' : ''; ?>><?= ht_h($mapel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="selesai" <?= $statusFilter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    <option value="" <?= $statusFilter === '' ? 'selected' : ''; ?>>Aktif & Selesai</option>
                    <option value="dihapus" <?= $statusFilter === 'dihapus' ? 'selected' : ''; ?>>Dihapus</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-success" type="submit"><i class="bi bi-funnel"></i> Tampilkan</button>
            </div>
        </form>
    </section>

    <section class="d-grid gap-3">
        <?php if (empty($tasks)): ?>
            <div class="panel p-5 text-center text-muted">
                <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
                Belum ada tugas sesuai filter ini.
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <article class="panel task-card">
                    <div class="d-flex gap-3">
                        <div class="task-icon"><i class="bi bi-clipboard-check"></i></div>
                        <div>
                            <h2 class="task-title h5"><?= ht_h($task['judul_tugas']); ?></h2>
                            <div class="task-meta">
                                <span><i class="bi bi-people"></i> <?= ht_h($task['kelas']); ?></span>
                                <span><i class="bi bi-book"></i> <?= ht_h($task['mapel']); ?></span>
                                <span><i class="bi bi-calendar"></i> <?= ht_h(date('d/m/Y', strtotime((string)$task['tanggal']))); ?></span>
                                <span><i class="bi bi-hourglass-split"></i> Deadline: <?= !empty($task['tanggal_pengumpulan']) ? ht_h(date('d/m/Y', strtotime((string)$task['tanggal_pengumpulan']))) : '-'; ?></span>
                            </div>
                            <p class="mt-2 mb-0 text-muted"><?= ht_h(mb_strimwidth((string)$task['deskripsi'], 0, 150, '...')); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <span class="badge rounded-pill badge-soft align-self-start"><?= ht_h($task['status']); ?></span>
                        <a class="btn btn-sm btn-outline-primary" href="task-detail?id=<?= (int)$task['id']; ?>"><i class="bi bi-eye"></i> Detail</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
