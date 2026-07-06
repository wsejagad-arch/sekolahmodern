<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\guru_notifikasi.php';
$content = file_get_contents($file);

$logic = <<<PHP

// Total notifikasi
PHP;

$newLogic = <<<PHP

// 4. Pengajuan Izin Menunggu Validasi (Jika Wali Kelas)
\$jmlIzinValidasi = 0;
if (!empty(\$nipguru)) {
    \$qWk = mysqli_query(\$conn, "SELECT kelas FROM tbl_kelas WHERE nip_wali = '\$nipguru' LIMIT 1");
    if (\$qWk && mysqli_num_rows(\$qWk) > 0) {
        \$rwk = mysqli_fetch_assoc(\$qWk);
        \$k_wali = mysqli_real_escape_string(\$conn, \$rwk['kelas']);
        \$qIzin = mysqli_query(\$conn, "SELECT COUNT(*) as jml FROM tbl_izin_siswa WHERE REPLACE(kelas_siswa, ' ', '') = REPLACE('\$k_wali', ' ', '') AND validasi_wali_kelas IN ('Menunggu', 'Menunggu Validasi')");
        if (\$qIzin) {
            \$rowIzin = mysqli_fetch_assoc(\$qIzin);
            \$jmlIzinValidasi = (int)(\$rowIzin['jml'] ?? 0);
        }
    }
}
if (\$jmlIzinValidasi > 0) {
    \$notifikasiData[] = [
        'type' => 'validasi_izin',
        'title' => 'Validasi Izin Siswa',
        'message' => \$jmlIzinValidasi . ' pengajuan izin menunggu validasi Anda.',
        'icon' => 'bi-patch-check',
        'color' => 'warning',
        'count' => \$jmlIzinValidasi,
        'link' => 'validasi-izin.php'
    ];
}

// Total notifikasi
PHP;

$content = str_replace($logic, $newLogic, $content);
file_put_contents($file, $content);
echo "Updated guru_notifikasi.php\n";
?>
