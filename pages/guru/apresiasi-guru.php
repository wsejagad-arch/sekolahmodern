<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;padding:24px;color:#991b1b;">Koneksi database tidak tersedia.</div>';
    exit;
}

function ag_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ag_table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '$safe'");
    return $q && mysqli_num_rows($q) > 0;
}

function ag_column_exists(mysqli $conn, string $table, string $column): bool
{
    $safeTable = mysqli_real_escape_string($conn, $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $q && mysqli_num_rows($q) > 0;
}

function ag_normalize_date(string $value, string $fallback): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $fallback;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : $fallback;
}

function ag_default_semester_start(): string
{
    $year = (int)date('Y');
    $month = (int)date('n');
    return $month <= 6 ? sprintf('%04d-01-01', $year) : sprintf('%04d-07-01', $year);
}

function ag_day_name(string $date): string
{
    if (function_exists('ubah_nama_hari')) {
        return ubah_nama_hari($date);
    }
    $map = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];
    return $map[date('l', strtotime($date))] ?? '';
}

function ag_count_weekday_between(string $start, string $end, string $hari): int
{
    $count = 0;
    $cursor = strtotime($start);
    $last = strtotime($end);
    while ($cursor !== false && $cursor <= $last) {
        if (strcasecmp(ag_day_name(date('Y-m-d', $cursor)), $hari) === 0) {
            $count++;
        }
        $cursor = strtotime('+1 day', $cursor);
    }
    return $count;
}

function ag_pct(float $part, float $total): float
{
    return $total > 0 ? min(100, max(0, ($part / $total) * 100)) : 0;
}

function ag_score_badge(float $score): array
{
    if ($score >= 85) {
        return ['Sangat Baik', 'badge-success'];
    }
    if ($score >= 70) {
        return ['Baik', 'badge-primary'];
    }
    if ($score >= 55) {
        return ['Cukup Baik', 'badge-warning'];
    }
    return ['Kurang Baik', 'badge-danger'];
}

$today = date('Y-m-d');
$defaultStart = ag_default_semester_start();
$startDate = ag_normalize_date((string)($_GET['mulai'] ?? $defaultStart), $defaultStart);
$endDate = ag_normalize_date((string)($_GET['sampai'] ?? $today), $today);
if (strtotime($startDate) > strtotime($endDate)) {
    [$startDate, $endDate] = [$endDate, $startDate];
}
$startEsc = mysqli_real_escape_string($conn, $startDate);
$endEsc = mysqli_real_escape_string($conn, $endDate);

$selectedDays = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400);
$elapsedRatio = min(1, $selectedDays / 182.5);

$teachers = [];
$qGuru = @mysqli_query($conn, "
    SELECT no_induk, nama_guru, status_kepegawaian, jabatan, status
    FROM tbl_guru
    WHERE no_induk <> '' AND (status='Aktif' OR status='' OR status IS NULL OR UPPER(status)='AKTIF')
    ORDER BY nama_guru ASC
");
while ($qGuru && ($row = mysqli_fetch_assoc($qGuru))) {
    $nip = (string)$row['no_induk'];
    $teachers[$nip] = [
        'no_induk' => $nip,
        'nama_guru' => (string)$row['nama_guru'],
        'status_kepegawaian' => (string)($row['status_kepegawaian'] ?? ''),
        'jabatan' => (string)($row['jabatan'] ?? ''),
        'jadwal_total' => 0,
        'jurnal_tepat' => 0,
        'jurnal_total' => 0,
        'penilaian_total' => 0,
        'absen_total' => 0,
        'absen_tepat' => 0,
        'absen_timing_total' => 0,
        'kelas_wali' => [],
        'pendampingan_total' => 0,
        'tindak_lanjut_total' => 0,
    ];
}

$scheduleById = [];
$expectedDatesByMapel = [];
if (!empty($teachers)) {
    $qSchedule = @mysqli_query($conn, "
        SELECT id_mapel, no_induk, nama_mapel, hari, jam_mulai, jam_selesai, kelas
        FROM tbl_mapel_ampu
        WHERE no_induk <> '' AND hari <> '' AND kelas <> ''
    ");
    while ($qSchedule && ($row = mysqli_fetch_assoc($qSchedule))) {
        $nip = (string)$row['no_induk'];
        if (!isset($teachers[$nip])) {
            continue;
        }
        $idMapel = (int)$row['id_mapel'];
        $hari = (string)$row['hari'];
        $expected = ag_count_weekday_between($startDate, $endDate, $hari);
        $scheduleById[$idMapel] = [
            'no_induk' => $nip,
            'hari' => $hari,
            'jam_mulai' => (string)$row['jam_mulai'],
            'kelas' => (string)$row['kelas'],
        ];
        $teachers[$nip]['jadwal_total'] += $expected;

        $cursor = strtotime($startDate);
        $last = strtotime($endDate);
        while ($cursor !== false && $cursor <= $last) {
            $date = date('Y-m-d', $cursor);
            if (strcasecmp(ag_day_name($date), $hari) === 0) {
                $expectedDatesByMapel[$idMapel][$date] = true;
            }
            $cursor = strtotime('+1 day', $cursor);
        }
    }
}

$totalExpected = 0;
$activeTeachersWithSchedule = 0;
foreach ($teachers as $nip => $teacher) {
    if ($teacher['jadwal_total'] > 0) {
        $totalExpected += $teacher['jadwal_total'];
        $activeTeachersWithSchedule++;
    }
}
$avgJadwal = $activeTeachersWithSchedule > 0 ? $totalExpected / $activeTeachersWithSchedule : 0;

if (ag_table_exists($conn, 'tbl_wali_kelas') && ag_table_exists($conn, 'tbl_kelas')) {
    $qWali = @mysqli_query($conn, "
        SELECT wk.nip_wali, k.kelas
        FROM tbl_wali_kelas wk
        JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
        WHERE wk.nip_wali <> '' AND k.kelas <> ''
    ");
    while ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
        $nip = (string)$row['nip_wali'];
        if (isset($teachers[$nip])) {
            $teachers[$nip]['kelas_wali'][(string)$row['kelas']] = (string)$row['kelas'];
        }
    }
}
if (ag_table_exists($conn, 'tbl_kelas') && ag_column_exists($conn, 'tbl_kelas', 'nip_wali')) {
    $qWaliLegacy = @mysqli_query($conn, "SELECT nip_wali, kelas FROM tbl_kelas WHERE nip_wali <> '' AND nip_wali <> '0' AND kelas <> ''");
    while ($qWaliLegacy && ($row = mysqli_fetch_assoc($qWaliLegacy))) {
        $nip = (string)$row['nip_wali'];
        if (isset($teachers[$nip])) {
            $teachers[$nip]['kelas_wali'][(string)$row['kelas']] = (string)$row['kelas'];
        }
    }
}

if (ag_table_exists($conn, 'tbl_materi')) {
    $qMateri = @mysqli_query($conn, "
        SELECT no_induk, id_mapel, tanggal, COUNT(*) AS qty
        FROM tbl_materi
        WHERE tanggal BETWEEN '$startEsc' AND '$endEsc' AND tanggal <> '0000-00-00'
        GROUP BY no_induk, id_mapel, tanggal
    ");
    while ($qMateri && ($row = mysqli_fetch_assoc($qMateri))) {
        $nip = (string)$row['no_induk'];
        $idMapel = (int)$row['id_mapel'];
        $tanggal = (string)$row['tanggal'];
        if (!isset($teachers[$nip])) {
            continue;
        }
        $teachers[$nip]['jurnal_total']++;
        if (isset($expectedDatesByMapel[$idMapel][$tanggal])) {
            $teachers[$nip]['jurnal_tepat']++;
        }
    }
}

if (ag_table_exists($conn, 'tbl_penilaian_item')) {
    $qNilai = @mysqli_query($conn, "
        SELECT no_induk_guru, COUNT(*) AS total
        FROM tbl_penilaian_item
        WHERE tanggal BETWEEN '$startEsc' AND '$endEsc'
        GROUP BY no_induk_guru
    ");
    while ($qNilai && ($row = mysqli_fetch_assoc($qNilai))) {
        $nip = (string)$row['no_induk_guru'];
        if (isset($teachers[$nip])) {
            $teachers[$nip]['penilaian_total'] = (int)$row['total'];
        }
    }
}

$absenKeys = [];
if (ag_table_exists($conn, 'tbl_absen')) {
    $qAbsen = @mysqli_query($conn, "
        SELECT no_induk_guru, id_mapel, tanggal
        FROM tbl_absen
        WHERE tanggal BETWEEN '$startEsc' AND '$endEsc'
        GROUP BY no_induk_guru, id_mapel, tanggal
    ");
    while ($qAbsen && ($row = mysqli_fetch_assoc($qAbsen))) {
        $idMapel = (int)$row['id_mapel'];
        $nip = trim((string)$row['no_induk_guru']);
        if ($nip === '' && isset($scheduleById[$idMapel])) {
            $nip = $scheduleById[$idMapel]['no_induk'];
        }
        if (!isset($teachers[$nip])) {
            continue;
        }
        $key = $nip . '|' . $idMapel . '|' . (string)$row['tanggal'];
        if (!isset($absenKeys[$key])) {
            $absenKeys[$key] = true;
            $teachers[$nip]['absen_total']++;
        }
    }
}

if (ag_table_exists($conn, 'tbl_konfirmasi_kehadiran_guru')) {
    $qKonf = @mysqli_query($conn, "
        SELECT no_induk_guru, id_mapel, tanggal, status, created_at
        FROM tbl_konfirmasi_kehadiran_guru
        WHERE tanggal BETWEEN '$startEsc' AND '$endEsc'
        GROUP BY no_induk_guru, id_mapel, tanggal, status, created_at
    ");
    while ($qKonf && ($row = mysqli_fetch_assoc($qKonf))) {
        $nip = (string)$row['no_induk_guru'];
        $idMapel = (int)$row['id_mapel'];
        $tanggal = (string)$row['tanggal'];
        if (!isset($teachers[$nip])) {
            continue;
        }

        $key = $nip . '|' . $idMapel . '|' . $tanggal;
        if (!isset($absenKeys[$key])) {
            $absenKeys[$key] = true;
            $teachers[$nip]['absen_total']++;
        }

        $status = strtolower(trim((string)$row['status']));
        $createdAt = (string)($row['created_at'] ?? '');
        $hasTiming = $status !== '';
        if ($hasTiming) {
            $teachers[$nip]['absen_timing_total']++;
            $isOnTime = in_array($status, ['hadir'], true);
            if ($createdAt !== '' && $createdAt !== '0000-00-00 00:00:00' && isset($scheduleById[$idMapel])) {
                $limit = strtotime($tanggal . ' ' . $scheduleById[$idMapel]['jam_mulai']);
                $actual = strtotime($createdAt);
                $isOnTime = $status === 'hadir' && $actual !== false && $limit !== false && $actual <= $limit;
            }
            if ($isOnTime) {
                $teachers[$nip]['absen_tepat']++;
            }
        }
    }
}

if (ag_table_exists($conn, 'tbl_jurnal_pendampingan')) {
    $qPendampingan = @mysqli_query($conn, "
        SELECT nip_guru, kelas, COUNT(*) AS total,
               SUM(CASE WHEN TRIM(COALESCE(tindak_lanjut,'')) <> '' THEN 1 ELSE 0 END) AS tindak_total
        FROM tbl_jurnal_pendampingan
        WHERE tanggal BETWEEN '$startEsc' AND '$endEsc'
        GROUP BY nip_guru, kelas
    ");
    while ($qPendampingan && ($row = mysqli_fetch_assoc($qPendampingan))) {
        $nip = (string)$row['nip_guru'];
        $kelas = (string)$row['kelas'];
        if (isset($teachers[$nip]) && isset($teachers[$nip]['kelas_wali'][$kelas])) {
            $teachers[$nip]['pendampingan_total'] += (int)$row['total'];
            $teachers[$nip]['tindak_lanjut_total'] += (int)$row['tindak_total'];
        }
    }
}

if (ag_table_exists($conn, 'tbl_guru_wali_jurnal_pendampingan')) {
    $qGuruWaliJurnal = @mysqli_query($conn, "
        SELECT no_induk_guru, kelas, COUNT(*) AS total,
               SUM(CASE WHEN TRIM(COALESCE(tindak_lanjut,'')) <> '' THEN 1 ELSE 0 END) AS tindak_total
        FROM tbl_guru_wali_jurnal_pendampingan
        WHERE tanggal BETWEEN '$startEsc' AND '$endEsc'
        GROUP BY no_induk_guru, kelas
    ");
    while ($qGuruWaliJurnal && ($row = mysqli_fetch_assoc($qGuruWaliJurnal))) {
        $nip = (string)$row['no_induk_guru'];
        $kelas = (string)$row['kelas'];
        if (isset($teachers[$nip]) && isset($teachers[$nip]['kelas_wali'][$kelas])) {
            $teachers[$nip]['pendampingan_total'] += (int)$row['total'];
            $teachers[$nip]['tindak_lanjut_total'] += (int)$row['tindak_total'];
        }
    }
}

if (ag_table_exists($conn, 'tbl_jurnal_tindak_lanjut')) {
    $qTindak = @mysqli_query($conn, "
        SELECT created_by, kelas, COUNT(*) AS total
        FROM tbl_jurnal_tindak_lanjut
        WHERE DATE(created_at) BETWEEN '$startEsc' AND '$endEsc'
        GROUP BY created_by, kelas
    ");
    while ($qTindak && ($row = mysqli_fetch_assoc($qTindak))) {
        $nip = (string)$row['created_by'];
        $kelas = (string)$row['kelas'];
        if (isset($teachers[$nip]) && isset($teachers[$nip]['kelas_wali'][$kelas])) {
            $teachers[$nip]['tindak_lanjut_total'] += (int)$row['total'];
        }
    }
}

$rows = [];
$summary = [
    'total_guru' => count($teachers),
    'avg_score' => 0,
    'top_score' => 0,
    'complete_journal' => 0,
];

foreach ($teachers as $nip => $teacher) {
    $expected = (int)$teacher['jadwal_total'];
    $jurnalPct = ag_pct((float)$teacher['jurnal_tepat'], (float)$expected);
    $jurnalConsistencyPct = ag_pct((float)$teacher['jurnal_total'], (float)$expected);
    
    // Dynamic Penilaian target (1 per 8 meetings)
    $targetPenilaian = max(1, round($expected / 8));
    $penilaianPct = min(100, ((int)$teacher['penilaian_total'] / $targetPenilaian) * 100);
    
    $absenPct = ag_pct((float)$teacher['absen_total'], (float)$expected);
    $timingPct = $teacher['absen_timing_total'] > 0 ? ag_pct((float)$teacher['absen_tepat'], (float)$teacher['absen_timing_total']) : null;

    $baseScore =
        ($jurnalPct * 0.35) +
        ($jurnalConsistencyPct * 0.10) +
        ($penilaianPct * 0.15) +
        ($absenPct * 0.25) +
        (($timingPct ?? $absenPct) * 0.15);

    $volumeBonus = 0;
    if ($avgJadwal > 0 && $expected > $avgJadwal) {
        $excessRatio = ($expected - $avgJadwal) / $avgJadwal;
        $volumeBonus = min(5, $excessRatio * 10);
    }

    $isWali = !empty($teacher['kelas_wali']);
    $waliBonus = 0;
    $targetPendampingan = max(1, round(5 * $elapsedRatio));
    $targetTindakLanjut = max(1, round(3 * $elapsedRatio));
    if ($isWali) {
        $pendampinganScore = min(5, ((int)$teacher['pendampingan_total'] / $targetPendampingan) * 5);
        $tindakScore = min(5, ((int)$teacher['tindak_lanjut_total'] / $targetTindakLanjut) * 5);
        $waliBonus = $pendampinganScore + $tindakScore;
    }

    $finalScore = min(110, $baseScore + $waliBonus + $volumeBonus);
    [$label, $badgeClass] = ag_score_badge($finalScore);
    $rows[] = array_merge($teacher, [
        'is_wali' => $isWali,
        'jurnal_pct' => $jurnalPct,
        'penilaian_pct' => $penilaianPct,
        'absen_pct' => $absenPct,
        'timing_pct' => $timingPct,
        'base_score' => $baseScore,
        'volume_bonus' => $volumeBonus,
        'wali_bonus' => $waliBonus,
        'target_penilaian' => $targetPenilaian,
        'target_pendampingan' => $targetPendampingan,
        'target_tindak' => $targetTindakLanjut,
        'final_score' => $finalScore,
        'label' => $label,
        'badge_class' => $badgeClass,
    ]);
}

usort($rows, static function (array $a, array $b): int {
    if (abs($a['final_score'] - $b['final_score']) < 0.001) {
        return strcmp($a['nama_guru'], $b['nama_guru']);
    }
    return $a['final_score'] < $b['final_score'] ? 1 : -1;
});

if (!empty($rows)) {
    $summary['avg_score'] = array_sum(array_column($rows, 'base_score')) / count($rows);
    $summary['top_score'] = max(array_column($rows, 'final_score'));
    foreach ($rows as $r) {
        if ($r['jadwal_total'] > 0 && $r['jurnal_pct'] >= 100) {
            $summary['complete_journal']++;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apresiasi Guru - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --bg:#f6f8fb; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --brand:#0f766e; --gold:#f59e0b; }
        body { margin:0; font-family: "Plus Jakarta Sans", system-ui, sans-serif; background: linear-gradient(135deg,#ecfeff,#f8fafc 42%,#fef9c3); color:var(--ink); padding-bottom:72px; }
        .shell { max-width: 1320px; margin:0 auto; padding:24px; }
        .hero { background: linear-gradient(135deg,#0f172a,#115e59); color:#fff; border-radius:24px; padding:28px; box-shadow:0 20px 50px rgba(15,23,42,.18); }
        .hero a { color:rgba(255,255,255,.78); text-decoration:none; font-weight:700; }
        .panel { background:rgba(255,255,255,.92); border:1px solid rgba(226,232,240,.9); border-radius:20px; box-shadow:0 12px 32px rgba(15,23,42,.08); }
        .panel-pad { padding:20px; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:18px 0; }
        .metric { background:#fff; border:1px solid var(--line); border-radius:18px; padding:16px; display:flex; align-items:center; gap:14px; }
        .metric i { width:44px; height:44px; border-radius:14px; display:grid; place-items:center; background:#ccfbf1; color:#0f766e; font-size:22px; }
        .metric span { display:block; color:var(--muted); font-size:12px; font-weight:700; }
        .metric strong { display:block; font-size:24px; line-height:1.1; }
        .formula { font-size:13px; color:#475569; line-height:1.7; }
        .table th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#64748b; white-space:nowrap; background:#f8fafc; }
        .table td { vertical-align:middle; font-size:13px; }
        .teacher { font-weight:800; color:#0f172a; }
        .score { font-size:22px; font-weight:900; color:#0f766e; }
        .bonus { color:#b45309; font-weight:800; font-size:12px; }
        .mini { color:#64748b; font-size:12px; }
        .progress { height:8px; background:#e2e8f0; }
        .badge-success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-primary { background:#dbeafe; color:#1d4ed8; border:1px solid #93c5fd; }
        .badge-warning { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
        .badge-danger { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .rank { width:34px; height:34px; border-radius:12px; display:grid; place-items:center; background:#f1f5f9; font-weight:900; }
        .rank.top { background:#fef3c7; color:#92400e; }
        .mobile-nav { position:fixed; left:0; right:0; bottom:0; background:rgba(255,255,255,.94); border-top:1px solid var(--line); backdrop-filter:blur(16px); padding:10px 16px; display:flex; justify-content:center; gap:22px; z-index:20; }
        .mobile-nav a { color:#64748b; text-decoration:none; font-size:12px; font-weight:700; display:flex; flex-direction:column; align-items:center; }
        .mobile-nav i { font-size:20px; }
        @media (max-width: 991px) { .metric-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width: 640px) { .shell { padding:16px; } .metric-grid { grid-template-columns:1fr; } .hero { padding:22px; } }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <a href="../../home.php"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <h1 class="mt-3 mb-2">Apresiasi Guru</h1>
        <p class="mb-0 text-white-50">Skor apresiasi berbasis jurnal tepat jadwal, penilaian, absensi kelas, ketepatan hadir, dan bonus wali kelas.</p>
    </section>

    <section class="panel panel-pad mt-3">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold" for="mulai">Mulai</label>
                <input class="form-control" type="date" id="mulai" name="mulai" value="<?= ag_h($startDate); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold" for="sampai">Sampai</label>
                <input class="form-control" type="date" id="sampai" name="sampai" value="<?= ag_h($endDate); ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100" type="submit"><i class="bi bi-funnel"></i> Terapkan Periode</button>
            </div>
            <div class="col-md-3 text-md-end">
                <div class="mini">Periode semester berjalan</div>
                <strong><?= date('d M Y', strtotime($startDate)); ?> - <?= date('d M Y', strtotime($endDate)); ?></strong>
            </div>
        </form>
    </section>

    <div class="metric-grid">
        <div class="metric"><i class="bi bi-people-fill"></i><div><span>Total Guru</span><strong><?= (int)$summary['total_guru']; ?></strong></div></div>
        <div class="metric"><i class="bi bi-trophy-fill"></i><div><span>Skor Tertinggi</span><strong><?= number_format($summary['top_score'], 1, ',', '.'); ?></strong></div></div>
        <div class="metric"><i class="bi bi-graph-up-arrow"></i><div><span>Rata-rata Skor Utama</span><strong><?= number_format($summary['avg_score'], 1, ',', '.'); ?></strong></div></div>
        <div class="metric"><i class="bi bi-journal-check"></i><div><span>Jurnal 100%</span><strong><?= (int)$summary['complete_journal']; ?></strong></div></div>
    </div>

    <section class="panel panel-pad mb-3">
        <div class="formula">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle text-primary"></i> Penyesuaian Beban Kerja & Waktu Berjalan (<?= round($elapsedRatio * 100); ?>% Semester)</h6>
            <strong>1. Target Penilaian Dinamis:</strong> Tidak dipatok rata. Guru wajib melakukan 1 penilaian untuk setiap 8 pertemuan kelas, menyesuaikan dengan jam mengajarnya.<br>
            <strong>2. Bonus Beban Mengajar (Volume Bonus):</strong> Rata-rata sesi sekolah saat ini adalah <?= round($avgJadwal); ?>. Guru dengan jam mengajar di atas rata-rata berhak mendapat skor kompensasi maksimal +5 poin.<br>
            <strong>3. Skala Waktu Wali Kelas:</strong> Karena periode yang difilter adalah <?= round($selectedDays); ?> hari, target wali kelas otomatis diproporsionalkan (menyesuaikan hari yang sudah berlalu).
        </div>
    </section>

    <section class="panel">
        <div class="panel-pad border-bottom">
            <h5 class="mb-1 fw-bold"><i class="bi bi-award-fill text-warning"></i> Peringkat Apresiasi Semua Guru</h5>
            <div class="mini">Guru tanpa jadwal pada periode ini tetap tampil, tetapi tidak diberi penalti tersembunyi di luar data yang tersedia.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center">Rank</th>
                        <th>Guru</th>
                        <th class="text-center">Skor</th>
                        <th>Jurnal</th>
                        <th>Penilaian</th>
                        <th>Absensi</th>
                        <th>Ketepatan</th>
                        <th>Wali Kelas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $idx => $row): ?>
                    <tr>
                        <td class="text-center"><div class="rank <?= $idx < 3 ? 'top' : ''; ?>"><?= $idx + 1; ?></div></td>
                        <td>
                            <div class="teacher"><?= ag_h($row['nama_guru']); ?></div>
                            <div class="mini">NIP/ID: <?= ag_h($row['no_induk']); ?><?= $row['status_kepegawaian'] ? ' • ' . ag_h($row['status_kepegawaian']) : ''; ?></div>
                        </td>
                        <td class="text-center">
                            <div class="score"><?= number_format($row['final_score'], 1, ',', '.'); ?></div>
                            <?php if ($row['volume_bonus'] > 0): ?>
                                <div class="bonus text-primary"><i class="bi bi-stars"></i> +<?= number_format($row['volume_bonus'], 1, ',', '.'); ?> bonus beban</div>
                            <?php endif; ?>
                            <?php if ($row['wali_bonus'] > 0): ?>
                                <div class="bonus">+<?= number_format($row['wali_bonus'], 1, ',', '.'); ?> bonus wali</div>
                            <?php endif; ?>
                        </td>
                        <td style="min-width:150px;">
                            <strong><?= (int)$row['jurnal_tepat']; ?>/<?= (int)$row['jadwal_total']; ?></strong>
                            <div class="progress mt-1"><div class="progress-bar bg-success" style="width:<?= number_format($row['jurnal_pct'], 1, '.', ''); ?>%"></div></div>
                            <div class="mini">Total isi: <?= (int)$row['jurnal_total']; ?></div>
                        </td>
                        <td>
                            <strong><?= (int)$row['penilaian_total']; ?></strong> <span class="mini">/ target <?= $row['target_penilaian']; ?></span>
                            <div class="progress mt-1"><div class="progress-bar bg-info" style="width:<?= number_format($row['penilaian_pct'], 1, '.', ''); ?>%"></div></div>
                        </td>
                        <td>
                            <strong><?= (int)$row['absen_total']; ?>/<?= (int)$row['jadwal_total']; ?></strong>
                            <div class="progress mt-1"><div class="progress-bar bg-primary" style="width:<?= number_format($row['absen_pct'], 1, '.', ''); ?>%"></div></div>
                        </td>
                        <td>
                            <?php if ($row['timing_pct'] === null): ?>
                                <span class="mini">Belum ada data jam/status</span>
                            <?php else: ?>
                                <strong><?= (int)$row['absen_tepat']; ?>/<?= (int)$row['absen_timing_total']; ?></strong>
                                <div class="progress mt-1"><div class="progress-bar bg-warning" style="width:<?= number_format($row['timing_pct'], 1, '.', ''); ?>%"></div></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['is_wali']): ?>
                                <strong><?= ag_h(implode(', ', $row['kelas_wali'])); ?></strong>
                                <div class="mini">Pendampingan: <?= (int)$row['pendampingan_total']; ?>/<?= $row['target_pendampingan']; ?> • Tindak lanjut: <?= (int)$row['tindak_lanjut_total']; ?>/<?= $row['target_tindak']; ?></div>
                            <?php else: ?>
                                <span class="mini">Bukan wali kelas.</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge rounded-pill <?= ag_h($row['badge_class']); ?>"><?= ag_h($row['label']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<nav class="mobile-nav">
    <a href="../../home.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
    <a href="laporan-kelas"><i class="bi bi-bar-chart"></i><span>Laporan</span></a>
    <a href="apresiasi-guru" style="color:#0f766e;"><i class="bi bi-award"></i><span>Apresiasi</span></a>
    <a href="profil-guru"><i class="bi bi-person"></i><span>Profil</span></a>
</nav>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
