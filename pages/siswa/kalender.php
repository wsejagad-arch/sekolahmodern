<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk'])) {
    header('Location: ../../index.php?haruslogin'); exit;
}
if ($_SESSION['hak_akses'] != 3) {
    header('Location: ../../403.php'); exit;
}

require_once '../../koneksi.php';
require_once '../../functions.php';

date_default_timezone_set('Asia/Jakarta');
$noInduk   = $_SESSION['no_induk'];
$namaSiswa = $_SESSION['nama'] ?? $noInduk;
$kelas     = $_SESSION['kelas'] ?? '';
$lembaga   = function_exists('data_lembaga') ? data_lembaga() : [];

// ── Navigasi Bulan ────────────────────────────────────────────────────────────
$today     = date('Y-m-d');
$todayDay  = (int)date('d');
$reqMonth  = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$reqYear   = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$reqMonth  = max(1, min(12, $reqMonth));
$reqYear   = max(2020, min(2040, $reqYear));

$firstDay  = mktime(0,0,0,$reqMonth,1,$reqYear);
$daysInMonth = (int)date('t', $firstDay);
$startWday = (int)date('N', $firstDay); // 1=Mon … 7=Sun
$monthName = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'][$reqMonth];

$prevM = $reqMonth - 1; $prevY = $reqYear;
if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $reqMonth + 1; $nextY = $reqYear;
if ($nextM > 12) { $nextM = 1; $nextY++; }

// ── Hari Libur Nasional ───────────────────────────────────────────────────────
$libur = [
    // 2025
    '2025-01-01'=>'Tahun Baru Masehi',
    '2025-01-27'=>'Isra Mi\'raj Nabi Muhammad SAW',
    '2025-01-29'=>'Tahun Baru Imlek 2576',
    '2025-03-29'=>'Wafat Isa Almasih',
    '2025-03-31'=>'Hari Suci Nyepi',
    '2025-04-18'=>'Idul Fitri 1446 H',
    '2025-04-19'=>'Idul Fitri 1446 H',
    '2025-04-20'=>'Paskah',
    '2025-05-01'=>'Hari Buruh Internasional',
    '2025-05-12'=>'Hari Raya Waisak',
    '2025-05-29'=>'Kenaikan Isa Almasih',
    '2025-06-01'=>'Hari Lahir Pancasila',
    '2025-06-06'=>'Idul Adha 1446 H',
    '2025-06-27'=>'Tahun Baru Islam 1447 H',
    '2025-08-17'=>'HUT Kemerdekaan RI ke-80',
    '2025-09-05'=>'Maulid Nabi Muhammad SAW',
    '2025-12-25'=>'Hari Raya Natal',
    '2025-12-26'=>'Cuti Bersama Natal',
    // 2026
    '2026-01-01'=>'Tahun Baru Masehi',
    '2026-01-17'=>'Isra Mi\'raj Nabi Muhammad SAW',
    '2026-01-28'=>'Tahun Baru Imlek 2577',
    '2026-03-20'=>'Hari Suci Nyepi',
    '2026-04-03'=>'Wafat Isa Almasih',
    '2026-04-05'=>'Paskah',
    '2026-04-06'=>'Idul Fitri 1447 H',
    '2026-04-07'=>'Idul Fitri 1447 H',
    '2026-05-01'=>'Hari Buruh Internasional',
    '2026-05-14'=>'Kenaikan Isa Almasih',
    '2026-05-20'=>'Hari Kebangkitan Nasional',
    '2026-06-01'=>'Hari Lahir Pancasila',
    '2026-06-13'=>'Hari Raya Waisak',
    '2026-06-14'=>'Idul Adha 1447 H',
    '2026-07-16'=>'Tahun Baru Islam 1448 H',
    '2026-08-17'=>'HUT Kemerdekaan RI ke-81',
    '2026-09-24'=>'Maulid Nabi Muhammad SAW',
    '2026-12-25'=>'Hari Raya Natal',
];

// ── Jadwal Pelajaran per Hari ──────────────────────────────────────────────────
$hariNames = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
$jadwalByHari = []; // ['Senin' => [...], ...]
$kelasEsc = mysqli_real_escape_string($conn, $kelas);
$qJ = mysqli_query($conn, "SELECT nama_mapel, hari, jam_mulai, jam_selesai
                            FROM tbl_mapel_ampu
                            WHERE kelas = '$kelasEsc'
                            ORDER BY hari, jam_mulai ASC");
if ($qJ) while ($r = mysqli_fetch_assoc($qJ)) {
    $jadwalByHari[$r['hari']][] = $r;
}

// ── Pengumuman di bulan ini ────────────────────────────────────────────────────
$firstDateStr = sprintf('%04d-%02d-01', $reqYear, $reqMonth);
$lastDateStr  = sprintf('%04d-%02d-%02d', $reqYear, $reqMonth, $daysInMonth);
$tingkat = strtok(trim($kelas), ' ');
$tingkatEsc = mysqli_real_escape_string($conn, $tingkat);
$whereScope = "(p.target_scope = 'SEMUA'
               OR (p.target_scope = 'KELAS'   AND p.target_value = '$kelasEsc')
               OR (p.target_scope = 'TINGKAT' AND p.target_value = '$tingkatEsc'))";
$qP = mysqli_query($conn, "SELECT id, judul, mulai, selesai, penting FROM tbl_pengumuman p
                            WHERE p.selesai >= '$firstDateStr' AND p.mulai <= '$lastDateStr'
                              AND $whereScope
                            ORDER BY p.mulai ASC");
$pengumumanList = [];
if ($qP) while ($r = mysqli_fetch_assoc($qP)) $pengumumanList[] = $r;

// Map setiap pengumuman ke array hari di bulan ini yang tercover
$pengumumanByDate = []; // ['2026-03-05' => [list]]
foreach ($pengumumanList as $p) {
    $cur = max(strtotime($p['mulai']), strtotime($firstDateStr));
    $end = min(strtotime($p['selesai']), strtotime($lastDateStr));
    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $pengumumanByDate[$d][] = $p;
        $cur = strtotime('+1 day', $cur);
    }
}

// ── Selected day detail ───────────────────────────────────────────────────────
$selDay   = isset($_GET['d']) ? (int)$_GET['d'] : (int)date('d');
$selDay   = max(1, min($daysInMonth, $selDay));
$selDate  = sprintf('%04d-%02d-%02d', $reqYear, $reqMonth, $selDay);
$selWday  = (int)date('N', strtotime($selDate)); // 1=Mon…7=Sun
$selHari  = $hariNames[$selWday - 1];
$selJadwal     = $jadwalByHari[$selHari] ?? [];
$selLibur      = $libur[$selDate] ?? null;
$selPengumuman = $pengumumanByDate[$selDate] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <title>Kalender – <?= htmlspecialchars($lembaga['nama_sekolah'] ?? 'Jurnal') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body{background:#f1f5f9;font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;}
    .day-cell{min-height:52px;cursor:pointer;transition:background .12s;}
    .day-cell:hover{background:#e0f2fe;}
    .day-cell.today{background:#dbeafe;}
    .day-cell.selected{background:#bfdbfe;border:2px solid #3b82f6;}
    .day-cell.libur .day-num{color:#ef4444;}
    .day-cell.minggu .day-num{color:#f87171;}
    .pill{display:inline-block;font-size:9px;border-radius:4px;padding:1px 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}
  </style>
</head>
<body>

<!-- TOP BAR -->
<div class="sticky top-0 z-40 bg-gradient-to-r from-indigo-600 to-blue-500 text-white shadow-lg">
  <div class="flex items-center justify-between px-4 py-3 max-w-3xl mx-auto">
    <a href="siswa.php" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 transition">
      <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <div class="text-center">
      <h1 class="font-bold text-base">Kalender Akademik</h1>
      <p class="text-xs text-white/70"><?= htmlspecialchars($kelas) ?></p>
    </div>
    <div class="w-9 h-9 flex items-center justify-center rounded-full bg-white/20">
      <i class="fas fa-calendar-alt text-sm"></i>
    </div>
  </div>
</div>

<div class="max-w-3xl mx-auto px-3 py-4 pb-10">

  <!-- BULAN NAVIGASI -->
  <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <div class="flex items-center justify-between">
      <a href="?m=<?= $prevM ?>&y=<?= $prevY ?>"
         class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition">
        <i class="fas fa-chevron-left"></i>
      </a>
      <div class="text-center">
        <h2 class="text-xl font-bold text-gray-800"><?= $monthName ?></h2>
        <p class="text-gray-500 text-sm"><?= $reqYear ?></p>
      </div>
      <a href="?m=<?= $nextM ?>&y=<?= $nextY ?>"
         class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition">
        <i class="fas fa-chevron-right"></i>
      </a>
    </div>

    <!-- HARI GRID HEADER -->
    <div class="grid grid-cols-7 mt-4 text-center text-xs font-bold text-gray-400 uppercase mb-1">
      <?php foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d): ?>
        <div class="py-1"><?= $d ?></div>
      <?php endforeach; ?>
    </div>

    <!-- HARI GRID BODY -->
    <div class="grid grid-cols-7 gap-0.5">
      <?php
      // Padding kosong sebelum hari pertama
      for ($pad = 1; $pad < $startWday; $pad++):
      ?>
        <div class="day-cell rounded-xl"></div>
      <?php endfor;

      for ($d = 1; $d <= $daysInMonth; $d++):
        $dateStr   = sprintf('%04d-%02d-%02d', $reqYear, $reqMonth, $d);
        $wday      = (int)date('N', strtotime($dateStr));  // 1=Mon…7=Sun
        $hariName  = $hariNames[$wday - 1];
        $isToday   = ($dateStr === $today);
        $isSel     = ($d === $selDay);
        $isLibur   = isset($libur[$dateStr]);
        $isMinggu  = ($wday === 7);
        $hasJadwal = !empty($jadwalByHari[$hariName]) && !$isLibur && !$isMinggu;
        $hasPengum = !empty($pengumumanByDate[$dateStr]);

        $classes = 'day-cell rounded-xl p-1 ';
        if ($isSel)   $classes .= 'selected ';
        elseif ($isToday) $classes .= 'today ';
        if ($isLibur) $classes .= 'libur ';
        if ($isMinggu) $classes .= 'minggu ';
      ?>
        <div class="<?= $classes ?>"
             onclick="window.location.href='?m=<?= $reqMonth ?>&y=<?= $reqYear ?>&d=<?= $d ?>'">
          <div class="day-num text-xs font-bold text-center <?= $isToday || $isSel ? 'text-blue-600' : ($isLibur ? 'text-red-500' : ($isMinggu ? 'text-red-300' : 'text-gray-700')) ?>">
            <?= $d ?>
            <?php if ($isToday): ?>
              <span class="block w-1.5 h-1.5 rounded-full bg-blue-500 mx-auto mt-0.5"></span>
            <?php endif; ?>
          </div>
          <?php if ($hasJadwal): ?>
            <div class="mt-0.5 flex justify-center">
              <span class="pill bg-blue-100 text-blue-700"><?= count($jadwalByHari[$hariName]) ?> mtpl</span>
            </div>
          <?php endif; ?>
          <?php if ($hasPengum): ?>
            <div class="mt-0.5 flex justify-center">
              <span class="pill bg-yellow-100 text-yellow-700"><i class="fas fa-bell" style="font-size:8px;"></i></span>
            </div>
          <?php endif; ?>
          <?php if ($isLibur): ?>
            <div class="mt-0.5 flex justify-center">
              <span class="pill bg-red-100 text-red-600"><i class="fas fa-star" style="font-size:7px;"></i></span>
            </div>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>

    <!-- LEGENDA -->
    <div class="flex flex-wrap items-center gap-3 mt-4 pt-3 border-t border-gray-100">
      <div class="flex items-center gap-1.5 text-xs text-gray-500">
        <div class="w-3 h-3 rounded bg-blue-200 border-2 border-blue-400"></div> Dipilih / Hari ini
      </div>
      <div class="flex items-center gap-1.5 text-xs text-gray-500">
        <span class="pill bg-blue-100 text-blue-700">3 mtpl</span> Jadwal pelajaran
      </div>
      <div class="flex items-center gap-1.5 text-xs text-gray-500">
        <span class="pill bg-yellow-100 text-yellow-700"><i class="fas fa-bell text-xs"></i></span> Pengumuman
      </div>
      <div class="flex items-center gap-1.5 text-xs text-gray-500">
        <span class="pill bg-red-100 text-red-600"><i class="fas fa-star" style="font-size:8px;"></i></span> Hari libur
      </div>
    </div>
  </div>

  <!-- DETAIL HARI DIPILIH -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <?php
    $selDateFormatted = date('d', strtotime($selDate));
    $selMonthName = $monthName;
    ?>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-bold text-gray-800 text-base">
          <?= $selHari ?>, <?= (int)$selDateFormatted . ' ' . $monthName . ' ' . $reqYear ?>
        </h3>
        <?php if ($selDate === $today): ?>
          <span class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full font-medium">Hari Ini</span>
        <?php endif; ?>
      </div>
      <div class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-50 text-indigo-600 font-bold text-lg">
        <?= (int)$selDate[8] . $selDate[9] ?>
      </div>
    </div>

    <?php if ($selLibur): ?>
    <div class="flex items-start gap-3 p-3 rounded-xl bg-red-50 border border-red-100 mb-3">
      <div class="w-9 h-9 flex-shrink-0 rounded-full bg-red-100 flex items-center justify-center text-red-500">
        <i class="fas fa-star-and-crescent"></i>
      </div>
      <div>
        <p class="font-semibold text-red-700 text-sm">Hari Libur Nasional</p>
        <p class="text-red-500 text-xs mt-0.5"><?= htmlspecialchars($selLibur) ?></p>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($selWday === 7): ?>
    <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100 mb-3">
      <div class="w-9 h-9 flex-shrink-0 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
        <i class="fas fa-moon"></i>
      </div>
      <div>
        <p class="font-semibold text-gray-500 text-sm">Hari Minggu</p>
        <p class="text-gray-400 text-xs mt-0.5">Tidak ada kegiatan sekolah</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- JADWAL PELAJARAN -->
    <?php if (!empty($selJadwal) && !$selLibur && $selWday !== 7): ?>
    <div class="mb-4">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">
        <i class="fas fa-book mr-1 text-blue-400"></i>Jadwal Pelajaran
      </p>
      <div class="space-y-2">
        <?php foreach ($selJadwal as $j): ?>
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-blue-50 border border-blue-100">
          <div class="w-8 h-8 flex-shrink-0 rounded-lg bg-blue-100 flex items-center justify-center text-blue-500">
            <i class="fas fa-clock text-xs"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 text-sm truncate"><?= htmlspecialchars($j['nama_mapel']) ?></p>
            <p class="text-xs text-gray-500"><?= htmlspecialchars(substr($j['jam_mulai'],0,5)) ?> – <?= htmlspecialchars(substr($j['jam_selesai'],0,5)) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif (!$selLibur && $selWday !== 7): ?>
    <div class="mb-4">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">
        <i class="fas fa-book mr-1 text-blue-400"></i>Jadwal Pelajaran
      </p>
      <p class="text-gray-400 text-sm text-center py-4 italic">Tidak ada jadwal pelajaran</p>
    </div>
    <?php endif; ?>

    <!-- PENGUMUMAN -->
    <?php if (!empty($selPengumuman)): ?>
    <div>
      <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">
        <i class="fas fa-bullhorn mr-1 text-yellow-500"></i>Pengumuman
      </p>
      <div class="space-y-2">
        <?php foreach ($selPengumuman as $p): ?>
        <a href="../../pengumuman.php"
           class="flex items-start gap-3 p-2.5 rounded-xl <?= $p['penting'] ? 'bg-orange-50 border border-orange-200' : 'bg-yellow-50 border border-yellow-100' ?> block hover:opacity-80 transition">
          <div class="w-8 h-8 flex-shrink-0 rounded-lg <?= $p['penting'] ? 'bg-orange-100 text-orange-500' : 'bg-yellow-100 text-yellow-500' ?> flex items-center justify-center flex-shrink-0">
            <i class="fas fa-<?= $p['penting'] ? 'exclamation' : 'bell' ?> text-xs"></i>
          </div>
          <div class="flex-1 min-w-0">
            <?php if ($p['penting']): ?>
              <span class="text-xs bg-orange-200 text-orange-700 px-1.5 py-0.5 rounded font-bold mr-1">PENTING</span>
            <?php endif; ?>
            <p class="font-semibold text-gray-800 text-sm leading-snug"><?= htmlspecialchars($p['judul']) ?></p>
            <p class="text-xs text-gray-500 mt-0.5">
              s/d <?= date('d/m/Y', strtotime($p['selesai'])) ?>
            </p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($selJadwal) && empty($selPengumuman) && !$selLibur && $selWday !== 7): ?>
    <div class="text-center py-6 text-gray-400">
      <i class="fas fa-calendar-day text-4xl mb-2 block opacity-30"></i>
      <p class="text-sm">Tidak ada acara pada hari ini</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- MINGGU INI -->
  <?php
  // Hitung jadwal minggu ini (Senin – Sabtu)
  $weekStart = strtotime('monday this week', strtotime($today));
  $weekDays  = [];
  for ($i = 0; $i < 6; $i++) {
      $ts   = strtotime("+$i day", $weekStart);
      $ds   = date('Y-m-d', $ts);
      $hn   = $hariNames[(int)date('N', $ts) - 1];
      $jd   = isset($jadwalByHari[$hn]) && !isset($libur[$ds]) ? $jadwalByHari[$hn] : [];
      $weekDays[] = ['date'=>$ds,'hari'=>$hn,'jadwal'=>$jd,'isToday'=>$ds===$today,'libur'=>$libur[$ds]??null];
  }
  $hasWeekJadwal = array_filter($weekDays, function($w) { return !empty($w['jadwal']); });
  ?>
  <?php if (!empty($hasWeekJadwal)): ?>
  <div class="bg-white rounded-2xl shadow-sm p-4 mt-4">
    <h3 class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2">
      <i class="fas fa-calendar-week text-indigo-500"></i> Jadwal Minggu Ini
    </h3>
    <div class="space-y-2">
      <?php foreach ($weekDays as $wd):
        if (empty($wd['jadwal'])) continue; ?>
        <div class="flex items-start gap-3 p-3 rounded-xl <?= $wd['isToday'] ? 'bg-indigo-50 border border-indigo-200' : 'bg-gray-50' ?>">
          <div class="text-center w-10 flex-shrink-0">
            <p class="text-xs font-bold <?= $wd['isToday'] ? 'text-indigo-600' : 'text-gray-500' ?>"><?= substr($wd['hari'],0,3) ?></p>
            <p class="text-lg font-bold <?= $wd['isToday'] ? 'text-indigo-600' : 'text-gray-700' ?>"><?= (int)date('d', strtotime($wd['date'])) ?></p>
          </div>
          <div class="flex-1 space-y-1">
            <?php foreach ($wd['jadwal'] as $j): ?>
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 w-20 flex-shrink-0"><?= substr($j['jam_mulai'],0,5) ?>–<?= substr($j['jam_selesai'],0,5) ?></span>
                <span class="text-xs font-medium text-gray-700 truncate"><?= htmlspecialchars($j['nama_mapel']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- PENGUMUMAN BULAN INI -->
  <?php if (!empty($pengumumanList)): ?>
  <div class="bg-white rounded-2xl shadow-sm p-4 mt-4">
    <h3 class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2">
      <i class="fas fa-bullhorn text-yellow-500"></i> Pengumuman di <?= $monthName ?>
    </h3>
    <div class="space-y-2">
      <?php foreach ($pengumumanList as $p): ?>
      <a href="../../pengumuman.php"
         class="flex items-start gap-3 p-3 rounded-xl <?= $p['penting'] ? 'bg-orange-50 border border-orange-100' : 'bg-yellow-50' ?> hover:opacity-80 transition block">
        <div class="w-8 h-8 flex-shrink-0 rounded-lg <?= $p['penting'] ? 'bg-orange-100 text-orange-500' : 'bg-yellow-100 text-yellow-500' ?> flex items-center justify-center">
          <i class="fas fa-<?= $p['penting'] ? 'exclamation' : 'bell' ?> text-xs"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($p['judul']) ?></p>
          <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($p['mulai'])) ?> – <?= date('d/m/Y', strtotime($p['selesai'])) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php include 'siswa_footer.php'; ?>

</body>
</html>
