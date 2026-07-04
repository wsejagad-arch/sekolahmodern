<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk'])) {
    echo "Harus login"; exit;
}
include 'koneksi.php';
include 'functions.php';
$nip = $_GET['nip'] ?? $_SESSION['no_induk'];
$forceLocal = isset($_GET['forceLocal']);
if ($forceLocal && function_exists('buat_koneksi_local')) { if (isset($conn)) { @mysqli_close($conn);} $conn = buat_koneksi_local(); }

header('Content-Type: text/html; charset=utf-8');
echo '<h3>Diagnose Jadwal (tbl_mapel_ampu)</h3>';
echo '<p>NIP: '.htmlspecialchars($nip).'</p>';

$q = "SELECT id_mapel,no_induk,hari,kelas,nama_mapel,jam_mulai,jam_selesai FROM tbl_mapel_ampu WHERE no_induk='".mysqli_real_escape_string($conn,$nip)."' ORDER BY no_induk, FIELD(hari,'Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai";
$res = mysqli_query($conn,$q);
if (!$res) {
    echo '<p style="color:red">Query Error: '.htmlspecialchars(mysqli_error($conn)).'</p>';
    echo '<pre>'.htmlspecialchars($q).'</pre>'; exit;
}
$rows = [];
while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
if (!$rows) { echo '<p style="color:orange">Tidak ada jadwal untuk guru ini.</p>'; exit; }

echo '<table border=1 cellpadding=4 cellspacing=0 style="border-collapse:collapse;font-family:Arial;font-size:12px">';
echo '<tr style="background:#eee"><th>#</th><th>ID Mapel</th><th>Hari</th><th>Kelas</th><th>Mapel</th><th>Mulai</th><th>Selesai</th><th>Case Check</th></tr>';
$no=1;
foreach($rows as $r){
    $caseOk = (in_array($r['hari'],['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'])) ? 'OK' : 'MISMATCH';
    echo '<tr>';
    echo '<td>'.$no++.'</td>';
    echo '<td>'.$r['id_mapel'].'</td>';
    echo '<td>'.htmlspecialchars($r['hari']).'</td>';
    echo '<td>'.htmlspecialchars($r['kelas']).'</td>';
    echo '<td>'.htmlspecialchars($r['nama_mapel']).'</td>';
    echo '<td>'.$r['jam_mulai'].'</td>';
    echo '<td>'.$r['jam_selesai'].'</td>';
    echo '<td>'.$caseOk.'</td>';
    echo '</tr>';
}
echo '</table>';

echo '<p>Total: '.count($rows).' jadwal.</p>';
?>