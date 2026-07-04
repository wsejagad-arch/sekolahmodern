<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/notification_helper.php';

require_admin();

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $judul = trim((string)($_POST['judul'] ?? ''));
    $pesan = trim((string)($_POST['pesan'] ?? ''));

    if (!verify_csrf_token($token)) {
        $error = 'Sesi formulir tidak valid.';
    } elseif ($judul === '' || $pesan === '') {
        $error = 'Judul dan isi notifikasi wajib diisi.';
    } elseif (!$conn instanceof mysqli) {
        $error = 'Database tidak tersambung.';
    } else {
        $queued = notif_queue_all_schools($conn, $judul, $pesan);
        $message = "Notifikasi berhasil dimasukkan ke antrean: $queued pesan.";
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kirim Notifikasi Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4" style="max-width:760px">
    <a href="home.php" class="text-decoration-none">&larr; Kembali</a>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold">Kirim Notifikasi Update</h1>
            <p class="text-muted">Pesan akan masuk antrean email dan WhatsApp semua sekolah aktif.</p>
            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                <div>
                    <label class="form-label fw-semibold">Judul</label>
                    <input class="form-control" name="judul" placeholder="Contoh: Update SIMANIS versi terbaru" required>
                </div>
                <div>
                    <label class="form-label fw-semibold">Isi Pesan</label>
                    <textarea class="form-control" name="pesan" rows="7" required></textarea>
                </div>
                <button class="btn btn-primary fw-semibold" type="submit">Masukkan ke Antrean</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
