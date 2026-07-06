<?php
$files = glob('c:\xampp\htdocs\jurnal\pages\siswa\*.php');
foreach ($files as $f) {
    if (basename($f) == 'literasi.php' || basename($f) == 'literasi_misi.php' || basename($f) == 'literasi_evaluasi.php' || basename($f) == 'siswa_footer.php') continue;
    
    $content = file_get_contents($f);
    
    // Replace <nav class="bottom-nav">...</nav> or <nav class="bnav">...</nav>
    // Note: .*? matches non-greedily, but we also want to match newlines using the 's' modifier.
    $pattern = '/<nav class="(bottom-nav|bnav)">.*?<\/nav>/is';
    
    if (preg_match($pattern, $content)) {
        $replacement = "<?php include 'siswa_footer.php'; ?>\n";
        $new_content = preg_replace($pattern, $replacement, $content);
        
        if ($new_content !== null) {
            file_put_contents($f, $new_content);
            echo "Updated " . basename($f) . "\n";
        }
    }
}
?>
