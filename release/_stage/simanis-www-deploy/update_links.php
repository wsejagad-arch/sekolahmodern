<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\siswa_footer.php';
$content = file_get_contents($file);

// Replace href="siswa.php" with href="/jurnal/pages/siswa/siswa.php", etc.
$content = str_replace('href="siswa.php"', 'href="/jurnal/pages/siswa/siswa.php"', $content);
$content = str_replace('href="presensi.php"', 'href="/jurnal/pages/siswa/presensi.php"', $content);
$content = str_replace('href="profil.php"', 'href="/jurnal/pages/siswa/profil.php"', $content);

file_put_contents($file, $content);
echo "Updated links in siswa_footer.php";
?>
