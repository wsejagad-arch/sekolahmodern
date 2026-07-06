<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');

$startMarker = '<!-- Modul Ajar (AI) -->';
$endMarker = '<!-- Daftar Nilai -->';

$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);

if ($startPos !== false && $endPos !== false) {
    $before = substr($content, 0, $startPos);
    $after = substr($content, $endPos);
    
    $uiPatch = file_get_contents('c:\xampp\htdocs\jurnal\ui_patch.php');
    $modalPatch = file_get_contents('c:\xampp\htdocs\jurnal\modal_patch.php');
    
    // Replace the UI
    $newContent = $before . $uiPatch . "\n                    " . $after;
    
    // Add modal before <script> at the bottom
    $scriptPos = strrpos($newContent, '<script');
    if ($scriptPos !== false) {
        $newContent = substr($newContent, 0, $scriptPos) . $modalPatch . "\n" . substr($newContent, $scriptPos);
    }
    
    file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $newContent);
    echo "Replaced successfully.";
} else {
    echo "Markers not found!";
}
?>
