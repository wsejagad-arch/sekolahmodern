<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_pusat_helper.php';

if ($conn instanceof mysqli) {
    pusat_admin_ensure_schema($conn);
}

if (function_exists('is_admin_pusat') && is_admin_pusat()) {
    redirect('admin-pusat.php');
}

$error = '';
$isFirstSetup = $conn instanceof mysqli ? pusat_admin_count($conn) === 0 : false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!verify_csrf_token($token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$conn instanceof mysqli) {
        $error = 'Database tidak tersambung.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif ($isFirstSetup) {
        $nama = trim((string)($_POST['nama'] ?? 'Admin Pusat'));
        $email = trim((string)($_POST['email'] ?? ''));

        if (strlen($password) < 8) {
            $error = 'Password admin pusat minimal 8 karakter.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (pusat_admin_create($conn, $username, $nama !== '' ? $nama : $username, $email, $password)) {
            $admin = pusat_admin_find($conn, $username);
            if ($admin) {
                set_admin_pusat_session($admin);
                session_regenerate_id(true);
                pusat_admin_mark_login($conn, (int)$admin['id_admin_pusat']);
                redirect('admin-pusat.php');
            }
            $error = 'Admin pusat berhasil dibuat, tetapi sesi login gagal dibuat.';
        } else {
            $error = 'Gagal membuat admin pusat. Username mungkin sudah digunakan.';
        }
    } else {
        $admin = pusat_admin_find($conn, $username);
        if (!$admin || ($admin['status'] ?? '') !== 'Aktif' || !password_verify($password, (string)$admin['password'])) {
            $error = 'Username atau password admin pusat tidak sesuai.';
        } else {
            set_admin_pusat_session($admin);
            session_regenerate_id(true);
            pusat_admin_mark_login($conn, (int)$admin['id_admin_pusat']);
            redirect('admin-pusat.php');
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Pusat - SIMANIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f1f5f9;
            font-family: "Poppins", "Segoe UI", sans-serif;
            color: #0f172a;
        }
        .login-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .panel {
            width: min(100%, 460px);
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 22px 70px rgba(15, 23, 42, .14);
            overflow: hidden;
        }
        .panel-head {
            padding: 28px;
            background: #0f172a;
            color: #ffffff;
        }
        .panel-body {
            padding: 28px;
        }
        .form-control {
            border-radius: 12px;
            min-height: 48px;
        }
        .btn-main {
            min-height: 48px;
            border: 0;
            border-radius: 12px;
            font-weight: 800;
            background: #2563eb;
            color: #ffffff;
        }
        .btn-main:hover {
            background: #1d4ed8;
            color: #ffffff;
        }
    </style>
</head>
<body>
<main class="login-shell">
    <section class="panel">
        <div class="panel-head">
            <a href="login.php" class="text-white-50 text-decoration-none"><i class="bi bi-arrow-left"></i> Login sekolah</a>
            <h1 class="h4 fw-bold mt-3 mb-1"><?= $isFirstSetup ? 'Buat Admin Pusat' : 'Login Admin Pusat'; ?></h1>
            <p class="mb-0 text-white-50">Area pemantauan semua sekolah dan client ID/NPSN yang terdaftar.</p>
        </div>
        <div class="panel-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($isFirstSetup): ?>
                <div class="alert alert-info">Belum ada admin pusat. Buat akun pertama untuk mengamankan dashboard pusat.</div>
            <?php endif; ?>

            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                <?php if ($isFirstSetup): ?>
                    <div>
                        <label class="form-label fw-semibold">Nama Admin</label>
                        <input class="form-control" name="nama" placeholder="Admin Pusat" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="admin@simanis.local">
                    </div>
                <?php endif; ?>
                <div>
                    <label class="form-label fw-semibold">Username</label>
                    <input class="form-control" name="username" autocomplete="username" required>
                </div>
                <div>
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" name="password" minlength="<?= $isFirstSetup ? '8' : '1'; ?>" autocomplete="<?= $isFirstSetup ? 'new-password' : 'current-password'; ?>" required>
                </div>
                <button class="btn btn-main w-100" type="submit">
                    <i class="bi <?= $isFirstSetup ? 'bi-shield-plus' : 'bi-box-arrow-in-right'; ?> me-1"></i>
                    <?= $isFirstSetup ? 'Buat dan Masuk' : 'Masuk Admin Pusat'; ?>
                </button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
