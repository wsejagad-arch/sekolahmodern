<?php
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

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


if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;padding:24px;color:#991b1b;">Koneksi database tidak tersedia.</div>';
    exit;
}

$nipGuru = (string) ($_SESSION['no_induk'] ?? '');
$namaGuru = (string) ($_SESSION['nama_guru'] ?? ($_SESSION['nama'] ?? 'Guru'));
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);

// Migration Check: Ensure gemini_api_key exists
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_setting LIKE 'gemini_api_key'");
if ($checkCol && mysqli_num_rows($checkCol) == 0) {
    mysqli_query($conn, "ALTER TABLE tbl_setting ADD COLUMN gemini_api_key VARCHAR(255) DEFAULT ''");
    mysqli_query($conn, "UPDATE tbl_setting SET gemini_api_key='AIzaSyC9zh6FHEnbqrW1MSlO4fVnSdu2L8SjSE8' WHERE id=1");
}
$geminiApiKey = '';
$qSetting = mysqli_query($conn, "SELECT gemini_api_key FROM tbl_setting WHERE id=1 LIMIT 1");
if ($qSetting && mysqli_num_rows($qSetting) > 0) {
    $rowSetting = mysqli_fetch_assoc($qSetting);
    $geminiApiKey = trim((string)($rowSetting['gemini_api_key'] ?? ''));
}
if ($geminiApiKey === '') {
    $geminiApiKey = 'AIzaSyC9zh6FHEnbqrW1MSlO4fVnSdu2L8SjSE8';
}

function guru_wk_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function guru_wk_table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $result = @mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    return $result && mysqli_num_rows($result) > 0;
}

function guru_wk_column_exists(mysqli $conn, string $table, string $column): bool
{
    $safeTable = mysqli_real_escape_string($conn, $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = @mysqli_query($conn, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result && mysqli_num_rows($result) > 0;
}

function guru_wk_status_code(string $status): string
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
    if ($status === 'telat' || $status === 'terlambat') {
        return 'T';
    }
    return 'H';
}

function guru_wk_pct(int $part, int $total): int
{
    return $total > 0 ? (int) round(($part / $total) * 100) : 0;
}

function guru_wk_normalize_month(string $value, string $fallback): string
{
    return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : $fallback;
}

function guru_wk_month_label(string $period, array $months): string
{
    [$year, $month] = array_map('intval', explode('-', $period));
    return ($months[$month] ?? $period) . ' ' . $year;
}

function guru_wk_priority_level(array $att, $nilai, bool $profileComplete): array
{
    $reasons = [];
    $score = 0;
    if ((int) ($att['A'] ?? 0) >= 3) {
        $score += 5;
        $reasons[] = 'Alpha ' . (int) $att['A'] . ' kali';
    }
    if ((int) ($att['pct'] ?? 100) < 80 && (int) ($att['total'] ?? 0) > 0) {
        $score += 3;
        $reasons[] = 'Kehadiran di bawah 80%';
    }
    if ($nilai !== null && (float) $nilai < 75) {
        $score += 2;
        $reasons[] = 'Rata-rata nilai ' . number_format((float) $nilai, 1, ',', '.');
    }
    if (!$profileComplete) {
        $score += 1;
        $reasons[] = 'Profil rencana belum lengkap';
    }

    if ($score <= 0) {
        return ['', [], ''];
    }
    if ($score >= 6) {
        return ['Prioritas Tinggi', $reasons, 'Panggil siswa, hubungi orang tua, koordinasi BK, dan pantau progres mingguan.'];
    }
    if ($score >= 3) {
        return ['Perlu Dipantau', $reasons, 'Konseling singkat wali kelas dan pantau presensi harian.'];
    }
    return ['Pemetaan Awal', $reasons, 'Lengkapi data minat, bakat, dan rencana siswa.'];
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
    12 => 'Desember',
];

$kelasOptions = [];
if (guru_wk_table_exists($conn, 'tbl_wali_kelas') && guru_wk_table_exists($conn, 'tbl_kelas')) {
    $qWali = @mysqli_query(
        $conn,
        "SELECT DISTINCT k.kelas
         FROM tbl_wali_kelas wk
         JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
         WHERE wk.nip_wali = '{$nipEsc}' AND k.kelas <> ''
         ORDER BY k.kelas ASC"
    );
    while ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
        $kelasOptions[(string) $row['kelas']] = (string) $row['kelas'];
    }
}
if (guru_wk_table_exists($conn, 'tbl_kelas') && guru_wk_column_exists($conn, 'tbl_kelas', 'nip_wali')) {
    $qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC");
    while ($qKelas && ($row = mysqli_fetch_assoc($qKelas))) {
        $kelasOptions[(string) $row['kelas']] = (string) $row['kelas'];
    }
}
ksort($kelasOptions, SORT_NATURAL | SORT_FLAG_CASE);

$periodeDefault = guru_wk_normalize_month(trim((string) ($_GET['periode'] ?? date('Y-m'))), date('Y-m'));
$periodeMulai = guru_wk_normalize_month(trim((string) ($_GET['periode_mulai'] ?? $periodeDefault)), $periodeDefault);
$periodeSelesai = guru_wk_normalize_month(trim((string) ($_GET['periode_selesai'] ?? $periodeDefault)), $periodeMulai);
if (strtotime($periodeSelesai . '-01') < strtotime($periodeMulai . '-01')) {
    [$periodeMulai, $periodeSelesai] = [$periodeSelesai, $periodeMulai];
}
$firstDay = $periodeMulai . '-01';
$lastDay = date('Y-m-t', strtotime($periodeSelesai . '-01'));
$selectedPeriodLabel = $periodeMulai === $periodeSelesai
    ? guru_wk_month_label($periodeMulai, $namaBulan)
    : guru_wk_month_label($periodeMulai, $namaBulan) . ' - ' . guru_wk_month_label($periodeSelesai, $namaBulan);


// Handle Validasi Izin Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'acc_wali' || $action === 'tolak_wali') {
        $id_izin = (int)$_POST['id_izin'];
        $nama_guru_val = $_SESSION['nama'] ?? $_SESSION['nama_guru'] ?? 'Guru';
        
        $qCek = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa WHERE id_izin = $id_izin");
        $rCek = mysqli_fetch_assoc($qCek);
        if ($rCek) {
            if ($action === 'acc_wali') {
                $status_baru = 'Menunggu Guru BK';
                if ($rCek['validasi_guru_bk'] === 'Disetujui') {
                    $status_baru = ($rCek['kategori_pengajuan'] === 'Keluar Sekolah') ? 'Menunggu Satpam' : 'Disetujui Penuh';
                }
                $q = "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Disetujui', validator_wali_kelas = '$nama_guru_val', waktu_validasi_wali_kelas = NOW(), status_izin = '$status_baru' WHERE id_izin = $id_izin";
                mysqli_query($conn, $q);
                $msg_validasi = "Izin berhasil disetujui.";
            } elseif ($action === 'tolak_wali') {
                $q = "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Ditolak', validator_wali_kelas = '$nama_guru_val', waktu_validasi_wali_kelas = NOW(), status_izin = 'Ditolak' WHERE id_izin = $id_izin";
                mysqli_query($conn, $q);
                $msg_validasi = "Izin berhasil ditolak.";
            }
            
            // Auto Absen jika sudah fully disetujui (selain keluar sekolah) dan aksi validasi berhasil
            if (isset($msg_validasi) && strpos($msg_validasi, 'berhasil') !== false) {
                $qCekAkhir = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa WHERE id_izin = $id_izin");
                $rCekAkhir = mysqli_fetch_assoc($qCekAkhir);
                if ($rCekAkhir['validasi_wali_kelas'] === 'Disetujui' && $rCekAkhir['validasi_guru_bk'] === 'Disetujui') {
                    $expectedStatus = ($rCekAkhir['kategori_pengajuan'] === 'Keluar Sekolah') ? 'Menunggu Satpam' : 'Disetujui Penuh';
                    if ($rCekAkhir['status_izin'] !== $expectedStatus) {
                        mysqli_query($conn, "UPDATE tbl_izin_siswa SET status_izin = '$expectedStatus' WHERE id_izin = $id_izin");
                        $rCekAkhir['status_izin'] = $expectedStatus;
                    }
                }
                if (in_array($rCekAkhir['status_izin'], ['Disetujui Penuh', 'Disetujui'], true)) {
                    $nis = $rCekAkhir['no_induk_siswa'];
                    $tgl = $rCekAkhir['tanggal_izin'];
                    $kls = $rCekAkhir['kelas_siswa'];
                    
                    $kat = strtolower($rCekAkhir['kategori_pengajuan']);
                    $kode_absen = 'I';
                    if (strpos($kat, 'sakit') !== false) $kode_absen = 'S';
                    elseif (strpos($kat, 'dispen') !== false) $kode_absen = 'D'; // D atau I
                    
                    // Cek apakah sudah ada absen di tbl_absen
                    $cekAbsen = mysqli_query($conn, "SELECT id FROM tbl_absen WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL LIMIT 1");
                    if (mysqli_num_rows($cekAbsen) > 0) {
                        mysqli_query($conn, "UPDATE tbl_absen SET status = '$kode_absen', sumber = 'Sistem Izin' WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL");
                    } else {
                        mysqli_query($conn, "INSERT INTO tbl_absen (id_sekolah, tanggal, kelas, no_induk, status, sumber, created_at) VALUES (1, '$tgl', '$kls', '$nis', '$kode_absen', 'Sistem Izin', NOW())");
                    }

                    $tblJurnal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jurnal'");
                    if ($tblJurnal && mysqli_num_rows($tblJurnal) > 0) {
                        $nisEsc = mysqli_real_escape_string($conn, $nis);
                        $tglEsc = mysqli_real_escape_string($conn, $tgl);
                        $klsEsc = mysqli_real_escape_string($conn, $kls);
                        $detailIzin = mysqli_real_escape_string($conn, $rCekAkhir['detail_izin'] ?? '');
                        $jenisIzin = mysqli_real_escape_string($conn, $rCekAkhir['jenis_izin'] ?? 'Izin');
                        $catatan = "Izin disetujui penuh: {$jenisIzin}. {$detailIzin}";
                        mysqli_query($conn, "INSERT INTO tbl_jurnal (no_induk, kelas, tanggal, mapel, jurnal, catatan) VALUES ('$nisEsc', '$klsEsc', '$tglEsc', 'Izin', 'Izin disetujui', '$catatan')");
                    }
                }
            }
        }
    }
}

$kelasFilter = trim((string) ($_GET['kelas'] ?? ''));

// Fetch pending izin for the selected class
$list_izin = [];
if ($kelasFilter !== '') {
    $kelas_esc_v = mysqli_real_escape_string($conn, str_replace(' ', '', $kelasFilter));
    $qIzin = mysqli_query($conn, "SELECT i.*, s.nama_siswa, s.kelas as kelas_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE REPLACE(s.kelas, ' ', '') = '$kelas_esc_v' AND i.validasi_wali_kelas = 'Menunggu' ORDER BY i.waktu_pengajuan DESC");
    if ($qIzin) {
        while ($row = mysqli_fetch_assoc($qIzin)) {
            $list_izin[] = $row;
        }
    }
}

if ($kelasFilter === '' && count($kelasOptions) === 1) {
    $kelasFilter = (string) reset($kelasOptions);
}
if ($kelasFilter !== '' && !isset($kelasOptions[$kelasFilter])) {
    $kelasFilter = '';
}

$hasClass = $kelasFilter !== '';
$kelasEsc = mysqli_real_escape_string($conn, $kelasFilter);
$students = [];
$attendanceMonth = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'T' => 0, 'total' => 0];
$profileComplete = 0;
$gradeDataCount = 0;

if ($hasClass && guru_wk_table_exists($conn, 'tbl_siswa')) {
    $columns = ['s.no_induk', 's.nama_siswa', 's.kelas'];
    foreach (['rencana_setelah_lulus', 'minat_jurusan', 'bakat_minat', 'no_wa', 'no_darurat'] as $column) {
        $columns[] = guru_wk_column_exists($conn, 'tbl_siswa', $column) ? "s.`{$column}`" : "'' AS `{$column}`";
    }
    $statusWhere = guru_wk_column_exists($conn, 'tbl_siswa', 'status')
        ? " AND (s.status='Aktif' OR s.status='aktif' OR s.status='' OR s.status IS NULL)"
        : '';
    $qStudents = @mysqli_query(
        $conn,
        "SELECT " . implode(', ', $columns) . "
         FROM tbl_siswa s
         WHERE s.kelas='{$kelasEsc}'{$statusWhere}
         ORDER BY s.nama_siswa ASC"
    );
    while ($qStudents && ($row = mysqli_fetch_assoc($qStudents))) {
        $nis = (string) $row['no_induk'];
        $students[$nis] = $row + [
            'attendance' => ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'T' => 0, 'total' => 0, 'pct' => 0],
            'nilai_final' => null,
        ];
    }
}

if ($hasClass && !empty($students) && guru_wk_table_exists($conn, 'tbl_absen')) {
    $qAttendance = @mysqli_query(
        $conn,
        "SELECT no_induk, status
         FROM tbl_absen
         WHERE kelas='{$kelasEsc}' AND tanggal BETWEEN '{$firstDay}' AND '{$lastDay}'"
    );
    while ($qAttendance && ($row = mysqli_fetch_assoc($qAttendance))) {
        $nis = (string) ($row['no_induk'] ?? '');
        if (!isset($students[$nis])) {
            continue;
        }
        $code = guru_wk_status_code((string) ($row['status'] ?? ''));
        if (!isset($students[$nis]['attendance'][$code])) {
            $code = 'H';
        }
        $students[$nis]['attendance'][$code]++;
        $students[$nis]['attendance']['total']++;
        $attendanceMonth[$code]++;
        $attendanceMonth['total']++;
    }
}

if ($hasClass && !empty($students) && guru_wk_table_exists($conn, 'tbl_penilaian_item') && guru_wk_table_exists($conn, 'tbl_nilai_item')) {
    $qGrades = @mysqli_query(
        $conn,
        "SELECT ni.no_induk_siswa, ROUND(AVG(ni.nilai),2) AS rata_nilai
         FROM tbl_nilai_item ni
         JOIN tbl_penilaian_item pi ON pi.id = ni.id_item
         WHERE pi.kelas='{$kelasEsc}' AND pi.tanggal BETWEEN '{$firstDay}' AND '{$lastDay}' AND ni.nilai > 0
         GROUP BY ni.no_induk_siswa"
    );
    while ($qGrades && ($row = mysqli_fetch_assoc($qGrades))) {
        $nis = (string) ($row['no_induk_siswa'] ?? '');
        if (isset($students[$nis])) {
            $students[$nis]['nilai_final'] = (float) $row['rata_nilai'];
            $gradeDataCount++;
        }
    }
}

if ($hasClass && !empty($students) && guru_wk_table_exists($conn, 'tbl_nilai')) {
    $qLegacyGrades = @mysqli_query(
        $conn,
        "SELECT no_induk_siswa,
                ROUND(AVG(NULLIF((COALESCE(nilai_tugas,0)+COALESCE(nilai_uh,0)+COALESCE(nilai_us,0))/3,0)),2) AS rata_nilai
         FROM tbl_nilai
         WHERE kelas='{$kelasEsc}' AND tanggal BETWEEN '{$firstDay}' AND '{$lastDay}'
         GROUP BY no_induk_siswa"
    );
    while ($qLegacyGrades && ($row = mysqli_fetch_assoc($qLegacyGrades))) {
        $nis = (string) ($row['no_induk_siswa'] ?? '');
        if (isset($students[$nis]) && $students[$nis]['nilai_final'] === null && $row['rata_nilai'] !== null) {
            $students[$nis]['nilai_final'] = (float) $row['rata_nilai'];
            $gradeDataCount++;
        }
    }
}

$studentRows = [];
$followUpRows = [];
$classGradeValues = [];
foreach ($students as $nis => $student) {
    $students[$nis]['attendance']['pct'] = guru_wk_pct((int) $student['attendance']['H'], (int) $student['attendance']['total']);
    $att = $students[$nis]['attendance'];
    $nilai = $student['nilai_final'];
    $planComplete = trim((string) ($student['rencana_setelah_lulus'] ?? '')) !== ''
        && (trim((string) ($student['bakat_minat'] ?? '')) !== '' || trim((string) ($student['minat_jurusan'] ?? '')) !== '');
    if ($planComplete) {
        $profileComplete++;
    }
    if ($nilai !== null) {
        $classGradeValues[] = (float) $nilai;
    }
    [$level, $reasons, $action] = guru_wk_priority_level($att, $nilai, $planComplete);
    if ($level !== '') {
        $followUpRows[] = [
            'nama_siswa' => (string) $student['nama_siswa'],
            'no_induk' => $nis,
            'level' => $level,
            'reasons' => $reasons,
            'action' => $action,
        ];
    }
    $studentRows[] = $students[$nis];
}
usort($studentRows, fn($a, $b) => strcmp((string) $a['nama_siswa'], (string) $b['nama_siswa']));

$studentCount = count($students);
$classAttendancePct = guru_wk_pct((int) $attendanceMonth['H'], (int) $attendanceMonth['total']);
$classGradeAvg = !empty($classGradeValues) ? round(array_sum($classGradeValues) / count($classGradeValues), 1) : null;
$profileCompletePct = guru_wk_pct($profileComplete, max(1, $studentCount));
$dataSiswaUrl = $hasClass ? 'data-siswa?kelas=' . rawurlencode($kelasFilter) : 'data-siswa';
$analysisCounts = ['kritis' => 0, 'perhatian' => 0, 'waspada' => 0];
foreach ($followUpRows as $row) {
    if (($row['level'] ?? '') === 'Prioritas Tinggi') {
        $analysisCounts['kritis']++;
    } elseif (($row['level'] ?? '') === 'Perlu Dipantau') {
        $analysisCounts['perhatian']++;
    } else {
        $analysisCounts['waspada']++;
    }
}
$analysisPriorityRows = array_slice($followUpRows, 0, 4);
$analysisRecommendationRows = array_slice($followUpRows, 0, 4);

if ($hasClass && isset($_GET['cetak_pdf']) && (string) $_GET['cetak_pdf'] === '1') {
    $autoloadCandidates = [
        __DIR__ . '/../../vendor/autoload.php',
    ];
    $autoloadPath = '';
    foreach ($autoloadCandidates as $candidate) {
        if (file_exists($candidate)) {
            $autoloadPath = $candidate;
            break;
        }
    }
    if ($autoloadPath === '') {
        die('Vendor autoload tidak ditemukan. Jalankan composer install terlebih dahulu.');
    }
    require_once $autoloadPath;
    if (!class_exists('TCPDF')) {
        die('TCPDF tidak tersedia.');
    }

    $lembaga = function_exists('data_lembaga') ? data_lembaga() : [];
    $kepalaSekolah = trim((string) ($lembaga['nmpimpinan'] ?? ''));
    $nipKepala = trim((string) ($lembaga['nippimpinan'] ?? ''));
    $namaSekolah = 'SMA NEGERI 1 SUMBER';
    $alamatSekolah = 'Jl. Raya Sumber - Rembang Km. 2, Kecamatan Sumber, Kabupaten Rembang';
    $websiteSekolah = 'Website: www.sman1sumber.sch.id';
    $tahunAjaranLabel = 'TAHUN AJARAN 2026/2027';
    $logoFile = trim((string) ($lembaga['logo'] ?? ''));
    $logoCandidates = array_filter([
        $logoFile !== '' ? __DIR__ . '/../../img/' . $logoFile : '',
        __DIR__ . '/../../img/logo dash.png',
    ]);
    $logoPath = '';
    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) {
            $logoPath = str_replace('\\', '/', (string) realpath($candidate));
            break;
        }
    }
    $tanggalCetak = date('d/m/Y H:i') . ' WIB';
    $tanggalTtd = function_exists('tgl_indo') ? tgl_indo(date('Y-m-d')) : date('d/m/Y');
    $fmtNumber = static fn($value, int $decimal = 1): string => $value !== null ? number_format((float) $value, $decimal, ',', '.') : '-';
    $safe = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $html = '
    <style>
        body { font-family: helvetica, sans-serif; color:#111827; font-size:9px; background-color:#ffffff; }
        .kop { width:100%; border:0 !important; padding:0; margin:0; border-collapse:collapse; }
        .kop td { border:0 !important; vertical-align:middle; padding:0; }
        .kop-logo { width:17%; text-align:left; }
        .kop-logo img { width:64px; height:auto; }
        .kop-text { width:83%; text-align:center; padding-right:30px; }
        .kop-line-text { font-size:13px; line-height:1.06; margin:0; padding:0; font-weight:bold; letter-spacing:.45px; text-transform:uppercase; color:#111827; }
        .kop h1 { font-size:21px; line-height:1.05; margin:4px 0 2px; padding:0; font-weight:bold; letter-spacing:1.15px; text-transform:uppercase; color:#0f172a; }
        .kop p { font-size:9.4px; line-height:1.16; margin:0; padding:0; color:#475569; }
        .kop-rule { height:5px; border-top:2px solid #0f172a; border-bottom:.55px solid #0f172a; margin:10px 0 14px; }
        .title { text-align:center; margin:0 0 12px; padding:12px 8px; border:.65px solid #cbd5e1; background-color:#eef6ff; border-radius:6px; }
        .title h2 { font-size:15.8px; margin:0 0 3px; padding:0; font-weight:bold; letter-spacing:.75px; text-transform:uppercase; color:#0f172a; }
        .title p { font-size:10px; margin:1px 0; color:#475569; }
        .title .year { font-size:10.8px; color:#111827; letter-spacing:.35px; }
        .summary, .report-table, .doc-info, .ttd { width:100%; border-collapse:collapse; }
        .doc-info { margin:0 0 14px; }
        .doc-info td { border:.55px solid #9fb0c8; padding:7px 9px; font-size:9.4px; line-height:1.25; }
        .doc-info .label { width:16%; background-color:#e2e8f0; font-weight:bold; border-color:#d8e0ec; color:#0f172a; }
        .doc-info .value { width:34%; background-color:#ffffff; }
        .summary { margin:0 0 15px; }
        .summary th { border:.55px solid #94a3b8; padding:7px 4px; font-size:8.9px; line-height:1.18; background-color:#dbeafe; font-weight:bold; text-align:center; color:#0f172a; }
        .summary td { border:.55px solid #94a3b8; padding:8px 4px; font-size:11.2px; line-height:1.18; font-weight:bold; text-align:center; background-color:#ffffff; color:#111827; }
        .report-table { margin:0 0 15px; }
        .report-table th { border:.5px solid #1e3a8a; padding:6px 4px; font-size:8.1px; line-height:1.16; background-color:#1e3a8a; color:#ffffff; font-weight:bold; text-align:center; }
        .report-table td { border:.45px solid #9fb0c8; padding:5.7px 5px; font-size:7.75px; line-height:1.22; color:#111827; }
        .row-alt td { background-color:#f8fafc; }
        .section-title { font-size:10.8px; font-weight:bold; margin:12px 0 5px; padding:7px 9px; background-color:#1e3a8a; color:#ffffff; letter-spacing:.45px; text-transform:uppercase; border-radius:5px; }
        .badge { display:inline-block; padding:3px 7px; border-radius:10px; font-size:7.2px; font-weight:bold; text-align:center; }
        .badge-red { background-color:#fee2e2; color:#991b1b; border:.45px solid #fecaca; }
        .badge-orange { background-color:#ffedd5; color:#9a3412; border:.45px solid #fed7aa; }
        .badge-blue { background-color:#dbeafe; color:#1e3a8a; border:.45px solid #bfdbfe; }
        .center { text-align:center; }
        .small { font-size:7.4px; color:#334155; line-height:1.18; }
        .doc-note { font-size:8px; color:#475569; margin-top:14px; border-top:.45px solid #cbd5e1; padding-top:8px; }
        .ttd { margin-top:25px; }
        .ttd td { width:50%; text-align:center; font-size:9px; vertical-align:top; line-height:1.24; color:#111827; }
        .name-line { height:78px; }
        .bold { font-weight:bold; }
    </style>
    <table class="kop" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td class="kop-logo">' . ($logoPath !== '' ? '<img src="' . $safe($logoPath) . '">' : '') . '</td>
            <td class="kop-text">
                <div class="kop-line-text">PEMERINTAH PROVINSI JAWA TENGAH</div>
                <div class="kop-line-text">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                <h1>' . $safe($namaSekolah) . '</h1>
                <p>' . $safe($alamatSekolah) . '</p>
                <p>' . $safe($websiteSekolah) . '</p>
            </td>
        </tr>
    </table>
    <div class="kop-rule"></div>
    <div class="title">
        <h2>LAPORAN RESMI WALI KELAS</h2>
        <p class="year"><strong>' . $safe($tahunAjaranLabel) . '</strong></p>
        <p>Kelas ' . $safe($kelasFilter) . ' | Periode ' . $safe($selectedPeriodLabel) . '</p>
    </div>
    <table class="doc-info">
        <tr>
            <td class="label">Kelas</td><td class="value">' . $safe($kelasFilter) . '</td>
            <td class="label">Periode</td><td class="value">' . $safe($selectedPeriodLabel) . '</td>
        </tr>
        <tr>
            <td class="label">Wali Kelas</td><td class="value">' . $safe($namaGuru) . '</td>
            <td class="label">Tanggal Cetak</td><td class="value">' . $safe($tanggalCetak) . '</td>
        </tr>
    </table>
    <div class="section-title">Ringkasan Kelas</div>
    <table class="summary">
        <tr>
            <th>Siswa Aktif</th>
            <th>Kehadiran</th>
            <th>Rata-rata Nilai</th>
            <th>Perlu Tindak Lanjut</th>
            <th>Profil Lengkap</th>
        </tr>
        <tr>
            <td>' . (int) $studentCount . '</td>
            <td>' . (int) $classAttendancePct . '% (' . (int) $attendanceMonth['H'] . '/' . (int) $attendanceMonth['total'] . ')</td>
            <td>' . $safe($fmtNumber($classGradeAvg)) . '</td>
            <td>' . count($followUpRows) . '</td>
            <td>' . (int) $profileCompletePct . '%</td>
        </tr>
    </table>';

    $html .= '<div class="section-title">Rekap Kehadiran dan Nilai Siswa</div>
    <table class="report-table">
        <tr>
            <th width="5%">No</th>
            <th width="30%">Nama Siswa</th>
            <th width="6%">H</th>
            <th width="6%">S</th>
            <th width="6%">I</th>
            <th width="6%">A</th>
            <th width="6%">T</th>
            <th width="10%">Hadir %</th>
            <th width="10%">Nilai</th>
            <th width="15%">Rencana Setelah Lulus</th>
        </tr>';
    foreach ($studentRows as $index => $row) {
        $att = $row['attendance'];
        $rowClass = $index % 2 === 1 ? ' class="row-alt"' : '';
        $html .= '<tr' . $rowClass . '>
            <td class="center">' . ($index + 1) . '</td>
            <td>' . $safe($row['nama_siswa']) . '</td>
            <td class="center">' . (int) $att['H'] . '</td>
            <td class="center">' . (int) $att['S'] . '</td>
            <td class="center">' . (int) $att['I'] . '</td>
            <td class="center">' . (int) $att['A'] . '</td>
            <td class="center">' . (int) $att['T'] . '</td>
            <td class="center">' . (int) $att['pct'] . '%</td>
            <td class="center">' . $safe($fmtNumber($row['nilai_final'] ?? null)) . '</td>
            <td>' . $safe($row['rencana_setelah_lulus'] ?: '-') . '</td>
        </tr>';
    }
    $html .= '</table>';

    $html .= '<div class="section-title">Prioritas Tindak Lanjut</div>';
    if (empty($followUpRows)) {
        $html .= '<p class="small">Tidak ada siswa berisiko pada periode ini.</p>';
    } else {
        $html .= '<table class="report-table">
            <tr>
                <th width="5%">No</th>
                <th width="24%">Nama Siswa</th>
                <th width="16%">Level Prioritas</th>
                <th width="27%">Alasan</th>
                <th width="28%">Rekomendasi</th>
            </tr>';
        foreach (array_slice($followUpRows, 0, 12) as $index => $row) {
            $rowClass = $index % 2 === 1 ? ' class="row-alt"' : '';
            $badgeClass = $row['level'] === 'Prioritas Tinggi' ? 'badge-red' : ($row['level'] === 'Perlu Dipantau' ? 'badge-orange' : 'badge-blue');
            $html .= '<tr' . $rowClass . '>
                <td class="center">' . ($index + 1) . '</td>
                <td>' . $safe($row['nama_siswa']) . '<br><span class="small">NIS ' . $safe($row['no_induk']) . '</span></td>
                <td class="center"><span class="badge ' . $badgeClass . '">' . $safe($row['level']) . '</span></td>
                <td>' . $safe(implode('; ', $row['reasons'])) . '</td>
                <td>' . $safe($row['action']) . '</td>
            </tr>';
        }
        $html .= '</table>';
    }

    $html .= '
    <div class="doc-note">Dokumen dicetak melalui SIMANIS pada ' . $safe($tanggalCetak) . '.</div>
    <table class="ttd">
        <tr>
            <td>
                Mengetahui,<br>Kepala Sekolah
                <div class="name-line"></div>
                <span class="bold">' . $safe($kepalaSekolah ?: '........................') . '</span><br>
                NIP. ' . $safe($nipKepala ?: '-') . '
            </td>
            <td>
                ' . $safe($tanggalTtd) . '<br>Wali Kelas ' . $safe($kelasFilter) . '
                <div class="name-line"></div>
                <span class="bold">' . $safe($namaGuru) . '</span><br>
                NIP. ' . $safe($nipGuru ?: '-') . '
            </td>
        </tr>
    </table>';

    $pdf = new TCPDF('P', 'mm', [210, 330], true, 'UTF-8', false);
    $pdf->SetCreator('SIMANIS');
    $pdf->SetAuthor($namaGuru);
    $pdf->SetTitle('Laporan Wali Kelas ' . $kelasFilter);
    $pdf->SetSubject('Laporan Wali Kelas');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 16);
    $pdf->SetFooterMargin(8);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    $fileName = 'laporan-walikelas-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $kelasFilter) . '-' . date('Ymd') . '.pdf';
    $pdf->Output($fileName, 'I');
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Walikelas - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background:#f6f8fc; color:#0f172a; font-family:Arial, sans-serif; }
        .page-shell { max-width:1100px; margin:0 auto; padding:24px; }
        .hero { background:linear-gradient(135deg,#0f172a,#1e3a8a); color:#fff; border-radius:22px; padding:24px; margin-bottom:16px; }
        .panel { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 14px 36px rgba(15,23,42,.08); }
        .panel-pad { padding:18px; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:16px 0; }
        .metric-card { padding:16px; border-radius:16px; background:#fff; border:1px solid #e2e8f0; }
        .metric-label { color:#64748b; margin:0; font-size:.85rem; }
        .metric-value { font-size:1.8rem; font-weight:700; margin:4px 0 0; }
        .table { font-size:.88rem; }
        .analysis-toolbar { display:flex; justify-content:flex-end; gap:10px; margin:0 0 14px; }
        .analysis-report { background:#fff; border:1px solid #dbe7f7; border-radius:18px; padding:22px; box-shadow:0 18px 45px rgba(15,23,42,.07); }
        .analysis-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:18px; }
        .analysis-title h2 { font-size:1.8rem; line-height:1.05; margin:0; font-weight:900; color:#0f172a; letter-spacing:-.03em; }
        .analysis-title p { margin:4px 0 0; font-size:1.05rem; color:#2563eb; font-weight:800; }
        .analysis-logo { width:58px; height:58px; border:1px solid #bfdbfe; border-radius:12px; display:grid; place-items:center; color:#2563eb; background:#f8fbff; box-shadow:0 8px 18px rgba(37,99,235,.12); font-size:30px; }
        .analysis-stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-bottom:16px; }
        .analysis-stat { min-height:88px; border:1px solid #e2e8f0; border-radius:14px; display:flex; align-items:center; gap:18px; padding:14px 18px; box-shadow:0 10px 24px rgba(15,23,42,.06); background:#fff; position:relative; overflow:hidden; }
        .analysis-stat::after { content:''; position:absolute; right:54px; top:18px; bottom:18px; width:1px; background:#e5e7eb; }
        .analysis-stat .stat-icon { width:58px; height:58px; border-radius:50%; display:grid; place-items:center; font-size:28px; }
        .analysis-stat .stat-number { font-size:2rem; line-height:1; font-weight:900; margin:0 0 5px; }
        .analysis-stat .stat-label { margin:0; font-weight:800; color:#0f172a; }
        .analysis-stat .stat-arrow { margin-left:auto; color:#2563eb; font-size:25px; }
        .tone-red .stat-icon { background:#fee2e2; color:#ef4444; }
        .tone-red .stat-number { color:#ef4444; }
        .tone-orange .stat-icon { background:#ffedd5; color:#f97316; }
        .tone-orange .stat-number { color:#f97316; }
        .tone-yellow .stat-icon { background:#fef3c7; color:#eab308; }
        .tone-yellow .stat-number { color:#eab308; }
        .analysis-columns { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .analysis-panel { border:1px solid #e2e8f0; border-radius:14px; padding:16px; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.045); }
        .analysis-panel-title { display:flex; align-items:center; gap:10px; margin:0 0 12px; color:#0f172a; font-size:1.05rem; font-weight:900; }
        .analysis-panel-title i { color:#2563eb; font-size:24px; }
        .priority-list, .recommend-list { display:flex; flex-direction:column; gap:8px; }
        .priority-row, .recommend-row { display:grid; grid-template-columns:36px 68px 1fr 118px; gap:12px; align-items:center; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; background:#fff; }
        .row-rank { width:28px; height:28px; border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:900; background:#2563eb; }
        .priority-avatar, .recommend-icon { width:56px; height:56px; border-radius:50%; display:grid; place-items:center; font-size:28px; }
        .priority-copy strong, .recommend-copy strong { display:block; color:#0f172a; font-size:.98rem; line-height:1.2; }
        .priority-copy .risk { display:block; color:#f97316; font-weight:900; font-size:.82rem; margin-top:2px; }
        .priority-copy p, .recommend-copy p { margin:3px 0 0; color:#334155; font-size:.82rem; line-height:1.25; }
        .priority-badge { border:1px solid currentColor; border-radius:999px; padding:6px 14px; text-align:center; font-size:.76rem; font-weight:900; background:#fff; }
        .badge-kritis { color:#ef4444; }
        .badge-perhatian { color:#f97316; }
        .badge-waspada { color:#eab308; }
        .recommend-row { grid-template-columns:36px 68px 1fr 178px; border-color:#bfdbfe; background:#f8fbff; }
        .recommend-goal { border:1px solid #bfdbfe; border-radius:8px; padding:8px 10px; color:#0f172a; font-size:.78rem; line-height:1.18; background:#fff; }
        .recommend-goal strong { color:#2563eb; display:block; margin-bottom:2px; }
        .analysis-quote { margin-top:18px; border:1px solid #bfdbfe; border-radius:12px; padding:18px 22px; display:grid; grid-template-columns:90px 1fr 126px; align-items:center; gap:18px; background:#f8fbff; color:#1e3a8a; }
        .quote-mark { width:68px; height:68px; border-radius:50%; display:grid; place-items:center; background:#eaf2ff; color:#2563eb; font-size:42px; }
        .quote-text { font-weight:700; line-height:1.55; margin:0; }
        .quote-art { color:#2563eb; font-size:66px; text-align:center; }
        .analysis-empty { border:1px dashed #cbd5e1; border-radius:12px; padding:18px; color:#64748b; text-align:center; background:#f8fafc; }
        .ai-extra-result { margin-top:16px; border-top:1px solid #dbeafe; padding-top:14px; }
        @media (max-width: 767px) { .page-shell { padding:14px; } .metric-grid { grid-template-columns:1fr; } }
        @media (max-width: 991px) {
            .analysis-stat-grid, .analysis-columns { grid-template-columns:1fr; }
            .priority-row, .recommend-row { grid-template-columns:32px 52px 1fr; }
            .priority-badge, .recommend-goal { grid-column:3; }
            .analysis-quote { grid-template-columns:1fr; text-align:center; }
            .quote-mark { margin:auto; }
        }
        @media print {
            body { background:#fff !important; }
            body * { visibility:hidden; }
            #analysisPrintArea, #analysisPrintArea * { visibility:visible; }
            #analysisPrintArea { position:absolute; left:0; top:0; width:100%; box-shadow:none; border:0; padding:12mm; }
            .no-print, .analysis-toolbar, .ai-extra-result { display:none !important; }
            .analysis-report { box-shadow:none; border:0; }
            .analysis-stat, .analysis-panel, .priority-row, .recommend-row, .analysis-quote { break-inside:avoid; box-shadow:none; }
            @page { size: A4 landscape; margin:8mm; }
        }
    </style>
</head>
<body>
<main class="page-shell">
    <section class="hero">
        <a href="guru_legacy" class="text-white-50 text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <h1 class="mt-3 mb-2">Ruang Analisis Walikelas</h1>
        <p class="mb-0 text-white-50">Pantau kehadiran, nilai, rencana siswa, dan cetak laporan resmi wali kelas.</p>
    </section>

    <section class="panel panel-pad">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="kelas">Kelas Wali</label>
                <select class="form-select" id="kelas" name="kelas" required>
                    <option value="">Pilih kelas wali</option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= guru_wk_h($kelas); ?>" <?= $kelasFilter === $kelas ? 'selected' : ''; ?>>
                            <?= guru_wk_h($kelas); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="periode_mulai">Bulan Awal</label>
                <input class="form-control" type="month" id="periode_mulai" name="periode_mulai" value="<?= guru_wk_h($periodeMulai); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="periode_selesai">Bulan Akhir</label>
                <input class="form-control" type="month" id="periode_selesai" name="periode_selesai" value="<?= guru_wk_h($periodeSelesai); ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Tampilkan</button>
            </div>
            <?php if ($hasClass): ?>
                <div class="col-md-2 d-grid">
                    <a class="btn btn-success" target="_blank" href="walikelas?<?= guru_wk_h(http_build_query([
                        'kelas' => $kelasFilter,
                        'periode_mulai' => $periodeMulai,
                        'periode_selesai' => $periodeSelesai,
                        'cetak_pdf' => '1',
                    ])); ?>">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF F4
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <?php if (empty($kelasOptions)): ?>
        <section class="panel panel-pad mt-3 text-center text-muted">Akun <?= guru_wk_h($namaGuru); ?> belum terhubung sebagai wali kelas.</section>
    <?php elseif (!$hasClass): ?>
        <section class="panel panel-pad mt-3 text-center text-muted">Pilih kelas terlebih dahulu untuk melihat ringkasan dan mencetak laporan.</section>
    <?php else: ?>
        <section class="metric-grid">
            <article class="metric-card"><p class="metric-label">Siswa Aktif</p><div class="metric-value"><?= $studentCount; ?></div></article>
            <article class="metric-card"><p class="metric-label">Kehadiran</p><div class="metric-value"><?= $classAttendancePct; ?>%</div></article>
            <article class="metric-card"><p class="metric-label">Rata-rata Nilai</p><div class="metric-value"><?= $classGradeAvg !== null ? guru_wk_h(number_format($classGradeAvg, 1, ',', '.')) : '-'; ?></div></article>
            <article class="metric-card"><p class="metric-label">Tindak Lanjut</p><div class="metric-value"><?= count($followUpRows); ?></div></article>
        </section>

        <!-- Tabs Nav -->
        <ul class="nav nav-pills mb-3 mt-4" id="waliTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold rounded-pill px-4 me-2 shadow-sm" id="monitoring-tab" data-bs-toggle="pill" data-bs-target="#monitoring" type="button" role="tab" aria-controls="monitoring" aria-selected="true"><i class="bi bi-activity"></i> Monitoring Siswa</button>
          </li>
                    
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-pill px-4 me-2 shadow-sm" id="validasi-tab" data-bs-toggle="pill" data-bs-target="#validasi" type="button" role="tab" aria-controls="validasi" aria-selected="false"><i class="bi bi-patch-check"></i> Validasi Izin</button>
          </li>
<li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-pill px-4 me-2 shadow-sm" id="jurnal-tab" data-bs-toggle="pill" data-bs-target="#jurnal" type="button" role="tab" aria-controls="jurnal" aria-selected="false"><i class="bi bi-journal-text"></i> Jurnal Pendampingan</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-pill px-4 shadow-sm" style="background-image: linear-gradient(135deg, #a855f7, #6366f1); color: #fff; border:none;" id="ai-tab" data-bs-toggle="pill" data-bs-target="#ai" type="button" role="tab" aria-controls="ai" aria-selected="false"><i class="bi bi-robot"></i> Analisis AI</button>
          </li>
        </ul>

        <div class="tab-content" id="waliTabsContent">
          <!-- Tab Validasi Izin -->
          <div class="tab-pane fade" id="validasi" role="tabpanel" aria-labelledby="validasi-tab">
            <section class="panel">
                <div class="panel-pad border-bottom">
                    <h2 class="h5 mb-1">Daftar Pengajuan Izin Menunggu Persetujuan</h2>
                    <p class="text-muted mb-0">Kelas <?= guru_wk_h($kelasFilter); ?></p>
                </div>
                <div class="panel-pad">
                    <?php if(isset($msg_validasi)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($msg_validasi) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if(empty($list_izin)): ?>
                    <div class="alert alert-info">Tidak ada pengajuan izin yang menunggu validasi Anda untuk kelas ini.</div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach($list_izin as $izin): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title fw-bold text-primary mb-0"><?= htmlspecialchars($izin['nama_siswa']) ?></h5>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($izin['kelas_siswa']) ?></span>
                                    </div>
                                    <p class="text-muted small mb-3"><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($izin['waktu_pengajuan'])) ?></p>
                                    
                                    <ul class="list-unstyled mb-3">
                                        <li><strong>Kategori:</strong> <?= htmlspecialchars($izin['kategori_pengajuan']) ?></li>
                                        <li><strong>Jenis:</strong> <?= htmlspecialchars($izin['jenis_izin']) ?></li>
                                        <li><strong>Keterangan:</strong> <?= htmlspecialchars($izin['detail_izin']) ?></li>
                                        <?php if ($izin['kategori_pengajuan'] === 'Keluar Sekolah'): ?>
                                        <li><strong>Opsi Kembali:</strong> <?= htmlspecialchars($izin['opsi_kembali'] ?: '-') ?></li>
                                        <?php endif; ?>
                                        <?php if (!empty($izin['validasi_guru_bk']) && $izin['validasi_guru_bk'] !== 'Menunggu'): ?>
                                        <li><strong>Status BK:</strong> <span class="badge <?= $izin['validasi_guru_bk'] === 'Disetujui' ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($izin['validasi_guru_bk']) ?></span> 
                                            <small class="text-muted">(<?= htmlspecialchars($izin['validator_guru_bk'] ?: 'Sistem') ?>)</small>
                                        </li>
                                        <?php endif; ?>
                                    </ul>

                                    <?php if (!empty($izin['foto_selfie'])): ?>
                                    <div class="mb-3">
                                        <img src="../../uploads/izin/<?= htmlspecialchars($izin['foto_selfie']) ?>" class="img-fluid rounded" alt="Bukti Foto" style="max-height:150px; object-fit:cover;">
                                    </div>
                                    <?php endif; ?>
                                    
                                </div>
                                <div class="card-footer bg-white border-top-0 d-flex gap-2">
                                    <form method="POST" class="w-50">
                                        <input type="hidden" name="id_izin" value="<?= $izin['id_izin'] ?>">
                                        <input type="hidden" name="action" value="acc_wali">
                                        <button type="submit" class="btn btn-success w-100 fw-bold" onclick="return confirm('Setujui izin ini?')"><i class="bi bi-check"></i> Setujui</button>
                                    </form>
                                    <form method="POST" class="w-50">
                                        <input type="hidden" name="id_izin" value="<?= $izin['id_izin'] ?>">
                                        <input type="hidden" name="action" value="tolak_wali">
                                        <button type="submit" class="btn btn-danger w-100 fw-bold" onclick="return confirm('Tolak izin ini?')"><i class="bi bi-x"></i> Tolak</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
          </div>

          <!-- Tab Monitoring -->
                    <div class="tab-pane fade show active" id="monitoring" role="tabpanel" aria-labelledby="monitoring-tab">
            <section class="panel">
            <div class="panel-pad border-bottom">
                <h2 class="h5 mb-1">Rekap Kehadiran dan Nilai Siswa</h2>
                <p class="text-muted mb-0">Kelas <?= guru_wk_h($kelasFilter); ?> | Periode <?= guru_wk_h($selectedPeriodLabel); ?></p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">H</th>
                            <th class="text-center">S</th>
                            <th class="text-center">I</th>
                            <th class="text-center">A</th>
                            <th class="text-center">T</th>
                            <th class="text-center">Hadir %</th>
                            <th class="text-center">Nilai</th>
                            <th>Rencana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentRows as $index => $row): $att = $row['attendance']; ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= guru_wk_h($row['nama_siswa']); ?><div class="small text-muted"><?= guru_wk_h($row['no_induk']); ?></div></td>
                                <td class="text-center"><?= (int) $att['H']; ?></td>
                                <td class="text-center"><?= (int) $att['S']; ?></td>
                                <td class="text-center"><?= (int) $att['I']; ?></td>
                                <td class="text-center"><?= (int) $att['A']; ?></td>
                                <td class="text-center"><?= (int) $att['T']; ?></td>
                                <td class="text-center"><?= (int) $att['pct']; ?>%</td>
                                <td class="text-center"><?= $row['nilai_final'] !== null ? guru_wk_h(number_format((float) $row['nilai_final'], 1, ',', '.')) : '-'; ?></td>
                                <td><?= guru_wk_h($row['rencana_setelah_lulus'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
      </div>

      <!-- Tab Jurnal Pendampingan -->
      <div class="tab-pane fade" id="jurnal" role="tabpanel" aria-labelledby="jurnal-tab">
        <section class="panel panel-pad">
            <h2 class="h5 mb-3">Jurnal Pendampingan Siswa</h2>
            <form id="formJurnal" class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="kelas" value="<?= guru_wk_h($kelasFilter); ?>">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Pilih Siswa</label>
                    <select name="nis" class="form-select" required>
                        <option value="">Pilih Siswa</option>
                        <?php foreach ($studentRows as $row): ?>
                            <option value="<?= guru_wk_h($row['no_induk']); ?>"><?= guru_wk_h($row['nama_siswa']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Progres</label>
                    <select name="status" class="form-select">
                        <option value="Belum Selesai">Belum Selesai</option>
                        <option value="Berjalan">Berjalan</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan / Permasalahan</label>
                    <textarea name="catatan" class="form-control" rows="2" required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Tindak Lanjut & Rekomendasi</label>
                    <textarea name="tindak_lanjut" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Jurnal</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableJurnal">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Catatan</th>
                            <th>Tindak Lanjut</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
      </div>

      <!-- Tab Analisis AI -->
      <div class="tab-pane fade" id="ai" role="tabpanel" aria-labelledby="ai-tab">
        <section>
            <div class="analysis-toolbar no-print">
                <button type="button" class="btn btn-outline-primary rounded-pill px-4" onclick="analyzeDataWithAI()"><i class="bi bi-robot me-1"></i> Analisis AI</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="printAnalysisReport()"><i class="bi bi-printer me-1"></i> Cetak</button>
            </div>

            <div class="analysis-report" id="analysisPrintArea">
                <div class="analysis-head">
                    <div class="analysis-title">
                        <h2>Hasil Analisis &amp; Prioritas Pendampingan</h2>
                        <p>Kelas <?= guru_wk_h($kelasFilter); ?></p>
                    </div>
                    <div class="analysis-logo"><i class="bi bi-mortarboard-fill"></i></div>
                </div>

                <div class="analysis-stat-grid">
                    <article class="analysis-stat tone-red">
                        <div class="stat-icon"><i class="bi bi-person"></i></div>
                        <div>
                            <div class="stat-number"><?= (int) $analysisCounts['kritis']; ?></div>
                            <p class="stat-label">siswa kritis</p>
                        </div>
                        <i class="bi bi-chevron-right stat-arrow"></i>
                    </article>
                    <article class="analysis-stat tone-orange">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="stat-number"><?= (int) $analysisCounts['perhatian']; ?></div>
                            <p class="stat-label">siswa perlu perhatian</p>
                        </div>
                        <i class="bi bi-chevron-right stat-arrow"></i>
                    </article>
                    <article class="analysis-stat tone-yellow">
                        <div class="stat-icon"><i class="bi bi-eye"></i></div>
                        <div>
                            <div class="stat-number"><?= (int) $analysisCounts['waspada']; ?></div>
                            <p class="stat-label">siswa kategori waspada</p>
                        </div>
                        <i class="bi bi-chevron-right stat-arrow"></i>
                    </article>
                </div>

                <div class="analysis-columns">
                    <section class="analysis-panel">
                        <h3 class="analysis-panel-title"><i class="bi bi-card-list"></i> Daftar Prioritas Siswa</h3>
                        <?php if (empty($analysisPriorityRows)): ?>
                            <div class="analysis-empty">Belum ada siswa prioritas pada periode ini.</div>
                        <?php else: ?>
                            <div class="priority-list">
                                <?php foreach ($analysisPriorityRows as $index => $row): ?>
                                    <?php
                                        $level = (string)($row['level'] ?? '');
                                        $badgeClass = $level === 'Prioritas Tinggi' ? 'badge-kritis' : ($level === 'Perlu Dipantau' ? 'badge-perhatian' : 'badge-waspada');
                                        $badgeText = $level === 'Prioritas Tinggi' ? 'Kritis' : ($level === 'Perlu Dipantau' ? 'Perlu Perhatian' : 'Waspada');
                                        $toneClass = $level === 'Prioritas Tinggi' ? 'tone-red' : ($level === 'Perlu Dipantau' ? 'tone-orange' : 'tone-yellow');
                                        $studentName = (string)($row['nama_siswa'] ?? 'Siswa');
                                        $shortReason = !empty($row['reasons']) ? implode(', ', array_slice((array)$row['reasons'], 0, 2)) : 'Perlu pemantauan lanjutan.';
                                    ?>
                                    <article class="priority-row <?= $toneClass; ?>">
                                        <div class="row-rank" style="background:<?= $level === 'Prioritas Tinggi' ? '#ef4444' : ($level === 'Perlu Dipantau' ? '#f97316' : '#eab308'); ?>;"><?= $index + 1; ?></div>
                                        <div class="priority-avatar stat-icon"><i class="bi <?= $level === 'Prioritas Tinggi' ? 'bi-person' : ($level === 'Perlu Dipantau' ? 'bi-people' : 'bi-bar-chart'); ?>"></i></div>
                                        <div class="priority-copy">
                                            <strong><?= guru_wk_h($studentName); ?></strong>
                                            <span class="risk">Hadir <?= (int)($students[$row['no_induk']]['attendance']['pct'] ?? 0); ?>%</span>
                                            <p><?= guru_wk_h($shortReason); ?></p>
                                        </div>
                                        <div class="priority-badge <?= $badgeClass; ?>"><?= guru_wk_h($badgeText); ?></div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="analysis-panel">
                        <h3 class="analysis-panel-title"><i class="bi bi-bullseye"></i> Rekomendasi Pendampingan</h3>
                        <?php if (empty($analysisRecommendationRows)): ?>
                            <div class="analysis-empty">Tidak ada rekomendasi khusus. Pertahankan monitoring rutin kelas.</div>
                        <?php else: ?>
                            <div class="recommend-list">
                                <?php foreach ($analysisRecommendationRows as $index => $row): ?>
                                    <?php
                                        $level = (string)($row['level'] ?? '');
                                        $icon = $level === 'Prioritas Tinggi' ? 'bi-house-door' : ($level === 'Perlu Dipantau' ? 'bi-people' : 'bi-star');
                                        $firstName = preg_split('/\s+/', trim((string)($row['nama_siswa'] ?? 'Siswa'))) ?: ['Siswa'];
                                    ?>
                                    <article class="recommend-row">
                                        <div class="row-rank"><?= $index + 1; ?></div>
                                        <div class="recommend-icon" style="background:#eaf2ff; color:#2563eb;"><i class="bi <?= $icon; ?>"></i></div>
                                        <div class="recommend-copy">
                                            <strong><?= guru_wk_h($index === 0 ? ($firstName[0] ?? 'Siswa') : ($row['nama_siswa'] ?? 'Siswa')); ?></strong>
                                            <p><?= guru_wk_h($row['action'] ?? 'Lakukan pendampingan wali kelas secara bertahap.'); ?></p>
                                        </div>
                                        <div class="recommend-goal">
                                            <strong>Tujuan</strong>
                                            <?= guru_wk_h($level === 'Prioritas Tinggi' ? 'Identifikasi akar masalah dan kontrak komitmen kehadiran.' : ($level === 'Perlu Dipantau' ? 'Peringatan dini dan motivasi sebelum berdampak pada nilai.' : 'Pemetaan minat, bakat, dan rencana studi lanjut.')); ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="analysis-quote">
                    <div class="quote-mark"><i class="bi bi-quote"></i></div>
                    <p class="quote-text">
                        Masa depan tidak dibangun dalam satu malam, melainkan melalui kehadiran dan konsistensi yang kalian bentuk setiap pagi di kelas ini.
                        Setiap hari kalian hadir, kalian sedang membuka satu pintu kesempatan baru. Mari kita mulai melangkah bersama, saling mendukung, dan pastikan tidak ada satu pun teman kita yang tertinggal di belakang!
                    </p>
                    <div class="quote-art"><i class="bi bi-book-half"></i></div>
                </div>

                <div id="ai-result-container" class="ai-extra-result d-none">
                    <div class="d-flex align-items-center mb-2" style="color:#2563eb;">
                        <i class="bi bi-stars fs-4 me-2"></i>
                        <h5 class="mb-0 fw-bold">Catatan Tambahan AI</h5>
                    </div>
                    <div id="ai-result-content" style="line-height:1.7; color:#334155; font-size:0.95rem;"></div>
                </div>
            </div>

            <div id="ai-loading" class="text-center py-4 d-none">
                <div class="spinner-border" style="color: #a855f7;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted fw-semibold">AI sedang menganalisis data kelas Anda, mohon tunggu...</p>
            </div>
        </section>
      </div>

    </div> <!-- End Tab Content -->
    <?php endif; ?>
</main>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script type="module">
import { GoogleGenAI } from "https://esm.sh/@google/genai";

const geminiApiKey = <?= json_encode($geminiApiKey); ?>;
const studentData = <?= json_encode($studentRows); ?>;
const kelasWali = "<?= guru_wk_h($kelasFilter); ?>";

function loadJurnal() {
    $.get('ajax_jurnal_pendampingan.php?action=get&kelas=' + encodeURIComponent(kelasWali), function(res) {
        if(res.status === 'success') {
            let html = '';
            if(res.data.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada jurnal pendampingan</td></tr>';
            } else {
                res.data.forEach(item => {
                    let badge = item.status === 'Selesai' ? 'bg-success' : (item.status === 'Berjalan' ? 'bg-warning' : 'bg-secondary');
                    html += `<tr>
                        <td class="text-nowrap">${item.tanggal}</td>
                        <td class="fw-semibold">${item.nama_siswa}</td>
                        <td>${item.catatan}</td>
                        <td>${item.tindak_lanjut}</td>
                        <td><span class="badge ${badge}">${item.status}</span></td>
                    </tr>`;
                });
            }
            $('#tableJurnal tbody').html(html);
        }
    });
}

$('#formJurnal').on('submit', function(e) {
    e.preventDefault();
    $.post('ajax_jurnal_pendampingan.php', $(this).serialize(), function(res) {
        if(res.status === 'success') {
            alert('Jurnal berhasil disimpan!');
            $('#formJurnal textarea').val('');
            loadJurnal();
        } else {
            alert('Error: ' + res.message);
        }
    }, 'json');
});

$(document).ready(function() {
    if(kelasWali !== '') loadJurnal();
});

async function analyzeDataWithAI() {
    const key = geminiApiKey;
    if(!key) {
        alert('API Key Gemini belum diatur di halaman pengaturan admin.');
        return;
    }

    if(studentData.length === 0) {
        alert('Data siswa kosong, tidak ada yang bisa dianalisis.');
        return;
    }

    $('#ai-result-container').addClass('d-none');
    $('#ai-loading').removeClass('d-none');

    // Buat ringkasan data untuk AI
    let summaryData = studentData.map(s => {
        return `- ${s.nama_siswa} (Hadir: ${s.attendance.pct}%, Nilai: ${s.nilai_final || 'Belum ada'}, Rencana Lulus: ${s.rencana_setelah_lulus || '-'}, Minat Jurusan: ${s.minat_jurusan || '-'})`;
    }).join('\n');

    const promptText = `Anda adalah seorang ahli bimbingan konseling dan pendidikan profesional.
Berikut adalah data ringkasan profil akademik dan kehadiran siswa kelas ${kelasWali}:
${summaryData}

Tolong berikan Analisis Prioritas Pendampingan:
1. Identifikasi 3-5 siswa yang paling membutuhkan pendampingan segera (prioritas tinggi) berdasarkan data di atas (misal kehadiran rendah, nilai kurang, atau kebingungan rencana lulus). Jelaskan alasannya singkat.
2. Berikan rekomendasi langkah spesifik (Jurnal Pendampingan) yang praktis dan dapat langsung dilakukan oleh Wali Kelas untuk siswa-siswa tersebut.
3. Berikan saran motivasi singkat untuk keseluruhan kelas.
Gunakan format Markdown (huruf tebal, daftar/bullet) dengan bahasa yang profesional, menyemangati, dan solutif. Tidak perlu basa basi panjang.`;

    try {
        const ai = new GoogleGenAI({ apiKey: key });
        const response = await ai.models.generateContent({
            model: "gemini-3-flash-preview",
            contents: promptText
        });

        const aiResponse = response.text;
        $('#ai-result-content').html(marked.parse(aiResponse));
        $('#ai-result-container').removeClass('d-none');
    } catch(err) {
        alert('Gagal mengambil analisis AI: ' + err.message);
    } finally {
        $('#ai-loading').addClass('d-none');
    }
}

// Expose event handler to global scope
window.analyzeDataWithAI = analyzeDataWithAI;
window.printAnalysisReport = function() {
    window.print();
};
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
