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
    UNIQUE KEY uniq_guru_siswa (no_induk_guru, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_guru_wali_jurnal_pendampingan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    no_induk_guru VARCHAR(50) NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    catatan TEXT NOT NULL,
    tindak_lanjut TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Dipantau',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_guru_tanggal (no_induk_guru, tanggal),
    KEY idx_siswa (no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim((string)($_POST['no_induk_siswa'] ?? ''));
    $tanggal = trim((string)($_POST['tanggal'] ?? date('Y-m-d')));
    $catatan = trim((string)($_POST['catatan'] ?? ''));
    $tindakLanjut = trim((string)($_POST['tindak_lanjut'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'Dipantau'));

    if ($nis === '' || $catatan === '') {
        $message = 'Pilih siswa binaan dan isi catatan pendampingan.';
        $messageType = 'danger';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $message = 'Tanggal tidak valid.';
        $messageType = 'danger';
    } else {
        $nisEsc = mysqli_real_escape_string($conn, $nis);
        $qBinaan = @mysqli_query($conn, "SELECT kelas FROM tbl_guru_wali_binaan WHERE no_induk_guru='{$nipEsc}' AND no_induk_siswa='{$nisEsc}' LIMIT 1");
        $binaan = $qBinaan ? mysqli_fetch_assoc($qBinaan) : null;
        if (!$binaan) {
            $message = 'Siswa belum terdaftar sebagai binaan.';
            $messageType = 'danger';
        } else {
            $kelasEsc = mysqli_real_escape_string($conn, (string)$binaan['kelas']);
            $tanggalEsc = mysqli_real_escape_string($conn, $tanggal);
            $catatanEsc = mysqli_real_escape_string($conn, $catatan);
            $tindakEsc = mysqli_real_escape_string($conn, $tindakLanjut);
            $statusEsc = mysqli_real_escape_string($conn, $status);
            $ok = @mysqli_query($conn, "INSERT INTO tbl_guru_wali_jurnal_pendampingan
                (no_induk_guru, no_induk_siswa, kelas, tanggal, catatan, tindak_lanjut, status)
                VALUES ('{$nipEsc}', '{$nisEsc}', '{$kelasEsc}', '{$tanggalEsc}', '{$catatanEsc}', '{$tindakEsc}', '{$statusEsc}')");
            $message = $ok ? 'Jurnal pendampingan berhasil disimpan.' : 'Gagal menyimpan jurnal: ' . mysqli_error($conn);
            $messageType = $ok ? 'success' : 'danger';
        }
    }
}

$binaanList = [];
$qBinaanList = @mysqli_query($conn, "SELECT b.no_induk_siswa, b.kelas, s.nama_siswa
    FROM tbl_guru_wali_binaan b
    LEFT JOIN tbl_siswa s ON s.no_induk=b.no_induk_siswa
    WHERE b.no_induk_guru='{$nipEsc}'
    ORDER BY b.kelas ASC, s.nama_siswa ASC");
while ($qBinaanList && ($row = mysqli_fetch_assoc($qBinaanList))) {
    $binaanList[] = $row;
}

$jurnal = [];
$qJurnal = @mysqli_query($conn, "SELECT j.*, s.nama_siswa
    FROM tbl_guru_wali_jurnal_pendampingan j
    LEFT JOIN tbl_siswa s ON s.no_induk=j.no_induk_siswa
    WHERE j.no_induk_guru='{$nipEsc}'
    ORDER BY j.tanggal DESC, j.id DESC
    LIMIT 50");
while ($qJurnal && ($row = mysqli_fetch_assoc($qJurnal))) {
    $jurnal[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Pendampingan Guru Wali</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body{background:#f8fafc;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#0f172a}
        .page{max-width:980px;margin:0 auto;padding:24px 14px 70px}
        .hero,.cardx{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 16px 36px rgba(15,23,42,.07)}
        .hero{padding:22px;margin-bottom:16px;display:flex;justify-content:space-between;gap:12px;align-items:center}
        .cardx{padding:18px}
        .btn-main{background:#4338ca;color:#fff;border:0;border-radius:12px;font-weight:800}
        .btn-main:hover{background:#3730a3;color:#fff}
    </style>
</head>
<body>
<main class="page">
    <section class="hero">
        <div>
            <div class="text-uppercase fw-bold text-primary small">Guru Wali</div>
            <h1 class="h4 fw-bold mb-1">Jurnal Pendampingan</h1>
            <p class="text-muted mb-0 small">Catat pendampingan dan tindak lanjut siswa binaan.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-primary rounded-pill fw-bold" href="guru-wali-siswa"><i class="bi bi-person-plus"></i> Tambah Siswa</a>
            <a class="btn btn-outline-secondary rounded-pill fw-bold" href="guru_legacy"><i class="bi bi-arrow-left"></i> Dashboard</a>
        </div>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> rounded-4"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <section class="cardx mb-3">
        <form method="post">
            <div class="row g-3">
                <div class="col-12 col-md-7">
                    <label class="form-label fw-bold small text-uppercase">Siswa Binaan</label>
                    <select class="form-select rounded-3" name="no_induk_siswa" required>
                        <option value="">Pilih siswa binaan</option>
                        <?php foreach ($binaanList as $row): ?>
                            <option value="<?= htmlspecialchars((string)$row['no_induk_siswa']); ?>"><?= htmlspecialchars((string)($row['nama_siswa'] ?: $row['no_induk_siswa'])); ?> - <?= htmlspecialchars((string)$row['kelas']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-bold small text-uppercase">Tanggal</label>
                    <input class="form-control rounded-3" type="date" name="tanggal" value="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">Catatan Pendampingan</label>
                    <textarea class="form-control rounded-3" name="catatan" rows="3" required placeholder="Tulis catatan pendampingan siswa."></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">Tindak Lanjut</label>
                    <textarea class="form-control rounded-3" name="tindak_lanjut" rows="2" placeholder="Tulis rencana tindak lanjut."></textarea>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-bold small text-uppercase">Status</label>
                    <select class="form-select rounded-3" name="status">
                        <option value="Dipantau">Dipantau</option>
                        <option value="Perlu Tindak Lanjut">Perlu Tindak Lanjut</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
                <div class="col-12 col-md-7 d-flex align-items-end">
                    <button class="btn btn-main w-100" type="submit" <?= empty($binaanList) ? 'disabled' : ''; ?>><i class="bi bi-save2"></i> Simpan Jurnal</button>
                </div>
            </div>
        </form>
    </section>

    <section class="cardx">
        <h2 class="h6 fw-bold mb-3">Riwayat Jurnal Pendampingan</h2>
        <?php if (empty($jurnal)): ?>
            <div class="text-center text-muted py-4 border rounded-4">Belum ada jurnal pendampingan.</div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($jurnal as $row): ?>
                    <article class="border rounded-4 p-3 bg-white">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <strong><?= htmlspecialchars((string)($row['nama_siswa'] ?: $row['no_induk_siswa'])); ?></strong>
                                <div class="small text-muted"><?= date('d M Y', strtotime((string)$row['tanggal'])); ?> · Kelas <?= htmlspecialchars((string)$row['kelas']); ?></div>
                            </div>
                            <span class="badge text-bg-primary align-self-start"><?= htmlspecialchars((string)$row['status']); ?></span>
                        </div>
                        <div class="small mt-2"><?= nl2br(htmlspecialchars((string)$row['catatan'])); ?></div>
                        <?php if (!empty($row['tindak_lanjut'])): ?>
                            <div class="small text-muted mt-2 pt-2 border-top"><strong>Tindak lanjut:</strong> <?= nl2br(htmlspecialchars((string)$row['tindak_lanjut'])); ?></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
