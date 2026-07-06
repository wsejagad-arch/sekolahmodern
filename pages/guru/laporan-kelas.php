<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not a teacher
if (!isset($_SESSION['no_induk'])) {
    header('location: ../../index.php?haruslogin');
    exit;
}
if ((int)$_SESSION['hak_akses'] !== 2) {
    echo '<script>window.location="../../404.html";</script>';
    exit;
}

require_once '../../koneksi.php';
require_once '../../functions.php';
date_default_timezone_set('Asia/Jakarta');

$nipGuru = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$namaGuru = $_SESSION['nama_guru'] ?? ($_SESSION['nama'] ?? 'Guru');

function normalize_date_input(?string $value, string $fallback): string
{
    $value = trim((string)$value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    return $fallback;
}

function normalize_month_input(?string $value, string $fallback): string
{
    $value = trim((string)$value);
    if (preg_match('/^\d{4}-\d{2}$/', $value)) {
        return $value;
    }
    return $fallback;
}

$currentMonth = date('Y-m');
$periodType = ($_GET['periode'] ?? 'bulan') === 'tanggal' ? 'tanggal' : 'bulan';
$monthStartInput = normalize_month_input($_GET['bulan_awal'] ?? null, $currentMonth);
$monthEndInput = normalize_month_input($_GET['bulan_akhir'] ?? null, $monthStartInput);
$dateStartInput = normalize_date_input($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$dateEndInput = normalize_date_input($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));

if ($periodType === 'tanggal') {
    $dateStart = $dateStartInput;
    $dateEnd = $dateEndInput;
} else {
    $dateStart = $monthStartInput . '-01';
    $dateEnd = date('Y-m-t', strtotime($monthEndInput . '-01'));
}

if (strtotime($dateStart) > strtotime($dateEnd)) {
    [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
    if ($periodType === 'tanggal') {
        [$dateStartInput, $dateEndInput] = [$dateEndInput, $dateStartInput];
    } else {
        [$monthStartInput, $monthEndInput] = [$monthEndInput, $monthStartInput];
    }
}

$dateStartEsc = mysqli_real_escape_string($conn, $dateStart);
$dateEndEsc = mysqli_real_escape_string($conn, $dateEnd);
$periodLabel = date('d M Y', strtotime($dateStart)) . ' - ' . date('d M Y', strtotime($dateEnd));
$backHomeUrl = php_sapi_name() === 'cli-server' ? '../../home.php' : '../../home.php';
$inputJurnalUrl = php_sapi_name() === 'cli-server' ? '../../home.php?open_jurnal=1' : '../../home.php?open_jurnal=1';

// Get Gemini API Key
$geminiApiKey = '';
$qSetting = mysqli_query($conn, "SELECT gemini_api_key FROM tbl_setting WHERE id=1 LIMIT 1");
if ($qSetting && mysqli_num_rows($qSetting) > 0) {
    $rowSetting = mysqli_fetch_assoc($qSetting);
    $geminiApiKey = trim((string)$rowSetting['gemini_api_key'] ?? '');
}
if ($geminiApiKey === '') {
    $geminiApiKey = 'AIzaSyC9zh6FHEnbqrW1MSlO4fVnSdu2L8SjSE8';
}

// Fetch classes taught by this teacher (Guru Mapel)
$kelasOptions = [];
$kelasWaliOptions = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND kelas <> '' ORDER BY kelas ASC");
while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
    $kelasOptions[] = $row['kelas'];
}

// Also fetch classes where they are Wali Kelas
$checkWali = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
if ($checkWali && mysqli_num_rows($checkWali) > 0) {
    $qWali = mysqli_query(
        $conn,
        "SELECT DISTINCT k.kelas
         FROM tbl_wali_kelas wk
         JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
         WHERE wk.nip_wali = '$nipEsc' AND k.kelas <> ''"
    );
    while ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
        $kelasOptions[] = $row['kelas'];
        $kelasWaliOptions[] = $row['kelas'];
    }
}
// Check legacy column in tbl_kelas
$checkKelasCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
if ($checkKelasCol && mysqli_num_rows($checkKelasCol) > 0) {
    $qKelasWali = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='$nipEsc' AND kelas <> ''");
    while ($qKelasWali && ($row = mysqli_fetch_assoc($qKelasWali))) {
        $kelasOptions[] = $row['kelas'];
        $kelasWaliOptions[] = $row['kelas'];
    }
}

// Remove duplicates, filters and sort
$kelasOptions = array_unique(array_filter($kelasOptions));
sort($kelasOptions);
$kelasWaliOptions = array_unique(array_filter($kelasWaliOptions));
sort($kelasWaliOptions);

$kelasFilter = trim((string)($_GET['kelas'] ?? ''));
if ($kelasFilter === '' && !empty($kelasOptions)) {
    $kelasFilter = $kelasOptions[0];
}
$kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
$canTindakLanjut = $kelasFilter !== '' && in_array($kelasFilter, $kelasWaliOptions, true);

// Get actual Wali Kelas details
$waliKelasNama = '........................................';
$waliKelasNip = '................................';

if ($kelasFilter !== '') {
    // 1. Try tbl_wali_kelas joined with tbl_kelas and tbl_guru
    $checkWaliTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
    if ($checkWaliTable && mysqli_num_rows($checkWaliTable) > 0) {
        $checkGuruCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'nip_guru'");
        $nipSelect = ($checkGuruCol && mysqli_num_rows($checkGuruCol) > 0)
            ? "COALESCE(NULLIF(g.nip_guru,''), g.no_induk)"
            : "g.no_induk";
            
        $qWaliInfo = mysqli_query(
            $conn,
            "SELECT g.nama_guru, {$nipSelect} AS nip_guru
             FROM tbl_wali_kelas wk
             JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
             JOIN tbl_guru g ON g.no_induk = wk.nip_wali
             WHERE k.kelas = '$kelasEsc'
             LIMIT 1"
        );
        if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
            $waliKelasNama = (string)($rowWali['nama_guru'] ?? $waliKelasNama);
            $waliKelasNip = (string)($rowWali['nip_guru'] ?? $waliKelasNip);
        }
    }
    
    // 2. Try tbl_kelas + tbl_guru directly (legacy column nip_wali in tbl_kelas)
    if ($waliKelasNama === '........................................') {
        $checkKelasCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
        if ($checkKelasCol && mysqli_num_rows($checkKelasCol) > 0) {
            $checkGuruCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'nip_guru'");
            $nipSelect = ($checkGuruCol && mysqli_num_rows($checkGuruCol) > 0)
                ? "COALESCE(NULLIF(g.nip_guru,''), g.no_induk)"
                : "g.no_induk";
                
            $qWaliInfo = mysqli_query(
                $conn,
                "SELECT g.nama_guru, {$nipSelect} AS nip_guru
                 FROM tbl_kelas k
                 JOIN tbl_guru g ON g.no_induk = k.nip_wali
                 WHERE k.kelas = '$kelasEsc'
                 LIMIT 1"
            );
            if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
                $waliKelasNama = (string)($rowWali['nama_guru'] ?? $waliKelasNama);
                $waliKelasNip = (string)($rowWali['nip_guru'] ?? $waliKelasNip);
            }
        }
    }
    
    // 3. Try tbl_wali_kelas nama_wali directly
    if ($waliKelasNama === '........................................') {
        $checkWaliTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
        if ($checkWaliTable && mysqli_num_rows($checkWaliTable) > 0) {
            $checkNamaWaliCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_wali_kelas LIKE 'nama_wali'");
            if ($checkNamaWaliCol && mysqli_num_rows($checkNamaWaliCol) > 0) {
                $qWaliInfo = mysqli_query(
                    $conn,
                    "SELECT wk.nama_wali, wk.nip_wali
                     FROM tbl_wali_kelas wk
                     JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
                     WHERE k.kelas = '$kelasEsc'
                     LIMIT 1"
                );
                if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
                    if (!empty($rowWali['nama_wali'])) {
                        $waliKelasNama = $rowWali['nama_wali'];
                        if (!empty($rowWali['nip_wali'])) {
                            $waliKelasNip = $rowWali['nip_wali'];
                        }
                    }
                }
            }
        }
    }

    // 4. Try tbl_kelas wali_kelas column directly
    if ($waliKelasNama === '........................................') {
        $checkWaliCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'wali_kelas'");
        if ($checkWaliCol && mysqli_num_rows($checkWaliCol) > 0) {
            $qWaliInfo = mysqli_query(
                $conn,
                "SELECT k.wali_kelas, k.nip_wali
                 FROM tbl_kelas k
                 WHERE k.kelas = '$kelasEsc'
                 LIMIT 1"
            );
            if ($qWaliInfo && $rowWali = mysqli_fetch_assoc($qWaliInfo)) {
                if (!empty($rowWali['wali_kelas']) && $rowWali['wali_kelas'] !== '0') {
                    $waliKelasNama = $rowWali['wali_kelas'];
                    if (!empty($rowWali['nip_wali']) && $rowWali['nip_wali'] !== '0') {
                        $waliKelasNip = $rowWali['nip_wali'];
                    }
                }
            }
        }
    }
}

$students = [];
$classStats = [
    'total_siswa' => 0,
    'total_absen_records' => 0,
    'total_hadir' => 0,
    'attendance_rate' => 100,
    'avg_nilai' => null,
    'total_pelanggaran' => 0
];

if ($kelasFilter !== '') {
    // 1. Get all active students in the class
    $qSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$kelasEsc' AND (status='Aktif' OR status='' OR status IS NULL OR UPPER(status)='AKTIF') ORDER BY nama_siswa ASC");
    while ($qSiswa && ($row = mysqli_fetch_assoc($qSiswa))) {
        $nis = $row['no_induk'];
        $students[$nis] = [
            'no_induk' => $nis,
            'nama_siswa' => $row['nama_siswa'],
            'attendance' => ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'D' => 0, 'T' => 0, 'total' => 0],
            'nilai_avg' => null,
            'nilai_count' => 0,
            'pelanggaran' => ['ringan' => 0, 'sedang' => 0, 'berat' => 0, 'total' => 0, 'details' => []],
            'indeks_masalah' => 0
        ];
    }

    $classStats['total_siswa'] = count($students);

    if (!empty($students)) {
        $nisList = array_keys($students);
        $nisIn = "'" . implode("','", array_map(function($n) use ($conn) { return mysqli_real_escape_string($conn, $n); }, $nisList)) . "'";

        // 2. Fetch Attendance
        $qAbsen = mysqli_query($conn, "SELECT no_induk, status, COUNT(*) as qty FROM tbl_absen WHERE no_induk IN ($nisIn) AND kelas='$kelasEsc' AND tanggal BETWEEN '$dateStartEsc' AND '$dateEndEsc' GROUP BY no_induk, status");
        while ($qAbsen && ($row = mysqli_fetch_assoc($qAbsen))) {
            $nis = $row['no_induk'];
            $st = strtoupper(trim($row['status']));
            $qty = (int)$row['qty'];

            $code = 'H';
            if ($st === 'SAKIT' || $st === 'S') $code = 'S';
            elseif ($st === 'IZIN' || $st === 'IJIN' || $st === 'I') $code = 'I';
            elseif ($st === 'ALPHA' || $st === 'ALPA' || $st === 'A') $code = 'A';
            elseif ($st === 'DISPEN' || $st === 'D') $code = 'D';
            elseif ($st === 'TELAT' || $st === 'TERLAMBAT' || $st === 'T') $code = 'T';

            if (isset($students[$nis])) {
                $students[$nis]['attendance'][$code] += $qty;
                $students[$nis]['attendance']['total'] += $qty;

                if ($code === 'H' || $code === 'T' || $code === 'D') {
                    $classStats['total_hadir'] += $qty;
                }
                $classStats['total_absen_records'] += $qty;
            }
        }

        // Calculate attendance rate
        if ($classStats['total_absen_records'] > 0) {
            $classStats['attendance_rate'] = round(($classStats['total_hadir'] / $classStats['total_absen_records']) * 100);
        }

        // 3. Fetch Grades (Nilai)
        $qNilai = mysqli_query($conn, "
            SELECT vi.no_induk_siswa, vi.nilai
            FROM tbl_nilai_item vi
            JOIN tbl_penilaian_item pi ON pi.id = vi.id_item
            WHERE vi.no_induk_siswa IN ($nisIn) AND pi.kelas='$kelasEsc' AND pi.tanggal BETWEEN '$dateStartEsc' AND '$dateEndEsc'
        ");
        $studentGrades = [];
        $allClassGrades = [];
        while ($qNilai && ($row = mysqli_fetch_assoc($qNilai))) {
            $nis = $row['no_induk_siswa'];
            $val = (float)$row['nilai'];
            $studentGrades[$nis][] = $val;
            $allClassGrades[] = $val;
        }
        foreach ($studentGrades as $nis => $grades) {
            if (isset($students[$nis]) && !empty($grades)) {
                $students[$nis]['nilai_avg'] = array_sum($grades) / count($grades);
                $students[$nis]['nilai_count'] = count($grades);
            }
        }
        if (!empty($allClassGrades)) {
            $classStats['avg_nilai'] = array_sum($allClassGrades) / count($allClassGrades);
        }

        // 4. Fetch Infractions (Pelanggaran)
        $checkPelTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran_siswa'");
        if ($checkPelTable && mysqli_num_rows($checkPelTable) > 0) {
            $qPelanggaran = mysqli_query($conn, "
                SELECT no_induk, kategori_pelanggaran, jenis_pelanggaran, deskripsi_pelanggaran, tanggal_pelanggaran
                FROM tbl_pelanggaran_siswa
                WHERE no_induk IN ($nisIn) AND kelas='$kelasEsc' AND tanggal_pelanggaran BETWEEN '$dateStartEsc' AND '$dateEndEsc'
            ");
            while ($qPelanggaran && ($row = mysqli_fetch_assoc($qPelanggaran))) {
                $nis = $row['no_induk'];
                $kat = trim($row['kategori_pelanggaran']);
                if (isset($students[$nis])) {
                    $students[$nis]['pelanggaran']['total']++;
                    $classStats['total_pelanggaran']++;
                    if (strtolower($kat) === 'ringan') $students[$nis]['pelanggaran']['ringan']++;
                    elseif (strtolower($kat) === 'sedang') $students[$nis]['pelanggaran']['sedang']++;
                    elseif (strtolower($kat) === 'berat') $students[$nis]['pelanggaran']['berat']++;
                    $students[$nis]['pelanggaran']['details'][] = $row;
                }
            }
        }

        // 5. Calculate Problem Score (Indeks Masalah)
        foreach ($students as $nis => &$s) {
            $score = 0;
            // Attendance weighting
            $score += $s['attendance']['A'] * 5;
            $score += $s['attendance']['T'] * 1.5;
            $score += $s['attendance']['I'] * 0.5;
            $score += $s['attendance']['S'] * 0.2;
            $score += $s['attendance']['D'] * 0.1;

            // Infractions weighting
            $score += $s['pelanggaran']['ringan'] * 3;
            $score += $s['pelanggaran']['sedang'] * 6;
            $score += $s['pelanggaran']['berat'] * 10;

            // Grade weighting
            if ($s['nilai_avg'] !== null && $s['nilai_avg'] < 75) {
                $score += (75 - $s['nilai_avg']) * 0.5;
            }

            $s['indeks_masalah'] = $score;
        }
        unset($s);

        // Sort students descending by Indeks Masalah
        uasort($students, function($a, $b) {
            if ($a['indeks_masalah'] == $b['indeks_masalah']) {
                return strcmp($a['nama_siswa'], $b['nama_siswa']);
            }
            return ($a['indeks_masalah'] < $b['indeks_masalah']) ? 1 : -1;
        });
    }
}

$attendanceTotals = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'D' => 0, 'T' => 0, 'total' => 0];
$problemScoreTotal = 0.0;
foreach ($students as $student) {
    foreach ($attendanceTotals as $code => $_) {
        $attendanceTotals[$code] += (int)($student['attendance'][$code] ?? 0);
    }
    $problemScoreTotal += (float)($student['indeks_masalah'] ?? 0);
}
$avgProblemScore = count($students) > 0 ? $problemScoreTotal / count($students) : 0;
$topProblemStudents = array_values(array_filter($students, static function ($student) {
    return (float)($student['indeks_masalah'] ?? 0) > 0;
}));
$topProblemStudents = array_slice($topProblemStudents, 0, 5);
$semesterLabel = (int)date('n') >= 1 && (int)date('n') <= 6 ? 'Genap' : 'Ganjil';
$tahunAjaranLabel = (int)date('n') >= 7 ? date('Y') . '/' . ((int)date('Y') + 1) : ((int)date('Y') - 1) . '/' . date('Y');
$kepalaSekolah = '';
try {
    $lembagaForReport = data_lembaga();
    $kepalaSekolah = (string)($lembagaForReport['nmpimpinan'] ?? '');
} catch (Throwable $e) {
    $kepalaSekolah = '';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Kelas AI - SIMANIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --shadow: 0 18px 42px rgba(15,23,42,.08);
            --gradient: linear-gradient(135deg, #a855f7, #6366f1);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            font-weight: 400;
            background: #ebf1f6;
            color: var(--text);
        }
        .page-shell { padding: 0; }
        .hero { display: none; }
        .background { display: none; }
        
        body {
            background-attachment: fixed;
            color: var(--text);
            padding-bottom: 112px;
            min-height: 100vh;
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
        .page-shell { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .hero {
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            color: #fff;
            border-radius: 22px;
            padding: 28px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .panel {
            background: rgba(255,255,255,.95);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .panel-pad { padding: 20px; }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 10px 25px rgba(15,23,42,.03);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
        }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-green { background: #ecfdf5; color: #10b981; }
        .icon-purple { background: #faf5ff; color: #a855f7; }
        .icon-red { background: #fef2f2; color: #ef4444; }
        
        .metric-info { flex: 1; }
        .metric-label { color: var(--muted); font-size: 0.8rem; margin: 0; }
        .metric-value { font-size: 1.4rem; font-weight: 600; margin: 2px 0 0; color: var(--text); }
        
        .table th {
            white-space: nowrap;
            color: var(--muted);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }
        .table td { vertical-align: middle; padding: 14px 12px; }
        .student-name { font-weight: 600; color: #0f172a; }
        
        .badge-masalah {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-high { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-med { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-low { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        
        .att-tag {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 6px;
            margin-right: 2px;
            display: inline-block;
        }
        .tag-s { background: #f1f5f9; color: #475569; }
        .tag-i { background: #e0f2fe; color: #0369a1; }
        .tag-a { background: #fee2e2; color: #b91c1c; font-weight: bold; }
        .tag-d { background: #f0fdf4; color: #166534; }
        .tag-t { background: #fef3c7; color: #b45309; }

        .btn-ai {
            background: var(--gradient);
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 14px;
            box-shadow: 0 10px 20px rgba(168, 85, 247, 0.25);
            transition: all 0.3s;
        }
        .btn-ai:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(168, 85, 247, 0.35);
        }
        
        .markdown-content table {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: collapse;
        }
        .markdown-content th, .markdown-content td {
            padding: 10px;
            border: 1px solid var(--border);
        }
        .markdown-content th { background: #f8fafc; }

        .bottom-nav-wrap { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000; padding: 12px 16px 20px; }
        .bottom-nav {
            max-width: 440px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            border-radius: 35px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -10px 40px rgba(0,0,0,.08);
            border: 1px solid rgba(255,255,255,.55);
            font-family: "Poppins", sans-serif;
        }
        .nav-link-item {
            text-decoration: none;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 500;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            font-family: "Poppins", sans-serif;
        }
        .nav-link-item i { font-size: 20px; }
        .nav-link-item.active { color: var(--primary); }
        .nav-center {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            margin-top: -45px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 34px;
            box-shadow: 0 10px 25px rgba(79,70,229,.4);
            border: 5px solid #f8fafc;
            text-decoration: none;
        }
        .period-field.is-hidden {
            display: none;
        }
        .analysis-report-shell {
            background: #eef2f7;
            padding: 22px;
            border-radius: 18px;
            overflow-x: auto;
        }
        .analysis-report-page {
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
            padding: 38px 46px;
            border: 2px solid #1e3a8a;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            color: #1f2937;
            font-family: "Times New Roman", Arial, sans-serif;
            line-height: 1.6;
        }
        .analysis-report-page .kop {
            border: 3px solid #1e3a8a;
            padding: 18px 20px;
            text-align: center;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white;
            margin-bottom: 18px;
        }
        .analysis-report-page .kop h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .analysis-report-page .kop h2 {
            margin: 6px 0 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .analysis-report-page .kop p {
            margin: 5px 0 0;
            font-size: 15px;
        }
        .analysis-report-page .line-double {
            border-top: 4px double #111827;
            margin: 16px 0 24px;
        }
        .analysis-report-page .section-title {
            background: #1e3a8a;
            color: white;
            padding: 9px 13px;
            margin-top: 28px;
            margin-bottom: 0;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #1e3a8a;
        }
        .analysis-report-page .sub-title {
            color: #1e3a8a;
            margin-top: 24px;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: bold;
            border-left: 5px solid #1e3a8a;
            padding-left: 10px;
        }
        .analysis-report-page table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border: 2px solid #1f2937;
        }
        .analysis-report-page th,
        .analysis-report-page td {
            border: 1.5px solid #1f2937;
            padding: 10px 12px;
            vertical-align: top;
            font-size: 14.5px;
        }
        .analysis-report-page th {
            background: #dbeafe;
            color: #111827;
            text-align: center;
            font-weight: bold;
        }
        .analysis-report-page .identity td:first-child {
            width: 28%;
            font-weight: bold;
            background: #eff6ff;
        }
        .analysis-report-page .center { text-align: center; }
        .analysis-report-page .highlight {
            font-weight: bold;
            color: #0f766e;
        }
        .analysis-report-page .warning {
            font-weight: bold;
            color: #b45309;
        }
        .analysis-report-page .note {
            border: 1.5px solid #92400e;
            background: #fffbeb;
            padding: 12px 14px;
            margin: 12px 0 18px;
            font-size: 14.5px;
        }
        .analysis-report-page .conclusion {
            border: 2px solid #1e3a8a;
            background: #eff6ff;
            padding: 14px 16px;
            margin-top: 10px;
            font-weight: 500;
        }
        .analysis-report-page .signature-table {
            margin-top: 34px;
            border: none;
        }
        .analysis-report-page .signature-table td {
            border: none;
            text-align: center;
            height: 120px;
            width: 50%;
        }
        .analysis-report-page .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .analysis-report-page .footer-line {
            margin-top: 24px;
            border-top: 2px solid #1f2937;
            padding-top: 8px;
            font-size: 12px;
            text-align: center;
            color: #4b5563;
        }
        .analysis-report-page .markdown-content {
            color: #1f2937 !important;
            font-size: 14.5px !important;
            line-height: 1.65 !important;
        }
        .analysis-report-page .markdown-content h1,
        .analysis-report-page .markdown-content h2,
        .analysis-report-page .markdown-content h3 {
            color: #1e3a8a;
            font-size: 16px;
            margin: 18px 0 8px;
            font-weight: bold;
        }
        .analysis-report-page .markdown-content table {
            border: 2px solid #1f2937;
        }

        @media (max-width: 991px) {
            .metric-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 575px) {
            .page-shell { padding: 16px; }
            .metric-grid { grid-template-columns: 1fr; }
            .hero { padding: 20px; }
            .analysis-report-shell { padding: 10px; }
            .analysis-report-page { padding: 24px 18px; }
            .analysis-report-page .kop h1 { font-size: 21px; }
            .analysis-report-page .kop h2 { font-size: 17px; }
            .analysis-report-page th,
            .analysis-report-page td { font-size: 13px; padding: 8px; }
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            body * { visibility: hidden; }
            .analysis-report-page,
            .analysis-report-page * { visibility: visible; }
            .analysis-report-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .analysis-report-shell {
                background: white;
                padding: 0;
            }
            .analysis-report-page {
                box-shadow: none;
                border: none;
                max-width: 100%;
                padding: 24px;
            }
            .analysis-report-page .kop,
            .analysis-report-page th,
            .analysis-report-page .section-title,
            .analysis-report-page .identity td:first-child {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
<?php include 'guru_sidebar_shared.php'; ?>


<div class="app-shell" style="grid-template-columns: 1fr; padding-right: 24px;">
    <div class="desktop-center-column">
        <!-- Welcome Banner -->
        <div class="welcome-banner-premium mb-4">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 class="animate-fade-in" style="font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.5px;">Laporan Analisis Kelas AI 🤖</h2>
                    <p class="banner-subtitle" style="font-size:1.05rem;opacity:0.9;">Analisis kondisi kelas dan identifikasi siswa yang membutuhkan pendampingan segera. Periode data: <strong class="text-white"><?= htmlspecialchars($periodLabel); ?></strong>.</p>
                </div>
                <div class="banner-actions">
                    <a href="<?= htmlspecialchars($backHomeUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-premium-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                </div>
            </div>
            <div class="banner-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

    <!-- Filters -->
    <section class="panel panel-pad">
        <form method="get" class="row g-3 align-items-end" id="filterPeriodeForm">
            <div class="col-md-6 col-lg-3">
                <label class="form-label fw-semibold" for="kelas">Pilih Kelas yang Diampu</label>
                <select class="form-select" id="kelas" name="kelas" required>
                    <?php if (empty($kelasOptions)): ?>
                        <option value="">Tidak ada kelas diampu</option>
                    <?php else: ?>
                        <?php foreach ($kelasOptions as $kls): ?>
                            <option value="<?= htmlspecialchars($kls); ?>" <?= $kelasFilter === $kls ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kls); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label fw-semibold" for="periode">Mode Periode</label>
                <select class="form-select" id="periode" name="periode">
                    <option value="bulan" <?= $periodType === 'bulan' ? 'selected' : ''; ?>>Rentang Bulan</option>
                    <option value="tanggal" <?= $periodType === 'tanggal' ? 'selected' : ''; ?>>Rentang Tanggal</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2 period-field period-month">
                <label class="form-label fw-semibold" for="bulan_awal">Bulan Awal</label>
                <input type="month" class="form-control" id="bulan_awal" name="bulan_awal" value="<?= htmlspecialchars($monthStartInput); ?>">
            </div>
            <div class="col-md-6 col-lg-2 period-field period-month">
                <label class="form-label fw-semibold" for="bulan_akhir">Bulan Akhir</label>
                <input type="month" class="form-control" id="bulan_akhir" name="bulan_akhir" value="<?= htmlspecialchars($monthEndInput); ?>">
            </div>
            <div class="col-md-6 col-lg-2 period-field period-date">
                <label class="form-label fw-semibold" for="tanggal_awal">Tanggal Awal</label>
                <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" value="<?= htmlspecialchars($dateStartInput); ?>">
            </div>
            <div class="col-md-6 col-lg-2 period-field period-date">
                <label class="form-label fw-semibold" for="tanggal_akhir">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= htmlspecialchars($dateEndInput); ?>">
            </div>
            <div class="col-md-6 col-lg-1 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
            <div class="col-12 text-md-end">
                <span class="text-muted small">Waktu Server: <strong><?= date('d M Y H:i'); ?></strong></span>
            </div>
        </form>
    </section>

    <?php if ($kelasFilter !== ''): ?>
        <!-- Metrics -->
        <div class="metric-grid">
            <div class="metric-card">
                <div class="metric-icon icon-blue"><i class="bi bi-people-fill"></i></div>
                <div class="metric-info">
                    <p class="metric-label">Siswa Aktif</p>
                    <h3 class="metric-value"><?= $classStats['total_siswa']; ?></h3>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon icon-green"><i class="bi bi-calendar2-check-fill"></i></div>
                <div class="metric-info">
                    <p class="metric-label">Kehadiran Kelas</p>
                    <h3 class="metric-value"><?= $classStats['attendance_rate']; ?>%</h3>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon icon-purple"><i class="bi bi-journal-text"></i></div>
                <div class="metric-info">
                    <p class="metric-label">Rata-rata Nilai</p>
                    <h3 class="metric-value"><?= $classStats['avg_nilai'] !== null ? number_format($classStats['avg_nilai'], 1, ',', '.') : '-'; ?></h3>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon icon-red"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="metric-info">
                    <p class="metric-label">Total Pelanggaran</p>
                    <h3 class="metric-value"><?= $classStats['total_pelanggaran']; ?></h3>
                </div>
            </div>
        </div>

        <!-- AI Analysis Section -->
        <section class="panel panel-pad text-center mb-4">
            <div id="ai-action-container">
                <h4 class="fw-bold mb-3"><i class="bi bi-stars" style="color:#a855f7;"></i> Analisis Kelas dengan Kecerdasan Buatan</h4>
                <p class="text-muted max-width-600 mx-auto mb-4">AI akan menganalisis data nilai, tingkat kehadiran, keterlambatan, sakit, izin, alpha, dan riwayat pelanggaran siswa kelas <?= htmlspecialchars($kelasFilter); ?> pada periode <?= htmlspecialchars($periodLabel); ?> untuk memetakan prioritas pendampingan bimbingan konseling.</p>
                <button type="button" class="btn btn-ai" id="ai-action-btn" onclick="analyzeClassWithAI()">
                    <i class="bi bi-robot me-2"></i> Analisis Kelas dengan AI
                </button>
            </div>

            <div id="ai-loading" class="py-4 d-none">
                <div class="spinner-border" style="color: #a855f7; width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3 fw-semibold">AI sedang mengolah data kelas <?= htmlspecialchars($kelasFilter); ?></h5>
                <p class="text-muted">Ini memerlukan waktu beberapa detik untuk merumuskan rekomendasi...</p>
            </div>

            <div id="ai-result-container" class="d-none text-start mt-2">
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-secondary" onclick="analyzeClassWithAI()"><i class="bi bi-arrow-clockwise"></i> Analisis Ulang</button>
                    <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
                </div>
                <div class="analysis-report-shell">
                    <div class="analysis-report-page">
                        <div class="kop">
                            <h1>Laporan Analisis Kondisi Kelas</h1>
                            <h2>Kelas <?= htmlspecialchars($kelasFilter); ?></h2>
                            <p>Periode <?= htmlspecialchars($periodLabel); ?></p>
                        </div>

                        <div class="line-double"></div>

                        <h3 class="section-title">A. Identitas Laporan</h3>
                        <table class="identity">
                            <tr>
                                <td>Nama Kelas</td>
                                <td><?= htmlspecialchars($kelasFilter); ?></td>
                            </tr>
                            <tr>
                                <td>Wali Kelas</td>
                                <td><?= htmlspecialchars($waliKelasNama); ?></td>
                            </tr>
                            <tr>
                                <td>Semester</td>
                                <td><?= htmlspecialchars($semesterLabel); ?></td>
                            </tr>
                            <tr>
                                <td>Tahun Ajaran</td>
                                <td><?= htmlspecialchars($tahunAjaranLabel); ?></td>
                            </tr>
                            <tr>
                                <td>Periode Laporan</td>
                                <td><?= htmlspecialchars($periodLabel); ?></td>
                            </tr>
                            <tr>
                                <td>Jumlah Siswa</td>
                                <td><?= (int)$classStats['total_siswa']; ?> Siswa</td>
                            </tr>
                        </table>

                        <h3 class="section-title">B. Ringkasan Kondisi Kelas</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No.</th>
                                    <th style="width: 30%;">Aspek Penilaian</th>
                                    <th style="width: 20%;">Hasil</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="center">1</td>
                                    <td>Tingkat Kehadiran</td>
                                    <td class="center highlight"><?= (int)$classStats['attendance_rate']; ?>%</td>
                                    <td><?= (int)$classStats['attendance_rate'] >= 90 ? 'Kehadiran kelas berada pada kategori sangat baik.' : 'Kehadiran kelas perlu mendapat perhatian dan pemantauan.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">2</td>
                                    <td>Alpha</td>
                                    <td class="center <?= $attendanceTotals['A'] > 0 ? 'warning' : 'highlight'; ?>"><?= (int)$attendanceTotals['A']; ?></td>
                                    <td><?= $attendanceTotals['A'] > 0 ? 'Terdapat siswa tanpa keterangan pada periode laporan.' : 'Tidak ada siswa tanpa keterangan.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">3</td>
                                    <td>Izin</td>
                                    <td class="center <?= $attendanceTotals['I'] > 0 ? 'warning' : 'highlight'; ?>"><?= (int)$attendanceTotals['I']; ?></td>
                                    <td><?= $attendanceTotals['I'] > 0 ? 'Terdapat catatan izin yang perlu dipantau sesuai frekuensinya.' : 'Tidak ada catatan izin.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">4</td>
                                    <td>Sakit</td>
                                    <td class="center <?= $attendanceTotals['S'] > 0 ? 'warning' : 'highlight'; ?>"><?= (int)$attendanceTotals['S']; ?></td>
                                    <td><?= $attendanceTotals['S'] > 0 ? 'Terdapat catatan sakit yang perlu dikonfirmasi bila berulang.' : 'Tidak ada catatan sakit.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">5</td>
                                    <td>Terlambat</td>
                                    <td class="center <?= $attendanceTotals['T'] > 0 ? 'warning' : 'highlight'; ?>"><?= (int)$attendanceTotals['T']; ?></td>
                                    <td><?= $attendanceTotals['T'] > 0 ? 'Terdapat keterlambatan yang perlu ditindaklanjuti.' : 'Tidak ada siswa terlambat.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">6</td>
                                    <td>Pelanggaran Perilaku</td>
                                    <td class="center <?= $classStats['total_pelanggaran'] > 0 ? 'warning' : 'highlight'; ?>"><?= (int)$classStats['total_pelanggaran']; ?></td>
                                    <td><?= $classStats['total_pelanggaran'] > 0 ? 'Terdapat catatan pelanggaran tata tertib.' : 'Tidak ditemukan pelanggaran tata tertib.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">7</td>
                                    <td>Data Akademik</td>
                                    <td class="center <?= $classStats['avg_nilai'] !== null ? 'highlight' : 'warning'; ?>"><?= $classStats['avg_nilai'] !== null ? number_format($classStats['avg_nilai'], 1, ',', '.') : 'Belum tersedia'; ?></td>
                                    <td><?= $classStats['avg_nilai'] !== null ? 'Rata-rata nilai sudah tercatat dalam periode laporan.' : 'Belum ada nilai yang masuk dalam sistem pada periode ini.'; ?></td>
                                </tr>
                                <tr>
                                    <td class="center">8</td>
                                    <td>Rata-rata Indeks Masalah</td>
                                    <td class="center <?= $avgProblemScore > 0 ? 'warning' : 'highlight'; ?>"><?= number_format($avgProblemScore, 1, ',', '.'); ?></td>
                                    <td><?= $avgProblemScore > 0 ? 'Terdapat anomali absensi, akademik, atau perilaku yang perlu dianalisis.' : 'Tidak ada anomali absensi maupun perilaku.'; ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h3 class="section-title">C. Analisis Umum</h3>
                        <div id="ai-result-content" class="markdown-content"></div>

                        <h3 class="section-title">D. Prioritas Pendampingan Siswa</h3>
                        <?php if (empty($topProblemStudents)): ?>
                            <p>Berdasarkan data yang tersedia, <strong>tidak terdapat siswa yang masuk kategori prioritas pendampingan</strong>. Seluruh siswa kelas <strong><?= htmlspecialchars($kelasFilter); ?></strong> memiliki catatan yang baik pada periode ini.</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 8%;">No.</th>
                                        <th>Nama Siswa</th>
                                        <th style="width: 18%;">Indeks Masalah</th>
                                        <th style="width: 18%;">Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="center">1</td>
                                        <td>Seluruh Siswa Kelas <?= htmlspecialchars($kelasFilter); ?></td>
                                        <td class="center highlight"><?= number_format($avgProblemScore, 1, ',', '.'); ?></td>
                                        <td class="center highlight">Baik</td>
                                        <td>Mempertahankan rekam jejak kehadiran, akademik, dan perilaku.</td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 8%;">No.</th>
                                        <th>Nama Siswa</th>
                                        <th style="width: 18%;">Indeks Masalah</th>
                                        <th style="width: 18%;">Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProblemStudents as $idx => $student): ?>
                                        <?php
                                            $score = (float)$student['indeks_masalah'];
                                            $statusLabel = $score >= 10 ? 'Prioritas Tinggi' : ($score >= 3 ? 'Perlu Dipantau' : 'Awal');
                                        ?>
                                        <tr>
                                            <td class="center"><?= $idx + 1; ?></td>
                                            <td><?= htmlspecialchars($student['nama_siswa']); ?><br><span style="font-size:12px;">NIS: <?= htmlspecialchars($student['no_induk']); ?></span></td>
                                            <td class="center warning"><?= number_format($score, 1, ',', '.'); ?></td>
                                            <td class="center warning"><?= htmlspecialchars($statusLabel); ?></td>
                                            <td>Alpha: <?= (int)$student['attendance']['A']; ?>, Telat: <?= (int)$student['attendance']['T']; ?>, Nilai rata-rata: <?= $student['nilai_avg'] !== null ? number_format($student['nilai_avg'], 1, ',', '.') : '-'; ?>, Pelanggaran: <?= (int)$student['pelanggaran']['total']; ?>.</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <div class="note">
                            <strong>Catatan Penting:</strong> Laporan ini mengikuti periode filter aktif, yaitu <?= htmlspecialchars($periodLabel); ?>. Ubah filter bulan atau tanggal untuk melihat perbandingan periode lain.
                        </div>

                        <h3 class="section-title">E. Rekomendasi Tindak Lanjut</h3>
                        <h4 class="sub-title">1. Bagi Guru Mata Pelajaran</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No.</th>
                                    <th style="width: 30%;">Rekomendasi</th>
                                    <th>Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="center">1</td>
                                    <td>Sinkronisasi Data Nilai</td>
                                    <td>Guru mata pelajaran memastikan nilai tugas, ulangan, proyek, atau penilaian lain sudah masuk pada periode laporan.</td>
                                </tr>
                                <tr>
                                    <td class="center">2</td>
                                    <td>Pendampingan Akademik</td>
                                    <td>Berikan remedial atau penguatan untuk siswa dengan rata-rata nilai di bawah KKM.</td>
                                </tr>
                            </tbody>
                        </table>

                        <h4 class="sub-title">2. Bagi Wali Kelas</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No.</th>
                                    <th style="width: 30%;">Rekomendasi</th>
                                    <th>Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="center">1</td>
                                    <td>Monitoring Kehadiran</td>
                                    <td>Validasi catatan alpha, sakit, izin, dispen, dan keterlambatan bersama guru mapel atau petugas piket.</td>
                                </tr>
                                <tr>
                                    <td class="center">2</td>
                                    <td>Pendampingan Perilaku</td>
                                    <td>Tindak lanjuti siswa yang memiliki catatan pelanggaran atau indeks masalah tinggi.</td>
                                </tr>
                                <tr>
                                    <td class="center">3</td>
                                    <td>Apresiasi Kelas</td>
                                    <td>Berikan penghargaan kolektif jika kelas menunjukkan kedisiplinan dan kehadiran yang baik.</td>
                                </tr>
                            </tbody>
                        </table>

                        <h4 class="sub-title">3. Langkah Pencegahan</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No.</th>
                                    <th style="width: 30%;">Langkah Preventif</th>
                                    <th>Tujuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="center">1</td>
                                    <td>Refleksi Berkala</td>
                                    <td>Mengetahui kondisi belajar, kenyamanan kelas, dan potensi masalah sejak awal.</td>
                                </tr>
                                <tr>
                                    <td class="center">2</td>
                                    <td>Monitoring Berkelanjutan</td>
                                    <td>Menjaga konsistensi kehadiran, kedisiplinan, dan perkembangan akademik.</td>
                                </tr>
                                <tr>
                                    <td class="center">3</td>
                                    <td>Koordinasi Orang Tua</td>
                                    <td>Membangun dukungan rumah dan sekolah untuk siswa yang mulai menunjukkan risiko.</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3 class="section-title">F. Kesimpulan</h3>
                        <div class="conclusion">
                            Kelas <strong><?= htmlspecialchars($kelasFilter); ?></strong> pada periode <strong><?= htmlspecialchars($periodLabel); ?></strong> memiliki tingkat kehadiran <strong><?= (int)$classStats['attendance_rate']; ?>%</strong>, total pelanggaran <strong><?= (int)$classStats['total_pelanggaran']; ?></strong>, dan rata-rata indeks masalah <strong><?= number_format($avgProblemScore, 1, ',', '.'); ?></strong>.
                            <br><br>
                            Fokus tindak lanjut diarahkan pada pemantauan siswa prioritas, kelengkapan data akademik, serta penguatan budaya disiplin dan apresiasi kelas.
                        </div>

                        <h3 class="section-title">G. Pengesahan</h3>
                        <table class="signature-table">
                            <tr>
                                <td>
                                    Mengetahui,<br>
                                    Kepala Sekolah
                                    <br><br><br><br>
                                    <span class="signature-name"><?= $kepalaSekolah !== '' ? htmlspecialchars($kepalaSekolah) : '........................................'; ?></span><br>
                                    NIP. ................................
                                </td>
                                <td>
                                    Wali Kelas,
                                    <br><br><br><br><br>
                                    <span class="signature-name"><?= htmlspecialchars($waliKelasNama); ?></span><br>
                                    NIP. <?= htmlspecialchars($waliKelasNip); ?>
                                </td>
                            </tr>
                        </table>

                        <div class="footer-line">
                            Dokumen Laporan Analisis Kondisi Kelas <?= htmlspecialchars($kelasFilter); ?> - Periode <?= htmlspecialchars($periodLabel); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Students Table -->
        <section class="panel">
            <div class="panel-pad border-bottom bg-white rounded-top-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-stars text-primary me-2"></i> Prioritas Pendampingan Siswa</h5>
                    <span class="badge bg-light text-dark border"><i class="bi bi-info-circle me-1"></i> Diurutkan berdasarkan tingkat masalah siswa</span>
                </div>
            </div>
            <?php if (empty($students)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Tidak ada siswa aktif ditemukan di kelas ini.</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Rata-rata Nilai</th>
                                <th>Kehadiran (S/I/A/D/T)</th>
                                <th>Catatan Pelanggaran</th>
                                <th class="text-center">Indeks Masalah</th>
                                <?php if ($canTindakLanjut): ?>
                                    <th style="width: 140px;" class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($students as $nis => $s):
                                $score = $s['indeks_masalah'];
                                $badgeClass = 'badge-low';
                                $badgeText = 'Normal';
                                if ($score >= 10) {
                                    $badgeClass = 'badge-high';
                                    $badgeText = 'Tinggi';
                                } elseif ($score >= 3) {
                                    $badgeClass = 'badge-med';
                                    $badgeText = 'Sedang';
                                }

                                $gradeColor = $s['nilai_avg'] !== null && $s['nilai_avg'] < 75 ? 'text-danger fw-semibold' : 'text-success fw-semibold';
                            ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td>
                                        <div class="student-name"><?= htmlspecialchars($s['nama_siswa']); ?></div>
                                        <div class="text-muted small">NIS: <?= htmlspecialchars($s['no_induk']); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="<?= $gradeColor; ?>">
                                            <?= $s['nilai_avg'] !== null ? number_format($s['nilai_avg'], 1, ',', '.') : '-'; ?>
                                        </span>
                                        <div class="text-muted small"><?= $s['nilai_count']; ?> penilaian</div>
                                    </td>
                                    <td>
                                        <span class="att-tag tag-s" title="Sakit">S: <?= $s['attendance']['S']; ?></span>
                                        <span class="att-tag tag-i" title="Izin">I: <?= $s['attendance']['I']; ?></span>
                                        <span class="att-tag <?= $s['attendance']['A'] > 0 ? 'tag-a' : 'tag-s'; ?>" title="Alpha">A: <?= $s['attendance']['A']; ?></span>
                                        <span class="att-tag tag-d" title="Dispen">D: <?= $s['attendance']['D']; ?></span>
                                        <span class="att-tag <?= $s['attendance']['T'] > 0 ? 'tag-t' : 'tag-s'; ?>" title="Telat">T: <?= $s['attendance']['T']; ?></span>
                                        <div class="text-muted small mt-1">Total Absen: <?= $s['attendance']['total']; ?> kali</div>
                                    </td>
                                    <td>
                                        <?php if ($s['pelanggaran']['total'] > 0): ?>
                                            <span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill"></i> <?= $s['pelanggaran']['total']; ?> Pelanggaran</span>
                                            <div class="text-muted small">
                                                Ringan: <?= $s['pelanggaran']['ringan']; ?>, Sedang: <?= $s['pelanggaran']['sedang']; ?>, Berat: <?= $s['pelanggaran']['berat']; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Bersih</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-masalah <?= $badgeClass; ?>">
                                            <?= $badgeText; ?> (<?= number_format($score, 1, ',', '.'); ?>)
                                        </span>
                                    </td>
                                    <?php if ($canTindakLanjut): ?>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-pill" onclick="openTindakLanjutModal('<?= htmlspecialchars($s['no_induk']); ?>', '<?= htmlspecialchars(addslashes($s['nama_siswa'])); ?>')">
                                                <i class="bi bi-journal-plus"></i> Tindak Lanjut
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    </div> <!-- End desktop-center-column -->
</div> <!-- End app-shell -->

<!-- Tindak Lanjut Modal -->
<div class="modal fade" id="tindakLanjutModal" tabindex="-1" aria-labelledby="tindakLanjutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-light rounded-top-4">
                <h5 class="modal-title fw-bold" id="tindakLanjutModalLabel"><i class="bi bi-journal-plus text-primary me-2"></i> Tindak Lanjut Pendampingan Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-primary-subtle text-primary border-primary-subtle mb-4">
                    <strong>Siswa:</strong> <span id="modal-student-name"></span> (<span id="modal-student-nis"></span>)
                </div>

                <ul class="nav nav-pills mb-3" id="modalTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="input-tab" data-bs-toggle="tab" data-bs-target="#input-panel" type="button" role="tab" aria-controls="input-panel" aria-selected="true"><i class="bi bi-pencil-square me-1"></i> Catat Jurnal Baru</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab" aria-controls="history-panel" aria-selected="false"><i class="bi bi-clock-history me-1"></i> Riwayat Pendampingan</button>
                    </li>
                </ul>

                <div class="tab-content" id="modalTabContent">
                    <!-- Form Input Tab -->
                    <div class="tab-pane fade show active" id="input-panel" role="tabpanel" aria-labelledby="input-tab">
                        <form id="formJurnal">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" id="modal-nis" name="nis">
                            <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelasFilter); ?>">
                            
                            <div class="mb-3">
                                <label for="catatan" class="form-label fw-semibold">Catatan Masalah/Kondisi Siswa</label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Deskripsikan kondisi, nilai rendah, ketidakhadiran, atau masalah lainnya..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="tindak_lanjut" class="form-label fw-semibold">Rencana / Tindak Lanjut yang Diambil</label>
                                <textarea class="form-control" id="tindak_lanjut" name="tindak_lanjut" rows="3" placeholder="Rencana konseling, memanggil siswa, penugasan remedial, dsb..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label fw-semibold">Status Tindakan</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Belum Selesai">Belum Selesai (Rencana/Proses)</option>
                                    <option value="Berjalan">Berjalan (Sedang Dilaksanakan)</option>
                                    <option value="Selesai">Selesai (Sudah Tertangani)</option>
                                </select>
                            </div>

                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Simpan Catatan</button>
                            </div>
                        </form>
                    </div>

                    <!-- History Tab -->
                    <div class="tab-pane fade" id="history-panel" role="tabpanel" aria-labelledby="history-tab">
                        <div id="history-loading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="history-list" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Catatan</th>
                                            <th>Tindak Lanjut</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="history-table-body">
                                        <!-- Dynamic items -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
const studentData = <?= json_encode(array_values($students)); ?>;
const kelasWali = <?= json_encode($kelasFilter); ?>;
const periodLabel = <?= json_encode($periodLabel); ?>;

function analyzeClassWithAI() {
    if(studentData.length === 0) {
        alert('Data siswa kosong, tidak ada yang bisa dianalisis.');
        return;
    }

    $('#ai-action-btn').addClass('d-none');
    $('#ai-result-container').addClass('d-none');
    $('#ai-loading').removeClass('d-none');

    // Create summary for AI
    let summaryData = studentData.map((s, idx) => {
        let att = s.attendance;
        let attText = `Alpha: ${att.A}, Telat: ${att.T}, Izin: ${att.I}, Sakit: ${att.S}, Dispen: ${att.D}`;
        let gradeText = s.nilai_avg !== null ? `Rata-rata Nilai: ${Number(s.nilai_avg).toFixed(1)}` : 'Nilai: Belum ada';
        let pelText = s.pelanggaran.total > 0 ? `Pelanggaran: ${s.pelanggaran.total} (${s.pelanggaran.ringan} Ringan, ${s.pelanggaran.sedang} Sedang, ${s.pelanggaran.berat} Berat)` : 'Pelanggaran: Tidak ada';
        return `${idx + 1}. ${s.nama_siswa} (NIS: ${s.no_induk}) -> ${attText} | ${gradeText} | ${pelText} | Indeks Masalah: ${s.indeks_masalah.toFixed(1)}`;
    }).join('\n');

    const promptText = `Anda adalah seorang Konselor Pendidikan AI dan Asisten Wali Kelas yang profesional.
Berikut adalah data kondisi akademik, kehadiran (izin, sakit, alpha, dispen, telat), dan catatan pelanggaran siswa kelas ${kelasWali} pada periode ${periodLabel}:
${summaryData}

Tolong berikan laporan analisis kondisi kelas yang komprehensif:
1. **Analisis Umum**: Ringkasan singkat mengenai tingkat kedisiplinan, kehadiran, akademik, dan kepatuhan kelas secara umum.
2. **Prioritas Pendampingan (Siswa Butuh Pendampingan)**: Identifikasi 3-5 siswa yang paling membutuhkan pendampingan (prioritas tinggi) berdasarkan Indeks Masalah mereka. Jelaskan aspek apa saja yang menjadi masalah utama bagi masing-masing siswa tersebut (misal: sering alpha/telat, nilai di bawah KKM 75, atau banyak pelanggaran).
3. **Rekomendasi Tindak Lanjut**: Berikan rekomendasi langkah konkret dan solutif yang bisa diambil oleh Guru Mata Pelajaran maupun Wali Kelas untuk mendampingi siswa-siswa yang membutuhkan pendampingan tersebut, serta langkah pencegahan agar siswa lain tidak mengalami hal serupa.

Gunakan format Markdown (tabel, bullet points, teks tebal) dengan penyajian yang profesional, objektif, dan solutif dalam bahasa Indonesia. Tidak perlu menyapa panjang lebar, langsung berikan hasil analisis secara rapi.`;

    $.ajax({
        url: 'ajax_laporan_kelas_ai.php',
        method: 'POST',
        data: { prompt: promptText },
        dataType: 'json'
    }).done(function(response) {
        if (response && response.success) {
            const aiResponse = response.text;
            $('#ai-result-content').html(marked.parse(aiResponse));
            $('#ai-result-container').removeClass('d-none');
        } else {
            alert('Gagal mengambil analisis AI: ' + (response.message || 'Error tidak dikenal.'));
            $('#ai-action-btn').removeClass('d-none');
        }
    }).fail(function(xhr) {
        let msg = 'Gagal menghubungi server AI.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        }
        alert('Gagal mengambil analisis AI: ' + msg);
        $('#ai-action-btn').removeClass('d-none');
    }).always(function() {
        $('#ai-loading').addClass('d-none');
    });
}
</script>
<script>
    function syncPeriodFields() {
        const mode = document.getElementById('periode')?.value || 'bulan';
        document.querySelectorAll('.period-month').forEach((item) => {
            item.classList.toggle('is-hidden', mode !== 'bulan');
        });
        document.querySelectorAll('.period-date').forEach((item) => {
            item.classList.toggle('is-hidden', mode !== 'tanggal');
        });
    }

    document.getElementById('periode')?.addEventListener('change', syncPeriodFields);
    syncPeriodFields();

    function openTindakLanjutModal(nis, nama) {
        $('#modal-student-name').text(nama);
        $('#modal-student-nis').text(nis);
        $('#modal-nis').val(nis);
        
        // Reset form
        $('#formJurnal')[0].reset();
        
        // Switch to input tab
        const triggerEl = document.querySelector('#modalTab button[data-bs-target="#input-panel"]');
        bootstrap.Tab.getInstance(triggerEl)?.show();
        
        // Fetch history
        $('#history-loading').removeClass('d-none');
        $('#history-list').addClass('d-none');
        
        $.get('ajax_jurnal_pendampingan?action=get&kelas=' + encodeURIComponent('<?= htmlspecialchars($kelasFilter); ?>') + '&nis=' + encodeURIComponent(nis), function(res) {
            $('#history-loading').addClass('d-none');
            if (res.status === 'success') {
                let html = '';
                // Filter history by student's NIS
                const studentHistory = res.data.filter(item => item.nis === nis);
                if (studentHistory.length === 0) {
                    html = '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat pendampingan untuk siswa ini.</td></tr>';
                } else {
                    studentHistory.forEach(item => {
                        let badge = 'bg-secondary';
                        if (item.status === 'Selesai') badge = 'bg-success';
                        else if (item.status === 'Berjalan') badge = 'bg-warning';
                        
                        html += `<tr>
                            <td class="text-nowrap">${item.tanggal}</td>
                            <td>${escapeHtml(item.catatan)}</td>
                            <td>${escapeHtml(item.tindak_lanjut)}</td>
                            <td><span class="badge ${badge}">${item.status}</span></td>
                        </tr>`;
                    });
                }
                $('#history-table-body').html(html);
                $('#history-list').removeClass('d-none');
            } else {
                $('#history-table-body').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat riwayat.</td></tr>');
                $('#history-list').removeClass('d-none');
            }
        });
        
        const myModal = new bootstrap.Modal(document.getElementById('tindakLanjutModal'));
        myModal.show();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    $('#formJurnal').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Menyimpan...');
        
        $.post('ajax_jurnal_pendampingan', $(this).serialize(), function(res) {
            submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Catatan');
            if(res.status === 'success') {
                alert('Jurnal pendampingan berhasil disimpan!');
                bootstrap.Modal.getInstance(document.getElementById('tindakLanjutModal')).hide();
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json').fail(function() {
            submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Catatan');
            alert('Koneksi terputus atau gagal menghubungi server.');
        });
    });
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
