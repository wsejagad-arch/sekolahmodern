<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

function p7_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function p7_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return $q && mysqli_num_rows($q) > 0;
}

function p7_create_table(mysqli $conn): void
{
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_7kih_jurnal (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            no_induk VARCHAR(50) NOT NULL,
            nama_siswa VARCHAR(150) NOT NULL DEFAULT '',
            kelas VARCHAR(60) NOT NULL DEFAULT '',
            tanggal DATE NOT NULL,
            habit_key VARCHAR(40) NOT NULL,
            habit_label VARCHAR(120) NOT NULL,
            prayer_key VARCHAR(30) NOT NULL DEFAULT '',
            submitted_at DATETIME NOT NULL,
            window_start TIME DEFAULT NULL,
            window_end TIME DEFAULT NULL,
            timeliness_status ENUM('sangat_tepat','tepat','terlambat','di_luar_waktu') NOT NULL DEFAULT 'tepat',
            score DECIMAL(5,2) NOT NULL DEFAULT 0,
            photo_path VARCHAR(255) DEFAULT NULL,
            photo_size INT UNSIGNED NOT NULL DEFAULT 0,
            photo_hash VARCHAR(80) DEFAULT NULL,
            is_photo_stored TINYINT(1) NOT NULL DEFAULT 1,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_7kih_daily_slot (no_induk, tanggal, habit_key, prayer_key),
            KEY idx_7kih_tanggal_kelas (tanggal, kelas),
            KEY idx_7kih_siswa_bulan (no_induk, tanggal),
            KEY idx_7kih_habit (habit_key, prayer_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function p7_predicate(float $score): array
{
    if ($score >= 85) return ['Sangat Baik', 'success'];
    if ($score >= 70) return ['Baik', 'primary'];
    if ($score >= 55) return ['Cukup', 'warning'];
    if ($score >= 40) return ['Kurang', 'danger'];
    return ['Sangat Kurang', 'dark'];
}

function p7_normalize_date(string $value, string $fallback): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $fallback;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : $fallback;
}

p7_create_table($conn);

$today = date('Y-m-d');
$defaultStart = date('Y-m-01');
$startDate = p7_normalize_date((string)($_GET['mulai'] ?? $defaultStart), $defaultStart);
$endDate = p7_normalize_date((string)($_GET['sampai'] ?? $today), $today);
if (strtotime($startDate) > strtotime($endDate)) {
    [$startDate, $endDate] = [$endDate, $startDate];
}
$days = max(1, (int)floor((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);

function p7_class_x_to_xii_where(string $column = 'kelas'): string
{
    return "(
        TRIM($column) REGEXP '^(X|XI|XII)([[:space:]-]|$)'
        OR TRIM($column) REGEXP '^(10|11|12)([[:space:]-]|$)'
    )";
}

$kelasList = [];
$classWhere = p7_class_x_to_xii_where('kelas');
$qKelas = @mysqli_query($conn, "
    SELECT DISTINCT kelas
    FROM tbl_siswa
    WHERE kelas IS NOT NULL AND kelas <> '' AND $classWhere
    ORDER BY kelas ASC
");
while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
    $kelasList[] = (string)$row['kelas'];
}

$kelasFilter = trim((string)($_GET['kelas'] ?? ''));
if ($kelasFilter !== '' && !in_array($kelasFilter, $kelasList, true)) {
    $kelasFilter = '';
}

$whereKelas = "AND $classWhere";
if ($kelasFilter !== '') {
    $kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
    $whereKelas = "AND kelas='$kelasEsc'";
}

$agamaSelect = p7_column_exists($conn, 'tbl_siswa', 'agama') ? 'agama' : "'' AS agama";
$students = [];
$qStudents = @mysqli_query($conn, "
    SELECT no_induk, nama_siswa, kelas, $agamaSelect
    FROM tbl_siswa
    WHERE no_induk <> '' $whereKelas
      AND (status='Aktif' OR status='' OR status IS NULL OR UPPER(status)='AKTIF')
    ORDER BY kelas ASC, nama_siswa ASC
");
while ($qStudents && ($row = mysqli_fetch_assoc($qStudents))) {
    $nis = (string)$row['no_induk'];
    $isIslam = strpos(strtolower((string)($row['agama'] ?? '')), 'islam') !== false;
    $students[$nis] = [
        'no_induk' => $nis,
        'nama_siswa' => (string)$row['nama_siswa'],
        'kelas' => (string)$row['kelas'],
        'expected' => $days * ($isIslam ? 11 : 7),
        'total' => 0,
        'avg_score' => 0,
        'sum_score' => 0,
        'sangat_tepat' => 0,
        'tepat' => 0,
        'terlambat' => 0,
        'di_luar_waktu' => 0,
        'active_days' => [],
    ];
}

if (!empty($students)) {
    $nisIn = "'" . implode("','", array_map(static function ($nis) use ($conn) {
        return mysqli_real_escape_string($conn, $nis);
    }, array_keys($students))) . "'";
    $startEsc = mysqli_real_escape_string($conn, $startDate);
    $endEsc = mysqli_real_escape_string($conn, $endDate);
    $qJurnal = @mysqli_query($conn, "
        SELECT no_induk, tanggal, timeliness_status, score
        FROM tbl_7kih_jurnal
        WHERE no_induk IN ($nisIn) AND tanggal BETWEEN '$startEsc' AND '$endEsc'
    ");
    while ($qJurnal && ($row = mysqli_fetch_assoc($qJurnal))) {
        $nis = (string)$row['no_induk'];
        if (!isset($students[$nis])) {
            continue;
        }
        $students[$nis]['total']++;
        $students[$nis]['sum_score'] += (float)$row['score'];
        $status = (string)$row['timeliness_status'];
        if (isset($students[$nis][$status])) {
            $students[$nis][$status]++;
        }
        $students[$nis]['active_days'][(string)$row['tanggal']] = true;
    }
}

$rows = [];
foreach ($students as $student) {
    $completion = $student['expected'] > 0 ? min(100, ($student['total'] / $student['expected']) * 100) : 0;
    $avg = $student['total'] > 0 ? $student['sum_score'] / $student['total'] : 0;
    $consistency = $days > 0 ? min(100, (count($student['active_days']) / $days) * 100) : 0;
    $final = ($completion * 0.55) + ($avg * 0.35) + ($consistency * 0.10);
    [$predicate, $badge] = p7_predicate($final);
    $rows[] = array_merge($student, [
        'completion' => $completion,
        'avg_score' => $avg,
        'consistency' => $consistency,
        'final_score' => $final,
        'predicate' => $predicate,
        'badge' => $badge,
        'active_days_count' => count($student['active_days']),
    ]);
}

usort($rows, static function (array $a, array $b): int {
    if (abs($a['final_score'] - $b['final_score']) < 0.001) {
        return strcmp($a['nama_siswa'], $b['nama_siswa']);
    }
    return $a['final_score'] < $b['final_score'] ? 1 : -1;
});

$topRows = array_slice($rows, 0, 3);
$avgFinal = !empty($rows) ? array_sum(array_column($rows, 'final_score')) / count($rows) : 0;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Piagam 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat) - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --line:#e2e8f0; --muted:#64748b; --ink:#0f172a; --brand:#16a34a; }
        body { margin:0; font-family:"Plus Jakarta Sans",system-ui,sans-serif; background:linear-gradient(135deg,#ecfdf5,#f8fafc 48%,#fef3c7); color:var(--ink); padding-bottom:72px; }
        .shell { max-width:1320px; margin:0 auto; padding:24px; }
        .hero { background:linear-gradient(135deg,#14532d,#0f172a); color:#fff; border-radius:22px; padding:26px; box-shadow:0 18px 44px rgba(15,23,42,.16); }
        .hero a { color:rgba(255,255,255,.82); text-decoration:none; font-weight:800; }
        .panel { background:rgba(255,255,255,.94); border:1px solid rgba(226,232,240,.9); border-radius:18px; box-shadow:0 12px 32px rgba(15,23,42,.07); }
        .panel-pad { padding:18px; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin:16px 0; }
        .metric { background:#fff; border:1px solid var(--line); border-radius:16px; padding:15px; display:flex; gap:12px; align-items:center; }
        .metric i { width:42px; height:42px; border-radius:12px; display:grid; place-items:center; background:#dcfce7; color:#15803d; font-size:21px; }
        .metric span { display:block; color:var(--muted); font-size:12px; font-weight:800; }
        .metric strong { display:block; font-size:24px; }
        .certificate-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
        .cert { background:#fff; border:1px solid #fde68a; border-radius:18px; padding:18px; position:relative; overflow:hidden; min-height:190px; }
        .cert:before { content:""; position:absolute; inset:0; background:linear-gradient(135deg,rgba(245,158,11,.16),rgba(22,163,74,.08)); pointer-events:none; }
        .cert > * { position:relative; }
        .rank { width:42px; height:42px; border-radius:14px; display:grid; place-items:center; background:#fef3c7; color:#92400e; font-weight:900; }
        .mini { color:#64748b; font-size:12px; }
        .score { font-size:22px; font-weight:900; color:#15803d; }
        .progress { height:8px; background:#e2e8f0; }
        .table th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#64748b; white-space:nowrap; background:#f8fafc; }
        .table td { vertical-align:middle; font-size:13px; }
        .mobile-nav { position:fixed; left:0; right:0; bottom:0; background:rgba(255,255,255,.94); border-top:1px solid var(--line); backdrop-filter:blur(16px); padding:10px 16px; display:flex; justify-content:center; gap:22px; z-index:20; }
        .mobile-nav a { color:#64748b; text-decoration:none; font-size:12px; font-weight:800; display:flex; flex-direction:column; align-items:center; }
        .mobile-nav i { font-size:20px; }
        @media (max-width:991px) { .metric-grid,.certificate-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width:640px) { .shell { padding:16px; } .metric-grid,.certificate-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <a href="guru_2026"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <h1 class="mt-3 mb-2">Piagam 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat)</h1>
        <p class="mb-0 text-white-50">Analisis otomatis Jurnal 7 Kebiasaan Anak Indonesia Hebat berdasarkan kelengkapan, konsistensi harian, dan ketepatan waktu siswa mengirim selfie jurnal.</p>
    </section>

    <section class="panel panel-pad mt-3">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Mulai</label>
                <input class="form-control" type="date" name="mulai" value="<?= p7_h($startDate); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Sampai</label>
                <input class="form-control" type="date" name="sampai" value="<?= p7_h($endDate); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Kelas</label>
                <select class="form-select" name="kelas">
                    <option value="">Semua kelas X-XII</option>
                    <?php foreach ($kelasList as $kelasOpt): ?>
                        <option value="<?= p7_h($kelasOpt); ?>" <?= $kelasFilter === $kelasOpt ? 'selected' : ''; ?>><?= p7_h($kelasOpt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100 fw-bold" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
            </div>
        </form>
    </section>

    <div class="metric-grid">
        <div class="metric"><i class="bi bi-people-fill"></i><div><span>Siswa Dianalisis</span><strong><?= count($rows); ?></strong></div></div>
        <div class="metric"><i class="bi bi-calendar-check"></i><div><span>Jumlah Hari</span><strong><?= (int)$days; ?></strong></div></div>
        <div class="metric"><i class="bi bi-graph-up-arrow"></i><div><span>Rata-rata Akhir</span><strong><?= number_format($avgFinal, 0); ?></strong></div></div>
        <div class="metric"><i class="bi bi-patch-check-fill"></i><div><span>Sangat Baik</span><strong><?= count(array_filter($rows, static fn($r) => $r['predicate'] === 'Sangat Baik')); ?></strong></div></div>
    </div>

    <section class="panel panel-pad mb-3">
        <h5 class="fw-bold mb-3"><i class="bi bi-award-fill text-warning"></i> Kandidat Piagam Terbaik</h5>
        <?php if (empty($topRows)): ?>
            <div class="mini">Belum ada data siswa untuk periode ini.</div>
        <?php else: ?>
            <div class="certificate-grid">
                <?php foreach ($topRows as $idx => $row): ?>
                    <article class="cert">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rank">#<?= $idx + 1; ?></div>
                            <div>
                                <div class="fw-black fw-bold"><?= p7_h($row['nama_siswa']); ?></div>
                                <div class="mini"><?= p7_h($row['kelas']); ?> - <?= p7_h($row['predicate']); ?></div>
                            </div>
                        </div>
                        <div class="score"><?= number_format($row['final_score'], 1, ',', '.'); ?></div>
                        <div class="mini">Kelengkapan <?= number_format($row['completion'], 0); ?>%, konsistensi <?= number_format($row['consistency'], 0); ?>%, rata-rata waktu <?= number_format($row['avg_score'], 0); ?>.</div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-pad border-bottom">
            <h5 class="fw-bold mb-1">Peringkat 7 KAIH Siswa</h5>
            <div class="mini">Skor akhir = kelengkapan 55%, ketepatan waktu 35%, konsistensi hari aktif 10%.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Siswa</th>
                        <th>Skor</th>
                        <th>Kelengkapan</th>
                        <th>Ketepatan</th>
                        <th>Konsistensi</th>
                        <th>Catatan Waktu</th>
                        <th>Predikat</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $idx => $row): ?>
                    <tr>
                        <td><div class="rank <?= $idx < 3 ? '' : 'bg-light text-secondary'; ?>">#<?= $idx + 1; ?></div></td>
                        <td>
                            <div class="fw-bold"><?= p7_h($row['nama_siswa']); ?></div>
                            <div class="mini"><?= p7_h($row['kelas']); ?> - <?= p7_h($row['no_induk']); ?></div>
                        </td>
                        <td><div class="score"><?= number_format($row['final_score'], 1, ',', '.'); ?></div></td>
                        <td style="min-width:150px;">
                            <strong><?= (int)$row['total']; ?>/<?= (int)$row['expected']; ?></strong>
                            <div class="progress mt-1"><div class="progress-bar bg-success" style="width:<?= number_format($row['completion'], 1, '.', ''); ?>%"></div></div>
                        </td>
                        <td style="min-width:140px;">
                            <strong><?= number_format($row['avg_score'], 1, ',', '.'); ?></strong>
                            <div class="progress mt-1"><div class="progress-bar bg-primary" style="width:<?= number_format($row['avg_score'], 1, '.', ''); ?>%"></div></div>
                        </td>
                        <td>
                            <strong><?= (int)$row['active_days_count']; ?>/<?= (int)$days; ?> hari</strong>
                            <div class="progress mt-1"><div class="progress-bar bg-info" style="width:<?= number_format($row['consistency'], 1, '.', ''); ?>%"></div></div>
                        </td>
                        <td class="mini">
                            Sangat tepat: <?= (int)$row['sangat_tepat']; ?><br>
                            Tepat: <?= (int)$row['tepat']; ?>, terlambat: <?= (int)$row['terlambat']; ?>
                        </td>
                        <td><span class="badge rounded-pill text-bg-<?= p7_h($row['badge']); ?>"><?= p7_h($row['predicate']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<nav class="mobile-nav">
    <a href="guru_2026"><i class="bi bi-house-door"></i><span>Beranda</span></a>
    <a href="piagam-7kih" style="color:#16a34a;"><i class="bi bi-patch-check"></i><span>7 KAIH</span></a>
    <a href="apresiasi-guru"><i class="bi bi-award"></i><span>Apresiasi</span></a>
    <a href="profil-guru"><i class="bi bi-person"></i><span>Profil</span></a>
</nav>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
