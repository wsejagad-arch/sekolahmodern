<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['no_induk'])) {
    header('location: ../../index.php?haruslogin');
    exit;
}

if (!isset($_SESSION['hak_akses']) || (int) $_SESSION['hak_akses'] !== 2) {
    echo '<script>window.location="../../404.html";</script>';
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

date_default_timezone_set('Asia/Jakarta');

$nipGuru = (string) $_SESSION['no_induk'];
$namaGuru = $_SESSION['nama_guru'] ?? ($_SESSION['nama'] ?? 'Guru');
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);

function guru_rk_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function guru_rk_table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $result = @mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    return $result && mysqli_num_rows($result) > 0;
}

function guru_rk_column_exists(mysqli $conn, string $table, string $column): bool
{
    $safeTable = mysqli_real_escape_string($conn, $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = @mysqli_query($conn, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result && mysqli_num_rows($result) > 0;
}

function guru_rk_status_code(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'sakit') {
        return 'S';
    }
    if ($status === 'ijin' || $status === 'izin') {
        return 'I';
    }
    if ($status === 'alpha' || $status === 'alpa') {
        return 'A';
    }
    if ($status === 'dispen' || $status === 'dispensasi') {
        return 'D';
    }
    if ($status === 'telat' || $status === 'terlambat') {
        return 'T';
    }
    return 'H';
}

function guru_rk_normalize_month(string $value, string $fallback): string
{
    return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : $fallback;
}

function guru_rk_month_label(string $period, array $namaBulan): string
{
    [$tahun, $bulan] = array_map('intval', explode('-', $period));
    return ($namaBulan[$bulan] ?? '') . ' ' . $tahun;
}

function guru_rk_get_wali_kelas(mysqli $conn, string $kelas): array
{
    $fallback = ['nama' => '________________________', 'nip' => '________________'];
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);

    if (guru_rk_table_exists($conn, 'tbl_wali_kelas') && guru_rk_table_exists($conn, 'tbl_kelas') && guru_rk_table_exists($conn, 'tbl_guru')) {
        $nipSelect = guru_rk_column_exists($conn, 'tbl_guru', 'nip_guru')
            ? "COALESCE(NULLIF(g.nip_guru,''), g.no_induk)"
            : "g.no_induk";
        $qWali = @mysqli_query(
            $conn,
            "SELECT g.nama_guru, {$nipSelect} AS nip_guru
             FROM tbl_wali_kelas wk
             JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
             JOIN tbl_guru g ON g.no_induk = wk.nip_wali
             WHERE k.kelas = '{$kelasEsc}'
             LIMIT 1"
        );
        if ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
            return [
                'nama' => (string)($row['nama_guru'] ?? $fallback['nama']),
                'nip' => (string)($row['nip_guru'] ?? $fallback['nip'])
            ];
        }
    }

    if (guru_rk_table_exists($conn, 'tbl_kelas') && guru_rk_table_exists($conn, 'tbl_guru') && guru_rk_column_exists($conn, 'tbl_kelas', 'nip_wali')) {
        $nipSelect = guru_rk_column_exists($conn, 'tbl_guru', 'nip_guru')
            ? "COALESCE(NULLIF(g.nip_guru,''), g.no_induk)"
            : "g.no_induk";
        $qWali = @mysqli_query(
            $conn,
            "SELECT g.nama_guru, {$nipSelect} AS nip_guru
             FROM tbl_kelas k
             JOIN tbl_guru g ON g.no_induk = k.nip_wali
             WHERE k.kelas = '{$kelasEsc}'
             LIMIT 1"
        );
        if ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
            return [
                'nama' => (string)($row['nama_guru'] ?? $fallback['nama']),
                'nip' => (string)($row['nip_guru'] ?? $fallback['nip'])
            ];
        }
    }

    return $fallback;
}

$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$kelasOptions = [];
$kelasMapelIds = [];

if (guru_rk_table_exists($conn, 'tbl_mapel_ampu')) {
    $qKelas = @mysqli_query($conn, "SELECT id_mapel, kelas FROM tbl_mapel_ampu WHERE no_induk='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC, nama_mapel ASC");
    while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
        $kelas = (string) $row['kelas'];
        $kelasOptions[$kelas] = $kelas;
        $kelasMapelIds[$kelas][] = (int) $row['id_mapel'];
    }
}

ksort($kelasOptions, SORT_NATURAL | SORT_FLAG_CASE);

$kelasFilter = trim((string) ($_GET['kelas'] ?? ''));
if ($kelasFilter !== '' && !isset($kelasOptions[$kelasFilter])) {
    $kelasFilter = '';
}

$mode = strtolower(trim((string)($_GET['mode'] ?? 'bulan')));
if (!in_array($mode, ['bulan', 'rentang'], true)) {
    $mode = 'bulan';
}

$periode = guru_rk_normalize_month(trim((string)($_GET['periode'] ?? date('Y-m'))), date('Y-m'));
$periodeDari = guru_rk_normalize_month(trim((string)($_GET['periode_dari'] ?? $periode)), $periode);
$periodeSampai = guru_rk_normalize_month(trim((string)($_GET['periode_sampai'] ?? $periode)), $periode);

if ($mode === 'rentang' && strtotime($periodeDari . '-01') > strtotime($periodeSampai . '-01')) {
    [$periodeDari, $periodeSampai] = [$periodeSampai, $periodeDari];
}

$periodeAwal = $mode === 'rentang' ? $periodeDari : $periode;
$periodeAkhir = $mode === 'rentang' ? $periodeSampai : $periode;
[$tahunFilter, $bulanFilter] = array_map('intval', explode('-', $periodeAwal));

$firstDay = $periodeAwal . '-01';
$lastDay = date('Y-m-t', strtotime($periodeAkhir . '-01'));
$hasFilter = $kelasFilter !== '';

$students = [];
$rekapRows = [];
$statusTotals = ['H' => 0, 'S' => 0, 'I' => 0, 'D' => 0, 'A' => 0, 'T' => 0];
$totalRecords = 0;
$totalHadir = 0;

if ($hasFilter && guru_rk_table_exists($conn, 'tbl_siswa')) {
    $kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
    $statusWhere = guru_rk_column_exists($conn, 'tbl_siswa', 'status') ? " AND status='Aktif'" : '';
    $qSiswa = @mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='{$kelasEsc}'{$statusWhere} ORDER BY nama_siswa ASC");
    while ($qSiswa && ($row = mysqli_fetch_assoc($qSiswa))) {
        $students[$row['no_induk']] = [
            'no_induk' => (string) $row['no_induk'],
            'nama_siswa' => (string) $row['nama_siswa'],
            'H' => 0,
            'S' => 0,
            'I' => 0,
            'D' => 0,
            'A' => 0,
            'T' => 0
        ];
    }
}

if ($hasFilter && !empty($students) && guru_rk_table_exists($conn, 'tbl_absen')) {
    $kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
    $firstEsc = mysqli_real_escape_string($conn, $firstDay);
    $lastEsc = mysqli_real_escape_string($conn, $lastDay);
    $mapelIds = array_values(array_unique(array_filter($kelasMapelIds[$kelasFilter] ?? [])));
    $ownershipParts = [];
    if (!empty($mapelIds) && guru_rk_column_exists($conn, 'tbl_absen', 'id_mapel')) {
        $ownershipParts[] = 'id_mapel IN (' . implode(',', array_map('intval', $mapelIds)) . ')';
    }
    if (guru_rk_column_exists($conn, 'tbl_absen', 'no_induk_guru')) {
        $ownershipParts[] = "no_induk_guru='{$nipEsc}'";
    }
    $ownershipWhere = !empty($ownershipParts) ? ' AND (' . implode(' OR ', $ownershipParts) . ')' : '';

    $qAbsen = @mysqli_query($conn, "SELECT no_induk, status FROM tbl_absen WHERE kelas='{$kelasEsc}' AND tanggal BETWEEN '{$firstEsc}' AND '{$lastEsc}'{$ownershipWhere}");
    while ($qAbsen && ($row = mysqli_fetch_assoc($qAbsen))) {
        $nis = (string) $row['no_induk'];
        if (!isset($students[$nis])) {
            continue;
        }

        $code = guru_rk_status_code((string) $row['status']);
        $students[$nis][$code]++;
        $statusTotals[$code]++;
        $totalRecords++;
        if ($code === 'H') {
            $totalHadir++;
        }
    }
}

foreach ($students as $student) {
    $total = $student['H'] + $student['S'] + $student['I'] + $student['D'] + $student['A'] + $student['T'];
    $student['total'] = $total;
    $student['persen_hadir'] = $total > 0 ? round(($student['H'] / $total) * 100, 1) : 0;
    $rekapRows[] = $student;
}

$hadirPct = $totalRecords > 0 ? round(($totalHadir / $totalRecords) * 100, 1) : 0;
$selectedPeriodLabel = $mode === 'rentang'
    ? guru_rk_month_label($periodeAwal, $namaBulan) . ' s.d. ' . guru_rk_month_label($periodeAkhir, $namaBulan)
    : guru_rk_month_label($periode, $namaBulan);
$kelasDetailUrl = $kelasFilter !== '' ? 'data-siswa?kelas=' . rawurlencode($kelasFilter) : 'data-siswa';
$waliKelas = $kelasFilter !== '' ? guru_rk_get_wali_kelas($conn, $kelasFilter) : ['nama' => '________________________', 'nip' => '________________'];
$lembaga = data_lembaga();
$tanggalCetak = tgl_indo(date('Y-m-d')) . ' pukul ' . date('H:i:s') . ' WIB';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Kehadiran Kelas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary:#0f766e;
            --primary-light:#14b8a6;
            --blue:#2563eb;
            --green:#10b981;
            --orange:#f59e0b;
            --red:#ef4444;
            --purple:#7c3aed;
            --bg:#f5f8fb;
            --text:#0f172a;
            --muted:#64748b;
            --border:#e2e8f0;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            font-family:"Poppins", sans-serif;
            background:
                radial-gradient(circle at top right,
                    rgba(223,255,154,0.35) 0%,
                    transparent 35%),
                radial-gradient(circle at bottom left,
                    rgba(0,107,47,0.35) 0%,
                    transparent 35%),
                linear-gradient(
                    135deg,
                    rgba(11,122,50,0.75),
                    rgba(126,217,87,0.55),
                    rgba(217,255,159,0.45)
                );
            background-attachment: fixed;
            color:var(--text);
            padding-bottom:118px;
            position: relative;
            overflow-x: hidden;
        }

        /* Beautiful Green App Background Overlays */
        .background {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: -10;
            pointer-events: none;
            backdrop-filter: blur(4px);
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(10px);
        }

        .shape.one {
            width: 420px;
            height: 420px;
            background: rgba(47,168,79,0.35);
            top: -120px;
            left: -130px;
        }

        .shape.two {
            width: 520px;
            height: 520px;
            background: rgba(184,240,106,0.28);
            top: -180px;
            right: -160px;
        }

        .shape.three {
            width: 620px;
            height: 620px;
            background: rgba(13,111,45,0.38);
            bottom: -230px;
            right: -190px;
        }

        .shape.four {
            width: 460px;
            height: 460px;
            background: rgba(105,201,74,0.25);
            bottom: -120px;
            left: -160px;
        }

        .wave {
            position: absolute;
            width: 100%;
            height: 100%;
            background:
                repeating-radial-gradient(
                    ellipse at bottom right,
                    transparent 0 12px,
                    rgba(255,255,255,0.08) 13px 14px
                );
            opacity: 0.2;
        }

        .dots {
            position: absolute;
            width: 220px;
            height: 300px;
            background-image:
                radial-gradient(
                    rgba(255,255,255,0.18) 3px,
                    transparent 3px
                );
            background-size: 22px 22px;
            right: 30px;
            top: 90px;
        }
        .page-shell { max-width:1180px; margin:0 auto; padding:24px; }
        .hero {
            position:relative;
            overflow:hidden;
            border-radius:30px;
            padding:24px;
            color:#fff;
            background:
                linear-gradient(135deg, rgba(15,23,42,.94), rgba(15,118,110,.9)),
                radial-gradient(circle at 80% 10%, rgba(20,184,166,.5), transparent 35%);
            box-shadow:0 24px 60px rgba(15,23,42,.16);
            margin-bottom:18px;
        }
        .hero::after {
            content:"";
            position:absolute;
            width:250px;
            height:250px;
            right:-80px;
            top:-110px;
            border-radius:50%;
            background:rgba(255,255,255,.12);
        }
        .hero-content { position:relative; z-index:1; display:flex; justify-content:space-between; align-items:flex-end; gap:18px; }
        .back-link {
            display:inline-flex;
            align-items:center;
            gap:8px;
            border:1px solid rgba(255,255,255,.24);
            border-radius:999px;
            padding:8px 12px;
            color:#fff;
            background:rgba(255,255,255,.12);
            text-decoration:none;
            font-size:12px;
            font-weight:500;
            margin-bottom:18px;
        }
        .page-title { margin:0 0 8px; font-size:clamp(24px, 3.4vw, 36px); line-height:1.15; font-weight:600; }
        .page-subtitle { margin:0; max-width:660px; color:rgba(255,255,255,.78); font-size:13px; line-height:1.6; }
        .hero-stat {
            min-width:170px;
            border:1px solid rgba(255,255,255,.2);
            border-radius:24px;
            padding:18px;
            background:rgba(255,255,255,.14);
            backdrop-filter:blur(14px);
            text-align:center;
        }
        .hero-stat strong { display:block; font-size:28px; line-height:1; font-weight:600; }
        .hero-stat span { color:rgba(255,255,255,.82); font-size:12px; }
        .panel {
            border:1px solid rgba(226,232,240,.9);
            border-radius:24px;
            background:rgba(255,255,255,.94);
            box-shadow:0 18px 44px rgba(15,23,42,.08);
            overflow:hidden;
        }
        .panel-pad { padding:18px; }
        .form-label { color:#334155; font-size:.86rem; font-weight:500; }
        .form-select,
        .form-control {
            min-height:46px;
            border-radius:14px;
            border-color:var(--border);
            font-size:14px;
        }
        .btn {
            border-radius:14px;
            min-height:46px;
            font-weight:600;
        }
        .btn-primary {
            border-color:var(--primary);
            background:linear-gradient(135deg, var(--primary-light), var(--primary));
            box-shadow:0 10px 22px rgba(15,118,110,.2);
        }
        .metric-grid {
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:12px;
            margin:18px 0;
        }
        .metric-card {
            border:1px solid var(--border);
            border-radius:20px;
            background:#fff;
            padding:16px;
        }
        .metric-card small { color:var(--muted); font-weight:500; }
        .metric-card strong { display:block; font-size:22px; font-weight:600; color:var(--text); }
        .table thead th {
            background:#f8fafc;
            color:#334155;
            font-size:11.5px;
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:.04em;
            white-space:nowrap;
        }
        .student-name { font-weight:600; color:#0f172a; min-width:220px; }
        .code-badge {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:34px;
            height:30px;
            border-radius:999px;
            font-weight:600;
            font-size:12px;
        }
        .code-H { color:#047857; background:rgba(16,185,129,.12); }
        .code-S { color:#0369a1; background:rgba(14,165,233,.13); }
        .code-I { color:#b45309; background:rgba(245,158,11,.16); }
        .code-D { color:#6d28d9; background:rgba(124,58,237,.12); }
        .code-A { color:#b91c1c; background:rgba(239,68,68,.13); }
        .code-T { color:#c2410c; background:rgba(249,115,22,.15); }
        .empty-state {
            padding:42px 20px;
            text-align:center;
            color:var(--muted);
        }
        .empty-icon {
            width:62px;
            height:62px;
            border-radius:22px;
            display:grid;
            place-items:center;
            margin:0 auto 14px;
            background:#ccfbf1;
            color:var(--primary);
            font-size:1.55rem;
        }
        .empty-title { color:#334155; font-weight:600; }
        .bottom-nav-wrap { position:fixed; bottom:0; left:0; right:0; z-index:1000; padding:12px 16px 20px; pointer-events:none; }
        .bottom-nav {
            max-width:440px;
            margin:0 auto;
            background:rgba(255,255,255,.92);
            backdrop-filter:blur(20px);
            border-radius:35px;
            padding:10px 12px;
            display:flex;
            justify-content:space-around;
            align-items:center;
            box-shadow:0 -10px 40px rgba(15,23,42,.12);
            border:1px solid rgba(255,255,255,.65);
            pointer-events:auto;
            font-family:"Poppins", sans-serif;
        }
        .nav-link {
            text-decoration:none;
            color:#94a3b8;
            font-size:10px;
            font-weight:600;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:4px;
            padding:0;
            font-family:"Poppins", sans-serif;
        }
        .nav-link i { font-size:20px; }
        .nav-link.active { color:var(--primary); }
        .nav-center {
            width:68px;
            height:68px;
            border-radius:50%;
            background:linear-gradient(135deg, var(--primary-light), var(--primary));
            margin-top:-45px;
            display:grid;
            place-items:center;
            color:#fff;
            font-size:30px;
            box-shadow:0 10px 25px rgba(15,118,110,.35);
            border:5px solid #f8fafc;
            text-decoration:none;
        }
        .print-only { display:none; }
        .print-toolbar {
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            justify-content:flex-end;
        }
        .official-letterhead {
            display:grid;
            grid-template-columns:74px 1fr 74px;
            gap:12px;
            align-items:center;
            border-bottom:3px double #111827;
            padding-bottom:10px;
            margin-bottom:12px;
            text-align:center;
        }
        .official-letterhead img {
            width:68px;
            height:68px;
            object-fit:contain;
        }
        .official-letterhead h1 {
            margin:0;
            font-size:16px;
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:.03em;
        }
        .official-letterhead p {
            margin:2px 0 0;
            font-size:10.5px;
            color:#111827;
        }
        .print-title {
            text-align:center;
            margin:10px 0 12px;
        }
        .print-title h2 {
            margin:0;
            font-size:13px;
            font-weight:600;
            text-transform:uppercase;
            text-decoration:underline;
        }
        .print-title p {
            margin:4px 0 0;
            font-size:10.5px;
        }
        .print-meta {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:4px 22px;
            margin-bottom:12px;
            font-size:10.5px;
        }
        .print-meta-row {
            display:grid;
            grid-template-columns:92px 8px 1fr;
            align-items:start;
            gap:4px;
        }
        .print-meta-label {
            font-weight:600;
            white-space:nowrap;
        }
        .print-meta-colon {
            text-align:center;
            font-weight:600;
        }
        .signature-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:58px;
            margin-top:30px;
            font-size:11px;
            align-items:start;
        }
        .signature-box {
            text-align:center;
            break-inside:avoid;
            min-height:132px;
        }
        .signature-position {
            min-height:34px;
            line-height:1.45;
        }
        .signature-space { height:62px; }
        .signature-name {
            display:inline-block;
            width:220px;
            max-width:100%;
            border-bottom:1px solid #111827;
            font-weight:600;
            line-height:1.35;
            padding-bottom:2px;
        }
        .signature-nip {
            margin-top:2px;
            line-height:1.35;
        }
        @media (max-width: 767px) {
            .page-shell { padding:14px; }
            .hero { border-radius:24px; padding:18px; }
            .hero-content { display:block; }
            .hero-stat { margin-top:16px; text-align:left; }
            .metric-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .panel-pad { padding:14px; }
        }
        @media print {
            @page {
                size: 215mm 330mm;
                margin: 14mm 12mm 16mm;
            }
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            html,
            body {
                width:215mm;
                min-height:330mm;
                background:#fff !important;
                color:#111827;
                padding:0 !important;
                font-family:"Poppins", Arial, sans-serif;
                font-size:10.5px;
            }
            .no-print,
            .bottom-nav-wrap,
            .hero,
            .metric-grid {
                display:none !important;
            }
            .print-only {
                display:block !important;
            }
            .signature-grid.print-only {
                display:grid !important;
                grid-template-columns:1fr 1fr !important;
                gap:58px !important;
                align-items:start;
            }
            .page-shell {
                max-width:none;
                width:100%;
                padding:0;
                margin:0;
            }
            .panel {
                border:0;
                border-radius:0;
                box-shadow:none;
                background:#fff;
                overflow:visible;
            }
            .panel-pad {
                padding:0;
            }
            .table-responsive {
                overflow:visible !important;
            }
            table {
                width:100% !important;
                border-collapse:collapse !important;
                page-break-inside:auto;
            }
            tr {
                page-break-inside:avoid;
                page-break-after:auto;
            }
            .table th,
            .table td {
                border:1px solid #111827 !important;
                padding:4px 5px !important;
                font-size:9.5px !important;
                background:#fff !important;
                color:#111827 !important;
            }
            .table thead th {
                text-transform:none;
                letter-spacing:0;
            }
            .code-badge {
                min-width:0;
                height:auto;
                padding:0;
                border-radius:0;
                background:transparent !important;
                color:#111827 !important;
                font-size:9.5px;
            }
            .student-name {
                min-width:140px;
            }
        }
    </style>
</head>
<body>

  <div class="background">
    <div class="shape one"></div>
    <div class="shape two"></div>
    <div class="shape three"></div>
    <div class="shape four"></div>
    <div class="wave"></div>
    <div class="dots"></div>
  </div>
<main class="page-shell">
    <section class="hero">
        <a class="back-link" href="guru_2026"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <div class="hero-content">
            <div>
                <h1 class="page-title">Lihat Kehadiran Kelas</h1>
                <p class="page-subtitle">Pilih kelas yang diampu dan bulan rekap untuk melihat akumulasi kehadiran siswa berdasarkan jurnal mengajar.</p>
            </div>
            <div class="hero-stat">
                <strong><?= count($kelasOptions); ?></strong>
                <span>Kelas diampu</span>
            </div>
        </div>
    </section>

    <section class="panel panel-pad mb-3 no-print">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" name="kelas" id="kelas" required>
                    <option value="">Pilih kelas yang diampu</option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= guru_rk_h($kelas); ?>" <?= $kelasFilter === $kelas ? 'selected' : ''; ?>>
                            <?= guru_rk_h($kelas); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="mode">Mode Rekap</label>
                <select class="form-select" name="mode" id="mode">
                    <option value="bulan" <?= $mode === 'bulan' ? 'selected' : ''; ?>>Per Bulan</option>
                    <option value="rentang" <?= $mode === 'rentang' ? 'selected' : ''; ?>>Rentang Bulan</option>
                </select>
            </div>
            <div class="col-md-3 period-single" style="<?= $mode === 'rentang' ? 'display:none;' : ''; ?>">
                <label class="form-label" for="periode">Bulan Rekap</label>
                <input class="form-control" type="month" name="periode" id="periode" value="<?= guru_rk_h($periode); ?>" required>
            </div>
            <div class="col-md-2 period-range" style="<?= $mode === 'rentang' ? '' : 'display:none;'; ?>">
                <label class="form-label" for="periode_dari">Dari Bulan</label>
                <input class="form-control" type="month" name="periode_dari" id="periode_dari" value="<?= guru_rk_h($periodeDari); ?>">
            </div>
            <div class="col-md-2 period-range" style="<?= $mode === 'rentang' ? '' : 'display:none;'; ?>">
                <label class="form-label" for="periode_sampai">Sampai Bulan</label>
                <input class="form-control" type="month" name="periode_sampai" id="periode_sampai" value="<?= guru_rk_h($periodeSampai); ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit"><i class="bi bi-search"></i> Panggil Data</button>
                <a class="btn btn-outline-secondary" href="rekap-kehadiran" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </section>

    <?php if (empty($kelasOptions)): ?>
        <section class="panel">
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                <div class="empty-title mb-1">Belum ada kelas diampu</div>
                <div>Guru ini belum memiliki kelas pada data jadwal mengajar.</div>
            </div>
        </section>
    <?php elseif (!$hasFilter): ?>
        <section class="panel">
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-funnel"></i></div>
                <div class="empty-title mb-1">Pilih kelas terlebih dahulu</div>
                <div>Gunakan filter di atas untuk memanggil rekap kehadiran siswa per bulan atau rentang bulan.</div>
            </div>
        </section>
    <?php else: ?>
        <div class="metric-grid">
            <div class="metric-card">
                <small>Siswa Aktif</small>
                <strong><?= count($students); ?></strong>
            </div>
            <div class="metric-card">
                <small>Total Catatan</small>
                <strong><?= $totalRecords; ?></strong>
            </div>
            <div class="metric-card">
                <small>Hadir</small>
                <strong><?= $statusTotals['H']; ?></strong>
            </div>
            <div class="metric-card">
                <small>Persentase Hadir</small>
                <strong><?= $hadirPct; ?>%</strong>
            </div>
        </div>

        <section class="panel print-area" id="printArea">
            <div class="print-only">
                <div class="official-letterhead">
                    <div>
                        <?php if (!empty($lembaga['logo'])): ?>
                            <img src="../../img/<?= guru_rk_h($lembaga['logo']); ?>" alt="Logo Sekolah">
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1><?= guru_rk_h($lembaga['nmsekolah'] ?? ''); ?></h1>
                        <p><?= guru_rk_h($lembaga['alamat'] ?? $lembaga['alamatlembaga'] ?? ''); ?></p>
                    </div>
                    <div></div>
                </div>
                <div class="print-title">
                    <h2>Rekap Kehadiran Siswa</h2>
                    <p>Kelas <?= guru_rk_h($kelasFilter); ?> | Periode <?= guru_rk_h($selectedPeriodLabel); ?></p>
                </div>
                <div class="print-meta">
                    <div class="print-meta-row">
                        <span class="print-meta-label">Nama Guru</span>
                        <span class="print-meta-colon">:</span>
                        <span><?= guru_rk_h($namaGuru); ?></span>
                    </div>
                    <div class="print-meta-row">
                        <span class="print-meta-label">Dicetak</span>
                        <span class="print-meta-colon">:</span>
                        <span><?= guru_rk_h($tanggalCetak); ?></span>
                    </div>
                    <div class="print-meta-row">
                        <span class="print-meta-label">Mode Rekap</span>
                        <span class="print-meta-colon">:</span>
                        <span><?= $mode === 'rentang' ? 'Rentang Bulan' : 'Per Bulan'; ?></span>
                    </div>
                    <div class="print-meta-row">
                        <span class="print-meta-label">Persentase Hadir</span>
                        <span class="print-meta-colon">:</span>
                        <span><?= guru_rk_h($hadirPct); ?>%</span>
                    </div>
                </div>
            </div>

            <div class="panel-pad border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 no-print">
                <div>
                    <h2 class="h5 mb-1 fw-semibold">Rekap <?= guru_rk_h($kelasFilter); ?></h2>
                    <div class="text-muted small">Periode <?= guru_rk_h($selectedPeriodLabel); ?></div>
                </div>
                <div class="print-toolbar">
                    <div class="d-flex flex-wrap gap-2 small">
                        <span class="code-badge code-H">H</span>
                        <span class="code-badge code-S">S</span>
                        <span class="code-badge code-I">I</span>
                        <span class="code-badge code-D">D</span>
                        <span class="code-badge code-A">A</span>
                        <span class="code-badge code-T">T</span>
                    </div>
                    <button class="btn btn-primary" type="button" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak F4
                    </button>
                </div>
            </div>

            <?php if (empty($rekapRows)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-people"></i></div>
                    <div class="empty-title mb-1">Data siswa tidak ditemukan</div>
                    <div>Belum ada siswa aktif pada kelas ini.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:64px;">No</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Sakit</th>
                                <th class="text-center">Izin</th>
                                <th class="text-center">Dispen</th>
                                <th class="text-center">Alpha</th>
                                <th class="text-center">Telat</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">% Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rekapRows as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td class="student-name"><?= guru_rk_h($row['nama_siswa']); ?></td>
                                    <td class="text-center"><span class="code-badge code-H"><?= (int) $row['H']; ?></span></td>
                                    <td class="text-center"><span class="code-badge code-S"><?= (int) $row['S']; ?></span></td>
                                    <td class="text-center"><span class="code-badge code-I"><?= (int) $row['I']; ?></span></td>
                                    <td class="text-center"><span class="code-badge code-D"><?= (int) $row['D']; ?></span></td>
                                    <td class="text-center"><span class="code-badge code-A"><?= (int) $row['A']; ?></span></td>
                                    <td class="text-center"><span class="code-badge code-T"><?= (int) $row['T']; ?></span></td>
                                    <td class="text-center fw-semibold"><?= (int) $row['total']; ?></td>
                                    <td class="text-center fw-semibold"><?= guru_rk_h($row['persen_hadir']); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-semibold">
                                <td colspan="2" class="text-end">Total</td>
                                <td class="text-center"><?= $statusTotals['H']; ?></td>
                                <td class="text-center"><?= $statusTotals['S']; ?></td>
                                <td class="text-center"><?= $statusTotals['I']; ?></td>
                                <td class="text-center"><?= $statusTotals['D']; ?></td>
                                <td class="text-center"><?= $statusTotals['A']; ?></td>
                                <td class="text-center"><?= $statusTotals['T']; ?></td>
                                <td class="text-center"><?= $totalRecords; ?></td>
                                <td class="text-center"><?= $hadirPct; ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="signature-grid print-only">
                    <div class="signature-box">
                        <div class="signature-position">
                            <div>Mengetahui,</div>
                            <div>Kepala Sekolah</div>
                        </div>
                        <div class="signature-space"></div>
                        <div class="signature-name"><?= guru_rk_h($lembaga['nmpimpinan'] ?? '________________________'); ?></div>
                        <div class="signature-nip">NIP. <?= guru_rk_h($lembaga['nippimpinan'] ?? '________________'); ?></div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-position">
                            <div>Wali Kelas</div>
                            <div><?= guru_rk_h($kelasFilter); ?></div>
                        </div>
                        <div class="signature-space"></div>
                        <div class="signature-name"><?= guru_rk_h($waliKelas['nama']); ?></div>
                        <div class="signature-nip">NIP. <?= guru_rk_h($waliKelas['nip']); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<div class="bottom-nav-wrap">
    <nav class="bottom-nav" aria-label="Navigasi guru">
        <a href="guru_2026" class="nav-link"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
        <a href="<?= guru_rk_h($kelasDetailUrl); ?>" class="nav-link active"><i class="bi bi-journal-bookmark"></i><span>Kelas</span></a>
        <a href="guru_2026?open_jurnal=1" class="nav-center" aria-label="Input jurnal"><i class="bi bi-fingerprint"></i></a>
        <a href="inputtugas" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Tugas</span></a>
        <a href="profil-guru" class="nav-link"><i class="bi bi-person-fill"></i><span>Profil</span></a>
    </nav>
</div>
<script>
    (function() {
        var mode = document.getElementById('mode');
        var single = document.querySelector('.period-single');
        var ranges = document.querySelectorAll('.period-range');

        function syncPeriodMode() {
            var isRange = mode && mode.value === 'rentang';
            if (single) {
                single.style.display = isRange ? 'none' : '';
            }
            ranges.forEach(function(item) {
                item.style.display = isRange ? '' : 'none';
            });
        }

        if (mode) {
            mode.addEventListener('change', syncPeriodMode);
            syncPeriodMode();
        }
    })();
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
