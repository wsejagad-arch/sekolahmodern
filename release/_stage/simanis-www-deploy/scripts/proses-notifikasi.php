<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../notification_helper.php';

if (!$conn instanceof mysqli) {
    fwrite(STDERR, "Database tidak tersambung.\n");
    exit(1);
}

// 1. Minta kunci (lock) ke database agar hanya 1 proses yang berjalan di waktu bersamaan
$lockName = 'simanis_wa_queue_lock';
$qLock = @mysqli_query($conn, "SELECT GET_LOCK('$lockName', 0) AS lck");
$rowLock = $qLock ? mysqli_fetch_assoc($qLock) : null;

// Jika lck != 1, berarti sudah ada proses lain yang sedang mengirim pesan. Proses ini dibatalkan saja.
if (!$rowLock || (int)$rowLock['lck'] !== 1) {
    exit(0);
}

// 2. Karena kita sudah memegang lock, kita bertugas menghabiskan semua antrean
$totalSent = 0;
$totalFailed = 0;
$batchLimit = 50; // Proses 50 pesan sekaligus agar memori tidak bengkak

while (true) {
    // Process pending messages
    $result = notif_process_pending($conn, $batchLimit);
    
    $totalSent += $result['sent'];
    $totalFailed += $result['failed'];
    
    // Jika dalam iterasi ini tidak ada pesan yang diproses, berarti antrean sudah habis
    if ($result['sent'] === 0 && $result['failed'] === 0) {
        break;
    }
}

// 3. Lepaskan lock setelah selesai
@mysqli_query($conn, "SELECT RELEASE_LOCK('$lockName')");

echo "Notifikasi diproses. Terkirim: {$totalSent}, gagal: {$totalFailed}\n";
