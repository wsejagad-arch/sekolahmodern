<?php
// Endpoint: progres pengisian jurnal per guru
// GET params:
//  mode: daily|weekly|monthly (default daily)
//  startDate, endDate for daily/weekly (YYYY-MM-DD)
//  year for monthly (YYYY)
//  guru (optional, substring match nama_guru)
//  limit (optional, default 100)
// Returns JSON array of guru with: id_guru, no_induk, nama_guru, foto(optional), scheduled, completed, percent

session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include_once __DIR__ . '/../koneksi.php';
include_once __DIR__ . '/../functions.php';

date_default_timezone_set('Asia/Jakarta');

function clampDate($dateStr){
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d ? $d->format('Y-m-d') : null;
}
function daterange_iter($start,$end){
    $out=[]; $cur=new DateTime($start); $endDt=new DateTime($end);
    while($cur <= $endDt){ $out[]=$cur->format('Y-m-d'); $cur->modify('+1 day'); }
    return $out;
}

$mode = $_GET['mode'] ?? 'daily';
$guruFilter = trim($_GET['guru'] ?? '');
$limit = intval($_GET['limit'] ?? 100); if($limit <=0 || $limit>500) $limit=100;
$startDate=null; $endDate=null; $year=null;
if ($mode === 'monthly') {
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
    if ($year < 2000 || $year > 2100) { $year = intval(date('Y')); }
    $startDate = $year . '-01-01';
    $endDate   = $year . '-12-31';
} else {
    $startDate = clampDate($_GET['startDate'] ?? date('Y-m-d'));
    $endDate   = clampDate($_GET['endDate'] ?? $_GET['startDate'] ?? date('Y-m-d'));
    if(!$startDate || !$endDate){
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error'=>'Invalid startDate or endDate']);
        exit;
    }
    if($startDate > $endDate){ $tmp=$startDate; $startDate=$endDate; $endDate=$tmp; }
}

// Ambil daftar guru
$whereGuru = '';
if ($guruFilter !== '') {
    $esc = mysqli_real_escape_string($conn, $guruFilter);
    $whereGuru = "WHERE g.nama_guru LIKE '%$esc%'";
}
$sqlGuru = "SELECT g.id_guru, g.no_induk, g.nama_guru, g.foto FROM tbl_guru g $whereGuru ORDER BY g.nama_guru ASC LIMIT $limit";
$resGuru = mysqli_query($conn, $sqlGuru);
$guruList=[]; $guruIds=[]; $guruByInduk=[];
while($resGuru && $row = mysqli_fetch_assoc($resGuru)){
    $guruList[] = $row;
    $guruIds[] = intval($row['id_guru']);
    $guruByInduk[$row['no_induk']] = $row; // map by no_induk for schedule join
}
if(empty($guruList)){
    header('Content-Type: application/json');
    echo json_encode(['mode'=>$mode,'startDate'=>$startDate,'endDate'=>$endDate,'year'=>$year,'data'=>[]]);
    exit;
}

// Preload jadwal per guru (jumlah mapel ampu dalam periode)
// jadwal disimpan per hari dalam tabel; kita butuh menghitung berapa kali jadwal terjadi dalam rentang.
$dates = [];
if ($mode === 'monthly') {
    $dates = daterange_iter($startDate, $endDate);
} else {
    $dates = daterange_iter($startDate, $endDate);
}

// Ambil semua jadwal guru (mapel ampu) untuk guru yang difilter
$indukList = array_map(function($g){return "'".addslashes($g['no_induk'])."'";}, $guruList);
$inInduk = implode(',', $indukList);
$jadwalMap = []; // key no_induk => array of rows
$qJ = mysqli_query($conn, "SELECT no_induk, id_mapel, hari FROM tbl_mapel_ampu WHERE no_induk IN ($inInduk)");
while($qJ && $rJ = mysqli_fetch_assoc($qJ)){
    $ni = $rJ['no_induk'];
    if(!isset($jadwalMap[$ni])) $jadwalMap[$ni]=[];
    $jadwalMap[$ni][] = $rJ; // store hari & id_mapel
}

// Hitung scheduled occurrences per guru
$scheduledCount = []; // no_induk => int
foreach($guruList as $g){ $scheduledCount[$g['no_induk']] = 0; }
foreach($dates as $d){
    $hari = ubah_nama_hari($d);
    foreach($jadwalMap as $ni=>$rows){
        foreach($rows as $r){ if($r['hari'] === $hari) { $scheduledCount[$ni]++; } }
    }
}

// Ambil jurnal (tbl_materi) yang terisi pada periode untuk mapel milik guru (distinct id_mapel per tanggal dihitung 1)
$startEsc = mysqli_real_escape_string($conn, $startDate);
$endEsc   = mysqli_real_escape_string($conn, $endDate);
$completedCount = []; foreach($guruList as $g){ $completedCount[$g['no_induk']] = 0; }
// Query materi join jadwal untuk tahu guru pemilik mapel
$qMat = mysqli_query($conn, "SELECT m.tanggal AS date, ma.no_induk, m.id_mapel FROM tbl_materi m JOIN tbl_mapel_ampu ma ON m.id_mapel=ma.id_mapel WHERE m.tanggal BETWEEN '$startEsc' AND '$endEsc' AND ma.no_induk IN ($inInduk)");
$seen = []; // key = no_induk|date|id_mapel to ensure distinct per day per mapel
while($qMat && $rM = mysqli_fetch_assoc($qMat)){
    $key = $rM['no_induk'].'|'.$rM['date'].'|'.$rM['id_mapel'];
    if(isset($seen[$key])) continue;
    $seen[$key]=true;
    $completedCount[$rM['no_induk']]++;
}

$out = [];
foreach($guruList as $g){
    $ni = $g['no_induk'];
    $sched = $scheduledCount[$ni] ?? 0;
    $comp  = $completedCount[$ni] ?? 0;
    $percent = $sched > 0 ? round(($comp/$sched)*100,2) : 0;
    $out[] = [
        'id_guru' => (int)$g['id_guru'],
        'no_induk'=> $ni,
        'nama_guru'=> $g['nama_guru'],
        'foto' => $g['foto'],
        'scheduled' => $sched,
        'completed' => $comp,
        'percent' => $percent
    ];
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo json_encode([
    'mode'=>$mode,
    'startDate'=>$startDate,
    'endDate'=>$endDate,
    'year'=>$year,
    'count'=>count($out),
    'data'=>$out
]);
