<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\walikelas.php';
$content = file_get_contents($file);
$content = str_replace("WHERE s.kelas = '\$kelas_esc' AND i.validasi_wali_kelas IN ('Menunggu', 'Menunggu Validasi')", "WHERE REPLACE(i.kelas_siswa, ' ', '') = REPLACE('\$kelas_esc', ' ', '') AND i.validasi_wali_kelas IN ('Menunggu', 'Menunggu Validasi')", $content);
file_put_contents($file, $content);
echo "Updated walikelas.php\n";

$file2 = 'c:\xampp\htdocs\jurnal\pages\guru\validasi-izin.php';
$content2 = file_get_contents($file2);
$content2 = str_replace("WHERE s.kelas = '\$kelas_esc' AND i.validasi_wali_kelas IN ('Menunggu', 'Menunggu Validasi')", "WHERE REPLACE(i.kelas_siswa, ' ', '') = REPLACE('\$kelas_esc', ' ', '') AND i.validasi_wali_kelas IN ('Menunggu', 'Menunggu Validasi')", $content2);
file_put_contents($file2, $content2);
echo "Updated validasi-izin.php\n";
?>
