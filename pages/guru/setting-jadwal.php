<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';
// date_default_timezone_set is already in bootstrap.php

function sj_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sj_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return $q && mysqli_num_rows($q) > 0;
}

function sj_create_or_upgrade_table(mysqli $conn): void
{
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_mapel_ampu (
            id_mapel INT NOT NULL AUTO_INCREMENT,
            no_induk VARCHAR(50) NOT NULL,
            nama_mapel VARCHAR(150) NOT NULL,
            kelas VARCHAR(80) NOT NULL,
            hari VARCHAR(20) NOT NULL,
            jam_mulai TIME NOT NULL,
            jam_selesai TIME NOT NULL,
            ruang VARCHAR(80) DEFAULT NULL,
            PRIMARY KEY (id_mapel),
            KEY idx_guru_hari (no_induk, hari),
            KEY idx_kelas_hari (kelas, hari)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!sj_column_exists($conn, 'tbl_mapel_ampu', 'ruang')) {
        @mysqli_query($conn, "ALTER TABLE tbl_mapel_ampu ADD COLUMN ruang VARCHAR(80) DEFAULT NULL AFTER jam_selesai");
    }
}

function sj_time_value(string $value): string
{
    if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
        return '';
    }
    return $value . ':00';
}

function sj_has_conflict(mysqli $conn, string $nip, string $hari, string $mulai, string $selesai, int $excludeId = 0): bool
{
    $nipEsc = mysqli_real_escape_string($conn, $nip);
    $hariEsc = mysqli_real_escape_string($conn, $hari);
    $mulaiEsc = mysqli_real_escape_string($conn, $mulai);
    $selesaiEsc = mysqli_real_escape_string($conn, $selesai);
    $excludeSql = $excludeId > 0 ? "AND id_mapel <> $excludeId" : '';
    $q = @mysqli_query($conn, "
        SELECT id_mapel
        FROM tbl_mapel_ampu
        WHERE no_induk='$nipEsc'
          AND hari='$hariEsc'
          $excludeSql
          AND jam_mulai < '$selesaiEsc'
          AND jam_selesai > '$mulaiEsc'
        LIMIT 1
    ");
    return $q && mysqli_num_rows($q) > 0;
}

sj_create_or_upgrade_table($conn);

$nip = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);
$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$flash = '';
$flashType = 'success';

$qGuru = @mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='$nipEsc' LIMIT 1");
$guru = $qGuru ? mysqli_fetch_assoc($qGuru) : [];
$namaGuru = (string)($guru['nama_guru'] ?? ($_SESSION['nama_guru'] ?? 'Guru'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id_mapel'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $ok = @mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE id_mapel=$id AND no_induk='$nipEsc' LIMIT 1");
        $flash = $ok && mysqli_affected_rows($conn) > 0 ? 'Jadwal berhasil dihapus.' : 'Jadwal tidak ditemukan atau bukan milik Anda.';
        $flashType = $ok && mysqli_affected_rows($conn) > 0 ? 'success' : 'danger';
    } else {
        $namaMapel = trim((string)($_POST['nama_mapel'] ?? ''));
        $kelas = trim((string)($_POST['kelas'] ?? ''));
        $hari = trim((string)($_POST['hari'] ?? ''));
        $jamMulai = sj_time_value(trim((string)($_POST['jam_mulai'] ?? '')));
        $jamSelesai = sj_time_value(trim((string)($_POST['jam_selesai'] ?? '')));
        $ruang = trim((string)($_POST['ruang'] ?? ''));

        if ($namaMapel === '' || $kelas === '' || !in_array($hari, $hariList, true) || $jamMulai === '' || $jamSelesai === '') {
            $flash = 'Lengkapi mapel, kelas, hari, jam mulai, dan jam selesai.';
            $flashType = 'danger';
        } elseif (strtotime($jamMulai) >= strtotime($jamSelesai)) {
            $flash = 'Jam selesai harus lebih besar dari jam mulai.';
            $flashType = 'danger';
        } elseif (sj_has_conflict($conn, $nip, $hari, $jamMulai, $jamSelesai, $action === 'update' ? $id : 0)) {
            $flash = 'Jadwal bentrok dengan jadwal Anda yang sudah ada pada hari tersebut.';
            $flashType = 'danger';
        } else {
            $mapelEsc = mysqli_real_escape_string($conn, $namaMapel);
            $kelasEsc = mysqli_real_escape_string($conn, $kelas);
            $hariEsc = mysqli_real_escape_string($conn, $hari);
            $mulaiEsc = mysqli_real_escape_string($conn, $jamMulai);
            $selesaiEsc = mysqli_real_escape_string($conn, $jamSelesai);
            $ruangEsc = mysqli_real_escape_string($conn, $ruang);

            if ($action === 'update' && $id > 0) {
                $ok = @mysqli_query($conn, "
                    UPDATE tbl_mapel_ampu
                    SET nama_mapel='$mapelEsc',
                        kelas='$kelasEsc',
                        hari='$hariEsc',
                        jam_mulai='$mulaiEsc',
                        jam_selesai='$selesaiEsc',
                        ruang='$ruangEsc'
                    WHERE id_mapel=$id AND no_induk='$nipEsc'
                    LIMIT 1
                ");
                $flash = $ok && mysqli_affected_rows($conn) >= 0 ? 'Jadwal berhasil diperbarui.' : 'Gagal memperbarui jadwal.';
                $flashType = $ok ? 'success' : 'danger';
            } else {
                $ok = @mysqli_query($conn, "
                    INSERT INTO tbl_mapel_ampu
                        (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai, ruang)
                    VALUES
                        ('$nipEsc', '$mapelEsc', '$kelasEsc', '$hariEsc', '$mulaiEsc', '$selesaiEsc', '$ruangEsc')
                ");
                $flash = $ok ? 'Jadwal baru berhasil ditambahkan.' : 'Gagal menambahkan jadwal: ' . mysqli_error($conn);
                $flashType = $ok ? 'success' : 'danger';
            }
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editData = null;
if ($editId > 0) {
    $qEdit = @mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE id_mapel=$editId AND no_induk='$nipEsc' LIMIT 1");
    $editData = $qEdit ? mysqli_fetch_assoc($qEdit) : null;
    if (!$editData) {
        $flash = 'Jadwal yang akan diedit tidak ditemukan atau bukan milik Anda.';
        $flashType = 'danger';
    }
}

$kelasOptions = [];
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
    $kelasOptions[] = (string)$row['kelas'];
}

$mapelOptions = [];
$qMapel = @mysqli_query($conn, "SELECT nama_mapel FROM tbl_mapel WHERE nama_mapel <> '' ORDER BY nama_mapel ASC");
while ($qMapel && ($row = mysqli_fetch_assoc($qMapel))) {
    $mapelOptions[] = (string)$row['nama_mapel'];
}
$qOwnMapel = @mysqli_query($conn, "SELECT DISTINCT nama_mapel FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND nama_mapel <> '' ORDER BY nama_mapel ASC");
while ($qOwnMapel && ($row = mysqli_fetch_assoc($qOwnMapel))) {
    if (!in_array((string)$row['nama_mapel'], $mapelOptions, true)) {
        $mapelOptions[] = (string)$row['nama_mapel'];
    }
}
sort($mapelOptions);

$jadwal = [];
$qJadwal = @mysqli_query($conn, "
    SELECT *
    FROM tbl_mapel_ampu
    WHERE no_induk='$nipEsc'
    ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai ASC, kelas ASC
");
while ($qJadwal && ($row = mysqli_fetch_assoc($qJadwal))) {
    $jadwal[] = $row;
}

$selectedMapel = (string)($editData['nama_mapel'] ?? '');
$selectedKelas = (string)($editData['kelas'] ?? '');
$selectedHari = (string)($editData['hari'] ?? 'Senin');
$selectedMulai = $editData ? substr((string)$editData['jam_mulai'], 0, 5) : '';
$selectedSelesai = $editData ? substr((string)$editData['jam_selesai'], 0, 5) : '';
$selectedRuang = (string)($editData['ruang'] ?? '');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setting Jadwal - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
    <style>
        :root { --line:#e2e8f0; --ink:#0f172a; --muted:#64748b; --brand:#2563eb; }
        body { margin:0; font-family:"Plus Jakarta Sans", system-ui, sans-serif; background:linear-gradient(135deg,#eff6ff,#f8fafc 48%,#ecfeff); color:var(--ink); padding-bottom:72px; }
        .shell { max-width:1220px; margin:0 auto; padding:24px; }
        .hero { background:linear-gradient(135deg,#1d4ed8,#0f172a); color:#fff; border-radius:22px; padding:26px; box-shadow:0 18px 44px rgba(15,23,42,.16); }
        .hero a { color:rgba(255,255,255,.82); text-decoration:none; font-weight:800; }
        .panel { background:rgba(255,255,255,.94); border:1px solid rgba(226,232,240,.9); border-radius:18px; box-shadow:0 12px 32px rgba(15,23,42,.07); }
        .panel-pad { padding:18px; }
        .form-control, .form-select { border-radius:12px; border-color:#dbe3ee; }
        .table th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#64748b; white-space:nowrap; background:#f8fafc; }
        .table td { vertical-align:middle; font-size:13px; }
        .day-badge { display:inline-flex; min-width:78px; justify-content:center; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-weight:900; font-size:12px; padding:5px 9px; }
        .mini { color:var(--muted); font-size:12px; }
        .mobile-nav { position:fixed; left:0; right:0; bottom:0; background:rgba(255,255,255,.94); border-top:1px solid var(--line); backdrop-filter:blur(16px); padding:10px 16px; display:flex; justify-content:center; gap:22px; z-index:20; }
        .mobile-nav a { color:#64748b; text-decoration:none; font-size:12px; font-weight:800; display:flex; flex-direction:column; align-items:center; }
        .mobile-nav i { font-size:20px; }
        @media (max-width:640px) { .shell { padding:16px; } .hero { padding:20px; } }
        
        /* Desktop specific styling overrides */
        @media (min-width: 768px) {
            .mobile-nav, 
            .guru-common-footer-wrap,
            body.guru-common-footer-active {
                display: none !important;
                padding-bottom: 0 !important;
            }
            body {
                padding-bottom: 0 !important;
                background: #ebf1f6 !important;
            }
            /* Shift main content container */
            .shell {
                margin-left: 260px !important;
                max-width: calc(100% - 260px) !important;
                padding: 32px !important;
            }
            .hero {
                background: #ffffff !important;
                color: #1e293b !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.02) !important;
                border: 1px solid rgba(226,232,240,.9) !important;
            }
            .hero h1 {
                color: #1e293b !important;
                font-weight: 800 !important;
            }
            .hero p {
                color: #64748b !important;
            }
            .hero a {
                color: #3c58b9 !important;
                font-weight: 700 !important;
            }
        }
    </style>
</head>
<body>

<!-- DESKTOP SIDEBAR -->
<div class="desktop-sidebar">
    <div class="desktop-logo">
        <i class="bi bi-book-half"></i> SIMANIS
    </div>
    <div class="desktop-nav">
        <a href="../../home.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="setting-jadwal" class="active"><i class="bi bi-calendar3"></i> Kelas Saya</a>
        <a href="data-siswa"><i class="bi bi-people"></i> Data Siswa</a>
        <a href="nilai"><i class="bi bi-journal-check"></i> Nilai & Tugas</a>
        <a href="materi"><i class="bi bi-book"></i> Materi</a>
        <a href="laporan-kelas"><i class="bi bi-cpu"></i> Laporan & AI</a>
        <a href="ekinerja"><i class="bi bi-speedometer2"></i> e-Kinerja</a>
        <a href="profil-guru"><i class="bi bi-gear"></i> Pengaturan</a>
    </div>
    <div class="desktop-logout-wrap">
        <a href="../../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="btn-desktop-logout">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</div>

<main class="shell">
    <section class="hero">
        <a href="../../home.php"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <h1 class="mt-3 mb-2">Setting Jadwal</h1>
        <p class="mb-0 text-white-50">Kelola jadwal mengajar milik Anda sendiri. Jadwal yang diubah di sini langsung dipakai oleh dashboard, jurnal mengajar, dan presensi siswa.</p>
    </section>

    <?php if ($flash !== ''): ?>
        <div class="alert alert-<?= sj_h($flashType); ?> mt-3 mb-0"><?= sj_h($flash); ?></div>
    <?php endif; ?>

    <section class="panel panel-pad mt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h5 class="fw-bold mb-1"><i class="bi bi-calendar2-plus-fill text-primary"></i> <?= $editData ? 'Edit Jadwal' : 'Tambah Jadwal'; ?></h5>
                <div class="mini"><?= sj_h($namaGuru); ?> - <?= sj_h($nip); ?></div>
            </div>
            <?php if ($editData): ?>
                <a href="setting-jadwal" class="btn btn-outline-secondary btn-sm fw-bold"><i class="bi bi-plus-circle"></i> Tambah Baru</a>
            <?php endif; ?>
        </div>
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create'; ?>">
            <input type="hidden" name="id_mapel" value="<?= (int)($editData['id_mapel'] ?? 0); ?>">
            <div class="col-md-4">
                <label class="form-label fw-bold">Mata Pelajaran</label>
                <input class="form-control" name="nama_mapel" list="mapelOptions" value="<?= sj_h($selectedMapel); ?>" required placeholder="Contoh: MATEMATIKA">
                <datalist id="mapelOptions">
                    <?php foreach ($mapelOptions as $mapel): ?>
                        <option value="<?= sj_h($mapel); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Kelas</label>
                <input class="form-control" name="kelas" list="kelasOptions" value="<?= sj_h($selectedKelas); ?>" required placeholder="Contoh: X E 1">
                <datalist id="kelasOptions">
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= sj_h($kelas); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Hari</label>
                <select class="form-select" name="hari" required>
                    <?php foreach ($hariList as $hari): ?>
                        <option value="<?= sj_h($hari); ?>" <?= $selectedHari === $hari ? 'selected' : ''; ?>><?= sj_h($hari); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Ruang</label>
                <input class="form-control" name="ruang" value="<?= sj_h($selectedRuang); ?>" placeholder="Opsional">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Jam Mulai</label>
                <input class="form-control" type="time" name="jam_mulai" value="<?= sj_h($selectedMulai); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Jam Selesai</label>
                <input class="form-control" type="time" name="jam_selesai" value="<?= sj_h($selectedSelesai); ?>" required>
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
                <button class="btn btn-primary fw-bold px-4" type="submit"><i class="bi bi-save"></i> <?= $editData ? 'Simpan Perubahan' : 'Tambah Jadwal'; ?></button>
                <?php if ($editData): ?>
                    <a class="btn btn-outline-secondary fw-bold" href="setting-jadwal">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel mt-3">
        <div class="panel-pad border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1"><i class="bi bi-calendar-week text-primary"></i> Jadwal Mengajar Saya</h5>
                <div class="mini"><?= count($jadwal); ?> jadwal tersimpan.</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Ruang</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($jadwal)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada jadwal. Tambahkan jadwal pertama Anda dari form di atas.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($jadwal as $row): ?>
                    <tr>
                        <td><span class="day-badge"><?= sj_h($row['hari']); ?></span></td>
                        <td><strong><?= sj_h(substr((string)$row['jam_mulai'], 0, 5)); ?> - <?= sj_h(substr((string)$row['jam_selesai'], 0, 5)); ?></strong></td>
                        <td>
                            <div class="fw-bold"><?= sj_h($row['nama_mapel']); ?></div>
                            <div class="mini">ID Jadwal: <?= (int)$row['id_mapel']; ?></div>
                        </td>
                        <td><?= sj_h($row['kelas']); ?></td>
                        <td><?= sj_h($row['ruang'] ?: 'R. Kelas'); ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary fw-bold" href="setting-jadwal?edit=<?= (int)$row['id_mapel']; ?>"><i class="bi bi-pencil-square"></i> Edit</a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_mapel" value="<?= (int)$row['id_mapel']; ?>">
                                <button class="btn btn-sm btn-outline-danger fw-bold" type="submit"><i class="bi bi-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<nav class="mobile-nav">
    <a href="../../home.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
    <a href="setting-jadwal" style="color:#2563eb;"><i class="bi bi-calendar-week"></i><span>Jadwal</span></a>
    <a href="materi"><i class="bi bi-journal-text"></i><span>Jurnal</span></a>
    <a href="profil-guru"><i class="bi bi-person"></i><span>Profil</span></a>
</nav>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
