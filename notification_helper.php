<?php
/**
 * Notification outbox for school-level messages.
 *
 * Messages are queued first, then processed by cron/CLI using
 * scripts/proses-notifikasi.php. Email uses PHP mail();
 * WhatsApp uses project WASENDER (whatsapp-web.js) running locally.
 *
 * WASENDER config diambil dari tbl_app_config (key: wa_*).
 * Fallback ke environment variable SIMANIS_WA_URL dan SIMANIS_WA_API_KEY.
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

    $success = (bool)@mysqli_query(
        $conn,
        "INSERT INTO tbl_notifikasi_outbox (id_sekolah, channel, tujuan, judul, pesan)
         VALUES ($schoolId, '$channelEsc', '$targetEsc', '$titleEsc', '$messageEsc')"
    );
    if ($success) {
        notif_trigger_background_process();
    }
    return $success;
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

/**
 * Ambil konfigurasi WASENDER dari tbl_app_config.
 * Jika tidak ada, fallback ke env var atau default lokal.
 */
function notif_get_wasender_config(?mysqli $conn = null): array
{
    $default = [
        'url'     => getenv('SIMANIS_WA_URL') ?: 'http://127.0.0.1:3002',
        'api_key' => getenv('SIMANIS_WA_API_KEY') ?: '8718395e79d750dfc0fc0a30cf099bd4',
        'enabled' => true,
    ];

    if (!$conn instanceof mysqli) {
        return $default;
    }

    $keys = ['wa_url', 'wa_api_key', 'wa_enabled'];
    $keyList = "'" . implode("','" , $keys) . "'";
    $q = @mysqli_query($conn, "SELECT kunci, nilai FROM tbl_app_config WHERE kunci IN ($keyList)");
    if (!$q) {
        return $default;
    }

    $cfg = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $cfg[$row['kunci']] = $row['nilai'];
    }

    return [
        'url'     => trim($cfg['wa_url'] ?? $default['url']) ?: $default['url'],
        'api_key' => trim($cfg['wa_api_key'] ?? $default['api_key']) ?: $default['api_key'],
        'enabled' => isset($cfg['wa_enabled']) ? ((int)$cfg['wa_enabled'] === 1) : $default['enabled'],
    ];
}

/**
 * Simpan/update satu konfigurasi ke tbl_app_config.
 */
function notif_save_wa_config(mysqli $conn, string $key, string $value): bool
{
    $allowedKeys = [
        'wa_url', 'wa_api_key', 'wa_enabled',
        'wa_notif_presensi_status', 'wa_notif_presensi_template', 'wa_notif_presensi_title',
        'wa_notif_jurnal_status', 'wa_notif_jurnal_template', 'wa_notif_jurnal_title',
        'wa_notif_izin_status', 'wa_notif_izin_template', 'wa_notif_izin_title',
        'wa_notif_laporan_status', 'wa_notif_laporan_template', 'wa_notif_laporan_title',
        'wa_notif_rekap_status', 'wa_notif_rekap_template', 'wa_notif_rekap_title',
        'wa_notif_rekap_last_sent_date',
        'wa_notif_reminder_status', 'wa_notif_reminder_template', 'wa_notif_reminder_title',
        'wa_notif_reminder_last_sent_date'
    ];
    if (!in_array($key, $allowedKeys, true)) {
        return false;
    }
    // Buat tabel jika belum ada
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_app_config (
        kunci VARCHAR(80) NOT NULL PRIMARY KEY,
        nilai TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        id_sekolah INT NOT NULL DEFAULT 1,
        KEY idx_tbl_app_config_id_sekolah (id_sekolah)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    return (bool)@mysqli_query($conn,
        "INSERT INTO tbl_app_config (kunci, nilai) VALUES ('$k', '$v')
         ON DUPLICATE KEY UPDATE nilai = '$v', updated_at = NOW()"
    );
}

/**
 * Kirim pesan WhatsApp melalui WASENDER atau Geolabs WA Gateway.
 */
function notif_send_whatsapp(string $target, string $title, string $message, ?mysqli $conn = null): array
{
    $config = notif_get_wasender_config($conn);

    if (!$config['enabled']) {
        return [false, 'Notifikasi WhatsApp dinonaktifkan di pengaturan sistem.'];
    }

    $baseUrl = rtrim($config['url'], '/');
    $apiKey  = $config['api_key'];

    if ($baseUrl === '') {
        return [false, 'URL WA Gateway belum dikonfigurasi. Buka Pengaturan › WhatsApp.'];
    }

    $phone = notif_normalize_phone($target);
    if ($phone === '') {
        return [false, 'Nomor tujuan tidak valid: ' . $target];
    }

    $fullMessage = trim($title . "\n\n" . $message);

    // Deteksi jika ini Geolabs WA Gateway
    $isGeolabs = (strpos($baseUrl, 'wa.geolabs.my.id') !== false) || (strpos($baseUrl, 'geolabs') !== false);
    
    $url = $baseUrl;
    if ($isGeolabs) {
        if (strpos($baseUrl, '/api/send') === false) {
            $url = $baseUrl . '/api/send';
        }
    } else {
        if (strpos($baseUrl, '/api/send') === false) {
            $url = $baseUrl . '/api/send-message';
        }
    }

    $payloadData = [
        'number'  => $phone,
        'message' => $fullMessage,
    ];

    if ($apiKey !== '' && ($isGeolabs || strpos($baseUrl, '/api/send') !== false)) {
        $payloadData['device_id'] = $apiKey;
    }

    $payload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $headers = [
        'Content-Type: application/json',
    ];
    if ($apiKey !== '' && !$isGeolabs && strpos($baseUrl, '/api/send') === false) {
        $headers[] = 'x-api-key: ' . $apiKey;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [false, 'cURL error: ' . $curlErr];
    }

    $ok = ($httpCode >= 200 && $httpCode < 300);

    if (!$ok) {
        $decoded = json_decode($response, true);
        $errMsg = $decoded['message'] ?? $decoded['error'] ?? ('HTTP ' . $httpCode . ' — ' . substr($response, 0, 200));
        return [false, 'WA Gateway: ' . $errMsg];
    }

    if ($isGeolabs) {
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !($decoded['success'] ?? false)) {
            $snippet = substr(strip_tags($response), 0, 100);
            return [false, 'WA Gateway respon tidak valid (pastikan endpoint /api/send benar): ' . $snippet];
        }
    }

    return [true, ''];
}

/**
 * Mengambil nilai konfigurasi kustom dari tbl_app_config.
 */
function notif_get_custom_setting(mysqli $conn, string $key, string $default = ''): string
{
    $keyEsc = mysqli_real_escape_string($conn, $key);
    $q = @mysqli_query($conn, "SELECT nilai FROM tbl_app_config WHERE kunci = '$keyEsc' LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return $row['nilai'];
    }
    return $default;
}

/**
 * Mengirim pesan WA kustom menggunakan template yang tersimpan di tbl_app_config.
 */
function notif_send_templated_wa(mysqli $conn, string $targetPhone, string $settingKeyPrefix, array $placeholders): bool
{
    $statusKey   = $settingKeyPrefix . '_status';
    $titleKey    = $settingKeyPrefix . '_title';
    $templateKey = $settingKeyPrefix . '_template';

    $q = @mysqli_query($conn, "SELECT kunci, nilai FROM tbl_app_config WHERE kunci IN ('$statusKey', '$titleKey', '$templateKey')");
    $cfg = [];
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $cfg[$row['kunci']] = $row['nilai'];
        }
    }

    $status = isset($cfg[$statusKey]) ? (int)$cfg[$statusKey] === 1 : true;
    if (!$status) {
        return false; // Notifikasi dinonaktifkan
    }

    $title = $cfg[$titleKey] ?? match ($settingKeyPrefix) {
        'wa_notif_presensi' => '🔔 Notifikasi Kehadiran Siswa',
        'wa_notif_jurnal'   => '📝 Pengisian Jurnal Mengajar',
        'wa_notif_izin'     => '✉️ Pengajuan Izin Siswa',
        'wa_notif_laporan'  => '🚨 Laporan Kejadian Baru',
        'wa_notif_rekap'    => '📊 Rekap Absensi Harian Siswa',
        default             => 'Notifikasi SIMANIS'
    };

    $template = $cfg[$templateKey] ?? match ($settingKeyPrefix) {
        'wa_notif_presensi' => "*INFO KEHADIRAN SISWA*\n\nHalo Bapak/Ibu Wali Murid,\nBerikut adalah informasi kehadiran siswa:\n*Nama Siswa:* {nama_siswa}\n*Kelas:* {kelas}\n*Status:* {status}\n*Waktu:* {waktu}\n\n*Semoga tetap semangat, diberi kesehatan, diberi keberkahan dalam mendidik anak bangsa. Karena semangat Bapak/Ibu guru adalah masa depan gemilang untuk anak-anak. Semoga berkah barokah ya.*",
        'wa_notif_jurnal'   => "*INFO PENGISIAN JURNAL MENGAJAR*\n\nHalo Bapak/Ibu guru {nama_guru},\nTerima kasih telah mengisi Jurnal Mengajar pada:\n*Hari/Tanggal:* {hari}, {tanggal}\n*Kelas:* {kelas}\n*Mata Pelajaran:* {mapel}\n*Materi:* {materi}\n*Jam ke:* {jam}\n\n*Semoga tetap semangat, diberi kesehatan, diberi keberkahan dalam mendidik anak bangsa. Karena semangat Bapak/Ibu guru adalah masa depan gemilang untuk anak-anak. Semoga berkah barokah ya.*",
        'wa_notif_izin'     => "*INFO PENGAJUAN IZIN SISWA*\n\nPengajuan Izin Siswa Baru:\n*Nama Siswa:* {nama_siswa}\n*Kelas:* {kelas}\n*Jenis Izin:* {jenis_izin}\n*Alasan:* {alasan}\n*Tanggal:* {tanggal}\n*Link Validasi:* {link_validasi}\n\nMohon untuk diperiksa dan ditindaklanjuti.\n\n*Semoga tetap semangat, diberi kesehatan, diberi keberkahan dalam mendidik anak bangsa. Karena semangat Bapak/Ibu guru adalah masa depan gemilang untuk anak-anak. Semoga berkah barokah ya.*",
        'wa_notif_laporan'  => "*INFO LAPORAN KEJADIAN BARU*\n\nLaporan Kejadian/Pelanggaran Baru:\n*Pelapor:* {nama_pelapor}\n*Siswa Terkait:* {nama_siswa}\n*Kelas:* {kelas}\n*Kejadian:* {kejadian}\n*Tanggal:* {tanggal}\n\nMohon lakukan verifikasi.\n\n*Semoga tetap semangat, diberi kesehatan, diberi keberkahan dalam mendidik anak bangsa. Karena semangat Bapak/Ibu guru adalah masa depan gemilang untuk anak-anak. Semoga berkah barokah ya.*",
        'wa_notif_rekap'    => "*INFO REKAP ABSENSI KELAS*\n\nBerikut adalah rekap absensi harian siswa (s.d. 07.45):\n*Hari/Tanggal:* {hari}, {tanggal}\n\nDetail Kehadiran:\n*Sakit:* {jumlah_sakit} siswa\n*Izin:* {jumlah_izin} siswa\n*Alfa:* {jumlah_alfa} siswa\n*Dispen:* {jumlah_dispen} siswa\n\n*Total Tidak Hadir:* {total_tidak_hadir} siswa\n\n*Semoga tetap semangat, diberi kesehatan, diberi keberkahan dalam mendidik anak bangsa. Karena semangat Bapak/Ibu guru adalah masa depan gemilang untuk anak-anak. Semoga berkah barokah ya.*",
        'wa_notif_reminder' => "*INFO PENGINGAT JURNAL MENGAJAR*\n\nHalo Bapak/Ibu guru {nama_guru},\nKami mengingatkan Bapak/Ibu untuk selalu mengisi jurnal kehadiran pada hari ini ({tanggal}) agar perkembangan siswa dapat terpantau dengan baik di aplikasi.\n\n*Semoga tetap semangat, diberi kesehatan, diberi keberkahan dalam mendidik anak bangsa. Karena semangat Bapak/Ibu guru adalah masa depan gemilang untuk anak-anak. Semoga berkah barokah ya.*",
        default             => ''
    };

    if ($template === '') {
        return false;
    }

    $message = $template;
    foreach ($placeholders as $key => $val) {
        $message = str_replace('{' . $key . '}', $val, $message);
    }

    return notif_queue($conn, 1, 'whatsapp', $targetPhone, $title, $message);
}

/**
 * Pemicu notifikasi presensi siswa ke orang tua.
 */
function notif_trigger_presensi(mysqli $conn, string $no_induk, string $status, string $waktu = '')
{
    if (empty($waktu)) {
        $waktu = date('Y-m-d H:i:s');
    }
    $no_induk_escaped = mysqli_real_escape_string($conn, $no_induk);
    $q = @mysqli_query($conn, "SELECT nama_siswa, kelas, no_wa FROM tbl_siswa WHERE no_induk='$no_induk_escaped' LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $parentWa = trim($row['no_wa'] ?? '');
        if ($parentWa !== '') {
            notif_send_templated_wa($conn, $parentWa, 'wa_notif_presensi', [
                'nama_siswa' => $row['nama_siswa'],
                'kelas'      => $row['kelas'],
                'status'     => $status,
                'waktu'      => $waktu
            ]);
        }
    }
}

/**
 * Pemicu notifikasi pengisian jurnal mengajar ke guru.
 */
function notif_trigger_jurnal(mysqli $conn, string $nip, string $idmapel, string $tanggal, string $materi, string $kegiatan, string $jam = '')
{
    $nip_escaped = mysqli_real_escape_string($conn, $nip);
    $q = @mysqli_query($conn, "SELECT nama_guru, no_wa FROM tbl_guru WHERE no_induk='$nip_escaped' LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $teacherWa = trim($row['no_wa'] ?? '');
        if ($teacherWa !== '') {
            $idmapel_escaped = mysqli_real_escape_string($conn, $idmapel);
            $qMapel = @mysqli_query($conn, "SELECT nama_mapel, kelas, hari, jam_mulai, jam_selesai FROM tbl_mapel_ampu WHERE id_mapel='$idmapel_escaped' LIMIT 1");
            $mapelName = '';
            $kelas = '';
            $hari = '';
            $jamRange = '';
            if ($qMapel && ($rowMapel = mysqli_fetch_assoc($qMapel))) {
                $mapelName = $rowMapel['nama_mapel'];
                $kelas = $rowMapel['kelas'];
                $hari = $rowMapel['hari'];
                $jamRange = (!empty($rowMapel['jam_mulai']) ? date('H:i', strtotime($rowMapel['jam_mulai'])) : '') . ' - ' . (!empty($rowMapel['jam_selesai']) ? date('H:i', strtotime($rowMapel['jam_selesai'])) : '');
            }
            notif_send_templated_wa($conn, $teacherWa, 'wa_notif_jurnal', [
                'nama_guru' => $row['nama_guru'],
                'hari'      => $hari,
                'tanggal'   => $tanggal,
                'kelas'     => $kelas,
                'mapel'     => $mapelName,
                'materi'    => $materi,
                'jam'       => $jam !== '' ? $jam : $jamRange
            ]);
        }
    }
}

/**
 * Pemicu notifikasi pengajuan izin ke BK dan wali kelas.
 */
function notif_trigger_izin(mysqli $conn, string $no_induk_siswa, string $jenis_izin, string $alasan)
{
    $no_induk_escaped = mysqli_real_escape_string($conn, $no_induk_siswa);
    $qSiswa = @mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk='$no_induk_escaped' LIMIT 1");
    if (!$qSiswa || mysqli_num_rows($qSiswa) === 0) {
        return;
    }
    $siswa = mysqli_fetch_assoc($qSiswa);
    $nama_siswa = $siswa['nama_siswa'];
    $kelas = $siswa['kelas'];
    $kelas_escaped = mysqli_real_escape_string($conn, $kelas);
    $tanggal = date('Y-m-d');
    
    $appUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/jurnal';
    $link_validasi = $appUrl . '/pages/guru/walikelas.php';

    // 1. Wali Kelas
    $qWali = @mysqli_query($conn, "
        SELECT nama_guru, no_wa FROM tbl_guru 
        WHERE no_induk = (SELECT nip_wali FROM tbl_kelas WHERE kelas = '$kelas_escaped' LIMIT 1)
           OR no_induk = (
               SELECT w.nip_wali FROM tbl_wali_kelas w 
               JOIN tbl_kelas k ON w.id_kelas = k.id_kelas 
               WHERE k.kelas = '$kelas_escaped' LIMIT 1
           )
        LIMIT 1
    ");
    if ($qWali && ($rowWali = mysqli_fetch_assoc($qWali))) {
        $waliWa = trim($rowWali['no_wa'] ?? '');
        if ($waliWa !== '') {
            notif_send_templated_wa($conn, $waliWa, 'wa_notif_izin', [
                'nama_siswa'    => $nama_siswa,
                'kelas'         => $kelas,
                'jenis_izin'    => $jenis_izin,
                'alasan'        => $alasan,
                'tanggal'       => $tanggal,
                'link_validasi' => $link_validasi
            ]);
        }
    }
    
    // 2. Guru BK
    $qBK = @mysqli_query($conn, "SELECT nama_guru, no_wa FROM tbl_guru WHERE is_guru_bk = 1 AND status = 'Aktif'");
    while ($qBK && ($rowBK = mysqli_fetch_assoc($qBK))) {
        $bkWa = trim($rowBK['no_wa'] ?? '');
        if ($bkWa !== '') {
            notif_send_templated_wa($conn, $bkWa, 'wa_notif_izin', [
                'nama_siswa'    => $nama_siswa,
                'kelas'         => $kelas,
                'jenis_izin'    => $jenis_izin,
                'alasan'        => $alasan,
                'tanggal'       => $tanggal,
                'link_validasi' => $link_validasi
            ]);
        }
    }
}

/**
 * Pemicu notifikasi input laporan ke admin.
 */
function notif_trigger_laporan(mysqli $conn, string $judul, string $deskripsi)
{
    $creatorName = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'User';
    $tanggal = date('Y-m-d');
    
    // 1. Kontak Sekolah
    $qSek = @mysqli_query($conn, "SELECT hp_kontak FROM tbl_sekolah WHERE id_sekolah=1 LIMIT 1");
    if ($qSek && ($rowSek = mysqli_fetch_assoc($qSek))) {
        $schoolWa = trim($rowSek['hp_kontak'] ?? '');
        if ($schoolWa !== '') {
            notif_send_templated_wa($conn, $schoolWa, 'wa_notif_laporan', [
                'nama_pelapor' => $creatorName,
                'nama_siswa'   => '-',
                'kelas'        => '-',
                'kejadian'     => $judul,
                'tanggal'      => $tanggal
            ]);
        }
    }
    
    // 2. Admin Guru
    $qAdmin = @mysqli_query($conn, "
        SELECT g.nama_guru, g.no_wa 
        FROM tbl_guru g
        JOIN tbl_pengguna p ON g.no_induk = p.no_induk
        WHERE p.hak_akses = '1' AND g.status = 'Aktif'
    ");
    while ($qAdmin && ($rowAdmin = mysqli_fetch_assoc($qAdmin))) {
        $adminWa = trim($rowAdmin['no_wa'] ?? '');
        if ($adminWa !== '') {
            notif_send_templated_wa($conn, $adminWa, 'wa_notif_laporan', [
                'nama_pelapor' => $creatorName,
                'nama_siswa'   => '-',
                'kelas'        => '-',
                'kejadian'     => $judul,
                'tanggal'      => $tanggal
            ]);
        }
    }
}

/**
 * Himpun dan kirim Rekap Absensi Harian (Sakit, Ijin, Alpha, Dispen) s.d. 07.45.
 */
function notif_send_daily_rekap(mysqli $conn)
{
    $hari = date('N');
    $hariIndo = match ($hari) {
        '1' => 'Senin',
        '2' => 'Selasa',
        '3' => 'Rabu',
        '4' => 'Kamis',
        '5' => 'Jumat',
        '6' => 'Sabtu',
        '7' => 'Minggu',
        default => ''
    };
    $tanggal = date('Y-m-d');
    
    // Pastikan kolom created_at ada di tbl_absen
    $_chkCol = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_absen LIKE 'created_at'");
    if ($_chkCol && mysqli_num_rows($_chkCol) === 0) {
        @mysqli_query($conn, "ALTER TABLE tbl_absen ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
    
    // Himpun stats absen s.d. 07.45 hari ini
    $q = @mysqli_query($conn, "
        SELECT status, COUNT(*) AS total 
        FROM tbl_absen 
        WHERE tanggal = '$tanggal' 
          AND TIME(created_at) <= '07:45:00'
        GROUP BY status
    ");
    
    $stats = [
        'Sakit'  => 0,
        'Ijin'   => 0,
        'Alpha'  => 0,
        'Dispen' => 0
    ];
    
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = (int)$row['total'];
            }
        }
    }
    
    $jumlah_sakit = $stats['Sakit'];
    $jumlah_izin  = $stats['Ijin'];
    $jumlah_alfa  = $stats['Alpha'];
    $jumlah_dispen = $stats['Dispen'];
    $total_tidak_hadir = $jumlah_sakit + $jumlah_izin + $jumlah_alfa + $jumlah_dispen;
    
    // Himpun target pengiriman (Kontak Sekolah, Admin, BK)
    $receivers = [];
    
    $qSek = @mysqli_query($conn, "SELECT hp_kontak FROM tbl_sekolah WHERE id_sekolah=1 LIMIT 1");
    if ($qSek && ($rowSek = mysqli_fetch_assoc($qSek))) {
        $hp = trim($rowSek['hp_kontak'] ?? '');
        if ($hp !== '') $receivers[] = $hp;
    }
    
    $qGuru = @mysqli_query($conn, "
        SELECT g.no_wa 
        FROM tbl_guru g
        LEFT JOIN tbl_pengguna p ON g.no_induk = p.no_induk
        WHERE (p.hak_akses = '1' OR g.is_guru_bk = 1) AND g.status = 'Aktif'
    ");
    while ($qGuru && ($rowGuru = mysqli_fetch_assoc($qGuru))) {
        $hp = trim($rowGuru['no_wa'] ?? '');
        if ($hp !== '' && !in_array($hp, $receivers, true)) {
            $receivers[] = $hp;
        }
    }
    
    foreach ($receivers as $targetPhone) {
        notif_send_templated_wa($conn, $targetPhone, 'wa_notif_rekap', [
            'hari'              => $hariIndo,
            'tanggal'           => $tanggal,
            'jumlah_sakit'      => $jumlah_sakit,
            'jumlah_izin'       => $jumlah_izin,
            'jumlah_alfa'       => $jumlah_alfa,
            'jumlah_dispen'     => $jumlah_dispen,
            'total_tidak_hadir' => $total_tidak_hadir
        ]);
    }
}

/**
 * Mengecek dan memicu rekap absensi harian secara otomatis.
 */
function notif_check_and_trigger_daily_rekap(mysqli $conn)
{
    $enabled = (int)notif_get_custom_setting($conn, 'wa_notif_rekap_status', '1');
    if (!$enabled) {
        return;
    }
    
    $currentHourMin = date('H:i');
    if ($currentHourMin < '07:45') {
        return; // Belum waktunya
    }
    
    $today = date('Y-m-d');
    $lastSentDate = notif_get_custom_setting($conn, 'wa_notif_rekap_last_sent_date', '');
    if ($lastSentDate === $today) {
        return; // Hari ini sudah dikirim
    }
    
    notif_send_daily_rekap($conn);
    notif_save_wa_config($conn, 'wa_notif_rekap_last_sent_date', $today);
}

/**
 * Mengecek dan memicu pengingat pengisian jurnal di pagi hari.
 */
function notif_check_and_trigger_morning_reminder(mysqli $conn)
{
    $enabled = (int)notif_get_custom_setting($conn, 'wa_notif_reminder_status', '1');
    if (!$enabled) {
        return;
    }
    
    $currentHourMin = date('H:i');
    if ($currentHourMin < '06:30' || $currentHourMin > '07:30') {
        // Hanya memicu pengingat pagi antara jam 06:30 hingga 07:30
        return;
    }
    
    $today = date('Y-m-d');
    $lastSentDate = notif_get_custom_setting($conn, 'wa_notif_reminder_last_sent_date', '');
    if ($lastSentDate === $today) {
        return; // Hari ini sudah dikirim
    }
    
    // Get current day in Indonesian
    $hari_indo = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $hari_ini = $hari_indo[date('l')] ?? '';
    $hari_ini_esc = mysqli_real_escape_string($conn, $hari_ini);
    
    // Ambil daftar guru yang aktif, memiliki nomor WA, dan memiliki jadwal mengajar hari ini
    $qGuru = @mysqli_query($conn, "
        SELECT nama_guru, no_wa 
        FROM tbl_guru 
        WHERE status = 'Aktif' 
          AND no_wa IS NOT NULL AND no_wa != ''
          AND EXISTS (SELECT 1 FROM tbl_jadwal WHERE tbl_jadwal.no_induk = tbl_guru.no_induk AND tbl_jadwal.hari = '$hari_ini_esc')
    ");
    if ($qGuru) {
        while ($rowGuru = mysqli_fetch_assoc($qGuru)) {
            $hp = trim($rowGuru['no_wa']);
            if ($hp !== '') {
                notif_send_templated_wa($conn, $hp, 'wa_notif_reminder', [
                    'nama_guru' => $rowGuru['nama_guru'],
                    'tanggal'   => date('d-m-Y')
                ]);
            }
        }
    }
    notif_save_wa_config($conn, 'wa_notif_reminder_last_sent_date', $today);
}

function notif_process_pending(mysqli $conn, int $limit = 20): array
{
    notif_ensure_schema($conn);
    
    // Cek dan kirim pengingat pagi jika sudah waktunya
    notif_check_and_trigger_morning_reminder($conn);
    
    // Cek dan kirim rekap harian jika sudah waktunya
    notif_check_and_trigger_daily_rekap($conn);
    
    $limit = max(1, min(100, $limit));
    $sent = 0;
    $failed = 0;
    
    $wa_delay       = (int)notif_get_custom_setting($conn, 'wa_delay', '5');
    $wa_batch_size  = (int)notif_get_custom_setting($conn, 'wa_batch_size', '10');
    $wa_batch_delay = (int)notif_get_custom_setting($conn, 'wa_batch_delay', '60');
    
    $loopCount = 0;

    $q = @mysqli_query($conn, "SELECT * FROM tbl_notifikasi_outbox WHERE status='pending' AND scheduled_at <= NOW() ORDER BY id ASC LIMIT $limit");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $id = (int)$row['id'];
        $channel = (string)$row['channel'];
        if ($channel === 'email') {
            [$ok, $err] = notif_send_email((string)$row['tujuan'], (string)$row['judul'], (string)$row['pesan']);
        } else {
            [$ok, $err] = notif_send_whatsapp((string)$row['tujuan'], (string)$row['judul'], (string)$row['pesan'], $conn);
        }

        $errEsc = mysqli_real_escape_string($conn, $err);
        if ($ok) {
            @mysqli_query($conn, "UPDATE tbl_notifikasi_outbox SET status='sent', sent_at=NOW(), percobaan=percobaan+1, error_message=NULL WHERE id=$id");
            $sent++;
            $loopCount++;
            
            // Jeda pengiriman (delay) agar tidak terdeteksi sebagai spam oleh WhatsApp
            if ($wa_batch_size > 0 && $loopCount % $wa_batch_size === 0) {
                sleep($wa_batch_delay);
            } else {
                sleep($wa_delay);
            }
        } else {
            @mysqli_query($conn, "UPDATE tbl_notifikasi_outbox SET status='failed', percobaan=percobaan+1, error_message='$errEsc' WHERE id=$id");
            $failed++;
        }
    }

    return ['sent' => $sent, 'failed' => $failed];
}

/**
 * Pemicu proses pengiriman notifikasi di antrean secara asynchronous (background).
 */
function notif_trigger_background_process(): void
{
    $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'proses-notifikasi.php';
    if (file_exists($scriptPath)) {
        $phpPath = 'C:\\xampp\\php\\php.exe';
        if (!file_exists($phpPath)) {
            $phpPath = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
        }
        
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $cmd = 'start /B "" "' . $phpPath . '" "' . $scriptPath . '" > NUL 2>&1';
            @pclose(@popen($cmd, "r"));
        } else {
            $cmd = '"' . $phpPath . '" "' . $scriptPath . '" > /dev/null 2>&1 &';
            @exec($cmd);
        }
    }
}
