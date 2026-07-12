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
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/clockpicker/0.0.7/bootstrap-clockpicker.min.css">
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
    <style>
        body { margin:0; font-family:"Plus Jakarta Sans", system-ui, sans-serif; background:#ebf1f6; color:#0f172a; }
        .mobile-nav { position:fixed; left:0; right:0; bottom:0; background:rgba(255,255,255,.94); border-top:1px solid #e2e8f0; backdrop-filter:blur(16px); padding:10px 16px; display:flex; justify-content:center; gap:22px; z-index:20; }
        .mobile-nav a { color:#64748b; text-decoration:none; font-size:12px; font-weight:800; display:flex; flex-direction:column; align-items:center; }
        .mobile-nav i { font-size:20px; }
        .mobile-nav a.active { color:#059669; }
        
        .premium-card { background: rgba(255, 255, 255, 0.95); border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title-modern { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; }
        .badge-subtle { background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
        
        .welcome-banner-green {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px;
            padding: 24px;
            color: white;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.2);
        }
        .welcome-banner-green .banner-text h2 { font-size: 1.6rem; font-weight: 800; margin-bottom: 8px; }
        .welcome-banner-green .banner-subtitle { font-size: 0.95rem; opacity: 0.9; margin: 0; }
        .welcome-banner-green .shape { position: absolute; border-radius: 50%; filter: blur(30px); opacity: 0.5; z-index: 1; }
        .welcome-banner-green .shape-1 { width: 150px; height: 150px; background: #34d399; top: -50px; right: -20px; }
        .welcome-banner-green .shape-2 { width: 100px; height: 100px; background: #a7f3d0; bottom: -30px; left: 50%; }

        @media (min-width: 768px) {
            .mobile-nav, .guru-common-footer-wrap { display: none !important; padding-bottom: 0 !important; }
            body { padding-bottom: 0 !important; }
            .premium-card { padding: 24px; border-radius: 20px; }
            .welcome-banner-green { padding: 32px; border-radius: 24px; }
            .welcome-banner-green .banner-text h2 { font-size: 2.2rem; margin-bottom: 12px; }
            .welcome-banner-green .banner-subtitle { font-size: 1.05rem; margin-bottom: 24px; }
            .welcome-banner-green .shape-1 { width: 300px; height: 300px; top: -100px; right: 0; }
            .welcome-banner-green .shape-2 { width: 200px; height: 200px; bottom: -80px; left: 40%; }
            .btn-green-desktop { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); transition: 0.3s; position: relative; z-index: 2; }
            .btn-green-desktop:hover { background: rgba(255,255,255,0.3); color: white; }
        }
        @media (max-width: 767px) {
            .app-shell { padding: 16px !important; margin-bottom: 60px; display: block !important; }
            .btn-green-desktop { display: none; }
            
            /* Center the clockpicker as a popup on mobile */
            .clockpicker-popover {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                z-index: 9999 !important;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3) !important;
                border: none !important;
                border-radius: 12px !important;
            }
            /* Add a backdrop */
            body.clockpicker-open::before {
                content: '';
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9998;
            }
        }

        .form-control, .form-select { border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px 14px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
        .form-control:focus, .form-select:focus { border-color: #059669; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }
        .form-label { font-weight: 700; color: #1e293b; font-size: 0.9rem; }
        .day-badge { display:inline-flex; min-width:78px; justify-content:center; border-radius:999px; background:#d1fae5; color:#059669; font-weight:900; font-size:12px; padding:5px 9px; }
        .table-modern th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background: #f8fafc; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; }
        .table-modern td { vertical-align: middle; padding: 16px; border-bottom: 1px solid #f1f5f9; }
        .table-modern tbody tr { transition: all 0.2s; }
        .table-modern tbody tr:hover { background: #f8fafc; }
        
        .btn-green { background: #059669; color: white; border: none; }
        .btn-green:hover { background: #047857; color: white; }
    </style>
</head>
<body>

<!-- DESKTOP SIDEBAR -->
<?php include 'guru_sidebar_shared.php'; ?>

<div class="app-shell" style="grid-template-columns: 1fr; padding-right: 24px;">
    <div class="w-100">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner-green">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 class="animate-fade-in" style="font-weight:800;letter-spacing:-0.5px;">Setting Jadwal 📅</h2>
                    <p class="banner-subtitle">Kelola jadwal mengajar Anda. Jadwal ini akan terhubung langsung ke dashboard, jurnal, dan presensi siswa.</p>
                </div>
                <div class="banner-actions">
                    <a href="../../home.php" class="btn-green-desktop"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                </div>
            </div>
            <div class="banner-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
            </div>
        </div>

        <?php if ($flash !== ''): ?>
            <div class="alert alert-<?= sj_h($flashType); ?> fw-bold" style="border-radius:12px; border:none; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                <i class="bi <?= $flashType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i> <?= sj_h($flash); ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="premium-card">
            <div class="card-header-flex">
                <h3 class="card-title-modern"><i class="bi bi-calendar2-plus-fill text-success me-2"></i> <?= $editData ? 'Edit Jadwal' : 'Tambah Jadwal'; ?></h3>
                <?php if ($editData): ?>
                    <a href="setting-jadwal" class="btn btn-sm btn-outline-secondary fw-bold" style="border-radius:8px;"><i class="bi bi-plus-circle"></i> Batal / Tambah Baru</a>
                <?php endif; ?>
            </div>
            
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create'; ?>">
                <input type="hidden" name="id_mapel" value="<?= (int)($editData['id_mapel'] ?? 0); ?>">
                
                <div class="col-md-4">
                    <label class="form-label">Mata Pelajaran</label>
                    <select class="form-select" name="nama_mapel" required>
                        <option value="" disabled <?= empty($selectedMapel) ? 'selected' : ''; ?>>-- pilih --</option>
                        <?php foreach ($mapelOptions as $mapel): ?>
                            <option value="<?= sj_h($mapel); ?>" <?= $selectedMapel === $mapel ? 'selected' : ''; ?>><?= sj_h($mapel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kelas</label>
                    <select class="form-select" name="kelas" required>
                        <option value="" disabled <?= empty($selectedKelas) ? 'selected' : ''; ?>>-- pilih --</option>
                        <?php foreach ($kelasOptions as $kelasOpt): ?>
                            <option value="<?= sj_h($kelasOpt); ?>" <?= $selectedKelas === $kelasOpt ? 'selected' : ''; ?>><?= sj_h($kelasOpt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hari</label>
                    <select class="form-select" name="hari" required>
                        <?php foreach ($hariList as $hari): ?>
                            <option value="<?= sj_h($hari); ?>" <?= $selectedHari === $hari ? 'selected' : ''; ?>><?= sj_h($hari); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ruang (Opsional)</label>
                    <input class="form-control" name="ruang" value="<?= sj_h($selectedRuang); ?>" placeholder="Misal: R. Lab Komputer">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jam Mulai</label>
                    <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                        <input id="inp_jam_mulai" class="form-control" type="text" name="jam_mulai" value="<?= sj_h($selectedMulai); ?>" required readonly style="background-color: white; cursor: pointer;">
                        <label class="input-group-text" for="inp_jam_mulai" style="cursor: pointer;"><i class="bi bi-clock" style="pointer-events: none;"></i></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jam Selesai</label>
                    <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                        <input id="inp_jam_selesai" class="form-control" type="text" name="jam_selesai" value="<?= sj_h($selectedSelesai); ?>" required readonly style="background-color: white; cursor: pointer;">
                        <label class="input-group-text" for="inp_jam_selesai" style="cursor: pointer;"><i class="bi bi-clock" style="pointer-events: none;"></i></label>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-green w-100 fw-bold" style="padding:10px; border-radius:12px; box-shadow:0 4px 12px rgba(5,150,105,0.2);" type="submit">
                        <i class="bi bi-save me-1"></i> <?= $editData ? 'Simpan Perubahan' : 'Simpan Jadwal Baru'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Schedule List Card -->
        <div class="premium-card">
            <div class="card-header-flex">
                <h3 class="card-title-modern"><i class="bi bi-calendar-week text-success me-2"></i> Jadwal Mengajar Saya</h3>
                <span class="badge-subtle"><?= count($jadwal); ?> Kelas</span>
            </div>
            
            <div class="table-responsive" style="border-radius:12px; border:1px solid #f1f5f9;">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Waktu</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th>Ruang</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($jadwal)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-calendar-x" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                                Belum ada jadwal yang ditambahkan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($jadwal as $row): ?>
                        <tr>
                            <td><span class="day-badge"><?= sj_h($row['hari']); ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= sj_h(substr((string)$row['jam_mulai'], 0, 5)); ?> - <?= sj_h(substr((string)$row['jam_selesai'], 0, 5)); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold" style="color:#0f172a;"><?= sj_h($row['nama_mapel']); ?></div>
                                <div style="font-size:0.75rem; color:#94a3b8;">ID: <?= (int)$row['id_mapel']; ?></div>
                            </td>
                            <td><div class="badge bg-light text-dark border fw-bold px-3 py-2" style="border-radius:8px; font-size:0.85rem;"><?= sj_h($row['kelas']); ?></div></td>
                            <td><span class="text-muted"><i class="bi bi-door-open me-1"></i> <?= sj_h($row['ruang'] ?: '-'); ?></span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-success fw-bold mb-1" style="border-radius:8px;" href="setting-jadwal?edit=<?= (int)$row['id_mapel']; ?>"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_mapel" value="<?= (int)$row['id_mapel']; ?>">
                                    <button class="btn btn-sm btn-outline-danger fw-bold mb-1" style="border-radius:8px;" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<nav class="mobile-nav">
    <a href="../../home.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
    <a href="setting-jadwal" class="active"><i class="bi bi-calendar-week"></i><span>Jadwal</span></a>
    <a href="materi"><i class="bi bi-journal-text"></i><span>Jurnal</span></a>
    <a href="profil-guru"><i class="bi bi-person"></i><span>Profil</span></a>
</nav>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clockpicker/0.0.7/bootstrap-clockpicker.min.js"></script>
<script>
$(document).ready(function(){
    // Initialize clockpicker directly on inputs so they open on click
    $('input[name="jam_mulai"], input[name="jam_selesai"]').clockpicker({
        placement: 'bottom',
        align: 'left',
        autoclose: true,
        'default': 'now',
        beforeShow: function() {
            $('body').addClass('clockpicker-open');
        },
        afterHide: function() {
            $('body').removeClass('clockpicker-open');
        }
    });
    
    // Explicit manual toggle as a robust fallback
    $('.input-group-text').on('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        var $input = $(this).siblings('input');
        var cp = $input.data('clockpicker');
        if (cp) {
            // Trigger native input behavior
            $input.trigger('focus').trigger('click');
            // Ensure popover is shown
            setTimeout(function(){
                if (!cp.isShown) {
                    cp.show();
                }
            }, 10);
        }
    });
});
</script>
</body>
</html>

