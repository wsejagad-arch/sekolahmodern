<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$role = current_role();
$isAdmin = $role === 1;
$isGuru = $role === 2;
$isSiswa = $role === 3;

if (!$isAdmin && !$isGuru && !$isSiswa) {
    header('Location: index.php?haruslogin');
    exit;
}

$title = 'Ubah Password Akun';
$backUrl = $isGuru ? guru_page('profil-guru') : ($isSiswa ? siswa_page('profil') : 'home.php');
$idUser = (string)($_SESSION['id_user'] ?? '');
$noInduk = (string)($_SESSION['no_induk'] ?? '');
$flash = $_SESSION['password_flash'] ?? '';
$flashType = $_SESSION['password_flash_type'] ?? 'info';
unset($_SESSION['password_flash'], $_SESSION['password_flash_type']);

function ujp_table_has_password_column(mysqli $conn, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    $check = @mysqli_query($conn, "SHOW COLUMNS FROM {$table} LIKE 'password'");
    $cache[$table] = $check && mysqli_num_rows($check) > 0;
    return $cache[$table];
}

function ujp_hash_password(string $password): string
{
    return md5($password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_password'])) {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($token)) {
        $_SESSION['password_flash'] = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
        $_SESSION['password_flash_type'] = 'danger';
        header('Location: ubah-password.php');
        exit;
    }

    $passwordLama = (string)($_POST['password_lama'] ?? '');
    $passwordBaru = (string)($_POST['password_baru'] ?? '');
    $konfirmasi = (string)($_POST['konfirmasi_password'] ?? '');

    if ($passwordLama === '' || $passwordBaru === '' || $konfirmasi === '') {
        $_SESSION['password_flash'] = 'Semua kolom password wajib diisi.';
        $_SESSION['password_flash_type'] = 'danger';
        header('Location: ubah-password.php');
        exit;
    }

    if (strlen($passwordBaru) < 5) {
        $_SESSION['password_flash'] = 'Password baru minimal 5 karakter.';
        $_SESSION['password_flash_type'] = 'danger';
        header('Location: ubah-password.php');
        exit;
    }

    if ($passwordBaru !== $konfirmasi) {
        $_SESSION['password_flash'] = 'Konfirmasi password tidak sama.';
        $_SESSION['password_flash_type'] = 'danger';
        header('Location: ubah-password.php');
        exit;
    }

    $oldHash = ujp_hash_password($passwordLama);
    $newHash = ujp_hash_password($passwordBaru);
    $ok = false;

    if ($isAdmin && $idUser !== '') {
        $oldEsc = mysqli_real_escape_string($conn, $oldHash);
        $idEsc = mysqli_real_escape_string($conn, $idUser);
        $check = mysqli_query($conn, "SELECT password FROM tbl_user WHERE id_user='" . $idEsc . "' AND password='" . $oldEsc . "' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $newEsc = mysqli_real_escape_string($conn, $newHash);
            $ok = (bool)mysqli_query($conn, "UPDATE tbl_user SET password='" . $newEsc . "' WHERE id_user='" . $idEsc . "'");
        }
    } elseif (($isGuru || $isSiswa) && $noInduk !== '') {
        $noEsc = mysqli_real_escape_string($conn, $noInduk);
        $oldEsc = mysqli_real_escape_string($conn, $oldHash);
        $check = mysqli_query($conn, "SELECT password FROM tbl_pengguna WHERE no_induk='" . $noEsc . "' AND password='" . $oldEsc . "' LIMIT 1");
        if (!$check || mysqli_num_rows($check) === 0) {
            if ($isGuru && ujp_table_has_password_column($conn, 'tbl_guru')) {
                $check = mysqli_query($conn, "SELECT password FROM tbl_guru WHERE no_induk='" . $noEsc . "' AND password='" . $oldEsc . "' LIMIT 1");
            } elseif ($isSiswa && ujp_table_has_password_column($conn, 'tbl_siswa')) {
                $check = mysqli_query($conn, "SELECT password FROM tbl_siswa WHERE no_induk='" . $noEsc . "' AND password='" . $oldEsc . "' LIMIT 1");
            }
        }

        if ($check && mysqli_num_rows($check) > 0) {
            $newEsc = mysqli_real_escape_string($conn, $newHash);
            $ok = (bool)mysqli_query($conn, "UPDATE tbl_pengguna SET password='" . $newEsc . "' WHERE no_induk='" . $noEsc . "'");
            if ($isGuru && ujp_table_has_password_column($conn, 'tbl_guru')) {
                @mysqli_query($conn, "UPDATE tbl_guru SET password='" . $newEsc . "' WHERE no_induk='" . $noEsc . "'");
            }
            if ($isSiswa && ujp_table_has_password_column($conn, 'tbl_siswa')) {
                @mysqli_query($conn, "UPDATE tbl_siswa SET password='" . $newEsc . "' WHERE no_induk='" . $noEsc . "'");
            }
        }
    }

    if ($ok) {
        $_SESSION['password_flash'] = 'Password berhasil diperbarui.';
        $_SESSION['password_flash_type'] = 'success';
        header('Location: ubah-password.php');
        exit;
    }

    $_SESSION['password_flash'] = 'Password lama tidak cocok.';
    $_SESSION['password_flash_type'] = 'danger';
    header('Location: ubah-password.php');
    exit;
}

$lembaga = data_lembaga();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title); ?> - SIMANIS</title>
    <link rel="icon" href="img/<?= htmlspecialchars($lembaga['logo'] ?? 'favicon.ico'); ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 50%, #0f766e 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, .95);
            border-radius: 24px;
            box-shadow: 0 30px 90px rgba(0, 0, 0, .30);
            border: 1px solid rgba(255, 255, 255, .15);
        }

        .page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 24px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .input-wrap {
            position: relative;
        }

        .form-control {
            padding-left: 46px;
            min-height: 54px;
            border-radius: 14px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #0f766e;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #ecfeff;
            color: #0f766e;
            font-weight: 700;
            font-size: .8rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="page-wrap">
        <div class="container" style="max-width: 920px;">
            <div class="glass-card overflow-hidden">
                <div class="row g-0">
                    <div class="col-lg-5 p-4 p-lg-5 text-white" style="background: linear-gradient(160deg, #1d4ed8, #0f766e);">
                        <span class="hero-chip mb-3"><i class="bi bi-shield-lock"></i> Keamanan Akun</span>
                        <h1 class="h2 fw-black mb-3">Ubah Password</h1>
                        <p class="mb-4 text-white-75">Gunakan halaman ini untuk mengganti password akun Anda sendiri. Password default saat akun baru dibuat adalah 12345 untuk guru dan siswa, sedangkan admin menggunakan admin12345.</p>
                        <div class="small text-white-50">Akun: <?= $isAdmin ? 'Admin' : ($isGuru ? 'Guru' : 'Siswa'); ?></div>
                    </div>
                    <div class="col-lg-7 p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($title); ?></h2>
                                <div class="text-muted small">Masukkan password lama, lalu set password baru Anda.</div>
                            </div>
                            <a href="<?= htmlspecialchars($backUrl); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
                        </div>

                        <?php if ($flash !== ''): ?>
                            <div class="alert alert-<?= htmlspecialchars($flashType); ?>"><?= htmlspecialchars($flash); ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <?= csrf_token_field(); ?>
                            <input type="hidden" name="ubah_password" value="1">

                            <div class="mb-3">
                                <label class="form-label">Password Lama</label>
                                <div class="input-wrap">
                                    <i class="bi bi-lock input-icon"></i>
                                    <input type="password" name="password_lama" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <div class="input-wrap">
                                    <i class="bi bi-key input-icon"></i>
                                    <input type="password" name="password_baru" class="form-control" required>
                                    <button type="button" class="password-toggle" id="toggleNewPwd"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Konfirmasi Password</label>
                                <div class="input-wrap">
                                    <i class="bi bi-check2-circle input-icon"></i>
                                    <input type="password" name="konfirmasi_password" class="form-control" required>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?= htmlspecialchars($backUrl); ?>" class="btn btn-light border">Batal</a>
                                <button type="submit" class="btn btn-primary px-4">Simpan Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const toggleNewPwd = document.getElementById('toggleNewPwd');
        const newPwdField = document.querySelector('input[name="password_baru"]');
        if (toggleNewPwd && newPwdField) {
            toggleNewPwd.addEventListener('click', () => {
                const isHidden = newPwdField.type === 'password';
                newPwdField.type = isHidden ? 'text' : 'password';
                toggleNewPwd.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        }
    </script>
</body>

</html>