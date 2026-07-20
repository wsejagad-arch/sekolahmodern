<?php
/**
 * SIMANIS — Script Perbaikan Database Lengkap
 * Cek dan tambah kolom-kolom baru di tbl_guru dan tbl_siswa
 *
 * ⚠️ HAPUS file ini setelah digunakan!
 */

// Keamanan minimal: harus diakses via browser hosting
if (php_sapi_name() === 'cli') {
    die("Jalankan via browser.\n");
}

include "koneksi.php";

$results  = [];
$warnings = [];

// ============================================================
// Tabel tbl_guru
// ============================================================
$guruColumns = [
    'no_wa'                  => "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru",
    'is_guru_bk'             => "ALTER TABLE tbl_guru ADD COLUMN is_guru_bk TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
    'is_pendamping_literasi' => "ALTER TABLE tbl_guru ADD COLUMN is_pendamping_literasi TINYINT(1) NOT NULL DEFAULT 0 AFTER is_guru_bk",
    'is_tim_aduan'           => "ALTER TABLE tbl_guru ADD COLUMN is_tim_aduan TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pendamping_literasi",
    'jabatan'                => "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian",
];

foreach ($guruColumns as $col => $sql) {
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE '$col'");
    if ($chk && mysqli_num_rows($chk) === 0) {
        $res = @mysqli_query($conn, $sql);
        if ($res) {
            $results[] = ['status' => 'ok', 'table' => 'tbl_guru', 'col' => $col, 'msg' => 'Berhasil ditambahkan'];
        } else {
            $err = mysqli_error($conn);
            $results[] = ['status' => 'err', 'table' => 'tbl_guru', 'col' => $col, 'msg' => 'Gagal: ' . $err];
            $warnings[] = "tbl_guru.$col gagal ditambah: $err";
        }
    } else {
        $results[] = ['status' => 'info', 'table' => 'tbl_guru', 'col' => $col, 'msg' => 'Sudah ada'];
    }
}

// ============================================================
// Tabel tbl_siswa
// ============================================================
$siswaColumns = [
    'jabatan' => "ALTER TABLE tbl_siswa ADD COLUMN jabatan ENUM('Siswa','Ketua Kelas') DEFAULT 'Siswa' AFTER kelas",
];

foreach ($siswaColumns as $col => $sql) {
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE '$col'");
    if ($chk && mysqli_num_rows($chk) === 0) {
        $res = @mysqli_query($conn, $sql);
        if ($res) {
            $results[] = ['status' => 'ok', 'table' => 'tbl_siswa', 'col' => $col, 'msg' => 'Berhasil ditambahkan'];
        } else {
            $err = mysqli_error($conn);
            $results[] = ['status' => 'err', 'table' => 'tbl_siswa', 'col' => $col, 'msg' => 'Gagal: ' . $err];
            $warnings[] = "tbl_siswa.$col gagal ditambah: $err";
        }
    } else {
        $results[] = ['status' => 'info', 'table' => 'tbl_siswa', 'col' => $col, 'msg' => 'Sudah ada'];
    }
}

// ============================================================
// Ambil struktur tabel sekarang
// ============================================================
function getTableColumns($conn, $table) {
    $cols = [];
    $r = @mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cols[] = $row['Field'] . ' <span style="color:#888">(' . $row['Type'] . ')</span>';
        }
    }
    return $cols;
}

$guruCols  = getTableColumns($conn, 'tbl_guru');
$siswaCols = getTableColumns($conn, 'tbl_siswa');

// Contoh data guru
$sampleGuru = [];
$qg = @mysqli_query($conn, "SELECT id_guru, nama_guru, no_induk, no_wa, jabatan FROM tbl_guru LIMIT 5");
if ($qg) while ($row = mysqli_fetch_assoc($qg)) $sampleGuru[] = $row;

// Contoh data siswa
$sampleSiswa = [];
$qs = @mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas, status, jabatan FROM tbl_siswa LIMIT 5");
if ($qs) while ($row = mysqli_fetch_assoc($qs)) $sampleSiswa[] = $row;

$hasError = count($warnings) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMANIS — Fix Database Schema</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #f5f7fa; color: #333; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        h1 { font-size: 1.4rem; color: #1a3a5c; }
        h2 { font-size: 1.1rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; font-size: .9rem; }
        th, td { border: 1px solid #e2e8f0; padding: 7px 12px; text-align: left; }
        th { background: #f1f5f9; font-weight: 600; }
        .ok  { color: #16a34a; font-weight: 600; }
        .err { color: #dc2626; font-weight: 600; }
        .info { color: #2563eb; }
        .banner { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-weight: 600; }
        .banner.success { background: #dcfce7; color: #15803d; border-left: 4px solid #16a34a; }
        .banner.danger  { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }
        .warn { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: .85rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔧 SIMANIS — Perbaikan Skema Database</h1>
    <?php if ($hasError): ?>
        <div class="banner danger">⚠️ Ada kolom yang gagal ditambahkan. Periksa detail di bawah.</div>
        <?php foreach ($warnings as $w): ?>
            <div class="warn"><?= htmlspecialchars($w) ?></div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="banner success">✅ Semua kolom berhasil diverifikasi / ditambahkan!</div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Hasil Pengecekan Kolom</h2>
    <table>
        <tr><th>Tabel</th><th>Kolom</th><th>Status</th></tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><code><?= htmlspecialchars($r['table']) ?></code></td>
            <td><code><?= htmlspecialchars($r['col']) ?></code></td>
            <td class="<?= $r['status'] ?>">
                <?php if ($r['status'] === 'ok'): ?>✅ <?php elseif ($r['status'] === 'err'): ?>❌ <?php else: ?>ℹ️ <?php endif; ?>
                <?= htmlspecialchars($r['msg']) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h2>Struktur tbl_guru (setelah fix)</h2>
    <p><?= implode(', ', $guruCols) ?></p>

    <h2 style="margin-top:16px">Contoh Data Guru (5 baris)</h2>
    <?php if ($sampleGuru): ?>
    <table>
        <tr><th>id_guru</th><th>nama_guru</th><th>no_induk</th><th>no_wa</th><th>jabatan</th></tr>
        <?php foreach ($sampleGuru as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['id_guru']) ?></td>
            <td><?= htmlspecialchars($row['nama_guru']) ?></td>
            <td><?= htmlspecialchars($row['no_induk']) ?></td>
            <td><?= htmlspecialchars($row['no_wa'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['jabatan'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?><p class="info">Tabel kosong atau tidak ada data.</p><?php endif; ?>
</div>

<div class="card">
    <h2>Struktur tbl_siswa (setelah fix)</h2>
    <p><?= implode(', ', $siswaCols) ?></p>

    <h2 style="margin-top:16px">Contoh Data Siswa (5 baris)</h2>
    <?php if ($sampleSiswa): ?>
    <table>
        <tr><th>no_induk</th><th>nama_siswa</th><th>kelas</th><th>status</th><th>jabatan</th></tr>
        <?php foreach ($sampleSiswa as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['no_induk']) ?></td>
            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
            <td><?= htmlspecialchars($row['kelas']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['jabatan'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?><p class="info">Tabel kosong atau tidak ada data.</p><?php endif; ?>
</div>

<div class="card" style="background:#fff7ed; border-left: 4px solid #f97316;">
    <strong>⚠️ Penting:</strong> Hapus file <code>fix_guru_no_wa.php</code> dari hosting setelah selesai digunakan untuk alasan keamanan!
</div>
</body>
</html>
