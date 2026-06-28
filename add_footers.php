<?php
$target_files = [
    'c:\xampp\htdocs\jurnal\pages\siswa\ajukan-izin.php',
    'c:\xampp\htdocs\jurnal\pages\siswa\literasi.php',
    'c:\xampp\htdocs\jurnal\pages\siswa\tugas.php',
    'c:\xampp\htdocs\jurnal\pages\siswa\pelanggaran.php',
    'c:\xampp\htdocs\jurnal\pages\siswa\kalender.php',
    'c:\xampp\htdocs\jurnal\pengumuman.php'
];

foreach ($target_files as $f) {
    if (!file_exists($f)) {
        echo "File not found: " . basename($f) . "\n";
        continue;
    }
    
    $content = file_get_contents($f);
    
    if (basename($f) == 'pengumuman.php') {
        if (strpos($content, 'siswa_footer.php') === false) {
            $footerCode = "<?php if ((\$hakAkses ?? 0) == 3) include 'pages/siswa/siswa_footer.php'; ?>\n";
            // Replace <nav class="bottom-nav"> if exists, otherwise insert before </body>
            if (preg_match('/<(div|nav)\s+class="[^"]*(bottom-nav|bnav)[^"]*">.*?<\/\1>/is', $content)) {
                $content = preg_replace('/<(div|nav)\s+class="[^"]*(bottom-nav|bnav)[^"]*">.*?<\/\1>/is', $footerCode, $content);
            } else {
                $content = str_replace('</body>', $footerCode . '</body>', $content);
            }
            file_put_contents($f, $content);
            echo "Added conditional footer to pengumuman.php\n";
        }
    } else {
        if (strpos($content, 'siswa_footer.php') === false) {
            $footerCode = "<?php include 'siswa_footer.php'; ?>\n";
            
            // Check if there's an existing bottom nav
            $pattern = '/<(div|nav)\s+class="[^"]*(bottom-nav|bnav)[^"]*">.*?<\/\1>/is';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $footerCode, $content);
            } else {
                // Determine insertion point
                if (strpos($content, '<!-- BOTTOM NAV -->') !== false) {
                    $content = str_replace('<!-- BOTTOM NAV -->', "<!-- BOTTOM NAV -->\n" . $footerCode, $content);
                } else if (preg_match('/<\/div>\s*<!--\s*\/\.main-wrap\s*-->/i', $content)) {
                    $content = preg_replace('/(<\/div>\s*<!--\s*\/\.main-wrap\s*-->)/i', "$1\n" . $footerCode, $content);
                } else {
                    $content = str_replace('</body>', $footerCode . "\n</body>", $content);
                }
            }
            file_put_contents($f, $content);
            echo "Added footer to " . basename($f) . "\n";
        } else {
            echo "Already has footer: " . basename($f) . "\n";
        }
    }
}
?>
