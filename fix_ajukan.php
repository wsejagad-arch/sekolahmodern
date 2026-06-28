<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\ajukan-izin.php';
$content = file_get_contents($file);
$content = str_replace('tbl_izin', 'tbl_izin_siswa', $content);
// Also the column no_induk_siswa instead of nis? Wait, let's check tbl_izin_siswa structure.
file_put_contents($file, $content);
echo "ajukan-izin.php updated to tbl_izin_siswa";
?>
