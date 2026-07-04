<?php
// Export HTML untuk PDF - Guru yang belum mengisi jurnal
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="guru_belum_isi_jurnal_' . date('Y-m-d_H-i-s') . '.html"');

// Pastikan koneksi database
if (!isset($conn)) {
    include "koneksi.php";
}

// Ambil parameter
$tanggal = $_GET['tanggal'] ?? date("Y-m-d");
$hari = $_GET['hari'] ?? '';

// Validasi parameter
if (empty($tanggal) || empty($hari)) {
    die("Parameter tanggal dan hari diperlukan");
}

// Query untuk mendapatkan guru yang belum isi jurnal
$query = "SELECT g.no_induk, g.nama_guru, ma.nama_mapel, ma.kelas, ma.jam_mulai, ma.jam_selesai, ma.hari
    FROM tbl_mapel_ampu ma
    INNER JOIN tbl_guru g ON ma.no_induk = g.no_induk
    LEFT JOIN tbl_materi m
           ON ma.id_mapel = m.id_mapel
          AND ma.no_induk = m.no_induk
          AND ma.kelas = m.kelas
          AND m.tanggal = '$tanggal'
    WHERE ma.hari = '$hari'
          AND ma.jam_mulai IS NOT NULL
          AND ma.jam_mulai != ''
          AND ma.jam_selesai IS NOT NULL
          AND ma.jam_selesai != ''
          AND m.id_materi IS NULL
    ORDER BY g.nama_guru ASC, ma.jam_mulai ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Guru Belum Isi Jurnal</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .total { font-weight: bold; margin: 20px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN GURU YANG BELUM MENGISI JURNAL</h1>
        <p>Hari: <?= htmlspecialchars($hari) ?> | Tanggal: <?= date('d F Y', strtotime($tanggal)) ?></p>
        <p>Dicetak: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <tr>
            <th>No</th>
            <th>Nama Guru</th>
            <th>Mata Pelajaran</th>
            <th>Kelas</th>
            <th>Jam Mengajar</th>
            <th>Hari</th>
        </tr>
        <?php
        $no = 1;
        $total = 0;
        while($row = mysqli_fetch_assoc($result)) {
            $total++;
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['nama_guru']) ?></td>
            <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
            <td><?= htmlspecialchars($row['kelas']) ?></td>
            <td><?= htmlspecialchars($row['jam_mulai']) ?> - <?= htmlspecialchars($row['jam_selesai']) ?></td>
            <td><?= htmlspecialchars($row['hari']) ?></td>
        </tr>
        <?php } ?>
    </table>

    <div class="total">
        Total Guru yang Belum Mengisi Jurnal: <?= $total ?> orang
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center; color: #666;">
        <p>Simpan halaman ini sebagai PDF menggunakan Ctrl+P atau Save as PDF</p>
    </div>
</body>
</html>