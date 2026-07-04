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
        $data = data_lembaga();
        if (is_array($data)) {
            return array_merge($default, $data);
        }
    } catch (Throwable $e) {
        // Fallback ke branding statis jika database belum siap.
    }

    return $default;
}

$lembaga = login_page_data_lembaga();
$logoFile = file_exists(__DIR__ . '/img/' . $lembaga['logo']) ? $lembaga['logo'] : 'logo dash.png';
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
            background: #0F172A url('img/bg_2026.png') no-repeat center center fixed;
            background-size: cover;
            color: var(--text);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
            background-size: 34px 34px;
            pointer-events: none;
        }

        .page-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            z-index: 1;
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
        }

        .hero-brand {
            margin-top: 28px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-mark {
            width: 74px;
            height: 74px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .16);
            display: grid;
            place-items: center;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .14);
        }

        .brand-mark img {
            width: 54px;
            height: 54px;
            object-fit: contain;
        }

        .brand-title {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            letter-spacing: .08em;
        }

        .brand-subtitle {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, .88);
            font-size: 1rem;
            line-height: 1.6;
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
            }

            .hero-brand {
                gap: 12px;
            }

            .brand-mark {
                width: 60px;
                height: 60px;
                border-radius: 16px;
            }

            .brand-mark img {
                width: 44px;
                height: 44px;
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
    <div class="page-shell">
        <section class="login-shell">
            <aside class="hero-panel">
                <div class="hero-brand">
                    <div class="brand-mark">
                        <img src="img/<?= htmlspecialchars($logoFile); ?>" alt="Logo <?= htmlspecialchars($lembaga['nama_aplikasi']); ?>">
                    </div>
                    <div>
                        <h1 class="brand-title"><?= htmlspecialchars($lembaga['nama_aplikasi']); ?></h1>
                        <p class="brand-subtitle">Sistem Informasi Manajemen Akademik <?= htmlspecialchars($lembaga['nmsekolah']); ?></p>
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
                    <?php if ($googleLoginReady): ?>
                        <a href="google-login.php?kode=<?= urlencode($_GET['kode'] ?? 'DEFAULT'); ?>" class="google-btn" id="googleLoginBtn">
                            <span class="google-dot">G</span>
                            <span>Masuk dengan Gmail</span>
                        </a>
                    <?php else: ?>
                        <button type="button" class="google-btn google-btn-disabled" disabled aria-disabled="true">
                            <span class="google-dot">G</span>
                            <span>Masuk dengan Gmail</span>
                        </button>
                        <div class="text-muted small mt-2">Login Gmail aktif setelah admin mengisi konfigurasi di Pengaturan &gt; Login Gmail.</div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="daftar-sekolah.php" class="text-decoration-none fw-semibold">Daftarkan sekolah baru</a>
                        <span class="text-muted mx-2">|</span>
                        <a href="admin-pusat-login.php" class="text-decoration-none fw-semibold">Admin pusat</a>
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

        const schoolCodeField = document.getElementById('kode_sekolah');
        const googleLoginBtn = document.getElementById('googleLoginBtn');
        if (schoolCodeField && googleLoginBtn) {
            googleLoginBtn.addEventListener('click', () => {
                const code = schoolCodeField.value.trim() || 'DEFAULT';
                googleLoginBtn.href = 'google-login.php?kode=' + encodeURIComponent(code);
            });
        }
    </script>
</body>

</html>
