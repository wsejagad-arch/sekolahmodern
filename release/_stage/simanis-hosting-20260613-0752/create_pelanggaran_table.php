<?php
include "koneksi.php";

echo "=== MEMBUAT TABEL PELANGGARAN SISWA ===" . PHP_EOL;

// SQL untuk membuat tabel pelanggaran
$createTableSQL = "
CREATE TABLE IF NOT EXISTS tbl_pelanggaran (
  id_pelanggaran int(11) NOT NULL AUTO_INCREMENT,
  no_induk varchar(25) NOT NULL,
  nama_siswa varchar(150) NOT NULL,
  kelas varchar(50) NOT NULL,
  tanggal_pelanggaran date NOT NULL,
  kategori_pelanggaran enum('Berat','Sedang','Ringan') NOT NULL,
  jenis_pelanggaran varchar(100) NOT NULL,
  deskripsi_pelanggaran text,
  tindakan_yang_diambil text,
  no_induk_guru varchar(25) NOT NULL,
  nama_guru varchar(150) NOT NULL,
  status_pelanggaran enum('Aktif','Selesai','Dibatalkan') DEFAULT 'Aktif',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pelanggaran),
  KEY idx_siswa (no_induk),
  KEY idx_tanggal (tanggal_pelanggaran),
  KEY idx_kategori (kategori_pelanggaran),
  KEY idx_guru (no_induk_guru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (mysqli_query($conn, $createTableSQL)) {
    echo "✅ Tabel tbl_pelanggaran berhasil dibuat!" . PHP_EOL;
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . PHP_EOL;
}

// Tambahkan data sample untuk testing
$sampleData = [
    ['05806', 'Ahmad Azis Savudin', 'XII F 6', '2024-09-20', 'Ringan', 'Telat masuk kelas', 'Terlambat 15 menit tanpa keterangan', 'Teguran lisan dan catatan di buku pelanggaran', '0029', 'YOSI KUSUMAWARDANI, S. Pd.'],
    ['05821', 'ANANDA FIRDA NUR AISYAH', 'XII F 6', '2024-09-18', 'Sedang', 'Baju tidak rapih', 'Baju keluar dari celana berulang kali', 'Teguran tertulis dan panggil orang tua', '0029', 'YOSI KUSUMAWARDANI, S. Pd.'],
    ['05422', 'FEBRIAN', 'XII F 6', '2024-09-15', 'Ringan', 'Alpa tanpa keterangan', 'Tidak masuk sekolah 1 hari tanpa ijin', 'Teguran dan surat panggilan orang tua', '0029', 'YOSI KUSUMAWARDANI, S. Pd.'],
    ['05263', 'MUHAMMAD AGUS WICAKSONO', 'XII F 6', '2024-09-22', 'Berat', 'Merokok di area sekolah', 'Kedapatan merokok di belakang kantin sekolah', 'Skorsing 3 hari dan panggil orang tua', '0029', 'YOSI KUSUMAWARDANI, S. Pd.'],
];

echo PHP_EOL . "=== MENAMBAHKAN DATA SAMPLE PELANGGARAN ===" . PHP_EOL;

foreach ($sampleData as $data) {
    $insertSQL = "INSERT INTO tbl_pelanggaran 
        (no_induk, nama_siswa, kelas, tanggal_pelanggaran, kategori_pelanggaran, jenis_pelanggaran, deskripsi_pelanggaran, tindakan_yang_diambil, no_induk_guru, nama_guru) 
        VALUES ('" . implode("', '", $data) . "')";
    
    if (mysqli_query($conn, $insertSQL)) {
        echo "✅ Sample pelanggaran: " . $data[1] . " - " . $data[4] . " (" . $data[5] . ")" . PHP_EOL;
    } else {
        echo "❌ Error: " . mysqli_error($conn) . PHP_EOL;
    }
}

echo PHP_EOL . "=== VERIFIKASI DATA PELANGGARAN ===" . PHP_EOL;

$result = mysqli_query($conn, "SELECT * FROM tbl_pelanggaran ORDER BY tanggal_pelanggaran DESC");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $icon = '';
        switch($row['kategori_pelanggaran']) {
            case 'Berat': $icon = '🚨'; break;
            case 'Sedang': $icon = '⚠️'; break; 
            case 'Ringan': $icon = '⚡'; break;
        }
        echo "$icon " . $row['tanggal_pelanggaran'] . " | " . $row['nama_siswa'] . " | " . $row['kategori_pelanggaran'] . " | " . $row['jenis_pelanggaran'] . PHP_EOL;
    }
} else {
    echo "Tidak ada data pelanggaran." . PHP_EOL;
}

mysqli_close($conn);
?>