<?php
$dirs = ['c:/xampp/htdocs/jurnal/pages', 'c:/xampp/htdocs/jurnal/admin', 'c:/xampp/htdocs/jurnal/guru', 'c:/xampp/htdocs/jurnal'];
foreach ($dirs as $d) {
    if (!is_dir($d)) continue;
    $dir = new RecursiveDirectoryIterator($d);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    foreach($files as $file) {
        $path = $file[0];
        $content = file_get_contents($path);
        // Only replace if it matches exactly `<?php\nsession_start();` or `<?php if (session_status() === PHP_SESSION_NONE) { session_start(); }`
        $newContent = preg_replace('/(<\?php\s+)session_start\(\);/', '$1if (session_status() === PHP_SESSION_NONE) { session_start(); }', $content);
        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            echo "Fixed $path\n";
        }
    }
}
echo "Done.\n";
