<?php
$hakAksesAduan = (int)($_SESSION['hak_akses'] ?? 0);
$noIndukAduan = (string)($_SESSION['no_induk'] ?? '');

function adm_ad_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function adm_ad_create_tables(mysqli $conn): void
{
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_aduan_siswa (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_aduan_tindak_lanjut (
            id_tindak INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_aduan INT UNSIGNED NOT NULL,
            tahap VARCHAR(40) NOT NULL,
            aksi VARCHAR(60) NOT NULL,
            catatan TEXT DEFAULT NULL,
            handled_by VARCHAR(50) NOT NULL DEFAULT '',
            handled_name VARCHAR(150) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_tindak),
            KEY idx_aduan (id_aduan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function adm_ad_stage_label(string $stage): string
{
    $labels = [
        'stpks' => 'STPKS',
        'bk' => 'BK',
        'kesiswaan' => 'Kesiswaan',
        'kepala_sekolah' => 'Kepala Sekolah',
        'selesai' => 'Selesai',
    ];
    return $labels[$stage] ?? strtoupper($stage);
}

function adm_ad_next_stage(string $stage): string
{
    $next = [
        'stpks' => 'bk',
        'bk' => 'kesiswaan',
        'kesiswaan' => 'kepala_sekolah',
        'kepala_sekolah' => 'selesai',
    ];
    return $next[$stage] ?? 'selesai';
}

function adm_ad_guru_role(mysqli $conn, string $noInduk): array
{
    if ($noInduk === '') {
        return ['name' => '', 'jabatan' => '', 'is_bk' => false, 'is_tim_aduan' => false, 'stages' => []];
    }
    $nipEsc = mysqli_real_escape_string($conn, $noInduk);
    $idSekolah = mt_current_school_id();
    $q = @mysqli_query($conn, "SELECT * FROM tbl_guru WHERE id_sekolah=$idSekolah AND no_induk='$nipEsc' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : [];
    $jabatan = strtolower((string)($row['jabatan'] ?? ''));
    $stages = [];
    if (!empty($row['is_guru_bk']) || strpos($jabatan, 'bk') !== false) $stages[] = 'bk';
    if (strpos($jabatan, 'stpks') !== false) $stages[] = 'stpks';
    if (strpos($jabatan, 'kesiswaan') !== false) $stages[] = 'kesiswaan';
    if (strpos($jabatan, 'kepala') !== false) $stages[] = 'kepala_sekolah';
    return [
        'name' => (string)($row['nama_guru'] ?? ''),
        'jabatan' => (string)($row['jabatan'] ?? ''),
        'is_bk' => !empty($row['is_guru_bk']),
        'is_tim_aduan' => (!empty($row['is_tim_aduan']) && (int)$row['is_tim_aduan'] === 1),
        'stages' => array_values(array_unique($stages)),
    ];
}

adm_ad_create_tables($conn);

$guruRoleAduan = adm_ad_guru_role($conn, $noIndukAduan);
$isAdminAduan = ($hakAksesAduan === 1 || $guruRoleAduan['is_tim_aduan']);
$allowedStagesAduan = $isAdminAduan ? ['stpks', 'bk', 'kesiswaan', 'kepala_sekolah'] : $guruRoleAduan['stages'];
if (!$isAdminAduan && empty($allowedStagesAduan)) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Anda tidak memiliki akses penanganan aduan siswa.</div></div>';
    return;
}

$flashAduan = '';
$flashTypeAduan = 'success';
$handlerName = $isAdminAduan ? (string)($_SESSION['nama'] ?? 'Admin') : ($guruRoleAduan['name'] ?: (string)($_SESSION['nama'] ?? 'Petugas'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aduan_action'])) {
    $idAduan = (int)($_POST['id_aduan'] ?? 0);
    $action = (string)($_POST['aduan_action'] ?? '');
    $catatan = trim((string)($_POST['catatan'] ?? ''));
    $idSekolah = mt_current_school_id();
    $qAduan = @mysqli_query($conn, "SELECT * FROM tbl_aduan_siswa WHERE id_sekolah=$idSekolah AND id_aduan=$idAduan LIMIT 1");
    $aduan = $qAduan ? mysqli_fetch_assoc($qAduan) : null;
    if (!$aduan) {
        $flashAduan = 'Aduan tidak ditemukan.';
        $flashTypeAduan = 'danger';
    } elseif (!$isAdminAduan && !in_array((string)$aduan['tahap_aktif'], $allowedStagesAduan, true)) {
        $flashAduan = 'Aduan ini belum berada pada tahap kewenangan Anda.';
        $flashTypeAduan = 'danger';
    } else {
        $currentStage = (string)$aduan['tahap_aktif'];
        $nextStage = $currentStage;
        $status = 'diproses';
        $aksi = $action;
        
        if ($action === 'hapus') {
            if ($hakAksesAduan !== 1) {
                $flashAduan = 'Hanya admin utama yang dapat menghapus aduan.';
                $flashTypeAduan = 'danger';
            } else {
                @mysqli_query($conn, "DELETE FROM tbl_aduan_tindak_lanjut WHERE id_aduan=$idAduan");
                $ok = @mysqli_query($conn, "DELETE FROM tbl_aduan_siswa WHERE id_sekolah=$idSekolah AND id_aduan=$idAduan LIMIT 1");
                $flashAduan = $ok ? 'Aduan berhasil dihapus.' : 'Gagal menghapus aduan.';
                $flashTypeAduan = $ok ? 'success' : 'danger';
            }
        } else {
            if ($action === 'tangani') {
                $status = 'diproses';
            } elseif ($action === 'lanjut') {
                $nextStage = adm_ad_next_stage($currentStage);
                $status = $nextStage === 'selesai' ? 'selesai' : 'diteruskan';
            } elseif ($action === 'selesai') {
                $nextStage = 'selesai';
                $status = 'selesai';
            } else {
                $flashAduan = 'Aksi tidak valid.';
                $flashTypeAduan = 'danger';
            }

            if ($flashAduan === '') {
                $catatanEsc = mysqli_real_escape_string($conn, $catatan);
                $handlerEsc = mysqli_real_escape_string($conn, $noIndukAduan);
                $handlerNameEsc = mysqli_real_escape_string($conn, $handlerName);
                $stageEsc = mysqli_real_escape_string($conn, $currentStage);
                $aksiEsc = mysqli_real_escape_string($conn, $aksi);
                $statusEsc = mysqli_real_escape_string($conn, $status);
                $nextEsc = mysqli_real_escape_string($conn, $nextStage);
                @mysqli_query($conn, "
                    INSERT INTO tbl_aduan_tindak_lanjut
                        (id_aduan, tahap, aksi, catatan, handled_by, handled_name)
                    VALUES
                        ($idAduan, '$stageEsc', '$aksiEsc', '$catatanEsc', '$handlerEsc', '$handlerNameEsc')
                ");
                $closedSql = $nextStage === 'selesai' ? ', closed_at=NOW()' : '';
                $idSekolah = mt_current_school_id();
                $ok = @mysqli_query($conn, "
                    UPDATE tbl_aduan_siswa
                    SET status='$statusEsc', tahap_aktif='$nextEsc' $closedSql
                    WHERE id_sekolah=$idSekolah AND id_aduan=$idAduan
                    LIMIT 1
                ");
                $flashAduan = $ok ? 'Status aduan berhasil diperbarui.' : 'Gagal memperbarui aduan.';
                $flashTypeAduan = $ok ? 'success' : 'danger';
            }
        }
    }
}

$filterStatus = (string)($_GET['status'] ?? 'aktif');
$idSekolah = mt_current_school_id();
$whereAduan = "a.id_sekolah=$idSekolah";
if ($filterStatus === 'selesai') {
    $whereAduan .= " AND a.status='selesai'";
} elseif ($filterStatus === 'semua') {
    $whereAduan .= "";
} else {
    $whereAduan .= " AND a.status <> 'selesai'";
}
if (!$isAdminAduan) {
    $stageIn = "'" . implode("','", array_map(static function ($s) use ($conn) {
        return mysqli_real_escape_string($conn, $s);
    }, $allowedStagesAduan)) . "'";
    $whereAduan .= " AND (a.tahap_aktif IN ($stageIn) OR a.status='selesai')";
}

$aduanRows = [];
$qRows = @mysqli_query($conn, "
    SELECT a.*
    FROM tbl_aduan_siswa a
    WHERE $whereAduan
    ORDER BY FIELD(a.prioritas,'darurat','tinggi','normal'), a.created_at DESC
    LIMIT 100
");
while ($qRows && ($row = mysqli_fetch_assoc($qRows))) {
    $aduanRows[] = $row;
}

$histories = [];
if (!empty($aduanRows)) {
    $ids = implode(',', array_map(static fn($r) => (int)$r['id_aduan'], $aduanRows));
    $qHist = @mysqli_query($conn, "SELECT * FROM tbl_aduan_tindak_lanjut WHERE id_aduan IN ($ids) ORDER BY created_at ASC");
    while ($qHist && ($row = mysqli_fetch_assoc($qHist))) {
        $histories[(int)$row['id_aduan']][] = $row;
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4" style="gap:12px;">
        <div>
            <h1 class="h3 mb-1 text-gray-800"><i class="fas fa-shield-heart text-danger mr-2"></i>Aduan Siswa</h1>
            <p class="text-muted mb-0">Alur penanganan: STPKS → BK → Kesiswaan → Kepala Sekolah. Aduan dapat diselesaikan pada tahap mana pun.</p>
        </div>
        <div class="btn-group">
            <a href="home.php?page=aduan-siswa&status=aktif" class="btn btn-sm <?= $filterStatus === 'aktif' ? 'btn-primary' : 'btn-outline-primary'; ?>">Aktif</a>
            <a href="home.php?page=aduan-siswa&status=selesai" class="btn btn-sm <?= $filterStatus === 'selesai' ? 'btn-primary' : 'btn-outline-primary'; ?>">Selesai</a>
            <a href="home.php?page=aduan-siswa&status=semua" class="btn btn-sm <?= $filterStatus === 'semua' ? 'btn-primary' : 'btn-outline-primary'; ?>">Semua</a>
        </div>
    </div>

    <?php if ($flashAduan !== ''): ?>
        <div class="alert alert-<?= adm_ad_h($flashTypeAduan); ?>"><?= adm_ad_h($flashAduan); ?></div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($aduanRows)): ?>
            <div class="col-12"><div class="alert alert-info">Belum ada aduan pada filter ini.</div></div>
        <?php endif; ?>
        <?php foreach ($aduanRows as $row): ?>
            <?php
            $canHandleThis = $isAdminAduan || in_array((string)$row['tahap_aktif'], $allowedStagesAduan, true);
            $priorityStyle = $row['prioritas'] === 'darurat' ? 'danger' : ($row['prioritas'] === 'tinggi' ? 'warning' : 'secondary');
            ?>
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius:18px; overflow:hidden;">
                    <div class="card-header bg-white border-0 pb-0">
                        <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                            <div>
                                <div class="small text-muted font-weight-bold"><?= adm_ad_h($row['kode_aduan']); ?></div>
                                <h5 class="font-weight-bold mb-1"><?= adm_ad_h($row['judul']); ?></h5>
                                <span class="badge badge-<?= $priorityStyle; ?>"><?= adm_ad_h(strtoupper($row['prioritas'])); ?></span>
                                <span class="badge badge-info"><?= adm_ad_h($row['kategori']); ?></span>
                                <span class="badge badge-primary">Tahap <?= adm_ad_h(adm_ad_stage_label((string)$row['tahap_aktif'])); ?></span>
                            </div>
                            <div class="text-right small text-muted"><?= date('d M Y H:i', strtotime($row['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="p-3 mb-3" style="background:#f8fafc; border-radius:14px;">
                            <div class="small text-muted font-weight-bold mb-1">Identitas Pelapor</div>
                            <div><strong>Pelapor Anonim</strong> - <?= adm_ad_h($row['kelas_pelapor']); ?></div>
                            <div class="small text-muted">Aduan ini bersifat anonim untuk menjaga privasi pelapor.</div>
                        </div>
                        <div class="mb-2"><strong>Lokasi:</strong> <?= adm_ad_h($row['lokasi'] ?: '-'); ?></div>
                        <div class="mb-3"><strong>Tanggal kejadian:</strong> <?= $row['tanggal_kejadian'] ? date('d M Y', strtotime($row['tanggal_kejadian'])) : '-'; ?></div>
                        <div style="white-space:pre-wrap; color:#334155;"><?= adm_ad_h($row['isi_laporan']); ?></div>

                        <hr>
                        <div class="small font-weight-bold mb-2">Histori Penanganan</div>
                        <div class="mb-3" style="max-height:160px; overflow:auto;">
                            <?php foreach (($histories[(int)$row['id_aduan']] ?? []) as $hist): ?>
                                <div class="small mb-2 p-2" style="background:#f8fafc; border-radius:10px;">
                                    <strong><?= adm_ad_h(adm_ad_stage_label((string)$hist['tahap'])); ?></strong> - <?= adm_ad_h($hist['aksi']); ?>
                                    <div class="text-muted"><?= date('d M Y H:i', strtotime($hist['created_at'])); ?> oleh <?= adm_ad_h($hist['handled_name']); ?></div>
                                    <?php if (!empty($hist['catatan'])): ?><div><?= nl2br(adm_ad_h($hist['catatan'])); ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($row['status'] !== 'selesai' && $canHandleThis): ?>
                            <form method="post">
                                <input type="hidden" name="id_aduan" value="<?= (int)$row['id_aduan']; ?>">
                                <label class="small font-weight-bold">Catatan penanganan</label>
                                <textarea class="form-control mb-2" name="catatan" rows="2" placeholder="Tulis catatan tindak lanjut..."></textarea>
                                <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                                    <button class="btn btn-sm btn-outline-primary" name="aduan_action" value="tangani" type="submit">Sudah Ditangani</button>
                                    <button class="btn btn-sm btn-outline-warning" name="aduan_action" value="lanjut" type="submit">Teruskan ke Tahap Berikut</button>
                                    <button class="btn btn-sm btn-success" name="aduan_action" value="selesai" type="submit" onclick="return confirm('Tandai aduan selesai?');">Selesai</button>
                                    <?php if ($hakAksesAduan === 1): ?>
                                    <button class="btn btn-sm btn-outline-danger ml-auto" name="aduan_action" value="hapus" type="submit" onclick="return confirm('Yakin ingin menghapus aduan ini beserta historinya? Tindakan ini tidak dapat dibatalkan.');"><i class="fas fa-trash"></i> Hapus</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php elseif ($row['status'] === 'selesai'): ?>
                            <div class="alert alert-success mb-2 py-2">Aduan selesai pada <?= $row['closed_at'] ? date('d M Y H:i', strtotime($row['closed_at'])) : '-'; ?>.</div>
                            <?php if ($hakAksesAduan === 1): ?>
                            <form method="post">
                                <input type="hidden" name="id_aduan" value="<?= (int)$row['id_aduan']; ?>">
                                <button class="btn btn-sm btn-outline-danger mt-1" name="aduan_action" value="hapus" type="submit" onclick="return confirm('Yakin ingin menghapus aduan ini beserta historinya? Tindakan ini tidak dapat dibatalkan.');"><i class="fas fa-trash"></i> Hapus Aduan</button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-2 py-2">Menunggu tahap <?= adm_ad_h(adm_ad_stage_label((string)$row['tahap_aktif'])); ?>.</div>
                            <?php if ($hakAksesAduan === 1): ?>
                            <form method="post">
                                <input type="hidden" name="id_aduan" value="<?= (int)$row['id_aduan']; ?>">
                                <button class="btn btn-sm btn-outline-danger mt-1" name="aduan_action" value="hapus" type="submit" onclick="return confirm('Yakin ingin menghapus aduan ini beserta historinya? Tindakan ini tidak dapat dibatalkan.');"><i class="fas fa-trash"></i> Hapus Aduan</button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
