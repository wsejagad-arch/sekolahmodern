<?php
require_once __DIR__ . '/google_auth.php';

if (!$conn instanceof mysqli) {
    google_oauth_error_redirect('db_error');
}

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
$stateData = $_SESSION['google_oauth_state'][$state] ?? null;

if ($state === '' || !$stateData || (($stateData['created_at'] ?? 0) < time() - 900)) {
    google_oauth_error_redirect('state');
}
unset($_SESSION['google_oauth_state'][$state]);

$schoolCode = strtoupper((string)($stateData['kode'] ?? 'DEFAULT'));
$schoolId = mt_resolve_school_id($conn, $schoolCode);
if ($schoolId <= 0) {
    google_oauth_error_redirect('school', $schoolCode);
}

if ($code === '') {
    google_oauth_error_redirect('cancelled', $schoolCode);
}
if (!google_oauth_is_configured()) {
    google_oauth_error_redirect('not_configured', $schoolCode);
}

$cfg = google_oauth_credentials();
$tokenResponse = google_oauth_post_json('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => $cfg['client_id'],
    'client_secret' => $cfg['client_secret'],
    'redirect_uri' => $cfg['redirect_uri'],
    'grant_type' => 'authorization_code',
]);

if (!$tokenResponse['ok'] || empty($tokenResponse['data']['access_token'])) {
    google_oauth_error_redirect('token', $schoolCode);
}

$profileResponse = google_oauth_get_json('https://openidconnect.googleapis.com/v1/userinfo', (string)$tokenResponse['data']['access_token']);
if (!$profileResponse['ok'] || empty($profileResponse['data']['email'])) {
    google_oauth_error_redirect('profile', $schoolCode);
}

$profile = $profileResponse['data'];
$email = strtolower(trim((string)$profile['email']));
$verified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
if (!$verified) {
    google_oauth_error_redirect('email_unverified', $schoolCode);
}

$matched = google_find_user_by_email($conn, $email, $schoolId);
if (!$matched) {
    google_oauth_error_redirect('email_not_found', $schoolCode);
}

$_SESSION['login_provider'] = 'google';
$_SESSION['google_email'] = $email;
$_SESSION['id_sekolah'] = $schoolId;
$_SESSION['kode_sekolah'] = $schoolCode;

if ($matched['role'] === 'admin') {
    set_admin_session($matched['data']);
    session_regenerate_id(true);
    redirect('home.php');
}

if ($matched['role'] === 'guru') {
    set_guru_session($matched['data']);
    session_regenerate_id(true);
    header('Location: ' . guru_page('guru_legacy'));
    exit;
}

set_siswa_session($matched['data']);
session_regenerate_id(true);
header('Location: ' . siswa_page('siswa'));
exit;
