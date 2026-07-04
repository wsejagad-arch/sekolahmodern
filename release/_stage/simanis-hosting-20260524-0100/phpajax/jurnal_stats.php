<?php
// Endpoint: Hitung prosentase pengisian jurnal guru
// Input via GET:
//   mode: 'daily' | 'weekly' | 'monthly'
//   startDate (YYYY-MM-DD) - required for daily/weekly
//   endDate   (YYYY-MM-DD) - required for daily/weekly
//   year (YYYY)            - required for monthly

session_start();
if (!isset($_SESSION["username"])) {
    http_response_code(401);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

include_once __DIR__ . '/../koneksi.php';
include_once __DIR__ . '/../functions.php';

date_default_timezone_set('Asia/Jakarta');

// Helpers
function clampDate($dateStr) {
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d ? $d->format('Y-m-d') : null;
}

function daterange_iter($start, $end) {
    // inclusive date range iterator, returns array of Y-m-d
    $out = [];
    $cur = new DateTime($start);
    $endDt = new DateTime($end);
    while ($cur <= $endDt) {
        $out[] = $cur->format('Y-m-d');
        $cur->modify('+1 day');
    }
    return $out;
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'daily';

// Resolve date window based on mode
$startDate = null; $endDate = null; $year = null;
if ($mode === 'monthly') {
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
    if ($year < 2000 || $year > 2100) { $year = intval(date('Y')); }
    $startDate = $year . '-01-01';
    $endDate   = $year . '-12-31';
} else {
    // daily/weekly use date range
    $startDate = clampDate($_GET['startDate'] ?? date('Y-m-d', strtotime('-6 days')));
    $endDate   = clampDate($_GET['endDate']   ?? date('Y-m-d'));
    if (!$startDate || !$endDate) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(["error" => "Invalid startDate or endDate"]);
        exit;
    }
    if ($startDate > $endDate) {
        // swap
        $tmp = $startDate; $startDate = $endDate; $endDate = $tmp;
    }
}

// Preload schedule counts per day (Indonesian day names as stored in DB)
$scheduleCounts = [
    'Minggu' => 0, 'Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0, 'Sabtu' => 0
];
$res = mysqli_query($conn, "SELECT hari, COUNT(*) AS cnt FROM tbl_mapel_ampu GROUP BY hari");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $h = $row['hari'];
        if (isset($scheduleCounts[$h])) {
            $scheduleCounts[$h] = intval($row['cnt']);
        }
    }
}

// Preload completed counts per date within the window
$completedByDate = [];
$q = sprintf(
    "SELECT `tanggal`, COUNT(DISTINCT id_mapel) AS cnt FROM tbl_materi WHERE `tanggal` BETWEEN '%s' AND '%s' GROUP BY `tanggal`",
    mysqli_real_escape_string($conn, $startDate),
    mysqli_real_escape_string($conn, $endDate)
);
$res2 = mysqli_query($conn, $q);
if ($res2) {
    while ($row = mysqli_fetch_assoc($res2)) {
        $completedByDate[$row['tanggal']] = intval($row['cnt']);
    }
}

// Build output based on mode
$labels = [];
$data = [];
$scheduledSeries = [];
$completedSeries = [];

if ($mode === 'daily') {
    foreach (daterange_iter($startDate, $endDate) as $d) {
        $labels[] = $d; // YYYY-MM-DD
        $hariIndo = ubah_nama_hari($d); // uses helper from functions.php
        $scheduled = $scheduleCounts[$hariIndo] ?? 0;
        $completed = $completedByDate[$d] ?? 0;
        $scheduledSeries[] = $scheduled;
        $completedSeries[] = $completed;
        $percent = $scheduled > 0 ? round(($completed / $scheduled) * 100, 2) : 0;
        $data[] = $percent;
    }
} elseif ($mode === 'weekly') {
    // Group by ISO week-year
    $buckets = []; // key => ['scheduled'=>int,'completed'=>int,'label'=>string]
    foreach (daterange_iter($startDate, $endDate) as $d) {
        $week = date('W', strtotime($d));
        $weekYear = date('o', strtotime($d)); // ISO year
        $key = $weekYear . '-W' . $week;
        if (!isset($buckets[$key])) {
            $buckets[$key] = ['scheduled' => 0, 'completed' => 0, 'label' => 'Minggu ' . $week . ' ' . $weekYear];
        }
        $hariIndo = ubah_nama_hari($d);
        $buckets[$key]['scheduled'] += ($scheduleCounts[$hariIndo] ?? 0);
        $buckets[$key]['completed'] += ($completedByDate[$d] ?? 0);
    }
    foreach ($buckets as $key => $agg) {
        $labels[] = $agg['label'];
        $scheduledSeries[] = $agg['scheduled'];
        $completedSeries[] = $agg['completed'];
        $percent = $agg['scheduled'] > 0 ? round(($agg['completed'] / $agg['scheduled']) * 100, 2) : 0;
        $data[] = $percent;
    }
} else { // monthly
    // Iterate months in the year and aggregate per month
    $bulanIndo = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    for ($m = 1; $m <= 12; $m++) {
        $labels[] = $bulanIndo[$m];
        $firstDay = $year . '-' . sprintf('%02d', $m) . '-01';
        $lastDay = date('Y-m-t', strtotime($firstDay));
        $sched = 0; $comp = 0;
        foreach (daterange_iter($firstDay, $lastDay) as $d) {
            $hariIndo = ubah_nama_hari($d);
            $sched += ($scheduleCounts[$hariIndo] ?? 0);
            $comp += ($completedByDate[$d] ?? 0);
        }
        $scheduledSeries[] = $sched;
        $completedSeries[] = $comp;
        $percent = $sched > 0 ? round(($comp / $sched) * 100, 2) : 0;
        $data[] = $percent;
    }
}

// Totals and period caption
$totalScheduled = array_sum($scheduledSeries);
$totalCompleted = array_sum($completedSeries);
$overallPercent = $totalScheduled > 0 ? round(($totalCompleted / $totalScheduled) * 100, 2) : 0;
$periodLabel = ($mode === 'monthly') ? ('Tahun ' . $year) : ($startDate . ' s/d ' . $endDate);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
echo json_encode([
    'labels' => $labels,
    'percentages' => $data,
    'scheduled' => $scheduledSeries,
    'completed' => $completedSeries,
    'mode' => $mode,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'year' => $year,
    'total_scheduled' => $totalScheduled,
    'total_completed' => $totalCompleted,
    'overall_percent' => $overallPercent,
    'period' => $periodLabel,
]);


