<?php
// buat_tabel_izin_siswa.php

include 'koneksi.php';

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$tableName = 'tbl_izin_siswa';

// Cek apakah tabel sudah ada
$checkTableQuery = "SHOW TABLES LIKE '$tableName'";
$result = mysqli_query($conn, $checkTableQuery);

if (mysqli_num_rows($result) > 0) {
    echo "Tabel `$tableName` sudah ada. Tidak ada tindakan yang dilakukan.";
} else {
    // SQL untuk membuat tabel
    $createQuery = "
    CREATE TABLE `$tableName` (
        `id_izin` INT(11) NOT NULL AUTO_INCREMENT,
        `no_induk_siswa` VARCHAR(50) NOT NULL,
        `kelas_siswa` VARCHAR(50) NOT NULL,
        `tanggal_izin` DATE NOT NULL,
        `jenis_izin` ENUM('Sakit', 'Izin', 'Dispensasi') NOT NULL,
        `detail_izin` TEXT NOT NULL,
        `lokasi_izin` VARCHAR(255) DEFAULT NULL,
        `foto_selfie` VARCHAR(255) DEFAULT NULL,
        `waktu_pengajuan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status_izin` ENUM('Menunggu Wali Kelas','Menunggu Guru BK','Disetujui Penuh','Ditolak','Menunggu Validasi','Disetujui Sebagian') NOT NULL DEFAULT 'Menunggu Wali Kelas',
        `validasi_wali_kelas` ENUM('Menunggu', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
        `validator_wali_kelas` VARCHAR(64) DEFAULT NULL,
        `waktu_validasi_wali_kelas` DATETIME DEFAULT NULL,
        `validasi_guru_bk` ENUM('Menunggu', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
        `validator_guru_bk` VARCHAR(64) DEFAULT NULL,
        `waktu_validasi_guru_bk` DATETIME DEFAULT NULL,
        `validasi_guru_mapel` ENUM('Menunggu', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
        `validator_guru_mapel` VARCHAR(64) DEFAULT NULL,
        `waktu_validasi_guru_mapel` DATETIME DEFAULT NULL,
        `catatan_penolakan` TEXT DEFAULT NULL,
        PRIMARY KEY (`id_izin`),
        KEY `idx_no_induk_siswa` (`no_induk_siswa`),
        KEY `idx_tanggal_izin` (`tanggal_izin`),
        KEY `idx_status_izin` (`status_izin`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (mysqli_query($conn, $createQuery)) {
        echo "Tabel `$tableName` berhasil dibuat.";
    } else {
        echo "Error saat membuat tabel `$tableName`: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
