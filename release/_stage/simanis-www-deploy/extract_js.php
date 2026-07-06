<?php
$html = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');
preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $html, $matches);
foreach ($matches[1] as $idx => $js) {
    if (trim($js) === '') continue;
    file_put_contents('c:\xampp\htdocs\jurnal\test_script.js', $js);
    echo "Checking script block $idx...\n";
    system('node -c c:\xampp\htdocs\jurnal\test_script.js');
}
?>
