<?php
/**
 * Notification outbox for school-level messages.
 *
 * Messages are queued first, then processed by cron/CLI using
 * scripts/proses-notifikasi.php. Email uses PHP mail(); WhatsApp uses an
 * optional webhook configured with environment variables.
 */

function notif_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function notif_ensure_schema(mysqli $conn): void
{
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_notifikasi_outbox (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        id_sekolah INT NOT NULL DEFAULT 1,
        channel ENUM('email','whatsapp') NOT NULL,
        tujuan VARCHAR(190) NOT NULL,
        judul VARCHAR(190) NOT NULL,
        pesan TEXT NOT NULL,
        status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
        percobaan INT NOT NULL DEFAULT 0,
        error_message TEXT NULL,
        scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notif_status (status, scheduled_at),
        INDEX idx_notif_school (id_sekolah)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function notif_normalize_phone(string $phone): string
{
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '0')) {
        return '62' . substr($digits, 1);
    }
    return $digits;
}

function notif_queue(mysqli $conn, int $schoolId, string $channel, string $target, string $title, string $message): bool
{
    notif_ensure_schema($conn);

    $channel = strtolower(trim($channel));
    if (!in_array($channel, ['email', 'whatsapp'], true)) {
        return false;
    }
    $target = trim($target);
    if ($channel === 'whatsapp') {
        $target = notif_normalize_phone($target);
    }
    if ($target === '') {
        return false;
    }

    $schoolId = max(1, $schoolId);
    $channelEsc = mysqli_real_escape_string($conn, $channel);
    $targetEsc = mysqli_real_escape_string($conn, $target);
    $titleEsc = mysqli_real_escape_string($conn, $title);
    $messageEsc = mysqli_real_escape_string($conn, $message);

    return (bool)@mysqli_query(
        $conn,
        "INSERT INTO tbl_notifikasi_outbox (id_sekolah, channel, tujuan, judul, pesan)
         VALUES ($schoolId, '$channelEsc', '$targetEsc', '$titleEsc', '$messageEsc')"
    );
}

function notif_queue_school(mysqli $conn, int $schoolId, string $title, string $message): int
{
    notif_ensure_schema($conn);
    $schoolId = max(1, $schoolId);
    $q = @mysqli_query($conn, "SELECT email_kontak, hp_kontak FROM tbl_sekolah WHERE id_sekolah=$schoolId LIMIT 1");
    $school = $q ? mysqli_fetch_assoc($q) : null;
    if (!$school) {
        return 0;
    }

    $queued = 0;
    $email = trim((string)($school['email_kontak'] ?? ''));
    $phone = trim((string)($school['hp_kontak'] ?? ''));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && notif_queue($conn, $schoolId, 'email', $email, $title, $message)) {
        $queued++;
    }
    if ($phone !== '' && notif_queue($conn, $schoolId, 'whatsapp', $phone, $title, $message)) {
        $queued++;
    }
    return $queued;
}

function notif_queue_all_schools(mysqli $conn, string $title, string $message): int
{
    notif_ensure_schema($conn);
    $count = 0;
    $q = @mysqli_query($conn, "SELECT id_sekolah FROM tbl_sekolah WHERE status='Aktif' ORDER BY id_sekolah ASC");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $count += notif_queue_school($conn, (int)$row['id_sekolah'], $title, $message);
    }
    return $count;
}

function notif_send_email(string $target, string $title, string $message): array
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: SIMANIS <no-reply@simanis.local>',
    ];
    $ok = @mail($target, $title, $message, implode("\r\n", $headers));
    return [$ok, $ok ? '' : 'mail() gagal atau belum dikonfigurasi server.'];
}

function notif_send_whatsapp(string $target, string $title, string $message): array
{
    $url = getenv('SIMANIS_WA_WEBHOOK_URL') ?: '';
    $token = getenv('SIMANIS_WA_WEBHOOK_TOKEN') ?: '';
    if ($url === '') {
        return [false, 'Webhook WhatsApp belum dikonfigurasi. Isi SIMANIS_WA_WEBHOOK_URL.'];
    }

    $payload = json_encode([
        'phone' => notif_normalize_phone($target),
        'message' => trim($title . "\n\n" . $message),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array_filter([
            'Content-Type: application/json',
            $token !== '' ? 'Authorization: Bearer ' . $token : '',
        ]),
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $ok = $response !== false && $httpCode >= 200 && $httpCode < 300;
    return [$ok, $ok ? '' : ($error ?: 'HTTP ' . $httpCode . ' dari webhook WhatsApp.')];
}

function notif_process_pending(mysqli $conn, int $limit = 20): array
{
    notif_ensure_schema($conn);
    $limit = max(1, min(100, $limit));
    $sent = 0;
    $failed = 0;

    $q = @mysqli_query($conn, "SELECT * FROM tbl_notifikasi_outbox WHERE status='pending' AND scheduled_at <= NOW() ORDER BY id ASC LIMIT $limit");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $id = (int)$row['id'];
        $channel = (string)$row['channel'];
        if ($channel === 'email') {
            [$ok, $err] = notif_send_email((string)$row['tujuan'], (string)$row['judul'], (string)$row['pesan']);
        } else {
            [$ok, $err] = notif_send_whatsapp((string)$row['tujuan'], (string)$row['judul'], (string)$row['pesan']);
        }

        $errEsc = mysqli_real_escape_string($conn, $err);
        if ($ok) {
            @mysqli_query($conn, "UPDATE tbl_notifikasi_outbox SET status='sent', sent_at=NOW(), percobaan=percobaan+1, error_message=NULL WHERE id=$id");
            $sent++;
        } else {
            @mysqli_query($conn, "UPDATE tbl_notifikasi_outbox SET status='failed', percobaan=percobaan+1, error_message='$errEsc' WHERE id=$id");
            $failed++;
        }
    }

    return ['sent' => $sent, 'failed' => $failed];
}
