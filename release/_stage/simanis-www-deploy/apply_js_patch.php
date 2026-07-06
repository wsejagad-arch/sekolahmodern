<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');

$startMarker = '    try {';
$endMarker = "    } finally {\n        $('#ai-perangkat-loading').addClass('d-none');\n    }\n}";

$startPos = strpos($content, $startMarker, strpos($content, 'window.generatePerangkatAI'));
$endPos = strpos($content, $endMarker, $startPos);

if ($startPos !== false && $endPos !== false) {
    $before = substr($content, 0, $startPos);
    $after = substr($content, $endPos + strlen($endMarker));
    
    $patch = file_get_contents('c:\xampp\htdocs\jurnal\generate_patch.js');
    $drivePatch = file_get_contents('c:\xampp\htdocs\jurnal\js_drive_patch.js');
    
    // Add the new try-catch block and also the new Drive patch right after the window.generatePerangkatAI function
    $newContent = $before . $patch . "\n\n" . $drivePatch . "\n\n" . $after;
    
    file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $newContent);
    echo "Replaced successfully.";
} else {
    echo "Markers not found!";
}
?>
