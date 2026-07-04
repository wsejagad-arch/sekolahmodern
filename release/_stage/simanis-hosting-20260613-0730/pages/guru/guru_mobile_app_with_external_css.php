<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk'])) {
    header('location: ../../index.php?haruslogin');
    exit;
}
if ((int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    echo "<script>window.location='404.html';</script>";
    exit;
}

include '../../koneksi.php';
include '../../functions.php';
date_default_timezone_set('Asia/Jakarta');

$nipguru = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipguru);
$tglskr = date('Y-m-d');
$hariini = ubah_nama_hari($tglskr);
$weekStart = date('Y-m-d', strtotime('-6 days'));

$lembaga = data_lembaga();
$sqlguru = @mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipEsc' LIMIT 1");
$dataguru = $sqlguru ? (mysqli_fetch_assoc($sqlguru) ?: []) : [];

function guru_route(string $page): string
{
    $safe = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
    return php_sapi_name() === 'cli-server' ? ($safe . '.php') : $safe;
}

function table_exists(mysqli $conn, string $table): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '$tableEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function pct(int $num, int $den): int
{
    if ($den <= 0) {
        return 0;
    }
    return (int)round(($num / $den) * 100);
}

function format_id_date(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    return date('d M Y', $ts);
}

function greeting_by_hour(int $hour): string
{
    if ($hour < 11) {
        return 'Selamat pagi,';
    }
    if ($hour < 15) {
        return 'Selamat siang,';
    }
    if ($hour < 18) {
        return 'Selamat sore,';
    }
    return 'Selamat malam,';
}

function guru_mobile_status_code(string $status): string
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

function guru_mobile_att_priority(string $code): int
{
    $priority = ['A' => 6, 'S' => 5, 'I' => 4, 'D' => 3, 'T' => 2, 'H' => 1];
    return $priority[$code] ?? 1;
}

$kelasAmpu = [];
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND kelas <> '' ORDER BY kelas ASC");
if ($qKelas) {
    while ($row = mysqli_fetch_assoc($qKelas)) {
        $kelasAmpu[] = $row['kelas'];
    }
}
$totalKelasAmpu = count($kelasAmpu);

$kelasIn = '';
if ($totalKelasAmpu > 0) {
    $kelasIn = "'" . implode("','", array_map(static function ($k) use ($conn) {
        return mysqli_real_escape_string($conn, (string)$k);
    }, $kelasAmpu)) . "'";
}

$totalSiswa = 0;
if ($kelasIn !== '') {
    $qSiswa = @mysqli_query(
        $conn,
        "SELECT COUNT(DISTINCT no_induk) AS total
         FROM tbl_siswa
         WHERE kelas IN ($kelasIn)
           AND (status IS NULL OR status='' OR UPPER(status)='AKTIF')"
    );
    if ($qSiswa) {
        $row = mysqli_fetch_assoc($qSiswa);
        $totalSiswa = (int)($row['total'] ?? 0);
    }
}

$jadwalHariIni = [];
$qJ = @mysqli_query(
    $conn,
    "SELECT m.id_mapel, m.kelas, m.nama_mapel, m.jam_mulai, m.jam_selesai
     FROM tbl_mapel_ampu m
     WHERE m.no_induk='$nipEsc' AND m.hari='$hariini'
     ORDER BY m.jam_mulai ASC"
);
if ($qJ) {
    while ($row = mysqli_fetch_assoc($qJ)) {
        $jadwalHariIni[] = $row;
    }
}
$totalJadwalHari = count($jadwalHariIni);

$mapelIds = array_map(static function ($j) {
    return (int)($j['id_mapel'] ?? 0);
}, $jadwalHariIni);
$mapelIds = array_values(array_filter($mapelIds, static function ($v) {
    return $v > 0;
}));

$jurnalTerisi = 0;
$jurnalTerisiIds = [];
if (count($mapelIds) > 0) {
    $idsCsv = implode(',', $mapelIds);
    $qTerisi = @mysqli_query(
        $conn,
        "SELECT DISTINCT id_mapel
         FROM tbl_materi
         WHERE tanggal='$tglskr' AND id_mapel IN ($idsCsv)"
    );
    if ($qTerisi) {
        while ($row = mysqli_fetch_assoc($qTerisi)) {
            $idTerisi = (int)($row['id_mapel'] ?? 0);
            if ($idTerisi > 0) {
                $jurnalTerisiIds[$idTerisi] = true;
            }
        }
    }
}
$jurnalTerisi = count($jurnalTerisiIds);
$jurnalBelum = max(0, $totalJadwalHari - $jurnalTerisi);
$jurnalProgress = pct($jurnalTerisi, max(1, $totalJadwalHari));
$jurnalBelumRows = array_values(array_filter($jadwalHariIni, static function ($item) use ($jurnalTerisiIds) {
    $id = (int)($item['id_mapel'] ?? 0);
    return $id > 0 && !isset($jurnalTerisiIds[$id]);
}));

$hadirToday = 0;
$absenTodayTotal = 0;
$hadirPct = 0;
if ($kelasIn !== '' && table_exists($conn, 'tbl_absen')) {
    $qAbsenHari = @mysqli_query(
        $conn,
        "SELECT
            SUM(CASE WHEN LOWER(status)='hadir' THEN 1 ELSE 0 END) AS hadir,
            COUNT(*) AS total
         FROM tbl_absen
         WHERE tanggal='$tglskr' AND kelas IN ($kelasIn)"
    );
    if ($qAbsenHari) {
        $row = mysqli_fetch_assoc($qAbsenHari);
        $hadirToday = (int)($row['hadir'] ?? 0);
        $absenTodayTotal = (int)($row['total'] ?? 0);
        $hadirPct = pct($hadirToday, max(1, $absenTodayTotal));
    }
}

$weeklyHadir = 0;
$weeklyTotal = 0;
$weeklyHadirPct = 0;
if ($kelasIn !== '' && table_exists($conn, 'tbl_absen')) {
    $qAbsenMinggu = @mysqli_query(
        $conn,
        "SELECT
            SUM(CASE WHEN LOWER(status)='hadir' THEN 1 ELSE 0 END) AS hadir,
            COUNT(*) AS total
         FROM tbl_absen
         WHERE tanggal BETWEEN '$weekStart' AND '$tglskr'
           AND kelas IN ($kelasIn)"
    );
    if ($qAbsenMinggu) {
        $row = mysqli_fetch_assoc($qAbsenMinggu);
        $weeklyHadir = (int)($row['hadir'] ?? 0);
        $weeklyTotal = (int)($row['total'] ?? 0);
        $weeklyHadirPct = pct($weeklyHadir, max(1, $weeklyTotal));
    }
}

$latestTasks = [];
$taskWeeklyTotal = 0;
$taskWeeklyDone = 0;
if (table_exists($conn, 'tbl_tugas')) {
    $qTask = @mysqli_query(
        $conn,
        "SELECT id, judul_tugas, kelas, tanggal_pengumpulan, created_at, status
         FROM tbl_tugas
         WHERE no_induk_guru='$nipEsc' AND status <> 'dihapus'
         ORDER BY id DESC
         LIMIT 3"
    );
    if ($qTask) {
        while ($row = mysqli_fetch_assoc($qTask)) {
            $latestTasks[] = $row;
        }
    }

    $qTaskWeek = @mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) AS selesai
         FROM tbl_tugas
         WHERE no_induk_guru='$nipEsc'
           AND status <> 'dihapus'
           AND DATE(created_at) BETWEEN '$weekStart' AND '$tglskr'"
    );
    if ($qTaskWeek) {
        $row = mysqli_fetch_assoc($qTaskWeek);
        $taskWeeklyTotal = (int)($row['total'] ?? 0);
        $taskWeeklyDone = (int)($row['selesai'] ?? 0);
    }
}
$taskWeeklyPct = pct($taskWeeklyDone, max(1, $taskWeeklyTotal));

$avgNilaiWeek = 0;
if (table_exists($conn, 'tbl_penilaian_item') && table_exists($conn, 'tbl_nilai_item')) {
    $qNilai = @mysqli_query(
        $conn,
        "SELECT AVG(n.nilai) AS avg_nilai
         FROM tbl_nilai_item n
         JOIN tbl_penilaian_item p ON p.id = n.id_item
         WHERE p.no_induk_guru='$nipEsc'
           AND p.tanggal BETWEEN '$weekStart' AND '$tglskr'"
    );
    if ($qNilai) {
        $row = mysqli_fetch_assoc($qNilai);
        $avgNilaiWeek = (int)round((float)($row['avg_nilai'] ?? 0));
    }
}

$waliKelasList = [];
if (table_exists($conn, 'tbl_wali_kelas') && table_exists($conn, 'tbl_kelas')) {
    $qWaliMobile = @mysqli_query(
        $conn,
        "SELECT DISTINCT k.kelas
         FROM tbl_wali_kelas wk
         JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
         WHERE wk.nip_wali='$nipEsc' AND k.kelas <> ''
         ORDER BY k.kelas ASC"
    );
    while ($qWaliMobile && ($row = mysqli_fetch_assoc($qWaliMobile))) {
        $waliKelasList[(string)$row['kelas']] = (string)$row['kelas'];
    }
}
if (table_exists($conn, 'tbl_kelas') && table_exists($conn, 'tbl_guru')) {
    $qColWali = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
    if ($qColWali && mysqli_num_rows($qColWali) > 0) {
        $qWaliMobile = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='$nipEsc' AND kelas <> '' ORDER BY kelas ASC");
        while ($qWaliMobile && ($row = mysqli_fetch_assoc($qWaliMobile))) {
            $waliKelasList[(string)$row['kelas']] = (string)$row['kelas'];
        }
    }
}

$followUpAlerts = [];
$monthStart = date('Y-m-01');
if (!empty($waliKelasList) && table_exists($conn, 'tbl_siswa') && table_exists($conn, 'tbl_absen')) {
    foreach ($waliKelasList as $kelasWali) {
        $kelasWaliEsc = mysqli_real_escape_string($conn, $kelasWali);
        $studentsRisk = [];
        $qStudentsRisk = @mysqli_query(
            $conn,
            "SELECT no_induk, nama_siswa
             FROM tbl_siswa
             WHERE kelas='$kelasWaliEsc'
               AND (status IS NULL OR status='' OR UPPER(status)='AKTIF')
             ORDER BY nama_siswa ASC"
        );
        while ($qStudentsRisk && ($row = mysqli_fetch_assoc($qStudentsRisk))) {
            $nisRisk = (string)($row['no_induk'] ?? '');
            if ($nisRisk === '') {
                continue;
            }
            $studentsRisk[$nisRisk] = [
                'no_induk' => $nisRisk,
                'nama_siswa' => (string)($row['nama_siswa'] ?? ''),
                'kelas' => $kelasWali,
                'attendance' => ['H' => 0, 'S' => 0, 'I' => 0, 'D' => 0, 'A' => 0, 'T' => 0, 'total' => 0],
                'daily' => [],
                'violations' => 0,
            ];
        }
        if (empty($studentsRisk)) {
            continue;
        }

        $qRiskAtt = @mysqli_query(
            $conn,
            "SELECT no_induk, tanggal, status
             FROM tbl_absen
             WHERE kelas='$kelasWaliEsc' AND tanggal BETWEEN '$monthStart' AND '$tglskr'
             ORDER BY tanggal ASC, id ASC"
        );
        while ($qRiskAtt && ($row = mysqli_fetch_assoc($qRiskAtt))) {
            $nisRisk = (string)($row['no_induk'] ?? '');
            if (!isset($studentsRisk[$nisRisk])) {
                continue;
            }
            $code = guru_mobile_status_code((string)($row['status'] ?? ''));
            $dateKey = (string)($row['tanggal'] ?? '');
            $studentsRisk[$nisRisk]['attendance'][$code]++;
            $studentsRisk[$nisRisk]['attendance']['total']++;
            if ($dateKey !== '') {
                $current = $studentsRisk[$nisRisk]['daily'][$dateKey] ?? 'H';
                if (guru_mobile_att_priority($code) > guru_mobile_att_priority($current)) {
                    $studentsRisk[$nisRisk]['daily'][$dateKey] = $code;
                }
            }
        }

        foreach (['tbl_pelanggaran_siswa', 'tbl_pelanggaran'] as $violationTable) {
            if (!table_exists($conn, $violationTable)) {
                continue;
            }
            $qViolation = @mysqli_query(
                $conn,
                "SELECT no_induk, COUNT(*) AS total
                 FROM `$violationTable`
                 WHERE kelas='$kelasWaliEsc'
                   AND (status_pelanggaran='Aktif' OR status_pelanggaran='Follow Up')
                 GROUP BY no_induk"
            );
            while ($qViolation && ($row = mysqli_fetch_assoc($qViolation))) {
                $nisRisk = (string)($row['no_induk'] ?? '');
                if (isset($studentsRisk[$nisRisk])) {
                    $studentsRisk[$nisRisk]['violations'] += (int)($row['total'] ?? 0);
                }
            }
        }

        foreach ($studentsRisk as $studentRisk) {
            ksort($studentRisk['daily']);
            $alphaStreak = 0;
            $sakitStreak = 0;
            $curA = 0;
            $curS = 0;
            foreach ($studentRisk['daily'] as $dailyCode) {
                if ($dailyCode === 'A') {
                    $curA++;
                    $curS = 0;
                } elseif ($dailyCode === 'S') {
                    $curS++;
                    $curA = 0;
                } else {
                    $curA = 0;
                    $curS = 0;
                }
                $alphaStreak = max($alphaStreak, $curA);
                $sakitStreak = max($sakitStreak, $curS);
            }

            $att = $studentRisk['attendance'];
            $hadirPctRisk = pct((int)$att['H'], max(1, (int)$att['total']));
            $score = 0;
            $level = 'Perlu Dipantau';
            $reason = '';
            $action = '';

            if ($alphaStreak >= 3 || (int)$att['A'] > 3) {
                $score = 9;
                $level = 'Segera';
                $reason = $alphaStreak >= 3 ? 'Alpha ' . $alphaStreak . ' kali berturut-turut' : 'Alpha ' . (int)$att['A'] . ' kali bulan ini';
                $action = 'Koordinasi BK dan panggilan orang tua.';
            } elseif ($sakitStreak >= 3) {
                $score = 8;
                $level = 'Segera';
                $reason = 'Sakit ' . $sakitStreak . ' kali berturut-turut';
                $action = 'Konfirmasi orang tua dan susun dukungan belajar.';
            } elseif ((int)$studentRisk['violations'] > 0) {
                $score = 7;
                $level = 'Segera';
                $reason = 'Ada pelanggaran aktif/follow up';
                $action = 'Cek catatan pelanggaran dan tindak lanjut wali kelas.';
            } elseif ((int)$att['A'] === 2) {
                $score = 5;
                $reason = 'Alpha 2 kali';
                $action = 'Berikan peringatan dan pantau presensi harian.';
            } elseif ((int)$att['A'] === 1) {
                $score = 3;
                $level = 'Awal';
                $reason = 'Alpha 1 kali';
                $action = 'Pengarahan dan pendampingan awal.';
            } elseif ((int)$att['total'] > 0 && $hadirPctRisk < 80) {
                $score = 3;
                $level = 'Awal';
                $reason = 'Kehadiran di bawah 80%';
                $action = 'Pantau presensi dan hubungi orang tua bila berlanjut.';
            }

            if ($score > 0) {
                $followUpAlerts[] = [
                    'nama_siswa' => $studentRisk['nama_siswa'],
                    'no_induk' => $studentRisk['no_induk'],
                    'kelas' => $studentRisk['kelas'],
                    'level' => $level,
                    'score' => $score,
                    'reason' => $reason,
                    'action' => $action,
                ];
            }
        }
    }
}
usort($followUpAlerts, static function ($a, $b) {
    return ((int)$b['score'] <=> (int)$a['score']) ?: strcmp((string)$a['nama_siswa'], (string)$b['nama_siswa']);
});
$followUpAlerts = array_slice($followUpAlerts, 0, 8);
$notifCount = $jurnalBelum + count($followUpAlerts);

$routeDetailMateri = guru_route('detailmateri');
$routeInputNilai = guru_route('inputnilai');
$routeCetakJurnal = guru_route('cetak_jurnal');
$routeNilai = guru_route('nilai');
$routePresensi = guru_route('presensi');
$routeRekapKehadiran = guru_route('rekap-kehadiran');
$routeInputTugas = guru_route('inputtugas');
$routeHistoryTugas = guru_route('history-tugas');
$routeGuruJurnal = guru_route('guru_jurnal');
$routeDataSiswa = guru_route('data-siswa');
$kelasDetailUrl = $routeDataSiswa;
if ($totalKelasAmpu === 1) {
    $kelasDetailUrl .= '?kelas=' . rawurlencode((string)$kelasAmpu[0]);
}
$routeDetailJadwal = guru_route('detail-jadwal');
$routeProfilGuru = guru_route('profil-guru');
$routeGuru = guru_route('guru');
$routeNotifikasi = guru_route('guru_notifikasi');
$routeLaporanKelas = guru_route('laporan-kelas');
$routeWalikelas = guru_route('walikelas');
$routeLeger = guru_route('leger');

$teacherName = trim((string)($dataguru['nama_guru'] ?? ($_SESSION['nama_guru'] ?? 'Guru')));
if ($teacherName === '') {
    $teacherName = 'Guru';
}
$greetingText = greeting_by_hour((int)date('G'));
$targetHarian = 80;
$jurnalTerisiPct = pct($jurnalTerisi, max(1, $totalJadwalHari));
$jurnalBelumPct = pct($jurnalBelum, max(1, $totalJadwalHari));
$taskPreview = array_slice($latestTasks, 0, 1);
$trendHadir = max(1, min(12, (int)round($weeklyHadirPct / 11)));
$trendTugas = max(1, min(12, (int)round($taskWeeklyPct / 9)));
$trendNilai = max(1, min(10, (int)round($avgNilaiWeek / 17)));
$latestTask = $taskPreview[0] ?? null;
$bulanId = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$periodeLabel = $bulanId[(int)date('n')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#059669">
    <title>Dashboard Guru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/guru-mobile-app.css">
</head>
<body>
<div class="app-shell">
    <header class="hero-header">
        <div class="profile-photo">
            <?php if (!empty($dataguru['foto'])) { ?>
                <img src="../../foto/<?= htmlspecialchars($dataguru['foto']); ?>" alt="Foto Guru">
            <?php } else { ?>
                <img src="../../img/no-photo.png" alt="Foto Guru">
            <?php } ?>
        </div>
        <div class="greet-block">
            <p class="greet-small"><?= htmlspecialchars($greetingText); ?></p>
            <h1><?= htmlspecialchars($teacherName); ?> 👋</h1>
            <p class="greet-school">Guru <?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Sekolah'); ?></p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button class="notif-btn" id="btnNotif" type="button" aria-label="Notifikasi">
                <i class="bi bi-bell"></i>
                <span class="notif-badge" id="notifBadge" <?= $notifCount > 0 ? '' : 'style="display:none;"'; ?>><?= $notifCount; ?></span>
            </button>
            <a href="../../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="notif-btn" style="text-decoration: none; color: var(--red); border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.05);" title="Keluar">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </header>

    <section class="summary-card">
        <div class="summary-grid">
            <div class="summary-metric">
                <div>
                    <p class="summary-label">Kelas yang diampu</p>
                    <p class="summary-value"><?= $totalKelasAmpu; ?> Kelas</p>
                </div>
                <span class="metric-icon"><i class="bi bi-people-fill"></i></span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-metric">
                <div>
                    <p class="summary-label">Total Siswa</p>
                    <p class="summary-value"><?= $totalSiswa; ?> Siswa</p>
                </div>
                <span class="metric-icon"><i class="bi bi-person-fill"></i></span>
            </div>
        </div>
        <a class="switch-btn" href="<?= htmlspecialchars($kelasDetailUrl, ENT_QUOTES, 'UTF-8'); ?>">
            Lihat Detail <i class="bi bi-arrow-right-circle"></i>
        </a>
        <div class="att-row">
            <span>Kehadiran Hari Ini</span>
            <span><?= $hadirPct; ?>% (<?= $hadirToday; ?> / <?= max(1, $absenTodayTotal); ?>)</span>
        </div>
        <div class="att-track">
            <div class="att-fill" style="width: <?= max(0, min(100, $hadirPct)); ?>%;"></div>
        </div>
    </section>

    <section class="announcement-card d-none" id="announcementBoard">
        <div class="announcement-head">
            <div>
                <p>Pengumuman</p>
                <h3>Informasi terbaru</h3>
            </div>
            <button class="announcement-refresh" id="annRefreshBtn" type="button" aria-label="Muat ulang pengumuman">
                <i class="bi bi-arrow-repeat"></i>
            </button>
        </div>
        <div class="announcement-list" id="annItems"></div>
    </section>

    <section class="dual-card-grid">
        <article class="panel-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="m-0">Progres Pengisian Jurnal Mengajar</h2>
                <button class="btn btn-sm btn-3d-plus rounded-circle btn-open-input-jurnal" title="Input Jurnal Sekarang" style="width: 38px; height: 38px; padding: 0;">
                    <i class="bi bi-journal-plus" style="font-size: 1.4rem;"></i>
                </button>
            </div>
            <div class="progress-wrap">
                <div class="ring" style="--progress: <?= max(0, min(100, $jurnalProgress)); ?>">
                    <div class="ring-center">
                        <strong><?= $jurnalProgress; ?>%</strong>
                        <span>Terisi</span>
                    </div>
                </div>
                <ul class="legend-list">
                    <li><span><span class="dot" style="background: #059669;"></span> Terisi</span> <b><?= $jurnalTerisi; ?> (<?= $jurnalTerisiPct; ?>%)</b></li>
                    <li><span><span class="dot" style="background: #e2e8f0;"></span> Belum Terisi</span> <b><?= $jurnalBelum; ?> (<?= $jurnalBelumPct; ?>%)</b></li>
                </ul>
            </div>
            <button class="period-label" type="button">
                Periode <?= htmlspecialchars($periodeLabel); ?> <i class="bi bi-chevron-down"></i>
            </button>
            <button class="progress-detail-btn btn-open-input-jurnal" id="btnInputJurnalUtama" type="button">
                <i class="bi bi-journal-plus me-1"></i> Input Jurnal
            </button>
        </article>

        <article class="panel-card input-card">
            <h2>Input Jurnal Mengajar</h2>
            <div class="input-body">
                <i class="bi bi-journal-bookmark-fill"></i>
                <p>Catat kegiatan mengajar Anda hari ini dengan mudah dan cepat.</p>
            </div>
            <button class="cta-btn" id="qaInputJurnal" type="button">Input Sekarang</button>
        </article>
    </section>

    <section class="section-block">
        <h3>Aksi Cepat</h3>
        <div class="quick-grid">
            <button class="quick-item" id="qaAmbilKehadiran" type="button"><i class="bi bi-clipboard2-data" style="color: #10b981;"></i><span>Lihat Kehadiran</span></button>
            <button class="quick-item" id="qaInputJurnalMini" type="button"><i class="bi bi-journal-plus" style="color: #8b5cf6;"></i><span>Input Jurnal Mengajar</span></button>
            <?php if (!empty($waliKelasList)) { ?>
            <button class="quick-item" id="qaPengumuman" type="button"><i class="bi bi-person-vcard" style="color: #3b82f6;"></i><span>Walikelas</span></button>
            <?php } ?>
            <button class="quick-item" id="qaLaporan" type="button"><i class="bi bi-bar-chart-line" style="color: #f59e0b;"></i><span>Lihat Laporan</span></button>
            <button class="quick-item" id="qaMateri" type="button"><i class="bi bi-book" style="color: #3b82f6;"></i><span>Materi Pembelajaran</span></button>
            <button class="quick-item" id="qaKelolaKelas" type="button"><i class="bi bi-people" style="color: #10b981;"></i><span>Kelola Kelas</span></button>
            <button class="quick-item" id="qaPenilaian" type="button"><i class="bi bi-table" style="color: #f59e0b;"></i><span>Nilai Siswa</span></button>
            <button class="quick-item" id="qaLeger" type="button"><i class="bi bi-file-earmark-spreadsheet" style="color: #059669;"></i><span>Leger</span></button>
            <button class="quick-item" id="qaLainnya" type="button"><i class="bi bi-grid-3x3-gap" style="color: #64748b;"></i><span>Lainnya</span></button>
        </div>
    </section>

    <section class="schedule-block">
        <div class="section-head">
            <h3>Jadwal Hari Ini</h3>
            <a href="<?= $routeDetailJadwal; ?>">Lihat Semua <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="schedule-list">
            <?php if ($totalJadwalHari === 0) { ?>
                <div class="empty-view">Belum ada jadwal hari ini.</div>
            <?php } else { ?>
                <?php
                $dotPalette = ['#059669', '#10b981', '#0d9488'];
                $roomNumbers = [201, 203, 205, 207, 209, 211];
                foreach ($jadwalHariIni as $idx => $jadwal) {
                    $dotColor = $dotPalette[$idx % count($dotPalette)];
                    $roomNo = $roomNumbers[$idx % count($roomNumbers)];
                    $idMapel = (int)($jadwal['id_mapel'] ?? 0);
                    ?>
                    <a href="#" class="schedule-row btn-open-jurnal" data-id="<?= $idMapel; ?>">
                        <div class="schedule-time">
                            <strong><?= htmlspecialchars($jadwal['jam_mulai']); ?></strong>
                            <span><?= htmlspecialchars($jadwal['jam_selesai']); ?></span>
                        </div>
                        <div class="schedule-track">
                            <span class="track-dot" style="background: <?= $dotColor; ?>;"></span>
                            <span class="track-line"></span>
                        </div>
                        <div class="schedule-main">
                            <strong>Kelas <?= htmlspecialchars($jadwal['kelas']); ?></strong>
                            <p><?= htmlspecialchars($jadwal['nama_mapel']); ?></p>
                            <span class="schedule-room">Ruang <?= $roomNo; ?></span>
                        </div>
                        <i class="bi bi-chevron-right" style="color: #cbd5e1;"></i>
                    </a>
                <?php } ?>
            <?php } ?>
        </div>
    </section>

    <section class="bottom-panels">
        <article class="panel-card">
            <div class="section-head" style="margin-bottom: 12px;">
                <h3>Tugas Terbaru</h3>
                <a href="<?= htmlspecialchars($routeHistoryTugas, ENT_QUOTES, 'UTF-8'); ?>">Lihat Semua</a>
            </div>
            <?php if (!$latestTask) { ?>
                <div class="empty-view small-empty">Belum ada tugas terbaru.</div>
            <?php } else { ?>
                <div class="summary-item">
                    <span class="si-icon"><i class="bi bi-file-earmark-text"></i></span>
                    <div class="si-content">
                        <p><?= htmlspecialchars($latestTask['judul_tugas'] ?? 'Tugas tanpa judul'); ?></p>
                        <strong>Kelas <?= htmlspecialchars($latestTask['kelas'] ?? '-'); ?></strong>
                        <small>Deadline <?= htmlspecialchars(format_id_date($latestTask['tanggal_pengumpulan'] ?? null)); ?></small>
                    </div>
                </div>
            <?php } ?>
        </article>

        <article class="panel-card">
            <div class="section-head" style="margin-bottom: 12px;">
                <h3>Ringkasan Mingguan</h3>
                <i class="bi bi-chevron-down" style="color: #94a3b8;"></i>
            </div>
            <div class="weekly-summary-list">
                <div class="summary-item">
                    <span class="si-icon"><i class="bi bi-people"></i></span>
                    <div class="si-content">
                        <p>Kehadiran Rata-rata</p>
                        <strong><?= $weeklyHadirPct; ?>% <span class="mini-trend"><i class="bi bi-activity"></i> <?= $weeklyHadir; ?>/<?= max(1, $weeklyTotal); ?></span></strong>
                    </div>
                </div>
                <div class="summary-item">
                    <span class="si-icon task"><i class="bi bi-clipboard-check"></i></span>
                    <div class="si-content">
                        <p>Tugas Selesai</p>
                        <strong><?= $taskWeeklyPct; ?>% <span class="mini-trend"><?= $taskWeeklyDone; ?>/<?= max(1, $taskWeeklyTotal); ?></span></strong>
                    </div>
                </div>
                <div class="summary-item">
                    <span class="si-icon score"><i class="bi bi-star"></i></span>
                    <div class="si-content">
                        <p>Rata-rata Nilai</p>
                        <strong><?= $avgNilaiWeek > 0 ? $avgNilaiWeek : '-'; ?></strong>
                    </div>
                </div>
            </div>
        </article>
    </section>
</div>

<nav class="bottom-nav">
    <a href="#" class="active"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
    <a href="<?= htmlspecialchars($kelasDetailUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-grid-fill"></i><span>Kelas</span></a>
    <button class="nav-center" id="navCenterAction" type="button">
        <i class="bi bi-fingerprint"></i>
    </button>
    <a href="#" id="navTugas"><i class="bi bi-clipboard-check-fill"></i><span>Tugas</span></a>
    <a href="<?= $routeProfilGuru; ?>"><i class="bi bi-person-fill"></i><span>Profil</span></a>
</nav>

<div class="modal fade" id="moreActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aksi Lainnya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <button class="more-action-btn" id="qaDaftarNilai" type="button"><i class="bi bi-ui-checks-grid"></i> Input Nilai</button>
                <button class="more-action-btn" id="qaCetakJurnal" type="button"><i class="bi bi-printer"></i> Cetak Jurnal</button>
                <button class="more-action-btn" id="qaDetailJadwal" type="button"><i class="bi bi-calendar-week"></i> Detail Jadwal</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content notification-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Notifikasi Prioritas</h5>
                    <p class="notif-modal-subtitle">Jurnal, pengumuman, dan siswa yang perlu tindakan segera.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <section class="notif-section">
                    <div class="notif-section-head">
                        <h6><i class="bi bi-journal-plus"></i> Jurnal Hari Ini</h6>
                        <span><?= count($jurnalBelumRows); ?></span>
                    </div>
                    <?php if (empty($jurnalBelumRows)) { ?>
                        <div class="notif-empty-mini">Semua jurnal jadwal hari ini sudah terisi.</div>
                    <?php } else { ?>
                        <div class="notif-list">
                            <?php foreach ($jurnalBelumRows as $j) { ?>
                                <button class="notif-item-row btn-open-jurnal" type="button" data-id="<?= (int)$j['id_mapel']; ?>" data-bs-dismiss="modal">
                                    <span class="notif-item-icon journal"><i class="bi bi-journal-text"></i></span>
                                    <span class="notif-item-main">
                                        <b><?= htmlspecialchars($j['nama_mapel']); ?></b>
                                        <small>Kelas <?= htmlspecialchars($j['kelas']); ?> | <?= htmlspecialchars($j['jam_mulai']); ?> - <?= htmlspecialchars($j['jam_selesai']); ?></small>
                                    </span>
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </section>

                <section class="notif-section">
                    <div class="notif-section-head">
                        <h6><i class="bi bi-megaphone"></i> Pengumuman Admin</h6>
                        <span id="notifAnnCount">0</span>
                    </div>
                    <div class="notif-list" id="notifAnnouncementList">
                        <div class="notif-empty-mini">Memuat pengumuman...</div>
                    </div>
                </section>

                <section class="notif-section">
                    <div class="notif-section-head">
                        <h6><i class="bi bi-exclamation-triangle"></i> Tindak Lanjut Siswa</h6>
                        <span><?= count($followUpAlerts); ?></span>
                    </div>
                    <?php if (empty($followUpAlerts)) { ?>
                        <div class="notif-empty-mini">Belum ada siswa prioritas dari analisis otomatis.</div>
                    <?php } else { ?>
                        <div class="notif-list">
                            <?php foreach ($followUpAlerts as $item) { ?>
                                <a class="notif-item-row" href="<?= htmlspecialchars($routeWalikelas, ENT_QUOTES, 'UTF-8'); ?>?kelas=<?= rawurlencode((string)$item['kelas']); ?>">
                                    <span class="notif-item-icon risk"><i class="bi bi-person-exclamation"></i></span>
                                    <span class="notif-item-main">
                                        <b><?= htmlspecialchars($item['nama_siswa']); ?></b>
                                        <small><?= htmlspecialchars($item['kelas']); ?> | <?= htmlspecialchars($item['reason']); ?></small>
                                        <em><?= htmlspecialchars($item['action']); ?></em>
                                    </span>
                                    <span class="notif-level <?= $item['level'] === 'Segera' ? 'urgent' : ''; ?>"><?= htmlspecialchars($item['level']); ?></span>
                                </a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selectJadwalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($totalJadwalHari === 0) { ?>
                    <div class="empty-view">Tidak ada jadwal untuk dipilih.</div>
                <?php } else { ?>
                    <div class="schedule-picker-list">
                        <?php foreach ($jadwalHariIni as $j) { ?>
                            <button class="picker-row btn-pilih-jadwal" type="button" data-id="<?= (int)$j['id_mapel']; ?>">
                                <div>
                                    <strong>Kelas <?= htmlspecialchars($j['kelas']); ?></strong>
                                    <p><?= htmlspecialchars($j['nama_mapel']); ?></p>
                                    <small><?= htmlspecialchars($j['jam_mulai']); ?> - <?= htmlspecialchars($j['jam_selesai']); ?></small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="show" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Input Jurnal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="modal-data loading-state">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Memuat...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNilai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Input Nilai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="modal-nilai-body loading-state">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Memuat...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cetak Jurnal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe src="" id="frameCetak" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function () {
    const ROUTES = <?= json_encode([
        'detailmateri' => $routeDetailMateri,
        'inputnilai' => $routeInputNilai,
        'cetak_jurnal' => $routeCetakJurnal,
        'nilai' => $routeNilai,
        'presensi' => $routePresensi,
        'rekap_kehadiran' => $routeRekapKehadiran,
        'inputtugas' => $routeInputTugas,
        'history_tugas' => $routeHistoryTugas,
        'guru_jurnal' => $routeGuruJurnal,
        'data_siswa' => $routeDataSiswa,
        'detail_jadwal' => $routeDetailJadwal,
        'profil_guru' => $routeProfilGuru,
        'guru' => $routeGuru,
        'notifikasi' => $routeNotifikasi,
        'leger' => $routeLeger,
        'api_pengumuman' => 'api_pengumuman.php',
        'api_pengumuman_read' => 'api_pengumuman_read.php',
        'laporan' => $routeLaporanKelas,
        'pengumuman' => $routeWalikelas
    ], JSON_UNESCAPED_SLASHES); ?>;

    const JOURNAL_NOTIF_COUNT = <?= (int)$jurnalBelum; ?>;
    const FOLLOWUP_NOTIF_COUNT = <?= count($followUpAlerts); ?>;
    const BASE_NOTIF_COUNT = JOURNAL_NOTIF_COUNT + FOLLOWUP_NOTIF_COUNT;
    let latestAnnouncements = [];

    window.JADWAL_TODAY = <?= json_encode(array_map(static function ($j) {
        return [
            'id_mapel' => (int)($j['id_mapel'] ?? 0),
            'kelas' => (string)($j['kelas'] ?? ''),
            'nama_mapel' => (string)($j['nama_mapel'] ?? ''),
            'jam_mulai' => (string)($j['jam_mulai'] ?? ''),
            'jam_selesai' => (string)($j['jam_selesai'] ?? '')
        ];
    }, $jadwalHariIni), JSON_UNESCAPED_SLASHES); ?>;

    let pendingScheduleAction = null;

    function openInputJurnal(idmapel) {
        if (!idmapel) return;
        $('.modal-data').html('<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span>Memuat...</span></div>');
        const modal = new bootstrap.Modal(document.getElementById('show'));
        modal.show();
        $.post(ROUTES.detailmateri, { getDetail: idmapel }, function (data) {
            $('.modal-data').html(data);
        }).fail(function () {
            $('.modal-data').html('<div class="alert alert-danger">Gagal memuat form jurnal.</div>');
        });
    }

    function openInputNilai(idmapel) {
        if (!idmapel) return;
        $('.modal-nilai-body').html('<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span>Memuat...</span></div>');
        const modal = new bootstrap.Modal(document.getElementById('modalNilai'));
        modal.show();
        $.post(ROUTES.inputnilai, { getDetail: idmapel }, function (data) {
            $('.modal-nilai-body').html(data);
        }).fail(function () {
            $('.modal-nilai-body').html('<div class="alert alert-danger">Gagal memuat form nilai.</div>');
        });
    }

    function runScheduleAction(action, idmapel) {
        if (!idmapel) return;
        if (action === 'jurnal') {
            openInputJurnal(idmapel);
            return;
        }
        if (action === 'nilai') {
            openInputNilai(idmapel);
            return;
        }
        if (action === 'tugas') {
            window.location = ROUTES.inputtugas + '?getDetail=' + encodeURIComponent(idmapel);
        }
    }

    function requireSchedule(action) {
        if (window.JADWAL_TODAY.length === 0) {
            alert('Belum ada jadwal untuk aksi ini.');
            return;
        }
        if (window.JADWAL_TODAY.length === 1) {
            runScheduleAction(action, window.JADWAL_TODAY[0].id_mapel);
            return;
        }
        pendingScheduleAction = action;
        const modal = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
        modal.show();
    }

    function openMoreAction() {
        const modal = new bootstrap.Modal(document.getElementById('moreActionModal'));
        modal.show();
    }

    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function formatAnnBody(raw) {
        const lines = String(raw || '').replace(/\r\n?/g, '\n').split('\n').map(line => line.trim()).filter(Boolean);
        if (lines.length === 0) {
            return '<span class="text-muted">Tidak ada isi pengumuman.</span>';
        }
        if (lines.length > 1 && lines.every(line => /^[-*•]\s+/.test(line))) {
            return '<ul>' + lines.map(line => '<li>' + escapeHtml(line.replace(/^[-*•]\s+/, '')) + '</li>').join('') + '</ul>';
        }
        return lines.map(line => '<p>' + escapeHtml(line) + '</p>').join('');
    }

    function updateNotifBadge(unreadAnnouncements) {
        const total = BASE_NOTIF_COUNT + Math.max(0, parseInt(unreadAnnouncements || 0, 10));
        const badge = $('#notifBadge');
        if (total > 0) {
            badge.text(total > 99 ? '99+' : total).show();
        } else {
            badge.hide();
        }
    }

    function renderNotificationAnnouncements(items) {
        const wrap = $('#notifAnnouncementList');
        const count = $('#notifAnnCount');
        const unread = (items || []).filter(function (item) { return !item.read; }).length;
        count.text(unread);
        wrap.empty();

        if (!items || items.length === 0) {
            wrap.html('<div class="notif-empty-mini">Tidak ada pengumuman admin aktif.</div>');
            return;
        }

        items.slice(0, 5).forEach(function (item) {
            const isUnread = !item.read;
            const row = $(
                '<div class="notif-item-row ' + (isUnread ? 'is-unread' : '') + '">' +
                    '<span class="notif-item-icon announcement"><i class="bi bi-megaphone"></i></span>' +
                    '<span class="notif-item-main">' +
                        (parseInt(item.penting, 10) === 1 ? '<span class="notif-mini-pill">Penting</span>' : '') +
                        '<b>' + escapeHtml(item.judul) + '</b>' +
                        '<small>' + escapeHtml(item.mulai) + ' s/d ' + escapeHtml(item.selesai) + '</small>' +
                        '<em>' + escapeHtml(String(item.isi || '').replace(/\s+/g, ' ').slice(0, 110)) + '</em>' +
                    '</span>' +
                    '<button class="notif-read-btn" type="button" data-ann-read="' + item.id + '"><i class="bi ' + (isUnread ? 'bi-eye' : 'bi-check-circle') + '"></i></button>' +
                '</div>'
            );
            wrap.append(row);
        });
    }

    function openNotificationModal() {
        renderNotificationAnnouncements(latestAnnouncements);
        const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        modal.show();
        loadAnnouncements(false);
    }

    function loadAnnouncements(manual) {
        $.getJSON(ROUTES.api_pengumuman, function (res) {
            const board = $('#announcementBoard');
            const wrap = $('#annItems');
            updateNotifBadge(res.unread_count || 0);

            if (!res.success || !res.items || res.items.length === 0) {
                latestAnnouncements = [];
                renderNotificationAnnouncements(latestAnnouncements);
                board.addClass('d-none');
                wrap.empty();
                return;
            }

            latestAnnouncements = res.items;
            renderNotificationAnnouncements(latestAnnouncements);
            board.removeClass('d-none');
            wrap.empty();
            res.items.forEach(function (item) {
                const isUnread = !item.read;
                const attach = item.lampiran
                    ? '<a class="ann-attach" href="../../materi/' + encodeURIComponent(item.lampiran) + '" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i></a>'
                    : '';
                const card = $(
                    '<article class="announcement-item ' + (isUnread ? 'is-unread' : '') + '">' +
                        '<div class="ann-title-row">' +
                            '<div>' +
                                (parseInt(item.penting, 10) === 1 ? '<span class="ann-pill">Penting</span>' : '') +
                                '<h4>' + escapeHtml(item.judul) + '</h4>' +
                            '</div>' +
                            '<div class="ann-actions">' + attach +
                                '<button class="ann-read-btn" type="button" data-ann-read="' + item.id + '" title="Tandai sudah dibaca">' +
                                    '<i class="bi ' + (isUnread ? 'bi-eye' : 'bi-check-circle') + '"></i>' +
                                '</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="ann-meta"><i class="bi bi-calendar-event"></i> ' + escapeHtml(item.mulai) + ' s/d ' + escapeHtml(item.selesai) + '</div>' +
                        '<div class="ann-body">' + formatAnnBody(item.isi) + '</div>' +
                    '</article>'
                );
                wrap.append(card);
            });
        }).fail(function () {
            if (manual) {
                alert('Gagal memuat pengumuman.');
            }
        });
    }

    $('#btnNotif').on('click', function () { openNotificationModal(); });
    $('#annRefreshBtn').on('click', function () { loadAnnouncements(true); });
    $('#qaInputJurnal, #navCenterAction, .btn-open-input-jurnal').on('click', function () { requireSchedule('jurnal'); });
    $('#qaInputJurnalMini').on('click', function () { requireSchedule('jurnal'); });
    $('#qaAmbilKehadiran').on('click', function () { window.location = ROUTES.rekap_kehadiran; });
    $('#qaPengumuman').on('click', function () { window.location = ROUTES.pengumuman; });
    $('#qaLaporan').on('click', function () { window.location = ROUTES.laporan; });
    $('#qaMateri').on('click', function () { window.location = ROUTES.guru_jurnal; });
    $('#qaKelolaKelas').on('click', function () { window.location = ROUTES.data_siswa; });
    $('#qaPenilaian').on('click', function () { window.location = ROUTES.nilai; });
    $('#qaLeger').on('click', function () { window.location = ROUTES.leger; });
    // Handler tombol Input Jurnal Utama ditangani oleh class .btn-open-input-jurnal
    $('#qaLainnya').on('click', function () { openMoreAction(); });
    $('#navTugas, #btnLihatTugas').on('click', function (e) { e.preventDefault(); window.location = ROUTES.history_tugas; });

    $('#qaDaftarNilai').on('click', function () {
        const more = bootstrap.Modal.getInstance(document.getElementById('moreActionModal'));
        if (more) more.hide();
        if (window.JADWAL_TODAY.length > 0) {
            requireSchedule('nilai');
        } else {
            window.location = ROUTES.nilai;
        }
    });

    $('#qaCetakJurnal').on('click', function () {
        const more = bootstrap.Modal.getInstance(document.getElementById('moreActionModal'));
        if (more) more.hide();
        $('#frameCetak').attr('src', ROUTES.cetak_jurnal);
        const modal = new bootstrap.Modal(document.getElementById('modalCetak'));
        modal.show();
    });

    $('#qaDetailJadwal').on('click', function () {
        const more = bootstrap.Modal.getInstance(document.getElementById('moreActionModal'));
        if (more) more.hide();
        window.location = ROUTES.detail_jadwal;
    });

    $('.btn-open-jurnal').on('click', function () {
        const id = parseInt($(this).data('id'), 10);
        if (id > 0) openInputJurnal(id);
    });

    $(document).on('click', '.btn-pilih-jadwal', function () {
        const id = parseInt($(this).data('id'), 10);
        if (pendingScheduleAction) {
            runScheduleAction(pendingScheduleAction, id);
            pendingScheduleAction = null;
        } else {
            openInputJurnal(id);
        }
        const active = bootstrap.Modal.getInstance(document.getElementById('selectJadwalModal'));
        if (active) active.hide();
    });

    $(document).on('click', '[data-ann-read]', function () {
        const btn = $(this);
        const id = parseInt(btn.data('ann-read'), 10);
        if (!id) return;

        $.post(ROUTES.api_pengumuman_read, { id: id }, function (res) {
            if (res && res.ok) {
                btn.closest('.announcement-item').removeClass('is-unread');
                btn.html('<i class="bi bi-check-circle"></i>');
                loadAnnouncements(false);
            }
        }, 'json');
    });

    loadAnnouncements(false);
    setInterval(function () { loadAnnouncements(false); }, 120000);
});
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
