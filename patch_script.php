<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');
$patch = file_get_contents('c:\xampp\htdocs\jurnal\ajax_patch.php');

$content = str_replace("if (\$action === 'get_siswa_kelas') {", $patch . "\n\n    if (\$action === 'get_siswa_kelas') {", $content);

file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $content);
echo "Patched successfully.";
?>
