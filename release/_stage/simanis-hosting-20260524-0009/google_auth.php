<?php
require_once __DIR__ . '/bootstrap.php';

function google_oauth_credentials(): array
{
    $clientId = getenv('SIMANIS_GOOGLE_CLIENT_ID') ?: ($GLOBALS['google_client_id'] ?? '');
    $clientSecret = getenv('SIMANIS_GOOGLE_CLIENT_SECRET') ?: ($GLOBALS['google_client_secret'] ?? '');
    $redirectUri = getenv('SIMANIS_GOOGLE_REDIRECT_URI') ?: ($GLOBALS['google_redirect_uri'] ?? '');

    if (($clientId === '' || $clientSecret === '') && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $dbCfg = google_oauth_db_credentials($GLOBALS['conn']);
        $clientId = $clientId !== '' ? $clientId : ($dbCfg['client_id'] ?? '');
        $clientSecret = $clientSecret !== '' ? $clientSecret : ($dbCfg['client_secret'] ?? '');
        $redirectUri = $redirectUri !== '' ? $redirectUri : ($dbCfg['redirect_uri'] ?? '');
    }

    if ($redirectUri === '') {
        $redirectUri = get_app_url() . get_base_path() . '/google-callback.php';
    }

    return [
        'client_id' => trim((string)$clientId),
        'client_secret' => trim((string)$clientSecret),
        'redirect_uri' => $redirectUri,
    ];
}

function google_oauth_db_credentials(mysqli $conn): array
{
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_app_config (
        kunci VARCHAR(80) PRIMARY KEY,
        nilai TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $values = [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => '',
    ];

    $q = @mysqli_query($conn, "SELECT kunci, nilai FROM tbl_app_config WHERE kunci IN ('google_client_id','google_client_secret','google_redirect_uri')");
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        if ($row['kunci'] === 'google_client_id') {
            $values['client_id'] = (string)$row['nilai'];
        } elseif ($row['kunci'] === 'google_client_secret') {
            $values['client_secret'] = (string)$row['nilai'];
        } elseif ($row['kunci'] === 'google_redirect_uri') {
            $values['redirect_uri'] = (string)$row['nilai'];
        }
    }

    return $values;
}

function google_oauth_save_db_credentials(mysqli $conn, string $clientId, string $clientSecret, string $redirectUri): bool
{
    google_oauth_db_credentials($conn);
    $pairs = [
        'google_client_id' => $clientId,
        'google_client_secret' => $clientSecret,
        'google_redirect_uri' => $redirectUri,
    ];

    foreach ($pairs as $key => $value) {
        $keyEsc = mysqli_real_escape_string($conn, $key);
        $valueEsc = mysqli_real_escape_string($conn, $value);
        $ok = @mysqli_query($conn, "INSERT INTO tbl_app_config (kunci, nilai) VALUES ('$keyEsc', '$valueEsc') ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)");
        if (!$ok) {
            return false;
        }
    }

    return true;
}

function google_oauth_is_configured(): bool
{
    $cfg = google_oauth_credentials();
    return $cfg['client_id'] !== '' && $cfg['client_secret'] !== '';
}

function google_oauth_error_redirect(string $reason, string $kode = ''): void
{
    $params = ['google_error' => $reason];
    if ($kode !== '') {
        $params['kode'] = $kode;
    }
    header('Location: login.php?' . http_build_query($params));
    exit;
}

function google_oauth_start(string $schoolCode): void
{
    if (!google_oauth_is_configured()) {
        google_oauth_error_redirect('not_configured', $schoolCode);
    }

    $cfg = google_oauth_credentials();
    $state = bin2hex(random_bytes(24));
    $_SESSION['google_oauth_state'] = $_SESSION['google_oauth_state'] ?? [];
    $_SESSION['google_oauth_state'][$state] = [
        'kode' => $schoolCode,
        'created_at' => time(),
    ];

    foreach ($_SESSION['google_oauth_state'] as $key => $item) {
        if (($item['created_at'] ?? 0) < time() - 900) {
            unset($_SESSION['google_oauth_state'][$key]);
        }
    }

    $params = [
        'client_id' => $cfg['client_id'],
        'redirect_uri' => $cfg['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'prompt' => 'select_account',
    ];

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}

function google_oauth_post_json(string $url, array $payload): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'Ekstensi PHP cURL belum aktif.', 'data' => null];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => $error ?: 'HTTP ' . $httpCode, 'data' => null];
    }

    $data = json_decode((string)$response, true);
    return is_array($data)
        ? ['ok' => true, 'error' => '', 'data' => $data]
        : ['ok' => false, 'error' => 'Response Google tidak valid.', 'data' => null];
}

function google_oauth_get_json(string $url, string $token): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'Ekstensi PHP cURL belum aktif.', 'data' => null];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => $error ?: 'HTTP ' . $httpCode, 'data' => null];
    }

    $data = json_decode((string)$response, true);
    return is_array($data)
        ? ['ok' => true, 'error' => '', 'data' => $data]
        : ['ok' => false, 'error' => 'Response profil Google tidak valid.', 'data' => null];
}

function google_find_user_by_email(mysqli $conn, string $email, int $schoolId): ?array
{
    mt_ensure_schema($conn);
    $emailEsc = mysqli_real_escape_string($conn, strtolower($email));
    $schoolId = max(1, $schoolId);

    if (mt_column_exists($conn, 'tbl_user', 'email')) {
        $qAdmin = mysqli_query(
            $conn,
            "SELECT u.id_user, u.username, u.nama, u.hak_akses, u.id_sekolah, COALESCE(s.kode_sekolah, 'DEFAULT') AS kode_sekolah
             FROM tbl_user u
             LEFT JOIN tbl_sekolah s ON s.id_sekolah=u.id_sekolah
             WHERE LOWER(u.email)='$emailEsc' AND u.id_sekolah=$schoolId LIMIT 1"
        );
        $admin = $qAdmin ? mysqli_fetch_assoc($qAdmin) : null;
        if ($admin) {
            return ['role' => 'admin', 'data' => $admin];
        }
    }

    if (mt_column_exists($conn, 'tbl_guru', 'email')) {
        $qGuru = mysqli_query(
            $conn,
            "SELECT g.no_induk, g.nama_guru, g.status_kepegawaian, g.id_sekolah, COALESCE(s.kode_sekolah, 'DEFAULT') AS kode_sekolah
             FROM tbl_guru g
             LEFT JOIN tbl_sekolah s ON s.id_sekolah=g.id_sekolah
             WHERE LOWER(g.email)='$emailEsc' AND g.id_sekolah=$schoolId AND g.status='Aktif' LIMIT 1"
        );
        $guru = $qGuru ? mysqli_fetch_assoc($qGuru) : null;
        if ($guru) {
            return ['role' => 'guru', 'data' => $guru];
        }
    }

    if (mt_column_exists($conn, 'tbl_siswa', 'email')) {
        $qSiswa = mysqli_query(
            $conn,
            "SELECT s.no_induk, s.nama_siswa, s.kelas, s.jabatan, 3 AS hak_akses, s.id_sekolah, COALESCE(sk.kode_sekolah, 'DEFAULT') AS kode_sekolah
             FROM tbl_siswa s
             LEFT JOIN tbl_sekolah sk ON sk.id_sekolah=s.id_sekolah
             WHERE LOWER(s.email)='$emailEsc' AND s.id_sekolah=$schoolId AND s.status='Aktif' LIMIT 1"
        );
        $siswa = $qSiswa ? mysqli_fetch_assoc($qSiswa) : null;
        if ($siswa) {
            return ['role' => 'siswa', 'data' => $siswa];
        }
    }

    return null;
}
