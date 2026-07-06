<?php
$file = 'c:\xampp\htdocs\jurnal\sidebar.php';
$content = file_get_contents($file);
// Remove cetak-kehadiran-guru from sidebar
$content = preg_replace("/\s*<a class=\"collapse-item\" href=\"home\.php\?page=cetak-kehadiran-guru\">.*?<\/a>/i", "", $content);
file_put_contents($file, $content);

$file2 = 'c:\xampp\htdocs\jurnal\home.php';
$content2 = file_get_contents($file2);
// Remove cetak-kehadiran-guru from home.php
$content2 = preg_replace("/\s*case \'cetak-kehadiran-guru\':\s*include \"form-cetak-kehadiran\.php\";\s*break;/", "", $content2);
file_put_contents($file2, $content2);

echo "Sidebar and routing updated.\n";
?>
