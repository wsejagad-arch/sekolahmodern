<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

if (!is_guru()) {
    header('Location: ../../403.php');
    exit;
}

$nipGuru = (string)($_SESSION['no_induk'] ?? '');
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$message = '';
$messageType = 'success';

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_guru_wali_binaan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    no_induk_guru VARCHAR(50) NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_guru_siswa (no_induk_guru, no_induk_siswa),
    KEY idx_guru (no_induk_guru),
    KEY idx_siswa (no_induk_siswa),
    KEY idx_kelas (kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'add') {
        $kelas = trim((string)($_POST['kelas'] ?? ''));
        $nis = trim((string)($_POST['no_induk_siswa'] ?? ''));
        if ($kelas === '' || $nis === '') {
            $message = 'Pilih kelas dan siswa terlebih dahulu.';
            $messageType = 'danger';
        } else {
            $kelasEsc = mysqli_real_escape_string($conn, $kelas);
            $nisEsc = mysqli_real_escape_string($conn, $nis);
            $qValid = @mysqli_query($conn, "SELECT no_induk FROM tbl_siswa WHERE no_induk='{$nisEsc}' AND kelas='{$kelasEsc}' LIMIT 1");
            if (!$qValid || mysqli_num_rows($qValid) === 0) {
                $message = 'Siswa tidak ditemukan pada kelas yang dipilih.';
                $messageType = 'danger';
            } else {
                $ok = @mysqli_query($conn, "INSERT IGNORE INTO tbl_guru_wali_binaan (no_induk_guru, no_induk_siswa, kelas) VALUES ('{$nipEsc}', '{$nisEsc}', '{$kelasEsc}')");
                $message = $ok ? 'Siswa binaan berhasil ditambahkan.' : 'Gagal menambahkan siswa binaan: ' . mysqli_error($conn);
                $messageType = $ok ? 'success' : 'danger';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $ok = $id > 0 ? @mysqli_query($conn, "DELETE FROM tbl_guru_wali_binaan WHERE id={$id} AND no_induk_guru='{$nipEsc}'") : false;
        $message = $ok ? 'Siswa binaan berhasil dihapus.' : 'Gagal menghapus siswa binaan.';
        $messageType = $ok ? 'success' : 'danger';
    }
}

$studentsByClass = [];
$kelasOptions = [];
$qStudents = @mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE kelas <> '' AND (status='Aktif' OR status='' OR status IS NULL OR UPPER(status)='AKTIF') ORDER BY kelas ASC, nama_siswa ASC");
while ($qStudents && ($row = mysqli_fetch_assoc($qStudents))) {
    $kelas = (string)$row['kelas'];
    $kelasOptions[$kelas] = $kelas;
    $studentsByClass[$kelas][] = [
        'no_induk' => (string)$row['no_induk'],
        'nama_siswa' => (string)$row['nama_siswa'],
    ];
}
ksort($kelasOptions, SORT_NATURAL | SORT_FLAG_CASE);

$binaan = [];
$qBinaan = @mysqli_query($conn, "SELECT b.id, b.kelas, b.no_induk_siswa, s.nama_siswa
    FROM tbl_guru_wali_binaan b
    LEFT JOIN tbl_siswa s ON s.no_induk=b.no_induk_siswa
    WHERE b.no_induk_guru='{$nipEsc}'
    ORDER BY b.kelas ASC, s.nama_siswa ASC");
while ($qBinaan && ($row = mysqli_fetch_assoc($qBinaan))) {
    $binaan[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Siswa Binaan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body{background:#f8fafc;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#0f172a}
        .page{max-width:960px;margin:0 auto;padding:24px 14px 70px}
        .hero,.cardx{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 16px 36px rgba(15,23,42,.07)}
        .hero{padding:22px;margin-bottom:16px;display:flex;justify-content:space-between;gap:12px;align-items:center}
        .cardx{padding:18px}
        .btn-main{background:#0f766e;color:#fff;border:0;border-radius:12px;font-weight:800}
        .btn-main:hover{background:#115e59;color:#fff}
    </style>
</head>
<body>
<main class="page">
    <section class="hero">
        <div>
            <div class="text-uppercase fw-bold text-primary small">Guru Wali</div>
            <h1 class="h4 fw-bold mb-1">Tambah Siswa Binaan</h1>
            <p class="text-muted mb-0 small">Pilih kelas dan siswa yang menjadi binaan pribadi Anda.</p>
        </div>
        <a class="btn btn-outline-secondary rounded-pill fw-bold" href="guru_2026"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> rounded-4"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <section class="cardx mb-3">
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-bold small text-uppercase" for="kelas">Kelas</label>
                    <select class="form-select rounded-3" id="kelas" name="kelas" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($kelasOptions as $kelas): ?>
                            <option value="<?= htmlspecialchars($kelas); ?>"><?= htmlspecialchars($kelas); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-bold small text-uppercase" for="siswa">Siswa</label>
                    <select class="form-select rounded-3" id="siswa" name="no_induk_siswa" required disabled>
                        <option value="">Pilih kelas terlebih dahulu</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-main w-100" type="submit"><i class="bi bi-plus-circle"></i> Tambah</button>
                </div>
            </div>
        </form>
    </section>

    <section class="cardx">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0">Daftar Siswa Binaan</h2>
            <span class="badge text-bg-info"><?= count($binaan); ?> siswa</span>
        </div>
        <?php if (empty($binaan)): ?>
            <div class="text-center text-muted py-4 border rounded-4 border-dashed">Belum ada siswa binaan.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($binaan as $row): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <strong><?= htmlspecialchars((string)($row['nama_siswa'] ?: $row['no_induk_siswa'])); ?></strong>
                            <div class="small text-muted">Kelas <?= htmlspecialchars((string)$row['kelas']); ?> · NIS <?= htmlspecialchars((string)$row['no_induk_siswa']); ?></div>
                        </div>
                        <form method="post" onsubmit="return confirm('Hapus siswa dari daftar binaan?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<script>
const studentsByClass = <?= json_encode($studentsByClass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
document.getElementById('kelas').addEventListener('change', function () {
    const siswa = document.getElementById('siswa');
    const rows = studentsByClass[this.value] || [];
    siswa.innerHTML = '';
    if (!rows.length) {
        siswa.innerHTML = '<option value="">Tidak ada siswa aktif</option>';
        siswa.disabled = true;
        return;
    }
    siswa.innerHTML = '<option value="">Pilih siswa</option>';
    rows.forEach(row => {
        const opt = document.createElement('option');
        opt.value = row.no_induk;
        opt.textContent = `${row.nama_siswa || row.no_induk} - ${row.no_induk}`;
        siswa.appendChild(opt);
    });
    siswa.disabled = false;
});
</script>
</body>
</html>
