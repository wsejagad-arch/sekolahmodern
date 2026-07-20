<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    header('Location: ../../index.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
session_write_close(); // UNBLOCK SESSION UNTUK SKALABILITAS 900+ SISWA
date_default_timezone_set('Asia/Jakarta');

function kihs_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kihs_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return $q && mysqli_num_rows($q) > 0;
}

function kihs_create_table(mysqli $conn): void
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
            keterangan TEXT DEFAULT NULL,
            lat VARCHAR(30) DEFAULT NULL,
            lng VARCHAR(30) DEFAULT NULL,
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

function kihs_habits(): array
{
    return [
        'bangun_pagi' => ['label' => 'Bangun Pagi', 'icon' => 'fa-sun', 'start' => '04:00', 'end' => '06:00', 'hint' => 'Mulai paling awal pukul 04.00.'],
        'beribadah' => ['label' => 'Beribadah', 'icon' => 'fa-praying-hands', 'start' => '04:00', 'end' => '21:00', 'hint' => 'Lampirkan selfie setelah beribadah.'],
        'berolahraga' => ['label' => 'Berolahraga', 'icon' => 'fa-running', 'start' => '05:00', 'end' => '07:30', 'hint' => 'Selfie saat atau setelah olahraga.'],
        'makan_sehat' => ['label' => 'Makan Sehat', 'icon' => 'fa-apple-alt', 'start' => '06:00', 'end' => '20:00', 'hint' => 'Selfie bersama makanan sehat.'],
        'gemar_belajar' => ['label' => 'Gemar Belajar', 'icon' => 'fa-book-reader', 'start' => '18:00', 'end' => '21:00', 'hint' => 'Selfie saat belajar mandiri.'],
        'bermasyarakat' => ['label' => 'Bermasyarakat', 'icon' => 'fa-hands-helping', 'start' => '15:00', 'end' => '18:00', 'hint' => 'Selfie aktivitas sosial/keluarga/lingkungan.'],
        'tidur_cepat' => ['label' => 'Tidur Cepat', 'icon' => 'fa-moon', 'start' => '20:00', 'end' => '22:00', 'hint' => 'Selfie persiapan tidur cepat.'],
    ];
}

function kihs_prayers(string $agama, bool $hideDzuhur = false, bool $hideJumat = false): array
{
    $agama = strtolower(trim($agama));
    if (strpos($agama, 'islam') !== false) {
        $p = [
            'subuh' => ['label' => 'Subuh', 'start' => '04:00', 'end' => '05:59'],
        ];
        if (!$hideDzuhur) {
            $p['dzuhur'] = ['label' => 'Dzuhur', 'start' => '11:30', 'end' => '13:30'];
        }
        $p['ashar'] = ['label' => 'Ashar', 'start' => '14:30', 'end' => '17:30'];
        $p['maghrib'] = ['label' => 'Maghrib', 'start' => '17:30', 'end' => '19:00'];
        $p['isya'] = ['label' => 'Isya', 'start' => '19:00', 'end' => '03:59'];
        
        if (date('N') == 5 && !$hideJumat) {
            $p['jumat'] = ['label' => 'Jumat', 'start' => '11:30', 'end' => '13:30'];
        }
        return $p;
    }
    if (strpos($agama, 'katolik') !== false) {
        return [
            'pagi' => ['label' => 'Ibadah Pagi (06:00)', 'start' => '05:00', 'end' => '08:00'],
            'siang' => ['label' => 'Ibadah Siang (12:00)', 'start' => '11:00', 'end' => '14:00'],
            'sore' => ['label' => 'Ibadah Sore (18:00)', 'start' => '17:00', 'end' => '20:00'],
            'malaikat_tuhan' => ['label' => 'Malaikat Tuhan', 'start' => '00:00', 'end' => '23:59'],
            'rosario' => ['label' => 'Doa Rosario', 'start' => '00:00', 'end' => '23:59'],
            'bapa_kami' => ['label' => 'Bapa Kami', 'start' => '00:00', 'end' => '23:59'],
            'salam_maria' => ['label' => 'Salam Maria', 'start' => '00:00', 'end' => '23:59'],
            'doa_umum' => ['label' => 'Doa Umum', 'start' => '00:00', 'end' => '23:59'],
            'novena' => ['label' => 'Doa Novena', 'start' => '00:00', 'end' => '23:59'],
        ];
    }
    return [
        'umum' => ['label' => 'Ibadah Umum', 'start' => '00:00', 'end' => '23:59']
    ];
}

kihs_create_table($conn);

// ── Cek Pengaturan Sholat Sekolah ─────────────────────────────────────────────
$sholatSettings = [];
$qSholatCfg = @mysqli_query($conn, "SELECT kunci, nilai FROM tbl_app_config WHERE kunci IN (
    'sholat_dzuhur_active', 'sholat_dzuhur_days',
    'sholat_jumat_active', 'sholat_jumat_days'
)");
if ($qSholatCfg) {
    while ($rCfg = mysqli_fetch_assoc($qSholatCfg)) {
        $sholatSettings[$rCfg['kunci']] = $rCfg['nilai'];
    }
}
$isDzuhurActive = ($sholatSettings['sholat_dzuhur_active'] ?? '0') === '1';
$isJumatActive = ($sholatSettings['sholat_jumat_active'] ?? '0') === '1';
$dzDays = json_decode($sholatSettings['sholat_dzuhur_days'] ?? '[]', true) ?: [];
$jmDays = json_decode($sholatSettings['sholat_jumat_days'] ?? '[]', true) ?: [];

$hariIndoMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hariIniIndo = $hariIndoMap[date('N')];

$hideDzuhur = ($isDzuhurActive && in_array($hariIniIndo, $dzDays));
$hideJumat = ($isJumatActive && in_array($hariIniIndo, $jmDays));
// ──────────────────────────────────────────────────────────────────────────────

$nis = (string)$_SESSION['no_induk'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$agamaSelect = kihs_column_exists($conn, 'tbl_siswa', 'agama') ? 'agama' : "'' AS agama";
$qSiswa = @mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas, $agamaSelect FROM tbl_siswa WHERE no_induk='$nisEsc' LIMIT 1");
$siswa = $qSiswa ? mysqli_fetch_assoc($qSiswa) : [];
$namaSiswa = (string)($siswa['nama_siswa'] ?? ($_SESSION['nama_siswa'] ?? 'Siswa'));
$kelas = (string)($siswa['kelas'] ?? ($_SESSION['kelas'] ?? ''));
$agama = strtolower(trim((string)($siswa['agama'] ?? '')));
$isIslam = strpos($agama, 'islam') !== false;
$isKatolik = strpos($agama, 'katolik') !== false;

$today = date('Y-m-d');
$month = date('Y-m');
$habits = kihs_habits();
$prayers = kihs_prayers($agama, $hideDzuhur, $hideJumat);
$done = [];
$qDone = @mysqli_query($conn, "SELECT habit_key, prayer_key, submitted_at, score, timeliness_status, photo_path FROM tbl_7kih_jurnal WHERE no_induk='$nisEsc' AND tanggal='$today'");
while ($qDone && ($row = mysqli_fetch_assoc($qDone))) {
    $done[$row['habit_key'] . '|' . (string)$row['prayer_key']] = $row;
}

$expectedToday = count($habits) - 1 + count($prayers); // 6 habits + N prayers
$doneToday = count($done);
$todayPct = $expectedToday > 0 ? min(100, round(($doneToday / $expectedToday) * 100)) : 0;
$avgScore = 0;
if ($doneToday > 0) {
    $avgScore = array_sum(array_map(static fn($r) => (float)$r['score'], $done)) / $doneToday;
}

$history = [];
$monthEsc = mysqli_real_escape_string($conn, $month);
$qHist = @mysqli_query($conn, "
    SELECT tanggal, COUNT(*) AS total, AVG(score) AS avg_score
    FROM tbl_7kih_jurnal
    WHERE no_induk='$nisEsc' AND DATE_FORMAT(tanggal, '%Y-%m')='$monthEsc'
    GROUP BY tanggal
    ORDER BY tanggal DESC
    LIMIT 14
");
while ($qHist && ($row = mysqli_fetch_assoc($qHist))) {
    $history[] = $row;
}
$qMonthStat = @mysqli_query($conn, "
    SELECT COUNT(*) AS total_jurnal, AVG(score) AS avg_score_month, COUNT(DISTINCT tanggal) AS total_hari
    FROM tbl_7kih_jurnal
    WHERE no_induk='$nisEsc' AND DATE_FORMAT(tanggal, '%Y-%m')='$monthEsc'
");
$monthStat = $qMonthStat ? mysqli_fetch_assoc($qMonthStat) : ['total_jurnal'=>0, 'avg_score_month'=>0, 'total_hari'=>0];
$totalJurnalBulanIni = (int)($monthStat['total_jurnal'] ?? 0);
$avgScoreBulanIni = (float)($monthStat['avg_score_month'] ?? 0);

// --- Hitung Bonus Apresiasi Ketepatan Waktu Tugas ---
$bonusTugas = 0;
$onTimeCount = 0;
$lateCount = 0;

$qTugasProg = @mysqli_query($conn, "
    SELECT t.batas_waktu, ts.waktu_submit 
    FROM tbl_tugas t 
    JOIN tbl_tugas_siswa ts ON t.id = ts.id_tugas 
    WHERE ts.no_induk_siswa = '$nisEsc' AND ts.status = 'Selesai' AND DATE_FORMAT(ts.waktu_submit, '%Y-%m') = '$bulanNow'
");
if ($qTugasProg) {
    while($row = mysqli_fetch_assoc($qTugasProg)) {
        if ($row['batas_waktu'] && strtotime($row['waktu_submit']) > strtotime($row['batas_waktu'])) {
            $lateCount++;
        } else {
            $onTimeCount++;
        }
    }
}

$qLitProg = @mysqli_query($conn, "
    SELECT t.batas_waktu, p.waktu_selesai 
    FROM tbl_literasi_tugas t 
    JOIN tbl_literasi_progress p ON t.id = p.id_tugas 
    WHERE p.no_induk_siswa = '$nisEsc' AND p.status = 'Selesai' AND DATE_FORMAT(p.waktu_selesai, '%Y-%m') = '$bulanNow'
");
if ($qLitProg) {
    while($row = mysqli_fetch_assoc($qLitProg)) {
        if ($row['batas_waktu'] && strtotime($row['waktu_selesai']) > strtotime($row['batas_waktu'])) {
            $lateCount++;
        } else {
            $onTimeCount++;
        }
    }
}

// Tambahkan 5 poin untuk setiap tugas tepat waktu, kurangi 2 poin untuk terlambat.
$bonusTugas = ($onTimeCount * 5) - ($lateCount * 2);
$avgScoreBulanIni += $bonusTugas;
if ($avgScoreBulanIni < 0) $avgScoreBulanIni = 0;
$hariAktifBulanIni = (int)($monthStat['total_hari'] ?? 0);
$daysInMonth = (int)date('t', strtotime($today));
$expectedMonth = $daysInMonth * $expectedToday;
$pctMonth = $expectedMonth > 0 ? min(100, round(($totalJurnalBulanIni / $expectedMonth) * 100)) : 0;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jurnal 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat) - SIMANIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg,#e0f2fe,#f8fafc 46%,#dcfce7); min-height:100vh; padding-bottom:78px; }
        .phone { max-width:480px; margin:0 auto; padding:16px; }
        .card { background:rgba(255,255,255,.95); border:1px solid rgba(226,232,240,.9); border-radius:20px; box-shadow:0 12px 30px rgba(15,23,42,.08); }
        .habit-btn.done, .habit-block.done { border-color:#16a34a; background:#f0fdf4; }
        .camera-modal { position:fixed; inset:0; background:rgba(15,23,42,.72); display:none; place-items:center; z-index:50; padding:16px; }
        .camera-modal.open { display:grid; }
        video, canvas, #previewImg { transform:scaleX(-1); }
        /* ── BOTTOM NAV ── */
        :root { --bottom-h: 70px; --card: #ffffff; --muted: #64748b; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; height: calc(var(--bottom-h) + env(safe-area-inset-bottom)); background: var(--card); box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.08); display: flex; align-items: flex-start; justify-content: space-around; padding-top: 12px; padding-bottom: env(safe-area-inset-bottom); z-index: 100; border-radius: 24px 24px 0 0; }
        .bnav-item { display: flex; flex-direction: column; align-items: center; gap: 4px; text-decoration: none; color: var(--muted); flex: 1; }
        .bnav-item i { font-size: 1.3rem; }
        .bnav-label { font-size: 0.65rem; font-weight: 600; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="../../assets/js/face-api.min.js"></script>
</head>
<body>
<main class="phone">
    <section class="rounded-[24px] bg-gradient-to-br from-emerald-700 to-slate-900 text-white p-5 shadow-xl">
        <a href="siswa.php" class="text-white/80 text-sm font-bold"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <h1 class="text-2xl font-black mt-3">Jurnal 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat)</h1>
        <p class="text-white/70 text-sm mt-1">Tujuh Kebiasaan Anak Indonesia Hebat. Ambil selfie sebagai bukti aktivitas, lalu kirim jurnal harian.</p>
        <div class="grid grid-cols-3 gap-2 mt-4">
            <div class="bg-white/12 rounded-2xl p-3">
                <div class="text-xs text-white/65">Hari ini</div>
                <div class="text-xl font-black"><?= (int)$doneToday; ?>/<?= (int)$expectedToday; ?></div>
            </div>
            <div class="bg-white/12 rounded-2xl p-3">
                <div class="text-xs text-white/65">Progres</div>
                <div class="text-xl font-black"><?= (int)$todayPct; ?>%</div>
            </div>
            <div class="bg-white/12 rounded-2xl p-3">
                <div class="text-xs text-white/65">Skor</div>
                <div class="text-xl font-black"><?= number_format($avgScore, 0); ?></div>
            </div>
        </div>
    </section>

    <section class="card p-4 mt-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="font-black text-slate-900">Pilih Jurnal</h2>
                <p class="text-xs text-slate-500"><?= kihs_h($namaSiswa); ?><?= $kelas !== '' ? ' - ' . kihs_h($kelas) : ''; ?></p>
            </div>
            <span class="text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-700 px-3 py-1">Agama: <?= kihs_h(ucfirst($agama)) ?: 'Umum'; ?></span>
        </div>

        <div class="space-y-3">
            <?php foreach ($habits as $key => $habit): ?>
                <?php if ($key === 'beribadah'): ?>
                    <?php 
                        $isAllPrayersDone = false;
                        if (!empty($prayers)) {
                            $c = 0;
                            foreach ($prayers as $pk => $pv) {
                                if (isset($done['beribadah|' . $pk])) $c++;
                            }
                            $isAllPrayersDone = ($c === count($prayers));
                        }
                    ?>
                    <div class="habit-block border rounded-2xl p-3 transition-colors <?= $isAllPrayersDone ? 'done' : 'border-slate-200' ?>">
                        <button type="button" class="w-full text-left flex gap-3 items-start mb-2 <?= $isAllPrayersDone ? 'done' : '' ?>" onclick="document.getElementById('prayers-grid').classList.toggle('hidden')" data-title="Semua Ibadah">
                            <div class="w-11 h-11 rounded-2xl <?= $isAllPrayersDone ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700' ?> grid place-items-center transition-colors">
                                <i class="fa-solid <?= $isAllPrayersDone ? 'fa-check' : kihs_h($habit['icon']); ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-black <?= $isAllPrayersDone ? 'text-emerald-700' : 'text-slate-900' ?>"><?= kihs_h($habit['label']); ?> <?= $isAllPrayersDone ? '<span class="text-[10px] bg-emerald-100 px-2 py-0.5 rounded-full ml-1">Selesai Semua</span>' : '' ?></div>
                                <div class="text-xs <?= $isAllPrayersDone ? 'text-emerald-600/70' : 'text-slate-500' ?>"><?= $isAllPrayersDone ? 'Alhamdulillah, semua kewajiban hari ini tertunaikan.' : 'Isi daftar ibadah sesuai keyakinan (Klik)' ?></div>
                            </div>
                            <i class="fa-solid fa-chevron-down <?= $isAllPrayersDone ? 'text-emerald-400' : 'text-slate-400' ?> mt-2 transition-transform"></i>
                        </button>
                        <div id="prayers-grid" class="grid grid-cols-2 gap-2 hidden pt-3 border-t border-slate-100 mt-1">
                            <?php foreach ($prayers as $pKey => $prayer): ?>
                                <?php $d = $done[$key . '|' . $pKey] ?? null; ?>
                                <button type="button" class="habit-btn <?= $d ? 'done bg-emerald-50/50 border-emerald-300' : 'border-slate-200'; ?> text-left border rounded-xl p-3 transition-colors" data-habit="<?= kihs_h($key); ?>" data-prayer="<?= kihs_h($pKey); ?>" data-title="<?= kihs_h($prayer['label']); ?>">
                                    <div class="font-black text-sm"><?= kihs_h($prayer['label']); ?></div>
                                    <div class="text-[11px] text-slate-500"><?= kihs_h($prayer['start']); ?>-<?= kihs_h($prayer['end']); ?></div>
                                    <div class="text-[11px] mt-1 <?= $d ? 'text-emerald-700' : 'text-slate-400'; ?>"><?= $d ? 'Terkirim ' . date('H:i', strtotime($d['submitted_at'])) : 'Belum diisi'; ?></div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                        $d = $done[$key . '|'] ?? null; 
                        $disabled = ($key === 'tidur_cepat' && date('H:i') > '21:00') ? 'opacity-50 pointer-events-none' : '';
                    ?>
                    <button type="button" class="habit-btn <?= $d ? 'done bg-emerald-50/50 border-emerald-300' : 'border-slate-200'; ?> <?= $disabled ?> w-full text-left border rounded-2xl p-3 flex gap-3 items-start transition-colors" data-habit="<?= kihs_h($key); ?>" data-prayer="" data-title="<?= kihs_h($habit['label']); ?>">
                        <div class="w-11 h-11 rounded-2xl <?= $d ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700' ?> grid place-items-center transition-colors">
                            <i class="fa-solid <?= $d ? 'fa-check' : kihs_h($habit['icon']); ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between gap-2">
                                <div class="font-black <?= $d ? 'text-emerald-700' : 'text-slate-900' ?>">
                                    <?= kihs_h($habit['label']); ?>
                                    <?= $d ? '<span class="text-[10px] bg-emerald-100 px-2 py-0.5 rounded-full ml-1">Selesai</span>' : '' ?>
                                </div>
                                <span class="text-[11px] <?= $d ? 'text-emerald-600/70' : 'text-slate-500' ?>"><?= kihs_h($habit['start']); ?>-<?= kihs_h($habit['end']); ?></span>
                            </div>
                            <div class="text-xs <?= $d ? 'text-emerald-600/70' : 'text-slate-500' ?>"><?= kihs_h($habit['hint']); ?></div>
                            <div class="text-[11px] mt-1 <?= $d ? 'text-emerald-700' : 'text-slate-400'; ?>">
                                <?php if($key === 'tidur_cepat' && date('H:i') > '21:00' && !$d): ?>
                                    <span class="text-red-500">Waktu habis (lewat 21:00)</span>
                                <?php else: ?>
                                    <?= $d ? 'Terkirim ' . date('H:i', strtotime($d['submitted_at'])) : 'Belum diisi'; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

        <section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-3"><i class="fa-solid fa-chart-pie text-emerald-600 mr-1"></i> Rekapitulasi</h2>
        
        <!-- Rekap Harian -->
        <div class="mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Hari Ini</h3>
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <?php 
                    foreach ($habits as $key => $habit) {
                        if ($key === 'beribadah') {
                            foreach ($prayers as $pKey => $prayer) {
                                $isDone = isset($done[$key . '|' . $pKey]);
                                $color = $isDone ? 'text-emerald-600' : 'text-slate-400';
                                $icon = $isDone ? 'fa-circle-check' : 'fa-clock';
                                $label = $prayer['label'];
                                echo "<div class='flex items-center gap-1.5 $color truncate' title='$label'><i class='fa-solid $icon'></i> <span class='truncate'>$label</span></div>";
                            }
                        } else {
                            $isDone = isset($done[$key . '|']);
                            $color = $isDone ? 'text-emerald-600' : 'text-slate-400';
                            $icon = $isDone ? 'fa-circle-check' : 'fa-clock';
                            $label = $habit['label'];
                            echo "<div class='flex items-center gap-1.5 $color truncate' title='$label'><i class='fa-solid $icon'></i> <span class='truncate'>$label</span></div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Rekap Bulanan -->
        <div>
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Bulan Ini (<?= date('M Y') ?>)</h3>
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-emerald-600 font-bold uppercase">Total Terkirim</div>
                        <div class="text-lg font-black text-emerald-900"><?= $totalJurnalBulanIni ?> <span class="text-xs font-normal text-emerald-700">/ <?= $expectedMonth ?></span></div>
                    </div>
                    <i class="fa-solid fa-list-check text-2xl text-emerald-200"></i>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-blue-600 font-bold uppercase">Skor Akhir + Apresiasi</div>
                        <div class="text-lg font-black text-blue-900"><?= number_format($avgScoreBulanIni, 1) ?></div>
                        <div class="text-[9px] text-blue-500 mt-1">Termasuk Bonus Tugas: <?= $bonusTugas > 0 ? "+".$bonusTugas : $bonusTugas ?> Poin (<?= $onTimeCount ?> Tepat, <?= $lateCount ?> Telat)</div>
                    </div>
                    <i class="fa-solid fa-star text-2xl text-blue-200"></i>
                </div>
                <div class="col-span-2 bg-slate-50 border border-slate-100 rounded-xl p-3">
                    <div class="flex justify-between items-center mb-1">
                        <div class="text-[10px] text-slate-500 font-bold uppercase">Tingkat Penyelesaian</div>
                        <div class="text-xs font-black text-slate-700"><?= $pctMonth ?>%</div>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5">
                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?= $pctMonth ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-2">Riwayat Bulan Ini</h2>
        <?php if (empty($history)): ?>
            <p class="text-sm text-slate-500">Belum ada jurnal bulan ini.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($history as $row): ?>
                    <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2">
                        <div>
                            <div class="font-bold text-sm"><?= date('d M Y', strtotime($row['tanggal'])); ?></div>
                            <div class="text-xs text-slate-500"><?= (int)$row['total']; ?> jurnal terkirim</div>
                        </div>
                        <div class="font-black text-emerald-700"><?= number_format((float)$row['avg_score'], 0); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<div id="cameraModal" class="camera-modal">
    <div class="card p-4 w-full max-w-sm">
        <div class="flex justify-between gap-3 items-start mb-3">
            <div>
                <h3 id="modalTitle" class="font-black text-slate-900">Ambil Selfie</h3>
                <p class="text-xs text-slate-500">Foto dikompres otomatis sebelum dikirim.</p>
            </div>
            <button id="btnClose" class="w-9 h-9 rounded-full bg-slate-100 text-slate-600" type="button"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="relative rounded-2xl overflow-hidden bg-slate-900 aspect-square grid place-items-center">
            <video id="video" autoplay playsinline class="w-full h-full object-cover"></video>
            <img id="previewImg" class="w-full h-full object-cover hidden" alt="Preview selfie">
            <canvas id="canvas" class="hidden"></canvas>
            <!-- Face Detection Overlay -->
            <div id="faceOverlay" class="absolute inset-0 flex items-center justify-center pointer-events-none z-10 hidden">
                <div id="faceBox" class="w-[65%] aspect-[3/4] rounded-[45%] border-[3px] border-dashed border-white/50 transition-all duration-300 flex flex-col items-center justify-center shadow-[0_0_0_999px_rgba(0,0,0,0.6)]">
                   <div id="faceIcon" class="text-white/60 text-4xl mb-2 transition-colors duration-300"><i class="fa-solid fa-expand"></i></div>
                   <div id="faceText" class="text-white text-[10px] font-bold text-center px-3 py-1 bg-black/40 rounded-full transition-colors duration-300 backdrop-blur-sm">Posisikan wajah di area ini</div>
                </div>
            </div>
        </div>
        <div id="modalMsg" class="text-xs text-slate-500 mt-3 min-h-5">Buka kamera, posisikan wajah, lalu ambil foto.</div>
        
        <div id="keteranganWrapper" class="hidden mt-3">
            <textarea id="keterangan" class="w-full text-sm p-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" rows="2" placeholder="Isi detail kegiatan..."></textarea>
        </div>

        <div class="grid grid-cols-2 gap-2 mt-3">
            <button id="btnCapture" class="rounded-xl bg-emerald-600 text-white font-black py-3" type="button"><i class="fa-solid fa-camera"></i> Ambil</button>
            <button id="btnSend" class="rounded-xl bg-slate-900 text-white font-black py-3 disabled:opacity-40" type="button" disabled><i class="fa-solid fa-paper-plane"></i> Kirim</button>
        </div>
    </div>
</div>

<?php include 'siswa_footer.php'; ?>


<script>
let stream = null;
let currentHabit = '';
let currentPrayer = '';
let photoData = '';
let currentLat = '';
let currentLng = '';
const isIslam = <?= $isIslam ? 'true' : 'false' ?>;

const modal = document.getElementById('cameraModal');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const previewImg = document.getElementById('previewImg');
const modalTitle = document.getElementById('modalTitle');
const modalMsg = document.getElementById('modalMsg');
const btnSend = document.getElementById('btnSend');
const keteranganWrapper = document.getElementById('keteranganWrapper');
const keteranganInput = document.getElementById('keterangan');

const faceBox = document.getElementById('faceBox');
const faceIcon = document.getElementById('faceIcon');
const faceText = document.getElementById('faceText');
const faceOverlay = document.getElementById('faceOverlay');
let isFaceApiLoaded = false;
let faceDetectionInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof faceapi === 'undefined') {
        console.error('Face API script belum dimuat!');
        return;
    }
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('../../assets/models'),
        faceapi.nets.faceLandmark68TinyNet.loadFromUri('../../assets/models')
    ]).then(() => {
        isFaceApiLoaded = true;
        console.log('Face API Models Loaded');
    }).catch(err => console.error('Gagal memuat model:', err));
});

async function startFaceDetection() {
    if (!isFaceApiLoaded) return;
    
    const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
    
    faceDetectionInterval = setInterval(async () => {
        if (video.paused || video.ended || previewImg.classList.contains('hidden') === false) return;
        
        const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks(true);
        
        let isStrictValid = false;
        let rejectReason = 'Posisikan mata, hidung, dan mulut tepat di area ini';
        
        if (detection && detection.landmarks && detection.detection.score > 0.55) {
            const box = detection.detection.box;
            const vw = video.videoWidth;
            
            // Wajah harus lumayan di tengah dan cukup besar
            const isCentered = box.x > vw * 0.1 && (box.x + box.width) < vw * 0.9 && box.width > vw * 0.2;
            
            const leftEye = detection.landmarks.getLeftEye();
            const rightEye = detection.landmarks.getRightEye();
            const nose = detection.landmarks.getNose();
            
            if (leftEye && rightEye && nose) {
                const lx = leftEye.reduce((s, p) => s + p.x, 0) / leftEye.length;
                const rx = rightEye.reduce((s, p) => s + p.x, 0) / rightEye.length;
                const nx = nose[nose.length - 1].x;
                
                const leftDist = Math.abs(nx - lx);
                const rightDist = Math.abs(rx - nx);
                
                // Rasio tidak boleh terlalu besar (menghindari muka miring / noleh samping)
                const ratio = Math.max(leftDist, rightDist) / (Math.min(leftDist, rightDist) || 1);
                
                if (!isCentered) {
                    rejectReason = 'Wajah tidak berada di tengah!';
                } else if (ratio >= 2.5) {
                    rejectReason = 'Harap menghadap lurus ke depan!';
                } else {
                    isStrictValid = true;
                }
            }
        }
        
        if (isStrictValid) {
            faceBox.classList.replace('border-white/50', 'border-emerald-400');
            faceBox.classList.replace('border-dashed', 'border-solid');
            faceIcon.classList.replace('text-white/60', 'text-emerald-400');
            faceText.textContent = 'Wajah (Mata, Hidung, Mulut) terdeteksi presisi. Silakan ambil foto.';
            faceText.classList.replace('bg-black/40', 'bg-emerald-600/90');
            
            document.getElementById('btnCapture').disabled = false;
            document.getElementById('btnCapture').classList.remove('opacity-50', 'pointer-events-none');
        } else {
            faceBox.classList.replace('border-emerald-400', 'border-white/50');
            faceBox.classList.replace('border-solid', 'border-dashed');
            faceIcon.classList.replace('text-emerald-400', 'text-white/60');
            faceText.textContent = rejectReason;
            faceText.classList.replace('bg-emerald-600/90', 'bg-black/40');
            
            document.getElementById('btnCapture').disabled = true;
            document.getElementById('btnCapture').classList.add('opacity-50', 'pointer-events-none');
        }
    }, 200);
}

function stopFaceDetection() {
    if (faceDetectionInterval) {
        clearInterval(faceDetectionInterval);
        faceDetectionInterval = null;
    }
}


async function openCamera(btn) {
    if (btn.classList.contains('done')) {
        const title = btn.dataset.title || 'Jurnal';
        let timeStr = 'hari ini';
        const timeDiv = Array.from(btn.querySelectorAll('div')).find(el => el.textContent.includes('Terkirim'));
        if (timeDiv) {
            timeStr = timeDiv.textContent.replace('Terkirim ', 'pada jam ');
        }
        Swal.fire({
            icon: 'info',
            title: 'Sudah Diisi',
            html: `<b>${title}</b> sudah diisi ${timeStr}.<br><br><span style="font-size:0.9em; color:#64748b;">Jurnal ini sifatnya hanya diisi sekali dalam sehari dan akan ter-reset otomatis besok.</span>`,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#059669'
        });
        return;
    }

    currentHabit = btn.dataset.habit || '';
    currentPrayer = btn.dataset.prayer || '';
    photoData = '';
    currentLat = '';
    currentLng = '';
    
    modalTitle.textContent = btn.dataset.title || 'Ambil Selfie';
    modalMsg.textContent = 'Mengaktifkan kamera...';
    keteranganInput.value = '';
    
    // Show/Hide Keterangan Form
    const needsKeterangan = ['bangun_pagi', 'berolahraga', 'makan_sehat', 'gemar_belajar', 'bermasyarakat'].includes(currentHabit) || (!isIslam && currentHabit === 'beribadah');
    if (needsKeterangan) {
        keteranganWrapper.classList.remove('hidden');
    } else {
        keteranganWrapper.classList.add('hidden');
    }

    btnSend.disabled = true;
    previewImg.classList.add('hidden');
    video.classList.remove('hidden');
    modal.classList.add('open');
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        video.srcObject = stream;
        document.getElementById('btnCapture').innerHTML = '<i class="fa-solid fa-camera"></i> Ambil';
        document.getElementById('btnCapture').disabled = true;
        document.getElementById('btnCapture').classList.add('opacity-50', 'pointer-events-none');
        faceOverlay.classList.remove('hidden');
        
        if (!isFaceApiLoaded) {
            modalMsg.textContent = 'Memuat modul pendeteksi wajah... tunggu sebentar.';
            const checkLoad = setInterval(() => {
                if (isFaceApiLoaded) {
                    clearInterval(checkLoad);
                    modalMsg.textContent = 'Posisikan mata, hidung, dan mulut terlihat jelas.';
                    startFaceDetection();
                }
            }, 500);
        } else {
            modalMsg.textContent = 'Posisikan mata, hidung, dan mulut terlihat jelas.';
            startFaceDetection();
        }
    } catch (err) {
        modalMsg.textContent = 'Kamera tidak dapat dibuka: ' + err.message;
    }
}

function closeCamera() {
    stopFaceDetection();
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    modal.classList.remove('open');
}

function capturePhoto() {
    if (!previewImg.classList.contains('hidden')) {
        // Mode: ULANGI FOTO
        previewImg.classList.add('hidden');
        video.classList.remove('hidden');
        faceOverlay.classList.remove('hidden');
        btnSend.disabled = true;
        photoData = '';
        
        // Reset capture button to wait for face detection
        const btnCapture = document.getElementById('btnCapture');
        btnCapture.disabled = true;
        btnCapture.classList.add('opacity-50', 'pointer-events-none');
        btnCapture.innerHTML = '<i class="fa-solid fa-camera"></i> Ambil';
        modalMsg.textContent = 'Posisikan mata, hidung, dan mulut terlihat jelas.';
        
        return; // Exit here so it goes back to detection mode
    }

    // Mode: AMBIL FOTO
    if (!video.videoWidth) {
        modalMsg.textContent = 'Kamera belum siap.';
        return;
    }
    const max = 420;
    const ratio = Math.min(max / video.videoWidth, max / video.videoHeight, 1);
    canvas.width = Math.round(video.videoWidth * ratio);
    canvas.height = Math.round(video.videoHeight * ratio);
    const ctx = canvas.getContext('2d');
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    photoData = canvas.toDataURL('image/jpeg', 0.38);
    previewImg.src = photoData;
    previewImg.classList.remove('hidden');
    video.classList.add('hidden');
    faceOverlay.classList.add('hidden'); // Sembunyikan bingkai setelah ambil foto
    
    document.getElementById('btnCapture').innerHTML = '<i class="fa-solid fa-rotate-left"></i> Ulangi';
    document.getElementById('btnCapture').disabled = false;
    document.getElementById('btnCapture').classList.remove('opacity-50', 'pointer-events-none');
    
    btnSend.disabled = false;
    modalMsg.textContent = 'Foto siap dikirim. Ukuran sudah diperkecil.';

    // Check GPS if needed
    if (currentHabit === 'beribadah' && (currentPrayer === 'dzuhur' || currentPrayer === 'jumat')) {
        modalMsg.textContent = 'Meminta akses lokasi untuk verifikasi mushola...';
        btnSend.disabled = true;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                pos => {
                    currentLat = pos.coords.latitude;
                    currentLng = pos.coords.longitude;
                    modalMsg.textContent = 'Lokasi berhasil didapatkan. Siap dikirim.';
                    btnSend.disabled = false;
                },
                err => {
                    modalMsg.textContent = 'Akses lokasi ditolak. Ibadah ini membutuhkan verifikasi lokasi.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            modalMsg.textContent = 'Perangkat tidak mendukung GPS.';
        }
    }
}

async function sendJournal() {
    if (!photoData) {
        modalMsg.textContent = 'Ambil foto terlebih dahulu.';
        return;
    }
    
    if (!keteranganWrapper.classList.contains('hidden')) {
        const ket = keteranganInput.value.trim();
        if (!ket) {
            Swal.fire({ icon: 'warning', title: 'Belum Diisi', text: 'Keterangan kegiatan harus diisi terlebih dahulu.', confirmButtonColor: '#059669' });
            return;
        }
        const words = ket.split(/\s+/).filter(w => w.length > 0);
        // User requested minimum 1 word, which is handled by !ket check above.
        // No further word count checking needed.
    }

    btnSend.disabled = true;
    modalMsg.textContent = 'Mengirim jurnal...';
    const form = new FormData();
    form.append('habit_key', currentHabit);
    form.append('prayer_key', currentPrayer);
    form.append('photo_data', photoData);
    if (!keteranganWrapper.classList.contains('hidden')) {
        form.append('keterangan', keteranganInput.value);
    }
    if (currentLat && currentLng) {
        form.append('lat', currentLat);
        form.append('lng', currentLng);
    }
    try {
        const res = await fetch('../../api/jurnal_7kih_save.php', { method: 'POST', body: form });
        const json = await res.json();
        if (!json.success) {
            modalMsg.textContent = json.message || 'Gagal mengirim jurnal.';
            btnSend.disabled = false;
            return;
        }
        modalMsg.textContent = json.message;
        setTimeout(() => window.location.reload(), 700);
    } catch (err) {
        modalMsg.textContent = 'Gagal mengirim: ' + err.message;
        btnSend.disabled = false;
    }
}

document.querySelectorAll('.habit-btn').forEach(btn => btn.addEventListener('click', () => openCamera(btn)));
document.getElementById('btnClose').addEventListener('click', closeCamera);
document.getElementById('btnCapture').addEventListener('click', capturePhoto);
document.getElementById('btnSend').addEventListener('click', sendJournal);
</script>
</body>
</html>
