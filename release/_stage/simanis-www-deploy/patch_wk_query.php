<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\walikelas.php';
$content = file_get_contents($file);
$content = str_replace("i.validasi_wali_kelas = 'Menunggu'", "i.validasi_wali_kelas IN ('Menunggu', 'Menunggu Validasi')", $content);
file_put_contents($file, $content);
echo "Updated walikelas.php query";
?>
