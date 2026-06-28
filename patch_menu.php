<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\siswa.php';
$content = file_get_contents($file);
$content = preg_replace("/\s*\[\'name\' => \'Profil\',\s*\'icon\' => \'fa-user-graduate\',\s*\'color\' => \'bg-purple\',\s*\'link\' => \'profil\.php\'\],/", "", $content);
file_put_contents($file, $content);
echo "Profil menu removed";
?>
