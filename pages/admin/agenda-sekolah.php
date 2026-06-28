<?php
require_once __DIR__ . '/../../agenda_helper.php';

date_default_timezone_set('Asia/Jakarta');
agenda_ensure_table($conn);

$hakAkses = (int)($_SESSION['hak_akses'] ?? 0);
$noInduk = (string)($_SESSION['no_induk'] ?? '');
$namaUser = (string)($_SESSION['nama'] ?? ($_SESSION['nama_guru'] ?? 'Pengguna'));
$guruProfile = null;
$userUnit = '';
$canManage = agenda_can_manage_user($conn, $hakAkses, $noInduk, $guruProfile, $userUnit);
$isAdmin = ($hakAkses === 1);
$canView = $isAdmin || $hakAkses === 2;

if (!$canView) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Anda tidak memiliki akses ke halaman agenda.</div></div>';
    return;
}

$alertType = '';
$alertMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $action = $_POST['agenda_action'] ?? '';

    if ($action === 'save') {
        $idAgenda = (int)($_POST['id_agenda'] ?? 0);
        $judul = trim((string)($_POST['judul'] ?? ''));
        $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
        $agendaDate = trim((string)($_POST['agenda_date'] ?? ''));
        $jamMulai = trim((string)($_POST['jam_mulai'] ?? ''));
        $jamSelesai = trim((string)($_POST['jam_selesai'] ?? ''));
        $unitInput = trim((string)($_POST['dibuat_unit'] ?? ''));
        $dibuatUnit = agenda_clean_unit($unitInput, $isAdmin, $userUnit);

        if ($judul === '' || $agendaDate === '' || $jamMulai === '' || $jamSelesai === '') {
            $alertType = 'danger';
            $alertMessage = 'Judul, tanggal, jam mulai, dan jam selesai wajib diisi.';
        } elseif (strtotime($agendaDate . ' ' . $jamSelesai) <= strtotime($agendaDate . ' ' . $jamMulai)) {
            $alertType = 'danger';
            $alertMessage = 'Jam selesai harus lebih besar dari jam mulai.';
        } else {
            $judulEsc = mysqli_real_escape_string($conn, $judul);
            $deskEsc = mysqli_real_escape_string($conn, $deskripsi);
            $dateEsc = mysqli_real_escape_string($conn, $agendaDate);
            $startEsc = mysqli_real_escape_string($conn, $jamMulai);
            $endEsc = mysqli_real_escape_string($conn, $jamSelesai);
            $unitEsc = mysqli_real_escape_string($conn, $dibuatUnit);
            $namaEsc = mysqli_real_escape_string($conn, $namaUser);
            $idEsc = mysqli_real_escape_string($conn, $noInduk);

            if ($idAgenda > 0) {
                $sql = "UPDATE tbl_agenda_sekolah
                        SET judul='" . $judulEsc . "',
                            deskripsi='" . $deskEsc . "',
                            agenda_date='" . $dateEsc . "',
                            jam_mulai='" . $startEsc . "',
                            jam_selesai='" . $endEsc . "',
                            dibuat_unit='" . $unitEsc . "',
                            is_active=1
                        WHERE id_agenda=" . $idAgenda . " AND id_sekolah=" . mt_current_school_id();

                if (!$isAdmin) {
                    $unitCond = mysqli_real_escape_string($conn, $userUnit);
                    $sql .= " AND (dibuat_unit='" . $unitCond . "' OR dibuat_oleh_id='" . $idEsc . "')";
                }

                $ok = mysqli_query($conn, $sql);
                if ($ok) {
                    $alertType = 'success';
                    $alertMessage = 'Agenda berhasil diperbarui.';
                } else {
                    $alertType = 'danger';
                    $alertMessage = 'Gagal memperbarui agenda: ' . mysqli_error($conn);
                }
            } else {
                $roleNama = $isAdmin ? 'admin' : 'guru';
                $roleEsc = mysqli_real_escape_string($conn, $roleNama);

                $sql = "INSERT INTO tbl_agenda_sekolah
                        (judul, deskripsi, agenda_date, jam_mulai, jam_selesai, dibuat_unit, dibuat_oleh_role, dibuat_oleh_id, dibuat_oleh_nama, is_active)
                        VALUES
                        ('" . $judulEsc . "', '" . $deskEsc . "', '" . $dateEsc . "', '" . $startEsc . "', '" . $endEsc . "', '" . $unitEsc . "', '" . $roleEsc . "', '" . $idEsc . "', '" . $namaEsc . "', 1)";

                if (mysqli_query($conn, $sql)) {
                    $alertType = 'success';
                    $alertMessage = 'Agenda baru berhasil ditambahkan.';
                } else {
                    $alertType = 'danger';
                    $alertMessage = 'Gagal menambahkan agenda: ' . mysqli_error($conn);
                }
            }
        }
    }

    if ($action === 'delete') {
        $idAgenda = (int)($_POST['id_agenda'] ?? 0);
        if ($idAgenda > 0) {
            $sql = "UPDATE tbl_agenda_sekolah SET is_active=0 WHERE id_agenda=" . $idAgenda . " AND id_sekolah=" . mt_current_school_id();
            if (!$isAdmin) {
                $idEsc = mysqli_real_escape_string($conn, $noInduk);
                $unitEsc = mysqli_real_escape_string($conn, $userUnit);
                $sql .= " AND (dibuat_unit='" . $unitEsc . "' OR dibuat_oleh_id='" . $idEsc . "')";
            }

            if (mysqli_query($conn, $sql)) {
                $alertType = 'success';
                $alertMessage = 'Agenda dinonaktifkan.';
            } else {
                $alertType = 'danger';
                $alertMessage = 'Gagal menghapus agenda: ' . mysqli_error($conn);
            }
        }
    }
}

$agendaEdit = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $qEdit = mysqli_query($conn, "SELECT * FROM tbl_agenda_sekolah WHERE id_agenda=" . $editId . " AND id_sekolah=" . mt_current_school_id() . " LIMIT 1");
        if ($qEdit && mysqli_num_rows($qEdit) > 0) {
            $agendaEdit = mysqli_fetch_assoc($qEdit);
            if (!$isAdmin) {
                $createdBy = (string)($agendaEdit['dibuat_oleh_id'] ?? '');
                $createdUnit = (string)($agendaEdit['dibuat_unit'] ?? '');
                if ($createdBy !== $noInduk && $createdUnit !== $userUnit) {
                    $agendaEdit = null;
                }
            }
        }
    }
}

$allowedFilters = ['all', 'today', 'week', 'active', 'passed'];
$agendaFilter = strtolower(trim((string)($_GET['filter'] ?? 'all')));
if (!in_array($agendaFilter, $allowedFilters, true)) {
    $agendaFilter = 'all';
}

$agendaItems = agenda_get_all_for_manage($conn, 120, $agendaFilter);
$unitOptions = agenda_allowed_units();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Agenda Sekolah</h1>
        <a href="home.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i>Kembali ke Dashboard
        </a>
    </div>

    <?php if ($alertMessage !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($alertType); ?>">
            <?= htmlspecialchars($alertMessage); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow border-0">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?= $agendaEdit ? 'Edit Agenda' : 'Tambah Agenda'; ?>
                    </h6>
                    <?php if (!$canManage): ?>
                        <span class="badge badge-secondary">Hanya baca</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($canManage): ?>
                        <form method="post" action="home.php?page=agenda-sekolah<?= $agendaEdit ? '&edit=' . (int)$agendaEdit['id_agenda'] : ''; ?>">
                            <input type="hidden" name="agenda_action" value="save">
                            <input type="hidden" name="id_agenda" value="<?= (int)($agendaEdit['id_agenda'] ?? 0); ?>">

                            <div class="form-group">
                                <label class="font-weight-bold">Judul Agenda</label>
                                <input type="text" name="judul" class="form-control" maxlength="180" required value="<?= htmlspecialchars($agendaEdit['judul'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" class="form-control" placeholder="Opsional"><?= htmlspecialchars($agendaEdit['deskripsi'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">Tanggal</label>
                                    <input type="date" name="agenda_date" class="form-control" required value="<?= htmlspecialchars($agendaEdit['agenda_date'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="font-weight-bold">Mulai</label>
                                    <input type="time" name="jam_mulai" class="form-control" required value="<?= htmlspecialchars(substr((string)($agendaEdit['jam_mulai'] ?? '07:00:00'), 0, 5)); ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="font-weight-bold">Selesai</label>
                                    <input type="time" name="jam_selesai" class="form-control" required value="<?= htmlspecialchars(substr((string)($agendaEdit['jam_selesai'] ?? '08:00:00'), 0, 5)); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Bidang</label>
                                <?php if ($isAdmin): ?>
                                    <select name="dibuat_unit" class="form-control" required>
                                        <?php foreach ($unitOptions as $unitOpt): ?>
                                            <option value="<?= htmlspecialchars($unitOpt); ?>" <?= (($agendaEdit['dibuat_unit'] ?? '') === $unitOpt ? 'selected' : ''); ?>>
                                                <?= htmlspecialchars($unitOpt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="form-control" readonly value="<?= htmlspecialchars($userUnit); ?>">
                                    <input type="hidden" name="dibuat_unit" value="<?= htmlspecialchars($userUnit); ?>">
                                <?php endif; ?>
                            </div>

                            <div class="d-flex" style="gap:8px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i><?= $agendaEdit ? 'Simpan Perubahan' : 'Tambah Agenda'; ?>
                                </button>
                                <?php if ($agendaEdit): ?>
                                    <a href="home.php?page=agenda-sekolah" class="btn btn-light border">Batal Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">Akun ini hanya bisa melihat agenda aktif. Tambah/Edit agenda hanya untuk Admin dan Guru dengan jabatan waka Kurikulum/Kesiswaan, Humas, atau Sarpras.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow border-0">
                <div class="card-header py-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Agenda</h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Filter agenda">
                            <a href="home.php?page=agenda-sekolah&filter=all" class="btn <?= $agendaFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">Semua</a>
                            <a href="home.php?page=agenda-sekolah&filter=today" class="btn <?= $agendaFilter === 'today' ? 'btn-primary' : 'btn-outline-primary'; ?>">Hari Ini</a>
                            <a href="home.php?page=agenda-sekolah&filter=week" class="btn <?= $agendaFilter === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">7 Hari</a>
                            <a href="home.php?page=agenda-sekolah&filter=active" class="btn <?= $agendaFilter === 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>">Aktif</a>
                            <a href="home.php?page=agenda-sekolah&filter=passed" class="btn <?= $agendaFilter === 'passed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Lewat</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($agendaItems)): ?>
                        <div class="p-4 text-muted">Belum ada agenda tersimpan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Agenda</th>
                                        <th>Waktu</th>
                                        <th>Bidang</th>
                                        <th>Status</th>
                                        <th style="width:150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($agendaItems as $item): ?>
                                        <?php
                                        $endAt = strtotime((string)$item['selesai_at']);
                                        $isPassed = $endAt !== false ? ($endAt < time()) : false;
                                        $canEditRow = $isAdmin || (($item['dibuat_oleh_id'] ?? '') === $noInduk) || (($item['dibuat_unit'] ?? '') === $userUnit);
                                        $unitPalette = agenda_unit_palette((string)($item['dibuat_unit'] ?? ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold text-dark"><?= htmlspecialchars($item['judul']); ?></div>
                                                <?php if (!empty($item['deskripsi'])): ?>
                                                    <div class="small text-muted"><?= nl2br(htmlspecialchars($item['deskripsi'])); ?></div>
                                                <?php endif; ?>
                                                <div class="small text-muted mt-1">Oleh: <?= htmlspecialchars($item['dibuat_oleh_nama']); ?></div>
                                            </td>
                                            <td class="small">
                                                <?= date('d/m/Y', strtotime($item['agenda_date'])); ?><br>
                                                <?= substr((string)$item['jam_mulai'], 0, 5); ?> - <?= substr((string)$item['jam_selesai'], 0, 5); ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:<?= htmlspecialchars($unitPalette['bg']); ?>; color:<?= htmlspecialchars($unitPalette['text']); ?>; border:1px solid <?= htmlspecialchars($unitPalette['border']); ?>;">
                                                    <?= htmlspecialchars($item['dibuat_unit']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ((int)$item['is_active'] !== 1): ?>
                                                    <span class="badge badge-secondary">Nonaktif</span>
                                                <?php elseif ($isPassed): ?>
                                                    <span class="badge badge-warning">Selesai</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($canManage && $canEditRow): ?>
                                                    <a href="home.php?page=agenda-sekolah&edit=<?= (int)$item['id_agenda']; ?>" class="btn btn-sm btn-outline-primary mb-1">Edit</a>
                                                    <form method="post" action="home.php?page=agenda-sekolah" onsubmit="return confirm('Nonaktifkan agenda ini?');" style="display:inline;">
                                                        <input type="hidden" name="agenda_action" value="delete">
                                                        <input type="hidden" name="id_agenda" value="<?= (int)$item['id_agenda']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger mb-1">Hapus</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>