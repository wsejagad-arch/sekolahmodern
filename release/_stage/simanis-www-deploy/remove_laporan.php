<?php
$file = 'c:\xampp\htdocs\jurnal\sidebar.php';
$content = file_get_contents($file);

// Find and remove the Laporan nav item
$start = strpos($content, '<!-- Nav Item - Laporan -->');
if ($start !== false) {
    $end = strpos($content, '</li>', $start) + 5;
    $content = substr($content, 0, $start) . substr($content, $end);
    file_put_contents($file, $content);
    echo "Removed from sidebar.php\n";
}

$file2 = 'c:\xampp\htdocs\jurnal\home.php';
$content2 = file_get_contents($file2);
$content2 = preg_replace("/\s*case \'cetak-jurnal-guru\':\s*include \"cetak-jurnal-guru\.php\";\s*break;/", "", $content2);
$content2 = preg_replace("/\s*case \'buat-laporan\':\s*include \"form-laporan\.php\";\s*break;/", "", $content2);
file_put_contents($file2, $content2);
echo "Removed from home.php\n";

?>
