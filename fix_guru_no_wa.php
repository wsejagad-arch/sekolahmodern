<?php
/**
 * Script perbaikan: Cek dan tambah kolom no_wa di tabel tbl_guru
 * Hapus file ini setelah digunakan!
 */
include "koneksi.php";

$results = [];

// Cek kolom-kolom yang mungkin belum ada
$columnsToCheck = [
    'no_wa'                  => "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru",
    'is_guru_bk'             => "ALTER TABLE tbl_guru ADD COLUMN is_guru_bk TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
    'is_pendamping_literasi' => "ALTER TABLE tbl_guru ADD COLUMN is_pendamping_literasi TINYINT(1) NOT NULL DEFAULT 0 AFTER is_guru_bk",
    'is_tim_aduan'           => "ALTER TABLE tbl_guru ADD COLUMN is_tim_aduan TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pendamping_literasi",
    'jabatan'                => "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian",
];

foreach ($columnsToCheck as $col => $sql) {
    $chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE '$col'");
    if ($chk && mysqli_num_rows($chk) === 0) {
        $res = @mysqli_query($conn, $sql);
        if ($res) {
            $results[] = "✅ Kolom <b>$col</b> berhasil ditambahkan.";
        } else {
            $results[] = "❌ Gagal menambahkan kolom <b>$col</b>: " . mysqli_error($conn);
        }
    } else {
        $results[] = "ℹ️ Kolom <b>$col</b> sudah ada.";
    }
}

// Tampilkan struktur tbl_guru sekarang
$strResult = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru");
$columns = [];
while ($row = mysqli_fetch_assoc($strResult)) {
    $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
}

// Cek contoh data guru
$dataResult = mysqli_query($conn, "SELECT id_guru, nama_guru, no_wa, no_induk FROM tbl_guru LIMIT 5");
$sampleData = [];
while ($row = mysqli_fetch_assoc($dataResult)) {
    $sampleData[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fix Guru no_wa</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .ok { color: green; }
        .err { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
<h2>Diagnostic & Fix: Kolom no_wa di tbl_guru</h2>

<h3>Hasil Pengecekan Kolom:</h3>
<ul>
    <?php foreach ($results as $r): ?>
        <li><?= $r ?></li>
    <?php endforeach; ?>
</ul>

<h3>Struktur Tabel tbl_guru (sekarang):</h3>
<p><?= implode(', ', $columns) ?></p>

<h3>Contoh Data Guru (5 baris pertama):</h3>
<table>
    <tr><th>id_guru</th><th>nama_guru</th><th>no_induk</th><th>no_wa</th></tr>
    <?php foreach ($sampleData as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['id_guru']) ?></td>
        <td><?= htmlspecialchars($row['nama_guru']) ?></td>
        <td><?= htmlspecialchars($row['no_induk']) ?></td>
        <td><?= htmlspecialchars($row['no_wa'] ?? '(kosong)') ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<br>
<p style="color:red;"><strong>⚠️ Hapus file ini (fix_guru_no_wa.php) setelah selesai digunakan!</strong></p>
</body>
</html>
