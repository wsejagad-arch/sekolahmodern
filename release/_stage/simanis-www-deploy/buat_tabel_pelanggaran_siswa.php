<?php
// Script untuk membuat tabel tbl_pelanggaran_siswa
require_once 'koneksi.php';

echo "=== MEMBUAT TABEL TBL_PELANGGARAN_SISWA ===\n\n";

// Cek koneksi
if ($conn === null || !($conn instanceof mysqli)) {
    die("ERROR: Koneksi database gagal! Pastikan MySQL sudah running.\n");
}

echo "✅ Koneksi database berhasil\n\n";

// Cek apakah tabel sudah ada
$check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran_siswa'");
if (mysqli_num_rows($check) > 0) {
    echo "⚠️ Tabel tbl_pelanggaran_siswa sudah ada!\n";
    echo "\n=== AUTO RECREATE ===\n";
    mysqli_query($conn, "DROP TABLE IF EXISTS tbl_pelanggaran_siswa");
    echo "✅ Tabel lama dihapus\n";
}

// SQL untuk membuat tabel
$sql = "
CREATE TABLE IF NOT EXISTS tbl_pelanggaran_siswa (
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
  status_pelanggaran enum('Aktif','Diselesaikan','Follow Up') DEFAULT 'Aktif',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pelanggaran),
  KEY idx_siswa (no_induk),
  KEY idx_tanggal (tanggal_pelanggaran),
  KEY idx_kategori (kategori_pelanggaran),
  KEY idx_guru (no_induk_guru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (mysqli_query($conn, $sql)) {
    echo "✅ Tabel tbl_pelanggaran_siswa berhasil dibuat!\n\n";
    
    // Verifikasi
    $verify = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran_siswa'");
    if (mysqli_num_rows($verify) > 0) {
        echo "✅ VERIFIKASI: Tabel tbl_pelanggaran_siswa sudah ada di database\n\n";
        
        // Tampilkan struktur
        echo "=== STRUKTUR TABEL ===\n";
        $columns = mysqli_query($conn, "DESCRIBE tbl_pelanggaran_siswa");
        while ($col = mysqli_fetch_assoc($columns)) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        
        echo "\n✅ SELESAI! Tabel pelanggaran_siswa siap digunakan.\n";
        echo "Silakan refresh halaman guru dan coba lagi.\n";
    } else {
        echo "❌ ERROR: Verifikasi gagal!\n";
    }
} else {
    echo "❌ ERROR: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
