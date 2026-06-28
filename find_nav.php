<?php
$files = glob('c:\xampp\htdocs\jurnal\pages\siswa\*.php');
foreach ($files as $f) {
    if (basename($f) == 'literasi.php' || basename($f) == 'siswa_footer.php') continue;
    $content = file_get_contents($f);
    // Remove existing bottom-nav blocks
    if (preg_match('/<nav class="(bottom-nav|bnav)">(.*?)<\/nav>/is', $content)) {
        echo "Found nav in " . basename($f) . "\n";
    }
}
?>
