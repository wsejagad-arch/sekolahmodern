<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/nocache.php';
require_once __DIR__ . '/google_auth.php';

// Redirect if already logged in
if (isset($_SESSION['username']) && (is_admin() || is_guru() || is_siswa())) {
    header('Location: home.php');
    exit;
} elseif (isset($_SESSION['username']) && is_admin_pusat()) {
    header('Location: admin-pusat.php');
    exit;
}


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
            padding: 0;
            min-height: 100vh;
            font-family: 'Nunito', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--panel);
            color: var(--text);
            overflow-x: hidden;
        }

        /* Ambient Glow Blobs Styling for Hero Panel */
        .ambient-glow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            mix-blend-mode: screen;
            animation: float-blob 20s infinite alternate ease-in-out;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #10B981 0%, transparent 70%);
        }

        .blob-2 {
            bottom: -10%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #059669 0%, transparent 70%);
            animation-delay: -5s;
            animation-duration: 25s;
        }

        .blob-3 {
            top: 40%;
            left: 30%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #34D399 0%, transparent 70%);
            animation-delay: -10s;
            animation-duration: 15s;
        }

        @keyframes float-blob {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(5%, 10%) scale(1.1); }
            100% { transform: translate(-5%, -5%) scale(0.9); }
        }

        .page-shell {
            display: flex;
            min-height: 100vh;
            width: 100vw;
        }

        .hero-panel {
            flex: 1.2;
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #fff;
            padding: 40px;
            overflow: hidden;
        }

        /* Glassmorphism card inside hero */
        .hero-content {
            z-index: 2;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 480px;
            width: 100%;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 1;
        }

        .brand-mark {
            width: 120px;
            height: 120px;
            border-radius: 28px;
            background: #ffffff;
            display: grid;
            place-items: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 15px;
            margin-bottom: 25px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .brand-mark:hover {
            transform: translateY(-8px) scale(1.05);
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-title {
            margin: 0;
            font-size: clamp(2rem, 3.5vw, 2.8rem);
            font-weight: 900;
            letter-spacing: .05em;
            background: linear-gradient(to right, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            margin: 15px 0 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .form-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            z-index: 5;
            box-shadow: -20px 0 40px rgba(0,0,0,0.05);
        }

        .form-card {
            width: 100%;
            max-width: 440px;
        }

        .form-heading {
            margin-bottom: 35px;
        }

        .form-heading h2 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 900;
            color: #0f172a;
        }

        .form-heading p {
            margin: 10px 0 0;
            color: #64748b;
            line-height: 1.6;
            font-size: 1.05rem;
        }

        .alert {
            border: none;
            border-radius: 16px;
            padding: 16px 20px;
            font-weight: 600;
            margin-bottom: 25px;
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
            margin-bottom: 22px;
        }

        .form-label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            display: block;
            font-size: 0.95rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2rem;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            min-height: 60px;
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            background: #f8fafc;
            padding: 12px 20px 12px 52px;
            font-size: 1.05rem;
            font-weight: 500;
            color: #0f172a;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 5px rgba(79, 70, 229, 0.1);
            outline: 0;
        }

        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .submit-btn {
            width: 100%;
            margin-top: 15px;
            min-height: 60px;
            border: 0;
            border-radius: 18px;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: .02em;
            color: #fff;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            box-shadow: 0 10px 25px var(--primary-glow);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.5);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .footer-note {
            margin-top: 35px;
            color: #94a3b8;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 992px) {
            .page-shell {
                flex-direction: column;
            }
            .hero-panel {
                flex: none;
                padding: 60px 20px;
                border-radius: 0 0 40px 40px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .hero-content {
                padding: 40px 30px;
                box-shadow: none;
                background: transparent;
                border: none;
                backdrop-filter: none;
            }
            .form-panel {
                padding: 50px 20px;
                box-shadow: none;
            }
            .brand-mark {
                width: 90px;
                height: 90px;
                padding: 12px;
                margin-bottom: 20px;
            }
            .brand-title {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-panel {
                padding: 40px 15px;
                border-radius: 0 0 30px 30px;
            }
            .hero-content {
                padding: 20px 10px;
            }
            .form-panel {
                padding: 40px 15px;
            }
            .form-heading h2 {
                font-size: 1.8rem;
            }
            .form-control, .submit-btn {
                min-height: 55px;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <!-- Panel Kiri: Hero Banner -->
        <aside class="hero-panel">
            <div class="ambient-glow">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
                <div class="blob blob-3"></div>
            </div>
            <div class="hero-content">
                <div class="brand-mark">
                    <img src="img/<?= htmlspecialchars($logoFile); ?>" alt="Logo <?= htmlspecialchars($lembaga['nama_aplikasi']); ?>">
                </div>
                <h1 class="brand-title"><?= htmlspecialchars($lembaga['nama_aplikasi']); ?></h1>
                <p class="brand-subtitle">
                    <span style="font-size: 0.95rem; opacity: 0.9; font-weight: 500; display: block; margin-bottom: 6px;">Sistem Informasi Manajemen Akademik</span>
                    <span style="font-weight: 800; font-size: 1.25rem; display: block; letter-spacing: 0.03em; text-transform: uppercase; color: #fff;"><?= htmlspecialchars($lembaga['nmsekolah']); ?></span>
                </p>
            </div>
        </aside>

        <!-- Panel Kanan: Form Login -->
        <section class="form-panel">
            <div class="form-card">
                <div class="form-heading">
                    <h2>Selamat Datang</h2>
                    <p>Masukkan username dan password Anda untuk mengakses sistem.</p>
                </div>

                <?php if (isset($_GET['haruslogin'])): ?>
                    <div class="alert alert-danger">Silakan login terlebih dahulu untuk melanjutkan.</div>
                <?php elseif (isset($_GET['db_error'])): ?>
                    <div class="alert alert-danger">Database tidak tersambung. Jalankan server dengan PHP XAMPP, lalu coba login lagi.</div>
                <?php elseif (isset($_GET['gagallogin'])): ?>
                    <div class="alert alert-danger">Username atau password tidak sesuai.</div>
                <?php elseif (isset($_GET['google_error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($googleErrorMessages[$_GET['google_error']] ?? 'Login Gmail gagal.'); ?></div>
                <?php elseif (isset($_GET['logout'])): ?>
                    <div class="alert alert-success">Anda berhasil logout.</div>
                <?php endif; ?>

                <form method="post" action="login_action.php">
                    <input type="hidden" name="hak_akses" value="auto">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                    <input type="hidden" id="kode_sekolah" name="kode_sekolah" value="<?= htmlspecialchars($_GET['kode'] ?? 'DEFAULT'); ?>">

                    <div class="form-group">
                        <label class="form-label" for="username">Username / NIP / NIS</label>
                        <div class="input-wrap">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Ketikkan username Anda" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Ketikkan password Anda" required autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Masuk Sekarang</button>
                </form>

                <div class="footer-note">© <?= date('Y'); ?> <?= htmlspecialchars($lembaga['nama_aplikasi']); ?> - Hak Cipta Dilindungi</div>
            </div>
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
