<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\siswa.php';
$content = file_get_contents($file);

$old_code = "if (!\$sudah_absen) {
    \$all_notifications[] = [
        'type' => 'absen',
        'icon' => 'fas fa-fingerprint',
        'color' => '#ef4444',
        'title' => 'Pengingat Presensi',
        'text' => 'Anda belum melakukan presensi hari ini.',
        'link' => 'presensi.php'
    ];
}";

$new_code = "\$ada_jadwal = false;
\$__kls = mysqli_real_escape_string(\$conn, \$kls);
\$__hr = mysqli_real_escape_string(\$conn, \$hariini);
\$__qJadwal = @mysqli_query(\$conn, \"SELECT id_mapel FROM tbl_mapel_ampu WHERE kelas='\$__kls' AND hari='\$__hr' LIMIT 1\");
if (\$__qJadwal && mysqli_num_rows(\$__qJadwal) > 0) {
    \$ada_jadwal = true;
}

if (!\$sudah_absen && \$ada_jadwal) {
    \$all_notifications[] = [
        'type' => 'absen',
        'icon' => 'fas fa-fingerprint',
        'color' => '#ef4444',
        'title' => 'Pengingat Presensi',
        'text' => 'Anda belum melakukan presensi hari ini.',
        'link' => 'presensi.php'
    ];
}";

$content = str_replace($old_code, $new_code, $content);
file_put_contents($file, $content);
echo "Updated siswa.php successfully.";
?>
