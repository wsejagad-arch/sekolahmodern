<?php
/**
 * Setup Tim Aduan Siswa
 * Script ini menambahkan kolom is_tim_aduan ke tbl_guru
 * dan menampilkan status konfigurasi tim aduan.
 * Jalankan sekali untuk setup, lalu hapus atau batasi akses file ini.
 */
require_once __DIR__ . '/bootstrap.php';

// Cek hak akses
if (!in_array(current_role(), ['admin', 'superadmin'])) {
    die('<p style="color:red;font-family:sans-serif;">Akses ditolak. Halaman ini hanya untuk admin.</p>');
}

$messages = [];

// 1. Pastikan kolom is_tim_aduan ada di tbl_guru
$chkCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_tim_aduan'");
if (mysqli_num_rows($chkCol) === 0) {
    $alter = mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_tim_aduan TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Guru ditugaskan sebagai tim aduan siswa (1=ya)'");
    $messages[] = $alter ? ['type'=>'success','text'=>'Kolom <code>is_tim_aduan</code> berhasil ditambahkan ke tabel <code>tbl_guru</code>.'] 
                         : ['type'=>'danger','text'=>'Gagal menambah kolom: '.mysqli_error($conn)];
} else {
    $messages[] = ['type'=>'info','text'=>'Kolom <code>is_tim_aduan</code> sudah ada di tabel <code>tbl_guru</code>.'];
}

// 2. Pastikan tabel aduan ada
$chkTbl = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_aduan_siswa'");
if (mysqli_num_rows($chkTbl) === 0) {
    $createTbl = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_aduan_siswa (
        id_aduan INT UNSIGNED NOT NULL AUTO_INCREMENT,
        kode_aduan VARCHAR(30) NOT NULL,
        no_induk_pelapor VARCHAR(50) NOT NULL,
        nama_pelapor VARCHAR(150) NOT NULL DEFAULT '',
        kelas_pelapor VARCHAR(80) NOT NULL DEFAULT '',
        kategori VARCHAR(80) NOT NULL,
        judul VARCHAR(180) NOT NULL,
        isi_laporan TEXT NOT NULL,
        lokasi VARCHAR(180) DEFAULT NULL,
        tanggal_kejadian DATE DEFAULT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'baru',
        tahap_aktif VARCHAR(40) NOT NULL DEFAULT 'stpks',
        prioritas VARCHAR(20) NOT NULL DEFAULT 'normal',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        closed_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id_aduan),
        UNIQUE KEY uniq_kode_aduan (kode_aduan),
        KEY idx_status_tahap (status, tahap_aktif),
        KEY idx_pelapor (no_induk_pelapor),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = $createTbl ? ['type'=>'success','text'=>'Tabel <code>tbl_aduan_siswa</code> berhasil dibuat.']
                             : ['type'=>'danger','text'=>'Gagal membuat tabel aduan: '.mysqli_error($conn)];
} else {
    $messages[] = ['type'=>'info','text'=>'Tabel <code>tbl_aduan_siswa</code> sudah ada.'];
}

// Handle POST: set/unset tim aduan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_tim_aduan'])) {
    $nipTarget = mysqli_real_escape_string($conn, trim($_POST['nip_guru'] ?? ''));
    $valTarget = (int)($_POST['is_tim_aduan'] ?? 0);
    if ($nipTarget !== '') {
        $upd = mysqli_query($conn, "UPDATE tbl_guru SET is_tim_aduan={$valTarget} WHERE no_induk='{$nipTarget}'");
        $messages[] = $upd ? ['type'=>'success','text'=>'Status tim aduan berhasil diperbarui.']
                           : ['type'=>'danger','text'=>'Gagal memperbarui: '.mysqli_error($conn)];
    }
}

// Ambil daftar guru
$idSekolah = mt_current_school_id();
$qGuru = mysqli_query($conn, "SELECT no_induk, nama_guru, jabatan, COALESCE(is_tim_aduan,0) as is_tim_aduan FROM tbl_guru WHERE id_sekolah=$idSekolah ORDER BY nama_guru ASC");
$guruList = [];
while ($r = mysqli_fetch_assoc($qGuru)) $guruList[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Tim Aduan Siswa</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin: 40px auto; }
        .card { border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .badge-tim { background: #dc2626; color: #fff; font-size: 11px; padding: 3px 8px; border-radius: 99px; }
        .badge-biasa { background: #e2e8f0; color: #64748b; font-size: 11px; padding: 3px 8px; border-radius: 99px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-shield-exclamation fs-2 text-danger"></i>
        <div>
            <h2 class="mb-0 fw-bold">Setup Tim Aduan Siswa</h2>
            <small class="text-muted">Tugaskan guru sebagai anggota tim aduan. Hanya guru tim aduan yang menerima notifikasi aduan.</small>
        </div>
    </div>

    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show py-2" role="alert" style="border-radius:12px; font-size:13px;">
            <?= $msg['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Daftar Guru</h5>
            <span class="badge bg-danger"><?= count(array_filter($guruList, fn($g) => (int)$g['is_tim_aduan'] === 1)) ?> Tim Aduan</span>
        </div>
        <div class="alert alert-warning py-2" style="font-size:12px; border-radius:12px;">
            <i class="bi bi-eye-slash-fill me-1"></i>
            <strong>Aduan bersifat anonim.</strong> Guru tim aduan menerima notifikasi aduan <strong>tanpa nama pelapor</strong>. Mereka bertugas mencari fakta di lapangan.
        </div>
        <table class="table table-hover align-middle" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th>Nama Guru</th>
                    <th>NIP/NIK</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guruList as $g): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($g['nama_guru']) ?></strong></td>
                    <td style="font-size:11px; color:#64748b;"><?= htmlspecialchars($g['no_induk']) ?></td>
                    <td style="font-size:11px;"><?= htmlspecialchars($g['jabatan'] ?? '-') ?></td>
                    <td>
                        <?php if ((int)$g['is_tim_aduan'] === 1): ?>
                            <span class="badge-tim"><i class="bi bi-shield-check me-1"></i>Tim Aduan</span>
                        <?php else: ?>
                            <span class="badge-biasa">Guru Biasa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="toggle_tim_aduan" value="1">
                            <input type="hidden" name="nip_guru" value="<?= htmlspecialchars($g['no_induk']) ?>">
                            <?php if ((int)$g['is_tim_aduan'] === 1): ?>
                                <input type="hidden" name="is_tim_aduan" value="0">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:11px;" onclick="return confirm('Hapus <?= htmlspecialchars($g['nama_guru']) ?> dari tim aduan?')">
                                    <i class="bi bi-x-circle me-1"></i>Hapus dari Tim
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="is_tim_aduan" value="1">
                                <button type="submit" class="btn btn-sm btn-danger" style="border-radius:8px; font-size:11px;" onclick="return confirm('Tugaskan <?= htmlspecialchars($g['nama_guru']) ?> sebagai tim aduan?')">
                                    <i class="bi bi-shield-plus me-1"></i>Tugaskan
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 p-3" style="background:#fef2f2; border-radius:12px; font-size:12px; color:#7f1d1d;">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Catatan untuk Admin:</strong>
        Setelah setup selesai, guru yang ditandai sebagai "Tim Aduan" akan melihat menu Aduan Siswa di panel notifikasi mereka. 
        Guru biasa <strong>tidak</strong> melihat menu aduan sama sekali. 
        Notifikasi aduan selalu <strong>anonim</strong> — nama dan identitas pelapor tidak ditampilkan.
    </div>
    <div class="mt-2 text-center">
        <a href="home.php" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;">← Kembali ke Dashboard</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
