<?php
$files = glob('c:\xampp\htdocs\jurnal\pages\siswa\*.php');
foreach ($files as $f) {
    if (in_array(basename($f), ['literasi.php', 'literasi_misi.php', 'literasi_evaluasi.php', 'siswa_footer.php'])) continue;
    
    $content = file_get_contents($f);
    if (strpos($content, 'siswa_footer.php') !== false) continue;
    
    // Find <div class="bottom-nav"> or <div class="bnav"> and its matching closing tag
    // Since we know the bottom nav is at the end, we can replace everything from <div class="bottom-nav"> to the end of the file except </body></html>
    
    $pattern = '/<(div|nav)\s+class="[^"]*(bottom-nav|bnav)[^"]*">.*?<\/\1>/is';
    
    if (preg_match($pattern, $content)) {
        $replacement = "<?php include 'siswa_footer.php'; ?>\n";
        $new_content = preg_replace($pattern, $replacement, $content);
        file_put_contents($f, $new_content);
        echo "Updated " . basename($f) . "\n";
    } else {
        echo "Could not find nav in " . basename($f) . "\n";
    }
}
?>
