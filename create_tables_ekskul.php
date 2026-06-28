<?php
include __DIR__ . '/koneksi.php';
$queries = [
  "CREATE TABLE IF NOT EXISTS tbl_ekskul (
    id_ekskul INT AUTO_INCREMENT PRIMARY KEY,
    nama_ekskul VARCHAR(100) NOT NULL,
    deskripsi TEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS tbl_pembina_ekskul (
    id_pembina INT AUTO_INCREMENT PRIMARY KEY,
    id_ekskul INT NOT NULL,
    no_induk_guru VARCHAR(50) NOT NULL,
    UNIQUE KEY (id_ekskul, no_induk_guru)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS tbl_anggota_ekskul (
    id_anggota INT AUTO_INCREMENT PRIMARY KEY,
    id_ekskul INT NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    nilai VARCHAR(5) DEFAULT '',
    UNIQUE KEY (id_ekskul, no_induk_siswa)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS tbl_jadwal_ekskul (
    id_jadwal INT AUTO_INCREMENT PRIMARY KEY,
    id_ekskul INT NOT NULL,
    hari VARCHAR(20) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS tbl_tugas_ekskul (
    id_tugas INT AUTO_INCREMENT PRIMARY KEY,
    id_ekskul INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    tanggal DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS tbl_presensi_ekskul (
    id_presensi INT AUTO_INCREMENT PRIMARY KEY,
    id_ekskul INT NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Hadir',
    foto_bukti VARCHAR(255) DEFAULT '',
    UNIQUE KEY (id_ekskul, no_induk_siswa, tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS tbl_jurnal_ekskul (
    id_jurnal INT AUTO_INCREMENT PRIMARY KEY,
    id_ekskul INT NOT NULL,
    no_induk_guru VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    materi TEXT,
    keterangan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (id_ekskul, tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
foreach($queries as $q) {
  if(!mysqli_query($conn, $q)) echo mysqli_error($conn)."\n";
}
echo "Tables checked/created.";
