<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../notification_helper.php';

if (!$conn instanceof mysqli) {
    fwrite(STDERR, "Database tidak tersambung.\n");
    exit(1);
}

$limit = isset($argv[1]) ? (int)$argv[1] : 20;
$result = notif_process_pending($conn, $limit);

echo "Notifikasi diproses. Terkirim: {$result['sent']}, gagal: {$result['failed']}\n";
