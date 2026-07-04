<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/google_auth.php';

function login_page_data_lembaga(): array
{
    $default = [
        'nmsekolah' => 'SIMANIS',
        'nama_aplikasi' => 'SIMANIS',
        'alamatlembaga' => '',
        'alamat' => '',
        'nmpimpinan' => '',
        'nippimpinan' => '',
        'logo' => 'logo dash.png',
        'maintenance_mode' => '0',
    ];

    try {
        global $conn;
        $idSekolah = 1;
        
        // Dynamic loading based on URL parameter 'kode' (NPSN) or session school ID
        if (isset($_GET['kode']) && trim($_GET['kode']) !== '' && isset($conn) && $conn) {
            $resolvedId = mt_resolve_school_id($conn, $_GET['kode']);
            if ($resolvedId > 0) {
                $idSekolah = $resolvedId;
            }
        } elseif (isset($_SESSION['id_sekolah']) && (int)$_SESSION['id_sekolah'] > 0) {
            $idSekolah = (int)$_SESSION['id_sekolah'];
        }

        if (isset($conn) && $conn) {
            $idSekolahEsc = (int)$idSekolah;
            $sql = mysqli_query($conn, "SELECT * FROM tbl_setting WHERE id_sekolah = $idSekolahEsc ORDER BY id DESC LIMIT 1");
            if ($sql && $row = mysqli_fetch_assoc($sql)) {
                return array_merge($default, [
                    "nmsekolah" => $row['nama_sekolah'] ?? $default['nmsekolah'],
                    "nama_aplikasi" => $row['nama_aplikasi'] ?? $default['nama_aplikasi'],
                    "alamatlembaga" => $row['alamat'] ?? $default['alamatlembaga'],
                    "alamat" => $row['alamat'] ?? $default['alamat'],
                    "nmpimpinan" => $row['nama_pimpinan'] ?? $default['nmpimpinan'],
                    "nippimpinan" => $row['nip_pimpinan'] ?? $default['nippimpinan'],
                    "logo" => $row['logo'] ?? $default['logo'],
                    "maintenance_mode" => $row['maintenance_mode'] ?? $default['maintenance_mode']
                ]);
            }
        }
    } catch (Throwable $e) {
        // Fallback
    }

    return $default;
}

$lembaga = login_page_data_lembaga();
$logoFile = (!empty($lembaga['logo']) && $lembaga['logo'] !== 'logo dash.png' && file_exists(__DIR__ . '/img/' . $lembaga['logo']) && is_file(__DIR__ . '/img/' . $lembaga['logo'])) ? $lembaga['logo'] : '6695f027d063a.png';
$googleLoginReady = google_oauth_is_configured();
$googleErrorMessages = [
    'not_configured' => 'Login Gmail belum dikonfigurasi. Admin perlu mengisi Google Client ID dan Client Secret di menu Pengaturan > Login Gmail.',
    'state' => 'Sesi login Gmail tidak valid. Silakan coba lagi.',
    'school' => 'NPSN sekolah tidak ditemukan atau belum aktif.',
    'cancelled' => 'Login Gmail dibatalkan.',
    'token' => 'Gagal menukar kode login Gmail.',
    'profile' => 'Gagal membaca profil Gmail.',
    'email_unverified' => 'Email Google belum terverifikasi.',
    'email_not_found' => 'Email Gmail ini belum terdaftar pada sekolah yang dipilih.',
    'db_error' => 'Database tidak tersambung.',
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login <?= htmlspecialchars($lembaga['nama_aplikasi']); ?> <?= htmlspecialchars($lembaga['nmsekolah']); ?></title>
    <link rel="icon" href="img/<?= htmlspecialchars($logoFile); ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-glow: rgba(79, 70, 229, 0.4);
            --bg-a: #0F172A;
            --bg-b: #1E1B4B;
            --panel: #FFFFFF;
            --text: #0F172A;
            --muted: #64748B;
            --input: #F8FAFC;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b0f19;
            background-attachment: fixed;
            color: var(--text);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1.5px, transparent 1.5px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 1;
        }

        /* Ambient Glow Blobs Styling */
        .ambient-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            mix-blend-mode: screen;
            animation: float-blob 25s infinite alternate ease-in-out;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vh;
            background: radial-gradient(circle, #4F46E5 0%, transparent 70%);
        }

        .blob-2 {
            bottom: -10%;
            right: -10%;
            width: 60vw;
            height: 60vh;
            background: radial-gradient(circle, #7C3AED 0%, transparent 70%);
            animation-delay: -5s;
            animation-duration: 30s;
        }

        .blob-3 {
            top: 40%;
            left: 30%;
            width: 40vw;
            height: 40vh;
            background: radial-gradient(circle, #10B981 0%, transparent 70%);
            animation-delay: -10s;
            animation-duration: 20s;
        }

        @keyframes float-blob {
            0% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(5%, 10%) scale(1.1);
            }
            100% {
                transform: translate(-5%, -5%) scale(0.9);
            }
        }

        .page-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            z-index: 2;
        }

        .login-shell {
            width: min(100%, 1060px);
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            border-radius: 28px;
            overflow: hidden;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .34);
            backdrop-filter: blur(18px);
        }

        .hero-panel {
            padding: 42px;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .14), transparent 40%),
                linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
            top: -70px;
            right: -60px;
            z-index: 1;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 36px;
            background: rgba(255, 255, 255, .06);
            bottom: -40px;
            left: -40px;
            transform: rotate(18deg);
            z-index: 1;
        }

        .hero-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
            z-index: 2;
        }

        .brand-mark {
            width: 110px;
            height: 110px;
            border-radius: 28px;
            background: #ffffff;
            display: grid;
            place-items: center;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            padding: 12px;
            transition: transform 0.3s ease;
        }

        .brand-mark:hover {
            transform: translateY(-5px);
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-title {
            margin: 0;
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 900;
            letter-spacing: .05em;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .brand-subtitle {
            margin: 10px 0 0;
            color: rgba(255, 255, 255, .9);
            font-size: 1rem;
            line-height: 1.6;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-panel {
            padding: 42px;
            background: #ffffff;
            color: #1e293b;
        }

        .form-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-heading h2 {
            margin: 0;
            font-size: 1.85rem;
            font-weight: 900;
        }

        .form-heading p {
            margin: 8px 0 0;
            color: #64748b;
            line-height: 1.6;
        }

        .alert {
            border: none;
            border-radius: 16px;
            padding: 14px 16px;
            font-weight: 600;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .form-group {
            margin-top: 16px;
        }

        .form-label {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            min-height: 56px;
            border: 1px solid #dbe3ef;
            border-radius: 16px;
            background: var(--input);
            padding: 12px 16px 12px 46px;
            font-size: 1rem;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: 0;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #0f766e;
            font-size: 1.1rem;
        }

        .submit-btn {
            width: 100%;
            margin-top: 24px;
            min-height: 56px;
            border: 0;
            border-radius: 16px;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: .02em;
            color: #fff;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            box-shadow: 0 10px 30px var(--primary-glow);
        }

        .submit-btn:hover {
            filter: brightness(1.03);
        }

        .google-btn {
            width: 100%;
            margin-top: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 13px 18px;
            background: #ffffff;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            font-weight: 800;
        }

        .google-btn:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        .google-btn:disabled,
        .google-btn-disabled {
            cursor: not-allowed;
            opacity: .65;
        }

        .google-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-grid;
            place-items: center;
            color: #ffffff;
            background: conic-gradient(#4285f4 0 25%, #34a853 0 50%, #fbbc05 0 75%, #ea4335 0);
            font-weight: 900;
            font-size: .8rem;
        }

        .footer-note {
            margin-top: 18px;
            color: #64748b;
            font-size: .85rem;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .hero-panel,
            .form-panel {
                padding: 28px 22px;
            }
        }

        @media (max-width: 576px) {
            .page-shell {
                padding: 14px;
                position: relative;
                z-index: 2;
            }

            .hero-brand {
                gap: 12px;
            }

            .brand-mark {
                width: 80px;
                height: 80px;
                border-radius: 20px;
                padding: 8px;
            }

            .brand-title,
            .form-heading h2 {
                font-size: 1.35rem;
            }

            .form-control,
            .submit-btn {
                min-height: 52px;
                border-radius: 14px;
            }
        }
    </style>
</head>

<body>
    <!-- Ambient Glow Blobs Background -->
    <div class="ambient-glow">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
    <div class="page-shell">
        <section class="login-shell">
            <aside class="hero-panel">
                <div class="hero-brand">
                    <div class="brand-mark">
                        <img src="img/<?= htmlspecialchars($logoFile); ?>" alt="Logo <?= htmlspecialchars($lembaga['nama_aplikasi']); ?>">
                    </div>
                    <div>
                        <h1 class="brand-title"><?= htmlspecialchars($lembaga['nama_aplikasi']); ?></h1>
                        <p class="brand-subtitle">
                            <span style="font-size: 0.9rem; opacity: 0.8; font-weight: 500; display: block; margin-bottom: 4px;">Sistem Informasi Manajemen Akademik</span>
                            <span style="font-weight: 800; font-size: 1.15rem; display: block; letter-spacing: 0.02em; text-transform: uppercase;"><?= htmlspecialchars($lembaga['nmsekolah']); ?></span>
                        </p>
                    </div>
                </div>
            </aside>

            <section class="form-panel">
                <div class="form-card">
                    <div class="form-heading">
                        <h2>Masuk</h2>
                        <p>Masukkan NPSN sekolah, username, dan password Anda untuk melanjutkan.</p>
                    </div>

                    <?php if (isset($_GET['haruslogin'])): ?>
                        <div class="alert alert-danger mt-4">Silakan login terlebih dahulu untuk melanjutkan.</div>
                    <?php elseif (isset($_GET['db_error'])): ?>
                        <div class="alert alert-danger mt-4">Database tidak tersambung. Jalankan server dengan PHP XAMPP, lalu coba login lagi.</div>
                    <?php elseif (isset($_GET['gagallogin'])): ?>
                        <div class="alert alert-danger mt-4">Username atau password tidak sesuai.</div>
                    <?php elseif (isset($_GET['google_error'])): ?>
                        <div class="alert alert-danger mt-4"><?= htmlspecialchars($googleErrorMessages[$_GET['google_error']] ?? 'Login Gmail gagal.'); ?></div>
                    <?php elseif (isset($_GET['logout'])): ?>
                        <div class="alert alert-success mt-4">Anda berhasil logout.</div>
                    <?php endif; ?>

                    <form method="post" action="login_action.php" class="mt-3">
                        <input type="hidden" name="hak_akses" value="auto">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">

                        <div class="form-group">
                            <label class="form-label" for="kode_sekolah">NPSN Sekolah</label>
                            <div class="input-wrap">
                                <i class="bi bi-building input-icon"></i>
                                <input type="text" class="form-control" id="kode_sekolah" name="kode_sekolah" placeholder="Masukkan NPSN sekolah" value="<?= htmlspecialchars($_GET['kode'] ?? 'DEFAULT'); ?>" required autocomplete="organization">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="username">Username / NIP / NIS</label>
                            <div class="input-wrap">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autocomplete="username">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-wrap">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                                <button type="button" class="password-toggle" id="togglePassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">Masuk Sekarang</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="daftar-sekolah.php" class="text-decoration-none fw-semibold">Daftarkan sekolah baru</a>
                    </div>

                    <div class="footer-note">© <?= date('Y'); ?> <?= htmlspecialchars($lembaga['nama_aplikasi']); ?></div>
                </div>
            </section>
        </section>
    </div>

    <script>
        const passwordField = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', () => {
            const isHidden = passwordField.type === 'password';
            passwordField.type = isHidden ? 'text' : 'password';
            togglePassword.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    </script>
</body>

</html>
