<?php
// Export TXT - Guru yang belum mengisi jurnal
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="guru_belum_isi_jurnal_' . date('Y-m-d_H-i-s') . '.txt"');

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

// Header file TXT
echo "================================================================================\n";
echo "                    LAPORAN GURU YANG BELUM MENGISI JURNAL                    \n";
echo "================================================================================\n";
echo "Hari          : " . str_pad(htmlspecialchars($hari), 20) . "\n";
echo "Tanggal       : " . str_pad(date('d F Y', strtotime($tanggal)), 20) . "\n";
echo "Dicetak pada  : " . str_pad(date('d/m/Y H:i:s'), 20) . "\n";
echo "================================================================================\n";
echo "\n";

// Header tabel
echo str_pad("No", 3) . " | " . str_pad("Nama Guru", 25) . " | " . str_pad("Mata Pelajaran", 20) . " | " . str_pad("Kelas", 8) . " | " . str_pad("Jam Mengajar", 15) . "\n";
echo "--------------------------------------------------------------------------------\n";

// Isi data
$no = 1;
$totalBelumIsi = 0;

while($row = mysqli_fetch_assoc($result)) {
    $namaGuru = htmlspecialchars($row['nama_guru']);
    $mapel = htmlspecialchars($row['nama_mapel']);
    $kelas = htmlspecialchars($row['kelas']);
    $jam = htmlspecialchars($row['jam_mulai']) . '-' . htmlspecialchars($row['jam_selesai']);

    echo str_pad($no++, 3) . " | " . str_pad(substr($namaGuru, 0, 25), 25) . " | " . str_pad(substr($mapel, 0, 20), 20) . " | " . str_pad($kelas, 8) . " | " . str_pad($jam, 15) . "\n";
    $totalBelumIsi++;
}

// Footer
echo "--------------------------------------------------------------------------------\n";
echo "\n";
echo "Total Guru yang Belum Mengisi Jurnal: " . $totalBelumIsi . " orang\n";
echo "\n";
echo "================================================================================\n";
echo "                        SIJURNAL - Sistem Informasi Jurnal                       \n";
echo "================================================================================\n";
?>