<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}
http_response_code(503);
$title = 'Fitur Guru';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title); ?> - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="bg-white border rounded-3 p-4 shadow-sm">
            <h1 class="h4 fw-bold mb-2">Fitur belum tersedia di salinan lokal</h1>
            <p class="text-muted mb-4">File halaman ini belum ditemukan di backup lokal project. Halaman utama guru dan Data Siswa tetap dapat digunakan.</p>
            <a href="../../home.php" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
    </main>
</body>
</html>