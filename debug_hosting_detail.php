<?php
/**
 * Alternative endpoint untuk hosting dengan compatibility yang lebih baik
 * get_siswa_by_kelas_v2.php - Updated to use hosting_config.php
 */

// Include konfigurasi hosting
require_once 'hosting_config.php';

require_once 'koneksi.php';

// Include functions.php jika ada
if (file_exists('functions.php')) {
    require_once 'functions.php';
}

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Allow CORS jika diperlukan untuk hosting
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Function untuk response JSON
function sendResponse($success, $message = '', $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Log function
function logMessage($message) {
    if (function_exists('error_log')) {
        error_log("[get_siswa_v2] " . $message);
    }
}

// Handle OPTIONS request untuk CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

logMessage("Script started - Session ID: " . session_id());

// Cek session dengan multiple fallbacks
$session_valid = false;
$debug_info = [];

// Method 1: Standard session check
if (isset($_SESSION['no_induk']) && isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 2) {
    $session_valid = true;
    $debug_info['auth_method'] = 'session';
}

// Method 2: Alternative session key check (jika ada variasi)
if (!$session_valid && isset($_SESSION['id']) && isset($_SESSION['level']) && $_SESSION['level'] == 2) {
    $session_valid = true;
    $debug_info['auth_method'] = 'session_alt';
}

// Method 3: Cookie fallback (jika session tidak bekerja)
if (!$session_valid && isset($_COOKIE['user_id']) && isset($_COOKIE['user_level']) && $_COOKIE['user_level'] == 2) {
    $session_valid = true;
    $debug_info['auth_method'] = 'cookie';
}

$debug_info['session_data'] = $_SESSION;
$debug_info['cookies'] = $_COOKIE;

logMessage("Session validation: " . ($session_valid ? 'VALID' : 'INVALID'));

if (!$session_valid) {
    logMessage("Authentication failed");
    sendResponse(false, 'Unauthorized', [
        'debug' => $debug_info,
        'session_id' => session_id(),
        'request_method' => $_SERVER['REQUEST_METHOD']
    ]);
}

// Validate kelas parameter
if (!isset($_GET['kelas']) || empty(trim($_GET['kelas']))) {
    logMessage("Kelas parameter missing");
    sendResponse(false, 'Parameter kelas tidak diberikan');
}

$kelas = trim($_GET['kelas']);
logMessage("Processing kelas: " . $kelas);

// Database query dengan error handling
try {
    // Check connection
    if (!$conn || mysqli_connect_errno()) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    // Prepare query
    $query = "SELECT no_induk, nama_siswa AS nama FROM tbl_siswa WHERE kelas = ? ORDER BY nama_siswa ASC";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    // Bind and execute
    if (!mysqli_stmt_bind_param($stmt, "s", $kelas)) {
        throw new Exception("Bind failed: " . mysqli_stmt_error($stmt));
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    // Fetch data
    $siswa = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $siswa[] = [
            'no_induk' => $row['no_induk'],
            'nama' => $row['nama']
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    logMessage("Success: Found " . count($siswa) . " students for kelas " . $kelas);
    
    sendResponse(true, 'Data berhasil dimuat', [
        'siswa' => $siswa,
        'count' => count($siswa),
        'kelas' => $kelas
    ]);
    
} catch (Exception $e) {
    logMessage("Exception: " . $e->getMessage());
    sendResponse(false, 'Database error: ' . $e->getMessage(), [
        'debug' => [
            'kelas' => $kelas,
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile())
        ]
    ]);
}
?>
