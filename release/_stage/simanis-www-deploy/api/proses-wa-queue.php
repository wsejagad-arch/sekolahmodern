<?php
/**
 * api/proses-wa-queue.php
 * Endpoint untuk memproses antrean notifikasi WhatsApp yang pending.
 * Bisa dipanggil dari browser (admin) atau via AJAX.
 */
session_start();
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../notification_helper.php';

// Hanya admin
if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database tidak tersambung']);
    exit;
}

$limit  = (int)($_GET['limit'] ?? 20);
$limit  = max(1, min(50, $limit));
$result = notif_process_pending($conn, $limit);

// Jika request dari browser (bukan AJAX), redirect dengan flash
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'sent'    => $result['sent'],
        'failed'  => $result['failed'],
    ]);
} else {
    // Redirect ke halaman pengaturan dengan pesan
    $msg = urlencode("Diproses: {$result['sent']} terkirim, {$result['failed']} gagal.");
    header("Location: ../pengaturan-wa.php?msg=$msg");
}
exit;
