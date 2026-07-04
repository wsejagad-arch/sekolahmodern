<?php
require_once __DIR__ . '/../bootstrap.php';
require_admin();

if (!$conn) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'Koneksi database tidak tersedia.';
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? ''));
$jk = trim((string)($_GET['jk'] ?? ''));

$whereParts = [];
if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    $whereParts[] = "(nama_siswa LIKE '%{$qEsc}%' OR nis LIKE '%{$qEsc}%' OR nisn LIKE '%{$qEsc}%' OR peserta_didik_id LIKE '%{$qEsc}%')";
}
if ($kelas !== '') {
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $whereParts[] = "kelas = '{$kelasEsc}'";
}
if ($jk !== '') {
    $jkEsc = mysqli_real_escape_string($conn, $jk);
    $whereParts[] = "jenis_kelamin = '{$jkEsc}'";
}

$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

$sql = "SELECT source_no, nama_siswa, nis, nisn, jenis_kelamin, kelas, peserta_didik_id, synced_at
        FROM tbl_siswa_eraport
        {$whereSql}
        ORDER BY nama_siswa ASC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'Query export gagal: ' . mysqli_error($conn);
    exit;
}

$filename = 'siswa_eraport_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
if ($out === false) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'Tidak dapat menulis output CSV.';
    exit;
}

// UTF-8 BOM for Excel compatibility.
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'No',
    'Nama Siswa',
    'NIS',
    'NISN',
    'Jenis Kelamin',
    'Kelas',
    'Peserta Didik ID',
    'Synced At'
]);

while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($out, [
        (string)($row['source_no'] ?? ''),
        (string)($row['nama_siswa'] ?? ''),
        (string)($row['nis'] ?? ''),
        (string)($row['nisn'] ?? ''),
        (string)($row['jenis_kelamin'] ?? ''),
        (string)($row['kelas'] ?? ''),
        (string)($row['peserta_didik_id'] ?? ''),
        (string)($row['synced_at'] ?? ''),
    ]);
}

fclose($out);
exit;
