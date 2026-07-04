<?php
// Script untuk membuat tabel pelanggaran
require_once 'koneksi.php';

echo "<h2>SETUP TABEL PELANGGARAN</h2>";

// Cek koneksi database
if ($conn === null || !($conn instanceof mysqli)) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 2px solid red; border-radius: 5px;'>";
    echo "<h3>❌ Error: Koneksi Database Gagal</h3>";
    echo "<p><strong>Kemungkinan penyebab:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL/MariaDB di XAMPP belum dijalankan</li>";
    echo "<li>Konfigurasi database di <code>koneksi_local.php</code> salah</li>";
    echo "<li>Database '<strong>sijurnal</strong>' belum dibuat</li>";
    echo "</ul>";
    echo "<p><strong>Langkah perbaikan:</strong></p>";
    echo "<ol>";
    echo "<li>Buka XAMPP Control Panel</li>";
    echo "<li>Start Apache dan MySQL</li>";
    echo "<li>Klik Admin pada MySQL untuk membuka phpMyAdmin</li>";
    echo "<li>Buat database baru dengan nama '<strong>sijurnal</strong>' jika belum ada</li>";
    echo "<li>Refresh halaman ini</li>";
    echo "</ol>";
    
    // Tampilkan info konfigurasi
    if (file_exists(__DIR__ . '/koneksi_local.php')) {
        echo "<p><strong>File koneksi lokal:</strong> koneksi_local.php (ditemukan)</p>";
        echo "<p>Pastikan konfigurasi di file tersebut sesuai dengan database lokal Anda.</p>";
    }
    
    echo "</div>";
    exit;
}

echo "<p>✅ Koneksi database berhasil</p>";

// Cek apakah tabel sudah ada
$check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran'");
if (mysqli_num_rows($check) > 0) {
    echo "<p style='color: orange;'>⚠️ Tabel tbl_pelanggaran sudah ada di database.</p>";
    echo "<p>Apakah Anda ingin membuat ulang tabel? (Data lama akan hilang)</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='recreate' value='yes' onclick=\"return confirm('Yakin ingin membuat ulang tabel? Data lama akan hilang!')\">Ya, Buat Ulang</button>";
    echo " <a href='javascript:history.back()'>Batal</a>";
    echo "</form>";
    
    if (isset($_POST['recreate']) && $_POST['recreate'] == 'yes') {
        mysqli_query($conn, "DROP TABLE IF EXISTS tbl_pelanggaran");
        echo "<p style='color: blue;'>Tabel lama sudah dihapus. Membuat tabel baru...</p>";
    } else {
        exit;
    }
}

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
  status_pelanggaran enum('Aktif','Diselesaikan','Follow Up') DEFAULT 'Aktif',
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
    echo "<p style='color: green;'>✅ <strong>Tabel tbl_pelanggaran berhasil dibuat!</strong></p>";
    
    echo "<h3>Struktur Tabel:</h3>";
    echo "<ul>";
    echo "<li>id_pelanggaran - Primary key auto increment</li>";
    echo "<li>no_induk - Nomor induk siswa</li>";
    echo "<li>nama_siswa - Nama siswa</li>";
    echo "<li>kelas - Kelas siswa</li>";
    echo "<li>tanggal_pelanggaran - Tanggal kejadian</li>";
    echo "<li>kategori_pelanggaran - Berat/Sedang/Ringan</li>";
    echo "<li>jenis_pelanggaran - Jenis pelanggaran</li>";
    echo "<li>deskripsi_pelanggaran - Detail kejadian</li>";
    echo "<li>tindakan_yang_diambil - Tindakan dari guru</li>";
    echo "<li>no_induk_guru - Nomor induk guru yang mencatat</li>";
    echo "<li>nama_guru - Nama guru</li>";
    echo "<li>status_pelanggaran - Aktif/Diselesaikan/Follow Up</li>";
    echo "<li>created_at - Waktu dibuat</li>";
    echo "<li>updated_at - Waktu terakhir diupdate</li>";
    echo "</ul>";
    
    // Verifikasi tabel
    $verify = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran'");
    if (mysqli_num_rows($verify) > 0) {
        echo "<p style='color: green;'>✅ Verifikasi: Tabel tbl_pelanggaran sudah tersedia di database</p>";
    }
    
    echo "<p><strong>Sekarang Anda dapat menggunakan fitur pelanggaran siswa.</strong></p>";
    echo "<p><a href=<?= guru_page('guru') ?>>Kembali ke Halaman Guru</a></p>";
    
} else {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> " . mysqli_error($conn) . "</p>";
    echo "<p>Silakan hubungi administrator untuk bantuan lebih lanjut.</p>";
}

mysqli_close($conn);
?>
