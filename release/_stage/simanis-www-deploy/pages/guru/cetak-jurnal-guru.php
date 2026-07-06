<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('location: ../../index.php?haruslogin');
    exit;
}

$nipGuru = (string) $_SESSION['no_induk'];
$namaGuru = $_SESSION['nama_guru'] ?? ($_SESSION['nama'] ?? 'Guru');

function cjg_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cjg_column_exists(mysqli $conn, string $table, string $column): bool
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = @mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggalSelesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');
$kelasFilter = trim((string)($_GET['kelas'] ?? ''));

$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$kelasOptions = [];
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC");
while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
    $kelasOptions[] = $row['kelas'];
}

$dateColumn = cjg_column_exists($conn, 'tbl_materi', 'tanggal') ? 'tanggal' : 'date';
$where = ["a.no_induk='{$nipEsc}'"];
$where[] = "m.`{$dateColumn}` BETWEEN '" . mysqli_real_escape_string($conn, $tanggalMulai) . "' AND '" . mysqli_real_escape_string($conn, $tanggalSelesai) . "'";

if ($kelasFilter !== '') {
    $where[] = "a.kelas='" . mysqli_real_escape_string($conn, $kelasFilter) . "'";
}

$fileColumn = cjg_column_exists($conn, 'tbl_materi', 'file_materi') ? 'm.file_materi' : "'' AS file_materi";
$sql = "SELECT m.*, {$fileColumn}, a.kelas, a.nama_mapel, a.jam_mulai, a.jam_selesai
        FROM tbl_materi m
        JOIN tbl_mapel_ampu a ON a.id_mapel = m.id_mapel
        WHERE " . implode(' AND ', $where) . "
        ORDER BY m.`{$dateColumn}` DESC, a.kelas ASC, a.nama_mapel ASC";
$qJurnal = @mysqli_query($conn, $sql);
$jurnal = [];
while ($qJurnal && ($row = mysqli_fetch_assoc($qJurnal))) {
    $jurnal[] = $row;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Jurnal - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background:#f7f9fc; color:#172033; }
        .page-shell { max-width:1120px; margin:0 auto; padding:24px; }
        .panel { background:#fff; border:1px solid #e6ebf2; border-radius:8px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
        .panel-pad { padding:18px; }
        @media print {
            .no-print { display:none !important; }
            body { background:#fff; }
            .page-shell { max-width:none; padding:0; }
            .panel { border:0; box-shadow:none; }
        }
    </style>
</head>
<body>
<main class="page-shell">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <a href="../../home.php" class="btn btn-sm btn-outline-secondary no-print mb-3">Kembali</a>
            <h1 class="h3 fw-bold mb-1">Cetak Jurnal Guru</h1>
            <div class="text-muted"><?= cjg_h($namaGuru); ?> - <?= cjg_h($nipGuru); ?></div>
        </div>
        <button class="btn btn-primary no-print" onclick="window.print()">Cetak</button>
    </div>

    <section class="panel panel-pad no-print mb-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Mulai</label>
                <input type="date" class="form-control" name="tanggal_mulai" value="<?= cjg_h($tanggalMulai); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Selesai</label>
                <input type="date" class="form-control" name="tanggal_selesai" value="<?= cjg_h($tanggalSelesai); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kelas</label>
                <select class="form-select" name="kelas">
                    <option value="">Semua kelas</option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= cjg_h($kelas); ?>" <?= $kelasFilter === $kelas ? 'selected' : ''; ?>><?= cjg_h($kelas); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:52px;">No</th>
                    <th>Tanggal</th>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Jam</th>
                    <th>Materi</th>
                    <th>Keterangan</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($jurnal)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Data jurnal tidak ditemukan.</td></tr>
                <?php else: ?>
                    <?php foreach ($jurnal as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td><?= cjg_h($row[$dateColumn] ?? ''); ?></td>
                            <td><?= cjg_h($row['kelas'] ?? ''); ?></td>
                            <td><?= cjg_h($row['nama_mapel'] ?? ''); ?></td>
                            <td><?= cjg_h(($row['jam_mulai'] ?? '') . ' - ' . ($row['jam_selesai'] ?? '')); ?></td>
                            <td><?= nl2br(cjg_h($row['materi'] ?? '')); ?></td>
                            <td><?= nl2br(cjg_h($row['keterangan'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
