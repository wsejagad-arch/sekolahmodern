<?php
$file = 'c:\xampp\htdocs\jurnal\koneksi.php';
$content = file_get_contents($file);

$auto_alpa_logic = "
// === AUTO-ALPA & AUTO-EXPIRE IZIN ===
// Cek izin yang belum divalidasi dan sudah lewat hari
\$today_alpa = date('Y-m-d');
\$qExpired = mysqli_query(\$conn, \"SELECT id_izin, no_induk_siswa, kelas_siswa FROM tbl_izin_siswa WHERE tanggal_izin < '\$today_alpa' AND status_izin IN ('Menunggu', 'Menunggu Validasi')\");
if (\$qExpired && mysqli_num_rows(\$qExpired) > 0) {
    while (\$rowExp = mysqli_fetch_assoc(\$qExpired)) {
        \$id_izin_exp = \$rowExp['id_izin'];
        \$nis_exp = \$rowExp['no_induk_siswa'];
        \$kelas_exp = \$rowExp['kelas_siswa'];
        
        // Ubah status jadi Ditolak (Auto-Alpa)
        mysqli_query(\$conn, \"UPDATE tbl_izin_siswa SET status_izin = 'Ditolak (Auto-Alpa)', validasi_wali_kelas = 'Ditolak', validasi_guru_bk = 'Ditolak' WHERE id_izin = '\$id_izin_exp'\");
        
        // Ambil mapel hari ini untuk siswa tsb
        // (Sistem kompleks, untuk saat ini cukup tandai di izinnya)
    }
}
";

if (strpos($content, "AUTO-ALPA") === false) {
    $content .= "\n" . $auto_alpa_logic;
    file_put_contents($file, $content);
    echo "Added auto-alpa to koneksi.php\n";
} else {
    echo "Auto-alpa already exists.\n";
}
?>
