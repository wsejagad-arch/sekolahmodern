<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../koneksi.php';
}
if (!function_exists('is_admin')) {
    require_once __DIR__ . '/../../auth_helper.php';
}
if (!is_admin()) {
    echo '<div class="alert alert-danger">Akses ditolak. Hanya admin yang bisa membuka halaman ini.</div>';
    return;
}

date_default_timezone_set('Asia/Jakarta');

$showNoWa = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
if ($showNoWa && mysqli_num_rows($showNoWa) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru");
}
$showJabatan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'jabatan'");
if ($showJabatan && mysqli_num_rows($showJabatan) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian");
}
@mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN IF NOT EXISTS alamat TEXT DEFAULT NULL AFTER no_wa");
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
    kunci VARCHAR(60) PRIMARY KEY,
    nilai VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai) VALUES ('izin_edit_profil_guru','0')");

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengajuan_profil_guru (
    id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,
    no_induk VARCHAR(25) NOT NULL,
    nama_guru VARCHAR(150) NOT NULL,
    no_wa VARCHAR(20) DEFAULT NULL,
    status_kepegawaian VARCHAR(50) DEFAULT NULL,
    jabatan VARCHAR(100) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    status_pengajuan ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
    catatan_guru TEXT DEFAULT NULL,
    catatan_admin TEXT DEFAULT NULL,
    reviewed_by VARCHAR(25) DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pengajuan_no_induk (no_induk),
    INDEX idx_pengajuan_status (status_pengajuan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$adminId = (string)($_SESSION['no_induk'] ?? $_SESSION['id_user'] ?? 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_izin_edit_profil_guru'])) {
    $nilaiBaru = isset($_POST['izin_edit_profil_guru']) && $_POST['izin_edit_profil_guru'] === '1' ? '1' : '0';
    $nilaiEsc = mysqli_real_escape_string($conn, $nilaiBaru);
    $okToggle = mysqli_query($conn, "UPDATE tbl_pengaturan SET nilai='$nilaiEsc' WHERE kunci='izin_edit_profil_guru'");
    $_SESSION['admin_flash'] = [
        'type' => $okToggle ? 'success' : 'danger',
        'msg' => $okToggle
            ? ($nilaiBaru === '1' ? 'Edit profil guru dibuka.' : 'Edit profil guru dikunci.')
            : 'Gagal memperbarui izin edit profil guru: ' . mysqli_error($conn)
    ];
    echo "<script>window.location='home.php?page=validasi-profil-guru';</script>";
    return;
}

$izinEditProfilGuru = '0';
$qIzinEditGuru = mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil_guru' LIMIT 1");
if ($qIzinEditGuru && ($rIzinEditGuru = mysqli_fetch_assoc($qIzinEditGuru))) {
    $izinEditProfilGuru = (string)($rIzinEditGuru['nilai'] ?? '0');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'], $_POST['id_pengajuan'])) {
    $aksi = (string)$_POST['aksi'];
    $idPengajuan = (int)$_POST['id_pengajuan'];
    $catatanAdmin = trim((string)($_POST['catatan_admin'] ?? ''));

    $qData = mysqli_query($conn, "SELECT * FROM tbl_pengajuan_profil_guru WHERE id_pengajuan=" . $idPengajuan . " LIMIT 1");
    $pengajuan = $qData ? mysqli_fetch_assoc($qData) : null;

    if (!$pengajuan) {
        $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => 'Data pengajuan tidak ditemukan.'];
        echo "<script>window.location='home.php?page=validasi-profil-guru';</script>";
        return;
    }

    if (($pengajuan['status_pengajuan'] ?? '') !== 'Menunggu') {
        $_SESSION['admin_flash'] = ['type' => 'warning', 'msg' => 'Pengajuan ini sudah diproses sebelumnya.'];
        echo "<script>window.location='home.php?page=validasi-profil-guru';</script>";
        return;
    }

    $noIndukEsc = mysqli_real_escape_string($conn, (string)$pengajuan['no_induk']);
    $namaEsc = mysqli_real_escape_string($conn, (string)$pengajuan['nama_guru']);
    $waEsc = mysqli_real_escape_string($conn, (string)($pengajuan['no_wa'] ?? ''));
    $statusPegEsc = mysqli_real_escape_string($conn, (string)($pengajuan['status_kepegawaian'] ?? ''));
    $jabatanEsc = mysqli_real_escape_string($conn, (string)($pengajuan['jabatan'] ?? ''));
    $fotoEsc = mysqli_real_escape_string($conn, (string)($pengajuan['foto'] ?? ''));
    $catatanEsc = mysqli_real_escape_string($conn, $catatanAdmin);
    $adminEsc = mysqli_real_escape_string($conn, $adminId);

    if ($aksi === 'setujui') {
        mysqli_begin_transaction($conn);
        $okGuru = mysqli_query($conn, "UPDATE tbl_guru SET
            nama_guru='" . $namaEsc . "',
            no_wa='" . $waEsc . "',
            status_kepegawaian='" . $statusPegEsc . "',
            jabatan='" . $jabatanEsc . "',
            foto='" . $fotoEsc . "'
            WHERE no_induk='" . $noIndukEsc . "'");

        $okPengajuan = mysqli_query($conn, "UPDATE tbl_pengajuan_profil_guru SET
            status_pengajuan='Disetujui',
            catatan_admin='" . $catatanEsc . "',
            reviewed_by='" . $adminEsc . "',
            reviewed_at=NOW(),
            updated_at=NOW()
            WHERE id_pengajuan=" . $idPengajuan);

        if ($okGuru && $okPengajuan) {
            mysqli_commit($conn);
            $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Pengajuan profil guru disetujui dan perubahan telah diterapkan.'];
        } else {
            mysqli_rollback($conn);
            $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => 'Gagal menyetujui pengajuan: ' . mysqli_error($conn)];
        }
    } elseif ($aksi === 'tolak') {
        $okReject = mysqli_query($conn, "UPDATE tbl_pengajuan_profil_guru SET
            status_pengajuan='Ditolak',
            catatan_admin='" . $catatanEsc . "',
            reviewed_by='" . $adminEsc . "',
            reviewed_at=NOW(),
            updated_at=NOW()
            WHERE id_pengajuan=" . $idPengajuan);

        if ($okReject) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'msg' => 'Pengajuan profil guru ditolak. Data guru aktif tidak berubah.'];
        } else {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => 'Gagal menolak pengajuan: ' . mysqli_error($conn)];
        }
    }

    echo "<script>window.location='home.php?page=validasi-profil-guru';</script>";
    return;
}

$pesanAdmin = '';
if (!empty($_SESSION['admin_flash'])) {
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    $type = htmlspecialchars((string)($flash['type'] ?? 'info'));
    $msg = htmlspecialchars((string)($flash['msg'] ?? ''));
    $pesanAdmin = '<div class="alert alert-' . $type . ' alert-dismissible fade show">'
        . $msg
        . '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
        . '</div>';
}

$pending = [];
$qPending = mysqli_query($conn, "SELECT p.*, g.nama_guru AS nama_guru_aktif, g.foto AS foto_aktif
    FROM tbl_pengajuan_profil_guru p
    LEFT JOIN tbl_guru g ON g.no_induk = p.no_induk
    WHERE p.status_pengajuan='Menunggu'
    ORDER BY p.created_at ASC");
if ($qPending) {
    while ($r = mysqli_fetch_assoc($qPending)) {
        $pending[] = $r;
    }
}

$riwayat = [];
$qRiwayat = mysqli_query($conn, "SELECT p.*, g.nama_guru AS nama_guru_aktif
    FROM tbl_pengajuan_profil_guru p
    LEFT JOIN tbl_guru g ON g.no_induk = p.no_induk
    WHERE p.status_pengajuan IN ('Disetujui','Ditolak')
    ORDER BY p.reviewed_at DESC, p.updated_at DESC
    LIMIT 20");
if ($qRiwayat) {
    while ($r = mysqli_fetch_assoc($qRiwayat)) {
        $riwayat[] = $r;
    }
}
?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="m-0"><i class="fas fa-user-check mr-2 text-primary"></i>Validasi Profil Guru</h4>
        <span class="badge badge-danger" style="font-size:.85rem;">Menunggu: <?= count($pending); ?></span>
    </div>

    <?= $pesanAdmin; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div>
                <strong><i class="fas fa-lock mr-2 text-primary"></i>Kunci Edit Profil Guru</strong>
                <div class="text-muted small">Atur apakah guru boleh mengubah identitas dan foto profil sendiri.</div>
            </div>
            <span class="badge badge-<?= $izinEditProfilGuru === '1' ? 'success' : 'secondary'; ?>" style="font-size:.85rem;">
                <?= $izinEditProfilGuru === '1' ? 'Edit Dibuka' : 'Edit Dikunci'; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="post" class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
                <input type="hidden" name="toggle_izin_edit_profil_guru" value="1">
                <input type="hidden" name="izin_edit_profil_guru" value="<?= $izinEditProfilGuru === '1' ? '0' : '1'; ?>">
                <div class="text-muted">
                    <?= $izinEditProfilGuru === '1'
                        ? 'Guru saat ini dapat menyimpan perubahan profil dan mengganti foto.'
                        : 'Guru saat ini hanya dapat melihat profil. Input dan upload foto dikunci.'; ?>
                </div>
                <button type="submit" class="btn btn-<?= $izinEditProfilGuru === '1' ? 'warning' : 'success'; ?>" onclick="return confirm('Ubah izin edit profil guru?');">
                    <i class="fas fa-<?= $izinEditProfilGuru === '1' ? 'lock' : 'unlock'; ?>"></i>
                    <?= $izinEditProfilGuru === '1' ? 'Kunci Edit Profil' : 'Buka Edit Profil'; ?>
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><strong>Pengajuan Menunggu Validasi</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Guru</th>
                            <th>Perubahan</th>
                            <th>Catatan Guru</th>
                            <th>Diajukan</th>
                            <th style="min-width:280px;">Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada pengajuan profil yang menunggu.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending as $i => $p): ?>
                                <tr>
                                    <td><?= $i + 1; ?></td>
                                    <td>
                                        <div><strong><?= htmlspecialchars((string)($p['nama_guru_aktif'] ?: $p['nama_guru'])); ?></strong></div>
                                        <small class="text-muted">NIP: <?= htmlspecialchars((string)$p['no_induk']); ?></small>
                                    </td>
                                    <td>
                                        <div><strong>Nama:</strong> <?= htmlspecialchars((string)$p['nama_guru']); ?></div>
                                        <div><strong>No WA:</strong> <?= htmlspecialchars((string)($p['no_wa'] ?: '-')); ?></div>
                                        <div><strong>Status Pegawai:</strong> <?= htmlspecialchars((string)($p['status_kepegawaian'] ?: '-')); ?></div>
                                        <div><strong>Jabatan:</strong> <?= htmlspecialchars((string)($p['jabatan'] ?: '-')); ?></div>
                                    </td>
                                    <td><?= nl2br(htmlspecialchars((string)($p['catatan_guru'] ?: '-'))); ?></td>
                                    <td><small><?= htmlspecialchars((string)$p['created_at']); ?></small></td>
                                    <td>
                                        <form method="post" class="mb-2">
                                            <input type="hidden" name="id_pengajuan" value="<?= (int)$p['id_pengajuan']; ?>">
                                            <input type="hidden" name="aksi" value="setujui">
                                            <textarea name="catatan_admin" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan admin (opsional)"></textarea>
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Setujui pengajuan ini?');">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="id_pengajuan" value="<?= (int)$p['id_pengajuan']; ?>">
                                            <input type="hidden" name="aksi" value="tolak">
                                            <textarea name="catatan_admin" class="form-control form-control-sm mb-2" rows="2" placeholder="Alasan penolakan (opsional)"></textarea>
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak pengajuan ini?');">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white"><strong>Riwayat Validasi Terakhir</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Guru</th>
                            <th>Status</th>
                            <th>Catatan Admin</th>
                            <th>Reviewer</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayat)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada riwayat validasi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat as $r): ?>
                                <?php $isOk = ($r['status_pengajuan'] === 'Disetujui'); ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($r['nama_guru_aktif'] ?: $r['nama_guru'])); ?> <small class="text-muted">(<?= htmlspecialchars((string)$r['no_induk']); ?>)</small></td>
                                    <td><span class="badge badge-<?= $isOk ? 'success' : 'danger'; ?>"><?= htmlspecialchars((string)$r['status_pengajuan']); ?></span></td>
                                    <td><?= nl2br(htmlspecialchars((string)($r['catatan_admin'] ?: '-'))); ?></td>
                                    <td><?= htmlspecialchars((string)($r['reviewed_by'] ?: '-')); ?></td>
                                    <td><?= htmlspecialchars((string)($r['reviewed_at'] ?: $r['updated_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
