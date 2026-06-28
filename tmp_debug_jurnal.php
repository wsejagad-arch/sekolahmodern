<?php
// tmp_debug_jurnal.php
// Usage: https://your-site/tmp_debug_jurnal.php?nip=YOUR_NIP&tgl=YYYY-MM-DD
// After debugging, delete this file.

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/bootstrap.php';
require_admin();
if (!isset($conn) || !$conn) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "DB connection failed: " . (function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'no message') . "\n";
    exit;
}

$nip = isset($_GET['nip']) ? trim($_GET['nip']) : '';
$tgl = isset($_GET['tgl']) && trim($_GET['tgl']) !== '' ? trim($_GET['tgl']) : date('Y-m-d');

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta charset="utf-8"><title>tmp_debug_jurnal</title><style>body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#fff;color:#111;padding:18px}pre{background:#f6f8fa;border:1px solid #e1e4e8;padding:12px;overflow:auto}</style>';

echo "<h2>Debug Jurnal</h2>";
echo "<p><strong>NIP</strong>: " . htmlspecialchars($nip) . " &nbsp; <strong>Tanggal</strong>: " . htmlspecialchars($tgl) . "</p>";
if ($nip === '') {
    echo "<div style='color:#b00'><strong>ERROR:</strong> Parameter <code>nip</code> kosong. Tambahkan <code>?nip=YOUR_NIP</code> pada URL.</div>\n";
}

// Show columns for tbl_materi
echo "<h3>Columns: tbl_materi</h3>\n<pre>";
$cols = mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi");
if ($cols) {
    while ($c = mysqli_fetch_assoc($cols)) {
        echo htmlspecialchars(json_encode($c, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "\n";
    }
} else {
    echo "(error) " . htmlspecialchars(mysqli_error($conn)) . "\n";
}
echo "</pre>";

// Detect date column
$dateCol = null;
$checkDate = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'date'");
if ($checkDate && mysqli_num_rows($checkDate) > 0) $dateCol = 'date';
$checkTanggal = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'tanggal'");
if (!$dateCol && $checkTanggal && mysqli_num_rows($checkTanggal) > 0) $dateCol = 'tanggal';

echo "<h3>Detected date column</h3><pre>" . ($dateCol ? $dateCol : 'NONE') . "</pre>";

// Count jurnal terisi for this guru on date
if ($nip !== '') {
    $nipEsc = mysqli_real_escape_string($conn, $nip);
    $tglEsc = mysqli_real_escape_string($conn, $tgl);

    // Count distinct id_mapel present in tbl_materi for that date (use `tanggal` column)
    $countQuery = "SELECT COUNT(DISTINCT id_mapel) AS jml FROM tbl_materi WHERE `tanggal` = '$tglEsc'";
    $countQuery .= " AND id_mapel IN (SELECT id_mapel FROM tbl_mapel_ampu WHERE no_induk = '$nipEsc')";

    echo "<h3>Count filled jurnal (query)</h3><pre>" . htmlspecialchars($countQuery) . "</pre>";

    $resCount = mysqli_query($conn, $countQuery);
    echo "<h3>Result</h3><pre>";
    if ($resCount) {
        $r = mysqli_fetch_assoc($resCount);
        echo htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    } else {
        echo "ERROR: " . htmlspecialchars(mysqli_error($conn));
    }
    echo "</pre>";

    // Show rows for that date and this teacher's mapel
    $rowsQ = "SELECT id_materi,id_mapel,no_induk,tanggal AS thedate,kelas,materi,keterangan FROM tbl_materi WHERE tanggal = '$tglEsc' AND id_mapel IN (SELECT id_mapel FROM tbl_mapel_ampu WHERE no_induk = '$nipEsc') ORDER BY id_materi DESC LIMIT 100";

    echo "<h3>Rows matching</h3><pre>" . htmlspecialchars($rowsQ) . "</pre>";
    $resRows = mysqli_query($conn, $rowsQ);
    echo "<h3>Rows result</h3><pre>";
    if ($resRows) {
        $i = 0;
        while ($row = mysqli_fetch_assoc($resRows)) {
            echo htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "\n";
            $i++;
        }
        if ($i === 0) echo "(no rows)\n";
    } else {
        echo "ERROR: " . htmlspecialchars(mysqli_error($conn)) . "\n";
    }
    echo "</pre>";

    // Also list jadwal today for this guru to compare total
    $jadwalQ = "SELECT id_mapel,nama_mapel,kelas,jam_mulai,jam_selesai,hari FROM tbl_mapel_ampu WHERE no_induk = '$nipEsc' AND hari = DAYNAME('$tglEsc') ORDER BY jam_mulai";
    echo "<h3>Jadwal hari ini (for guru)</h3><pre>" . htmlspecialchars($jadwalQ) . "</pre>";
    $resJadwal = mysqli_query($conn, $jadwalQ);
    echo "<h3>Jadwal result</h3><pre>";
    if ($resJadwal) {
        $c = 0;
        while ($jj = mysqli_fetch_assoc($resJadwal)) {
            echo htmlspecialchars(json_encode($jj, JSON_UNESCAPED_UNICODE)) . "\n";
            $c++;
        }
        if ($c === 0) echo "(no jadwal)\n";
    } else {
        echo "ERROR: " . htmlspecialchars(mysqli_error($conn)) . "\n";
    }
    echo "</pre>";
}

echo "<hr><div style='color:#b33'>Hapus file ini setelah debugging selesai!</div>";
