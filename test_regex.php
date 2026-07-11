<?php
function get_app_url() { return "http://localhost"; }
function get_base_path() { return ""; }
function asset_url($path) { return "http://localhost/" . ltrim($path, '/'); }

function hide_links_callback($buffer) {
    $masuk = asset_url('masuk');
    $keluar = asset_url('keluar');
    $home = asset_url('home');
    
    // 1. Clean URL Replacements using absolute paths
    $buffer = str_replace('href="login.php"', 'href="' . $masuk . '"', $buffer);
    $buffer = str_replace("href='login.php'", "href='" . $masuk . "'", $buffer);
    $buffer = str_replace('href="logout.php"', 'href="' . $keluar . '"', $buffer);
    $buffer = str_replace("href='logout.php'", "href='" . $keluar . "'", $buffer);
    $buffer = str_replace('href="home.php"', 'href="' . $home . '"', $buffer);
    $buffer = str_replace("href='home.php'", "href='" . $home . "'", $buffer);
    
    // Replace home.php?page=xxx to absolute URL home/xxx
    $buffer = preg_replace_callback('/href=["\']home\.php\?page=([a-zA-Z0-9_-]+)["\']/', function($matches) {
        return 'href="' . asset_url('home/' . $matches[1]) . '"';
    }, $buffer);

    // 2. JS Obfuscation for extreme hiding (Optional, but enabled here)
    // We only obfuscate links that start with masuk, keluar, home, or pages
    $app_url_quoted = preg_quote(get_app_url() . get_base_path(), '/');
    
    $buffer = preg_replace_callback('/<a\s+(.*?)href="((?:' . $app_url_quoted . '\/|)(?:masuk|keluar|home|pages)[^"]*)"(.*?)>/i', function($matches) {
        $url = $matches[2];
        if (strpos($url, 'http') !== 0) {
            $url = asset_url(ltrim($url, '/'));
        }
        $b64 = base64_encode($url);
        return '<a ' . $matches[1] . 'href="javascript:void(0)" data-sec-target="' . $b64 . '" onclick="secNav(this)"' . $matches[3] . '>';
    }, $buffer);

    return $buffer;
}

$buffer = '<a class="collapse-item" href="home.php?page=reset-semester"><i class="fas fa-trash-restore-alt text-danger mr-1" style="font-size:11px"></i>Reset Semester Baru</a>';
echo "ORIGINAL: " . $buffer . "\n";
$buffer = hide_links_callback($buffer);
echo "REPLACED: " . $buffer . "\n";
