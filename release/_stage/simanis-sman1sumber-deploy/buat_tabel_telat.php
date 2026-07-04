<?php
// Script otomatis untuk membuat tabel telat
require_once 'koneksi.php';

echo "=== MEMBUAT TABEL TELAT ===\n\n";

// Cek koneksi
if ($conn === null || !($conn instanceof mysqli)) {
    die("ERROR: Koneksi database gagal! Pastikan MySQL sudah running.\n");
}

echo "✅ Koneksi database berhasil\n\n";

// Cek apakah tabel sudah ada
$check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_telat'");
if (mysqli_num_rows($check) > 0) {
    echo "⚠️ Tabel tbl_telat sudah ada!\n";
    echo "Apakah ingin DROP dan buat ulang? (y/n): ";

    // Untuk CLI, kita skip konfirmasi dan langsung recreate
    echo "\n\n=== AUTO RECREATE ===\n";
    mysqli_query($conn, "DROP TABLE IF EXISTS tbl_telat");
    echo "✅ Tabel lama dihapus\n";
}

// SQL untuk membuat tabel
$sql = "
CREATE TABLE IF NOT EXISTS tbl_telat (
  id_telat int(11) NOT NULL AUTO_INCREMENT,
  no_induk varchar(25) NOT NULL,
  nama_siswa varchar(150) NOT NULL,
  kelas varchar(50) NOT NULL,
  tanggal date NOT NULL,
  waktu_telat time DEFAULT NULL,
  id_mapel int(11) DEFAULT NULL,
  no_induk_guru varchar(25) NOT NULL,
  keterangan text,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_telat),
  KEY idx_siswa (no_induk),
  KEY idx_tanggal (tanggal),
  KEY idx_mapel (id_mapel),
  KEY idx_guru (no_induk_guru),
  FOREIGN KEY (id_mapel) REFERENCES tbl_mapel_ampu(id_mapel) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

// Eksekusi query
if (mysqli_query($conn, $sql)) {
    echo "✅ Tabel tbl_telat berhasil dibuat!\n\n";

    // Insert sample data jika diperlukan
    echo "=== MENAMBAHKAN SAMPLE DATA ===\n";
    $sample_sql = "
    INSERT INTO tbl_telat (no_induk, nama_siswa, kelas, tanggal, waktu_telat, id_mapel, no_induk_guru, keterangan) VALUES
    ('12345', 'Siswa Contoh', 'X-A', '2024-01-24', '07:15:00', NULL, '67890', 'Terlambat masuk kelas')
    ";
    if (mysqli_query($conn, $sample_sql)) {
        echo "✅ Sample data berhasil ditambahkan\n";
    } else {
        echo "⚠️ Gagal menambahkan sample data: " . mysqli_error($conn) . "\n";
    }

} else {
    echo "❌ Gagal membuat tabel: " . mysqli_error($conn) . "\n";
}

echo "\n=== SELESAI ===\n";
echo "Tabel tbl_telat siap digunakan untuk mencatat keterlambatan siswa.\n";

mysqli_close($conn);
?>