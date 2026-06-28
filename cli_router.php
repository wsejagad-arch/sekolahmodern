<?php
if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $path = __DIR__ . $uri;

    // 1. Exact rewrites from .htaccess
    $exactMatches = [
        '/masuk' => '/login.php',
        '/auth' => '/login_action.php',
        '/keluar' => '/logout.php',
        '/lupa-password' => '/forgot-password.php',
        '/admin' => '/home.php?page=admin',
        '/guru' => '/home.php?page=guru',
        '/siswa' => '/home.php?page=siswa',
        '/kelas' => '/home.php?page=kelas',
        '/mapel' => '/home.php?page=mapel',
        '/jadwal' => '/home.php?page=jadwal',
        '/kehadiran' => '/home.php?page=kehadiran',
        '/jurnal' => '/home.php?page=jurnal',
        '/pengumuman' => '/home.php?page=pengumuman',
    ];

    if (isset($exactMatches[$uri])) {
        $parts = explode('?', $exactMatches[$uri]);
        $_SERVER['SCRIPT_NAME'] = $parts[0];
        if (isset($parts[1])) {
            parse_str($parts[1], $query);
            $_GET = array_merge($_GET, $query);
        }
        include __DIR__ . $parts[0];
        return true;
    }

    // 2. Regex rewrites. Keep these before static file serving because some
    // legacy extensionless files contain PHP and must not be served as text.
    if (preg_match('#^/pages/(guru|siswa|admin|public)/([^/.]+)(?:\.php)?/?$#', $uri, $matches)) {
        $_GET['type'] = $matches[1] === 'public' ? 'public' : $matches[1];
        $_GET['page'] = $matches[2];
        $_SERVER['SCRIPT_NAME'] = '/router.php';
        include __DIR__ . '/router.php';
        return true;
    }

    // 3. Fallback for /pages/...
    if (preg_match('#^/pages/([^/.]+)(?:\.php)?/?$#', $uri, $matches)) {
        $_GET['type'] = 'public';
        $_GET['page'] = $matches[1];
        $_SERVER['SCRIPT_NAME'] = '/router.php';
        include __DIR__ . '/router.php';
        return true;
    }

    // 4. If it's a real file/dir (like assets/css), serve it directly
    if ($uri !== '/' && (is_file($path) || is_dir($path)) && $uri !== '/cli_router.php') {
        return false;
    }

    if (preg_match('#^/home/?$#', $uri)) {
        $_SERVER['SCRIPT_NAME'] = '/home.php';
        include __DIR__ . '/home.php';
        return true;
    }
    
    if (preg_match('#^/home/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
        $_GET['page'] = $matches[1];
        $_SERVER['SCRIPT_NAME'] = '/home.php';
        include __DIR__ . '/home.php';
        return true;
    }
    
    // If we have an extensionless file in root like /login that actually should hit login.php
    if ($uri !== '/' && file_exists(__DIR__ . $uri . '.php')) {
        $_SERVER['SCRIPT_NAME'] = $uri . '.php';
        include __DIR__ . $uri . '.php';
        return true;
    }
    
    // 5. Default index.php
    if ($uri === '/') {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        include __DIR__ . '/index.php';
        return true;
    }
    
    // 404
    http_response_code(404);
    echo "The requested resource $uri was not found on this server.";
    return true;
}
return false;
