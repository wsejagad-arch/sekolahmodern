<?php
/**
 * Migrasi: Tambah kolom foto_selfie pada tbl_izin_siswa
 * Jalankan sekali: http://localhost/jurnal/migrasi_izin_swafoto.php
 */
include 'koneksi.php';

$messages = [];

// 1. Cek & tambah kolom foto_selfie
$cekKolom = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa LIKE 'foto_selfie'");
if ($cekKolom && mysqli_num_rows($cekKolom) === 0) {
    $r = mysqli_query($conn, "ALTER TABLE tbl_izin_siswa ADD COLUMN `foto_selfie` VARCHAR(255) DEFAULT NULL AFTER `lokasi_izin`");
    $messages[] = $r ? "✅ Kolom foto_selfie berhasil ditambahkan." : "❌ Gagal: " . mysqli_error($conn);
} else {
    $messages[] = "ℹ️ Kolom foto_selfie sudah ada.";
}

// 2. Cek & tambah kolom status_izin enum baru jika perlu
$cekEnum = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa LIKE 'status_izin'");
if ($cekEnum) {
    $row = mysqli_fetch_assoc($cekEnum);
    if (strpos($row['Type'], 'Menunggu Wali Kelas') === false) {
        $r = mysqli_query($conn,
            "ALTER TABLE tbl_izin_siswa MODIFY COLUMN `status_izin`
             ENUM('Menunggu Wali Kelas','Menunggu Guru BK','Disetujui Penuh','Ditolak','Menunggu Validasi','Disetujui Sebagian')
             NOT NULL DEFAULT 'Menunggu Wali Kelas'"
        );
        $messages[] = $r ? "✅ ENUM status_izin diperbarui (tambah Menunggu Wali Kelas, Menunggu Guru BK)." : "❌ Gagal ubah ENUM: " . mysqli_error($conn);
    } else {
        $messages[] = "ℹ️ ENUM status_izin sudah up-to-date.";
    }
}

// 3. Update data lama yang masih 'Menunggu Validasi' ganti ke 'Menunggu Wali Kelas'
$r2 = mysqli_query($conn, "UPDATE tbl_izin_siswa SET status_izin='Menunggu Wali Kelas' WHERE status_izin='Menunggu Validasi' AND validasi_wali_kelas='Menunggu'");
$messages[] = "ℹ️ Data lama dikonversi ke alur baru: " . mysqli_affected_rows($conn) . " baris.";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Migrasi Izin Swafoto</title>
<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;background:#f5f5f5}
.card{background:#fff;border-radius:8px;padding:24px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
h2{color:#333;margin-top:0}li{padding:6px 0;border-bottom:1px solid #eee}
a{color:#2563eb;text-decoration:none}a:hover{text-decoration:underline}</style>
</head>
<body>
<div class="card">
    <h2>🔧 Migrasi: Izin Siswa + Swafoto</h2>
    <ul>
        <?php foreach ($messages as $m): ?>
            <li><?= $m ?></li>
        <?php endforeach; ?>
    </ul>
    <p style="margin-top:20px">
        <a href="index.php">← Kembali ke Beranda</a>
    </p>
</div>
</body>
</html>
