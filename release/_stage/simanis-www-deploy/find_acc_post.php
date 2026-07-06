<?php
$dir = new RecursiveDirectoryIterator('c:\xampp\htdocs\jurnal');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
foreach($files as $file) {
    $content = file_get_contents($file[0]);
    if (strpos($content, 'acc_wali') !== false && strpos($content, '$_POST') !== false) {
        echo $file[0] . "\n";
    }
}
?>
