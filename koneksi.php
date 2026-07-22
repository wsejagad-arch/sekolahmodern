<?php
// Global File Upload Size Validator (PDF max 200KB, others max 500KB)
if (!empty($_FILES)) {
    $validateUploads = function($files) use (&$validateUploads) {
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                // Handle multiple file upload structure recursively
                $subFiles = [];
                foreach (array_keys($file['name']) as $subKey) {
                    $subFiles[$subKey] = [
                        'name' => $file['name'][$subKey],
                        'type' => $file['type'][$subKey],
                        'tmp_name' => $file['tmp_name'][$subKey],
                        'error' => $file['error'][$subKey],
                        'size' => $file['size'][$subKey]
                    ];
                }
                $validateUploads($subFiles);
            } else {
                if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $size = $file['size'];
                    
                    $isPdf = ($ext === 'pdf');
                    $maxSize = $isPdf ? 200 * 1024 : 500 * 1024;
                    $maxSizeLabel = $isPdf ? '200 KB' : '500 KB';
                    
                    if ($size > $maxSize) {
                        $errorMsg = "Gagal unggah: Ukuran file " . ($isPdf ? "PDF " : "") . "tidak boleh melebihi " . $maxSizeLabel . " untuk menjaga kapasitas memori server.";
                        
                        // Detect if AJAX request or expecting JSON
                        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                                  || (isset($_GET['ajax']) || isset($_POST['ajax']) || isset($_GET['type']) && $_GET['type'] === 'jurnal')
                                  || (isset($_GET['api']) || strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
                        
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                        } else {
                            // Friendly HTML error page
                            echo "<div style='font-family: sans-serif; padding: 20px; max-width: 500px; margin: 50px auto; border: 1px solid #fecdd3; background: #fff5f5; color: #991b1b; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>";
                            echo "<h4 style='margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid #fecdd3; padding-bottom: 10px;'>Peringatan Batas Ukuran File</h4>";
                            echo "<p style='font-size: 0.95rem; line-height: 1.5;'>" . htmlspecialchars($errorMsg) . "</p>";
                            echo "<a href='javascript:history.back()' style='display: inline-block; background: #dc2626; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9rem; transition: background 0.2s;'>Kembali</a>";
                            echo "</div>";
                        }
                        exit;
                    }
                }
            }
        }
    };
    $validateUploads($_FILES);
}

// koneksi.php - dispatcher: pilih koneksi lokal saat dijalankan di localhost

// If a local connection file exists and we're running on localhost/CLI, prefer it.
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';

$isLocal = false;
if (in_array(php_sapi_name(), ['cli', 'cli-server'], true)) {
    $isLocal = true;
} else {
    if (in_array($serverAddr, ['127.0.0.1', '::1'], true) ||
        stripos($serverName, 'localhost') !== false ||
        stripos($httpHost, 'localhost') !== false ||
        stripos($httpHost, '127.0.0.1') !== false ||
        empty($serverAddr) ||
        strpos($serverAddr, '192.168.') === 0 ||
        strpos($serverAddr, '100.') === 0 ||
        strpos($serverAddr, '10.') === 0 ||
        strpos($httpHost, 'ngrok-free.app') !== false) {
        $isLocal = true;
    }
}

if ($isLocal && file_exists(__DIR__ . '/koneksi_local.php')) {
    include __DIR__ . '/koneksi_local.php';
    if (isset($conn) && $conn instanceof mysqli) {
        require_once __DIR__ . '/multi_tenant.php';
        mt_bootstrap($conn);
        // Auto migrate dimatikan SEMENTARA karena menyebabkan Metadata Lock saat jam sibuk
        // if (!file_exists(__DIR__ . '/.migrated_v3')) {
        //     require_once __DIR__ . '/auto_migrate.php';
        //     run_auto_migrations($conn);
        //     @file_put_contents(__DIR__ . '/.migrated_v3', '1');
        // }
    }
    return;
}

// Database configuration untuk hosting
$host = 'localhost';
$port = 3306;
$user = '';
$password = '';
$database = '';
$persistent = false; // Nonaktifkan persistent connection di shared hosting (mencegah Too many connections)

if (file_exists(__DIR__ . '/config.hosting.php')) {
    $cfg = require __DIR__ . '/config.hosting.php';
    if (is_array($cfg)) {
        $host = (string) ($cfg['host'] ?? $host);
        $port = (int) ($cfg['port'] ?? $port);
        $user = (string) ($cfg['user'] ?? $user);
        $password = (string) ($cfg['password'] ?? $password);
        $database = (string) ($cfg['database'] ?? $database);
        if (isset($cfg['persistent'])) {
            $persistent = (bool) $cfg['persistent'];
        }
    }
} else {
    // Fallback legacy (isi via config.hosting.php di production)
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'smasumb1_simanis1';
    $password = 'W@hyu1234!';
    $database = 'smasumb1_simanis';
}

// Create connection
mysqli_report(MYSQLI_REPORT_OFF);
$conn = null;
try {
    // Hapus fitur persistent (p:host) untuk menghindari bug max_connections di cPanel/Shared Hosting
    $connect_host = $host;


    $conn = @new mysqli($connect_host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        error_log('[koneksi.php] MySQL connect error: ' . $conn->connect_error);
        $conn = null;
    } else {
        mysqli_set_charset($conn, 'utf8');
        // Auto-close koneksi saat script PHP selesai mengeksekusi
        register_shutdown_function(function() use (&$conn) {
            if ($conn instanceof mysqli) {
                @$conn->close();
            }
        });
        require_once __DIR__ . '/multi_tenant.php';
        mt_bootstrap($conn);
        // Auto migrate dimatikan SEMENTARA karena menyebabkan Metadata Lock saat jam sibuk
        // if (!file_exists(__DIR__ . '/.migrated_v3')) {
        //     require_once __DIR__ . '/auto_migrate.php';
        //     run_auto_migrations($conn);
        //     @file_put_contents(__DIR__ . '/.migrated_v3', '1');
        // }
    }
} catch (Throwable $e) {
    error_log('[koneksi.php] MySQL exception: ' . $e->getMessage());
    $conn = null;
}

if (!$conn) {
    http_response_code(503);
    die("<!doctype html><html lang='id'><head><meta charset='utf-8'><title>Gangguan Database</title><style>body { font-family: system-ui, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; } .error-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; max-width: 500px; } h2 { color: #dc2626; margin-top:0; } p { color: #64748b; line-height: 1.5; }</style></head><body><div class='error-box'><h2>Oops! Database Tidak Merespons</h2><p>Sistem saat ini tidak dapat terhubung ke database. Hal ini biasanya terjadi karena proses pemeliharaan atau beban server tinggi. Silakan muat ulang halaman ini beberapa saat lagi.</p></div></body></html>");
}

// === AUTO-ALPA & AUTO-EXPIRE IZIN ===
// Cek izin yang belum divalidasi dan sudah lewat hari (Jalankan maksimal 1x sehari)
if ($conn instanceof mysqli) {
    $today_alpa = date('Y-m-d');
    $lock_file = __DIR__ . '/.alpa_' . $today_alpa;
    if (!file_exists($lock_file)) {
        $qExpired = mysqli_query($conn, "SELECT id_izin, no_induk_siswa, kelas_siswa FROM tbl_izin_siswa WHERE tanggal_izin < '$today_alpa' AND status_izin IN ('Menunggu', 'Menunggu Validasi')");
        if ($qExpired && mysqli_num_rows($qExpired) > 0) {
            while ($rowExp = mysqli_fetch_assoc($qExpired)) {
                $id_izin_exp = $rowExp['id_izin'];
                $nis_exp = $rowExp['no_induk_siswa'];
                $kelas_exp = $rowExp['kelas_siswa'];
                
                // Ubah status jadi Ditolak (Auto-Alpa)
                mysqli_query($conn, "UPDATE tbl_izin_siswa SET status_izin = 'Ditolak (Auto-Alpa)', validasi_wali_kelas = 'Ditolak', validasi_guru_bk = 'Ditolak' WHERE id_izin = '$id_izin_exp'");
            }
        }
        @file_put_contents($lock_file, '1');
        // Hapus file lock hari sebelumnya agar tidak menumpuk
        $yesterday_alpa = date('Y-m-d', strtotime('-1 day'));
        @unlink(__DIR__ . '/.alpa_' . $yesterday_alpa);
    }
}

