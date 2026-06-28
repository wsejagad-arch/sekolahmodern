<?php
$files = glob('c:\xampp\htdocs\jurnal\pages\siswa\*.php');
foreach ($files as $f) {
    if (basename($f) == 'literasi.php' || basename($f) == 'literasi_misi.php' || basename($f) == 'literasi_evaluasi.php' || basename($f) == 'siswa_footer.php') continue;
    
    $content = file_get_contents($f);
    
    if (strpos($content, 'siswa_footer.php') === false) {
        echo "Missing in: " . basename($f) . "\n";
    }
}
?>
