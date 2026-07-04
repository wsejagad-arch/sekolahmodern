<?php
require_once __DIR__ . '/google_auth.php';

require_admin();

$message = '';
$error = '';
$cfg = google_oauth_credentials();
$defaultRedirect = get_app_url() . get_base_path() . '/google-callback.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $clientId = trim((string)($_POST['google_client_id'] ?? ''));
    $clientSecret = trim((string)($_POST['google_client_secret'] ?? ''));
    $redirectUri = trim((string)($_POST['google_redirect_uri'] ?? $defaultRedirect));

    if (!verify_csrf_token($token)) {
        $error = 'Sesi formulir tidak valid.';
    } elseif ($clientId === '' || $clientSecret === '') {
        $error = 'Client ID dan Client Secret wajib diisi.';
    } elseif (!filter_var($redirectUri, FILTER_VALIDATE_URL)) {
        $error = 'Redirect URI tidak valid.';
    } elseif (!$conn instanceof mysqli) {
        $error = 'Database tidak tersambung.';
    } elseif (google_oauth_save_db_credentials($conn, $clientId, $clientSecret, $redirectUri)) {
        $message = 'Konfigurasi Login Gmail berhasil disimpan.';
        $cfg = google_oauth_credentials();
    } else {
        $error = 'Gagal menyimpan konfigurasi Google.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Login Gmail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4" style="max-width:860px">
    <a href="home.php" class="text-decoration-none">&larr; Kembali</a>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold">Pengaturan Login Gmail</h1>
            <p class="text-muted mb-2">Masukkan OAuth Client dari Google Cloud Console.</p>
            <div class="alert alert-info">
                Redirect URI yang harus dimasukkan di Google Cloud Console:<br>
                <code><?= htmlspecialchars($cfg['redirect_uri'] ?: $defaultRedirect); ?></code>
            </div>
            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                <div>
                    <label class="form-label fw-semibold">Google Client ID</label>
                    <input class="form-control" name="google_client_id" value="<?= htmlspecialchars($cfg['client_id']); ?>" required>
                </div>
                <div>
                    <label class="form-label fw-semibold">Google Client Secret</label>
                    <input class="form-control" name="google_client_secret" value="<?= htmlspecialchars($cfg['client_secret']); ?>" required>
                </div>
                <div>
                    <label class="form-label fw-semibold">Redirect URI</label>
                    <input class="form-control" name="google_redirect_uri" value="<?= htmlspecialchars($cfg['redirect_uri'] ?: $defaultRedirect); ?>" required>
                </div>
                <button class="btn btn-primary fw-semibold" type="submit">Simpan Konfigurasi</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
