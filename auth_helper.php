<?php

/**
 * auth_helper.php
 * Centralized session + auth utilities for admin panel.
 * Include this file at the top of every protected page BEFORE output.
 */

// Harden session cookie params (adjust domain if needed)
if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookieParams = session_get_cookie_params();
    // Force path root and HttpOnly; domain left automatic for localhost
    // Set lifetime ke 10 tahun (315360000 detik) untuk login "selamanya"
    session_set_cookie_params([
        'lifetime' => 315360000, // 10 tahun dalam detik
        'path' => '/',
        'domain' => $cookieParams['domain'] ?: '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Set session garbage collection lifetime
    ini_set('session.gc_maxlifetime', 315360000); // 10 tahun

    session_start();
}

// Normalize role accessor
function current_role()
{
    return isset($_SESSION['hak_akses']) ? (int)$_SESSION['hak_akses'] : null;
}

function is_admin()
{
    return current_role() === 1;
}
function is_guru()
{
    return current_role() === 2;
}
function is_siswa()
{
    return current_role() === 3;
}
function is_admin_pusat()
{
    return current_role() === 9;
}

function login_required_url(): string
{
    $basePath = '';
    if (function_exists('get_base_path')) {
        $basePath = get_base_path();
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($scriptName, '/jurnal/') === 0 || $scriptName === '/jurnal') {
            $basePath = '/jurnal';
        }
    }

    return $basePath . '/login.php?haruslogin';
}

function require_login()
{
    if (!isset($_SESSION['username']) && !is_admin() && !is_guru() && !is_siswa() && !is_admin_pusat()) {
        // Log login requirement violation
        log_access_violation('login_required', 'anonymous', $_SERVER['REQUEST_URI'] ?? '');
        $loginUrl = login_required_url();

        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
            exit;
        } else {
            // Headers already sent, use JavaScript redirect
            echo '<script>window.location.href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '"></noscript>';
            exit;
        }
    }
}

function require_admin_pusat()
{
    if (!is_admin_pusat() || empty($_SESSION['id_admin_pusat'])) {
        log_access_violation('admin_pusat_required', current_user_id(), $_SERVER['REQUEST_URI'] ?? '');
        $loginUrl = 'admin-pusat-login.php?haruslogin';

        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
            exit;
        }

        echo '<script>window.location.href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit;
    }
}

function require_admin()
{
    require_login();
    if (!is_admin()) {
        // Log access violation
        log_access_violation('admin_required', current_user_id(), $_SERVER['REQUEST_URI'] ?? '');

        if (isset($_GET['debug_akses'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h4>Akses ditolak (bukan admin).</h4>';
            echo 'Session hak_akses=' . htmlspecialchars((string)current_role());
            exit;
        }

        // Redirect based on role instead of 403.php
        if (is_guru()) {
            $redirectUrl = 'home.php';
        } elseif (is_siswa()) {
            $redirectUrl = function_exists('siswa_page') ? siswa_page('siswa') : 'home.php';
        } else {
            $redirectUrl = login_required_url();
        }

        // Check if headers already sent (due to sidebar output)
        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            // Headers already sent, use JavaScript redirect
            echo '<script>window.location.href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '"></noscript>';
            exit;
        }
    }
}

function require_admin_ajax()
{
    if (!is_admin()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
}

// Helper to unify ID info
function current_user_id()
{
    if (isset($_SESSION['id_admin_pusat'])) return $_SESSION['id_admin_pusat'];
    if (isset($_SESSION['id_user'])) return $_SESSION['id_user'];
    if (isset($_SESSION['no_induk'])) return $_SESSION['no_induk'];
    return null;
}

function set_admin_session(array $user): void
{
    $_SESSION['id_user'] = $user['id_user'] ?? null;
    $_SESSION['username'] = $user['username'] ?? '';
    $_SESSION['nama'] = $user['nama'] ?? $_SESSION['username'];
    $_SESSION['hak_akses'] = isset($user['hak_akses']) ? (int)$user['hak_akses'] : 1;
    $_SESSION['id_sekolah'] = isset($user['id_sekolah']) ? (int)$user['id_sekolah'] : 1;
    $_SESSION['kode_sekolah'] = $user['kode_sekolah'] ?? ($_SESSION['kode_sekolah'] ?? 'DEFAULT');
    unset($_SESSION['id_admin_pusat'], $_SESSION['email'], $_SESSION['_online_last_ping']);
}

function set_admin_pusat_session(array $admin): void
{
    $_SESSION['id_admin_pusat'] = $admin['id_admin_pusat'] ?? null;
    $_SESSION['username'] = $admin['username'] ?? '';
    $_SESSION['nama'] = $admin['nama'] ?? $_SESSION['username'];
    $_SESSION['email'] = $admin['email'] ?? '';
    $_SESSION['hak_akses'] = 9;
    $_SESSION['id_sekolah'] = 0;
    $_SESSION['kode_sekolah'] = 'PUSAT';
    unset($_SESSION['id_user'], $_SESSION['no_induk'], $_SESSION['nama_guru'], $_SESSION['nama_siswa'], $_SESSION['kelas'], $_SESSION['_online_last_ping']);
}

function set_guru_session(array $guru): void
{
    $_SESSION['no_induk'] = $guru['no_induk'] ?? '';
    $_SESSION['username'] = $guru['no_induk'] ?? '';
    $_SESSION['nama_guru'] = $guru['nama_guru'] ?? '';
    $_SESSION['nama'] = $guru['nama_guru'] ?? $_SESSION['username'];
    $_SESSION['hak_akses'] = 2;
    $_SESSION['status_kepegawaian'] = $guru['status_kepegawaian'] ?? '';
    $_SESSION['id_sekolah'] = isset($guru['id_sekolah']) ? (int)$guru['id_sekolah'] : 1;
    $_SESSION['kode_sekolah'] = $guru['kode_sekolah'] ?? ($_SESSION['kode_sekolah'] ?? 'DEFAULT');
    unset($_SESSION['id_admin_pusat'], $_SESSION['email'], $_SESSION['_online_last_ping']);
}

function set_siswa_session(array $siswa): void
{
    $_SESSION['no_induk'] = $siswa['no_induk'] ?? '';
    $_SESSION['username'] = $siswa['no_induk'] ?? '';
    $_SESSION['nama_siswa'] = $siswa['nama_siswa'] ?? '';
    $_SESSION['nama'] = $siswa['nama_siswa'] ?? $_SESSION['username'];
    $_SESSION['kelas'] = $siswa['kelas'] ?? '';
    $_SESSION['hak_akses'] = isset($siswa['hak_akses']) ? (int)$siswa['hak_akses'] : 3;
    $_SESSION['id_sekolah'] = isset($siswa['id_sekolah']) ? (int)$siswa['id_sekolah'] : 1;
    $_SESSION['kode_sekolah'] = $siswa['kode_sekolah'] ?? ($_SESSION['kode_sekolah'] ?? 'DEFAULT');
    unset($_SESSION['id_admin_pusat'], $_SESSION['email'], $_SESSION['_online_last_ping']);
}

// Security logging function
function log_access_violation($type, $user_id, $uri)
{
    $logFile = __DIR__ . '/logs/access_violations.log';
    $logDir = dirname($logFile);
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'direct';
    $logEntry = sprintf(
        "[%s] %s | User: %s | IP: %s | URI: %s | Referer: %s | UserAgent: %s\n",
        $timestamp,
        strtoupper($type),
        $user_id ? $user_id : 'anonymous',
        $ip,
        $uri,
        $referer,
        $userAgent
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function online_status_ensure_table($conn)
{
    static $initialized = false;
    if ($initialized || !$conn) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS tbl_user_online (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_key VARCHAR(120) NOT NULL,
        user_type VARCHAR(20) NOT NULL,
        user_ref VARCHAR(100) NOT NULL,
        display_name VARCHAR(150) DEFAULT NULL,
        last_activity DATETIME NOT NULL,
        is_online TINYINT(1) NOT NULL DEFAULT 1,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        latitude DECIMAL(10, 8) DEFAULT NULL,
        longitude DECIMAL(11, 8) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_user_key (user_key),
        KEY idx_last_activity (last_activity),
        KEY idx_is_online (is_online)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    @mysqli_query($conn, $sql);

    // Add latitude and longitude columns if they don't exist (for existing databases)
    $checkLat = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_user_online WHERE Field='latitude'");
    if (!$checkLat || mysqli_num_rows($checkLat) === 0) {
        @mysqli_query($conn, "ALTER TABLE tbl_user_online ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL AFTER user_agent");
    }
    $checkLng = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_user_online WHERE Field='longitude'");
    if (!$checkLng || mysqli_num_rows($checkLng) === 0) {
        @mysqli_query($conn, "ALTER TABLE tbl_user_online ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL AFTER latitude");
    }

    $initialized = true;
}

function online_status_get_identity()
{
    $role = current_role();
    $schoolId = (int)($_SESSION['id_sekolah'] ?? 1);
    if ($role === 9 && isset($_SESSION['username'])) {
        return [
            'user_key' => 'central:admin:' . (string)$_SESSION['username'],
            'user_type' => 'admin_pusat',
            'user_ref' => (string)$_SESSION['username'],
            'display_name' => (string)($_SESSION['nama'] ?? $_SESSION['username'])
        ];
    }
    if ($role === 1 && isset($_SESSION['username'])) {
        return [
            'user_key' => 'school:' . $schoolId . ':admin:' . (string)$_SESSION['username'],
            'user_type' => 'admin',
            'user_ref' => (string)$_SESSION['username'],
            'display_name' => (string)($_SESSION['nama'] ?? $_SESSION['username'])
        ];
    }
    if ($role === 2 && isset($_SESSION['no_induk'])) {
        return [
            'user_key' => 'school:' . $schoolId . ':guru:' . (string)$_SESSION['no_induk'],
            'user_type' => 'guru',
            'user_ref' => (string)$_SESSION['no_induk'],
            'display_name' => (string)($_SESSION['nama_guru'] ?? $_SESSION['no_induk'])
        ];
    }
    if ($role === 3 && isset($_SESSION['no_induk'])) {
        return [
            'user_key' => 'school:' . $schoolId . ':siswa:' . (string)$_SESSION['no_induk'],
            'user_type' => 'siswa',
            'user_ref' => (string)$_SESSION['no_induk'],
            'display_name' => (string)($_SESSION['nama_siswa'] ?? $_SESSION['no_induk'])
        ];
    }
    return null;
}

function track_user_online_status($conn, $force = false)
{
    if (!$conn) {
        return;
    }

    $identity = online_status_get_identity();
    if (!$identity) {
        return;
    }

    $nowTs = time();
    $lastPing = isset($_SESSION['_online_last_ping']) ? (int)$_SESSION['_online_last_ping'] : 0;
    if (!$force && ($nowTs - $lastPing) < 60) {
        return;
    }

    online_status_ensure_table($conn);

    $userKey = mysqli_real_escape_string($conn, $identity['user_key']);
    $userType = mysqli_real_escape_string($conn, $identity['user_type']);
    $userRef = mysqli_real_escape_string($conn, $identity['user_ref']);
    $displayName = mysqli_real_escape_string($conn, $identity['display_name']);
    $ipAddress = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
    $userAgent = mysqli_real_escape_string($conn, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));

    // Capture geolocation from session if available (set during login)
    $latitude = isset($_SESSION['login_latitude']) && $_SESSION['login_latitude'] !== null ? (float)$_SESSION['login_latitude'] : null;
    $longitude = isset($_SESSION['login_longitude']) && $_SESSION['login_longitude'] !== null ? (float)$_SESSION['login_longitude'] : null;

    @mysqli_query($conn, "UPDATE tbl_user_online SET is_online=0 WHERE last_activity < (NOW() - INTERVAL 5 MINUTE) AND is_online=1");

    $upsert = "INSERT INTO tbl_user_online (user_key, user_type, user_ref, display_name, last_activity, is_online, ip_address, user_agent, latitude, longitude)
               VALUES ('$userKey', '$userType', '$userRef', '$displayName', NOW(), 1, '$ipAddress', '$userAgent', " . ($latitude !== null ? $latitude : 'NULL') . ", " . ($longitude !== null ? $longitude : 'NULL') . ")
               ON DUPLICATE KEY UPDATE
                   user_type=VALUES(user_type),
                   user_ref=VALUES(user_ref),
                   display_name=VALUES(display_name),
                   last_activity=VALUES(last_activity),
                   is_online=1,
                   ip_address=VALUES(ip_address),
                   user_agent=VALUES(user_agent)";
    if ($latitude !== null) {
        $upsert .= ",\n                   latitude=" . $latitude;
    }
    if ($longitude !== null) {
        $upsert .= ",\n                   longitude=" . $longitude;
    }
    @mysqli_query($conn, $upsert);

    $_SESSION['_online_last_ping'] = $nowTs;
}

function mark_current_user_offline($conn)
{
    if (!$conn) {
        return;
    }
    $identity = online_status_get_identity();
    if (!$identity) {
        return;
    }
    online_status_ensure_table($conn);
    $userKey = mysqli_real_escape_string($conn, $identity['user_key']);
    @mysqli_query($conn, "UPDATE tbl_user_online SET is_online=0, last_activity=NOW() WHERE user_key='$userKey'");
}

/**
 * Detect teacher gender based on standard 18-digit NIP or fallback name analysis.
 * Returns 'L' (Laki-laki) or 'P' (Perempuan).
 */
function get_guru_gender($nip, $nama) {
    // 1. Cek NIP standar PNS (18 digit)
    $cleanNip = preg_replace('/\s+/', '', (string)$nip);
    if (strlen($cleanNip) === 18 && is_numeric($cleanNip)) {
        $genderDigit = substr($cleanNip, 14, 1);
        if ($genderDigit === '1') {
            return 'L';
        } elseif ($genderDigit === '2') {
            return 'P';
        }
    }
    
    // 2. Fallback: Analisis teks Nama Guru
    $namaLower = strtolower((string)$nama);
    
    // Kata kunci penentu perempuan
    $keywordsP = [
        'bu ', 'ibu ', 'dra.', 'siti', 'sri', 'putri', 'dewi', 'ayu', 'eka', 
        'mega', 'indah', 'rini', 'retno', 'wulan', 'ani', 'ana', 'kartika', 
        'tri', 'yanti', 'ni ', 'wati', 'tri', 'ning', 'sih', 'astuti', 
        'purnami', 'lestari', 'fitri', 'lia', 'sari', 'maharani', 'istiqomah',
        'nurul', 'khofifah', 'ariani', 'arum', 'hidayati', 'pujiati', 'eli', 
        'asri', 'khoiriyatin', 'kiswati', 'maya', 'kurniawati', 'noviyanti'
    ];
    
    // Kata kunci penentu laki-laki
    $keywordsL = [
        'pak ', 'bapak ', 'drs.', 'muh ', 'm. ', 'muhammad', 'ahmad', 'abdul', 
        'bagus', 'agus', 'hadi', 'joko', 'budi', 'andi', 'eko', 'dwi', 'yanto', 
        'wibowo', 'prabowo', 'agung', 'rudi', 'hendra', 'asep', 'dedi', 'iwan', 
        'taufik', 'soleh', 'ardika', 'arif', 'musthofa', 'bambang', 'dedik', 
        'setiawan', 'didik', 'hartono', 'diyan', 'wahyudi', 'mustajab', 'eko', 
        'sutarman', 'ginna', 'santosa', 'irfan', 'irwan', 'kasmad', 'mastur', 
        'nurhadi', 'ngateman', 'partono', 'rahmanta', 'slamet', 'setiyana'
    ];
    
    foreach ($keywordsP as $kw) {
        if (strpos($namaLower, $kw) !== false) {
            return 'P';
        }
    }
    
    foreach ($keywordsL as $kw) {
        if (strpos($namaLower, $kw) !== false) {
            return 'L';
        }
    }
    
    return 'L'; // Default
}

/**
 * Returns raw SVG markup for the beautiful profile avatar.
 */
function get_guru_avatar_svg($gender) {
    if ($gender === 'P') {
        // Female Avatar
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" style="width:100%; height:100%; border-radius:50%; display:block;">
  <defs>
    <linearGradient id="bgGradF" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#EC4899"/>
      <stop offset="100%" stop-color="#8B5CF6"/>
    </linearGradient>
    <linearGradient id="skinF" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#FFE5CC"/>
      <stop offset="100%" stop-color="#FCD0A1"/>
    </linearGradient>
    <linearGradient id="hijabF" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#E2E8F0"/>
      <stop offset="100%" stop-color="#94A3B8"/>
    </linearGradient>
  </defs>
  <circle cx="60" cy="60" r="60" fill="url(#bgGradF)"/>
  <path d="M22,110 C22,82 40,70 60,70 C80,70 98,82 98,110 L98,120 L22,120 Z" fill="url(#hijabF)"/>
  <path d="M35,90 C45,108 75,108 85,90 C85,90 75,116 60,116 C45,116 35,90 35,90 Z" fill="#CBD5E1"/>
  <circle cx="60" cy="46" r="23" fill="url(#hijabF)"/>
  <path d="M42,46 C42,32 50,30 60,30 C70,30 78,32 78,46 C78,60 70,66 60,66 C50,66 42,60 42,46 Z" fill="url(#skinF)"/>
  <circle cx="52" cy="44" r="2" fill="#1A202C"/>
  <circle cx="68" cy="44" r="2" fill="#1A202C"/>
  <path d="M54,54 Q60,59 66,54" stroke="#C53030" stroke-width="2" stroke-linecap="round" fill="none"/>
  <circle cx="52" cy="44" r="7" stroke="#4A5568" stroke-width="1.5" fill="none"/>
  <circle cx="68" cy="44" r="7" stroke="#4A5568" stroke-width="1.5" fill="none"/>
  <line x1="59" y1="44" x2="61" y2="44" stroke="#4A5568" stroke-width="1.5"/>
  <circle cx="46" cy="49" r="3" fill="#F6AD55" opacity="0.5"/>
  <circle cx="74" cy="49" r="3" fill="#F6AD55" opacity="0.5"/>
</svg>';
    } else {
        // Male Avatar
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" style="width:100%; height:100%; border-radius:50%; display:block;">
  <defs>
    <linearGradient id="bgGradM" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#10B981"/>
      <stop offset="100%" stop-color="#0EA5E9"/>
    </linearGradient>
    <linearGradient id="skinM" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#FFD8B3"/>
      <stop offset="100%" stop-color="#FCD0A1"/>
    </linearGradient>
    <linearGradient id="hairM" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#2D3748"/>
      <stop offset="100%" stop-color="#1A202C"/>
    </linearGradient>
  </defs>
  <circle cx="60" cy="60" r="60" fill="url(#bgGradM)"/>
  <path d="M25,105 C25,85 40,76 60,76 C80,76 95,85 95,105 L95,120 L25,120 Z" fill="#1F2937"/>
  <path d="M45,76 L60,94 L75,76 Z" fill="#FFFFFF"/>
  <path d="M56,88 L64,88 L66,108 L60,116 L54,108 Z" fill="#10B981"/>
  <path d="M48,55 L48,78 C48,82 72,82 72,78 L72,55 Z" fill="url(#skinM)"/>
  <circle cx="60" cy="48" r="20" fill="url(#skinM)"/>
  <circle cx="38" cy="48" r="5" fill="url(#skinM)"/>
  <circle cx="82" cy="48" r="5" fill="url(#skinM)"/>
  <path d="M38,42 C38,26 50,22 60,22 C70,22 82,26 82,42 C82,44 80,36 78,35 C75,34 72,35 70,33 C66,30 64,28 60,28 C56,28 54,30 50,33 C48,35 45,34 42,35 C40,36 38,44 38,42 Z" fill="url(#hairM)"/>
  <circle cx="52" cy="46" r="2" fill="#1A202C"/>
  <circle cx="68" cy="46" r="2" fill="#1A202C"/>
  <path d="M54,58 Q60,63 66,58" stroke="#C53030" stroke-width="2" stroke-linecap="round" fill="none"/>
  <circle cx="52" cy="46" r="7" stroke="#4A5568" stroke-width="1.5" fill="none"/>
  <circle cx="68" cy="46" r="7" stroke="#4A5568" stroke-width="1.5" fill="none"/>
  <line x1="59" y1="46" x2="61" y2="46" stroke="#4A5568" stroke-width="1.5"/>
</svg>';
    }
}
