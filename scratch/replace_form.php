<?php
$profilFile = 'c:\xampp\htdocs\jurnal\pages\siswa\profil.php';
$newFormFile = 'c:\xampp\htdocs\jurnal\scratch\new_form.php';

$lines = file($profilFile);
$newForm = file_get_contents($newFormFile);

$startIdx = -1;
$endIdx = -1;

foreach ($lines as $i => $line) {
    if (strpos($line, '<form method="POST" action="" id="formProfil">') !== false) {
        $startIdx = $i;
    }
    if (strpos($line, '</form>') !== false) {
        $endIdx = $i;
    }
}

if ($startIdx !== -1 && $endIdx !== -1 && $endIdx > $startIdx) {
    $before = array_slice($lines, 0, $startIdx);
    $after = array_slice($lines, $endIdx + 1);
    
    $newLines = array_merge($before, [$newForm . PHP_EOL], $after);
    file_put_contents($profilFile, implode("", $newLines));
    echo "Form replaced successfully. Start: $startIdx, End: $endIdx\n";
} else {
    echo "Failed to find form boundaries. Start: $startIdx, End: $endIdx\n";
}
?>
