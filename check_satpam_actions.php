<?php
$file = 'c:\xampp\htdocs\jurnal\satpam.php';
$content = file_get_contents($file);
$matches = [];
preg_match_all('/if\s*\(\$action\s*===\s*.*?\{.*?(?=\n\s*(?:elseif|if\s*\(\$action\s*===|\?>))/is', $content, $matches);
foreach ($matches[0] as $match) {
    echo "=================\n" . substr($match, 0, 1000) . "...\n";
}
?>
