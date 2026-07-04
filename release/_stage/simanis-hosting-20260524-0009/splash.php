<?php
require_once __DIR__ . '/bootstrap.php';
$lembaga = data_lembaga();
$namaAplikasi = $lembaga['nama_aplikasi'] ?? 'SIMANIS';
$namaSekolah = $lembaga['nmsekolah'] ?? 'SIMANIS';
$brandLogo = file_exists(__DIR__ . '/img/logo dash.png') ? 'img/logo dash.png' : 'img/favicon.ico';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($namaAplikasi); ?></title>
    <meta http-equiv="refresh" content="4;url=login.php">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-1: #0f172a;
            --bg-2: #1d4ed8;
            --bg-3: #10b981;
            --card: rgba(255, 255, 255, .08);
            --text: #e5eefb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            overflow: hidden;
            background: radial-gradient(circle at top left, rgba(16, 185, 129, .24), transparent 28%), radial-gradient(circle at bottom right, rgba(29, 78, 216, .28), transparent 30%), linear-gradient(135deg, var(--bg-1) 0%, #111827 38%, #0b3b6e 100%);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
            background-size: 36px 36px;
            opacity: .35;
            pointer-events: none;
        }

        .splash-stage,
        .enter-stage {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .splash-card {
            width: min(92vw, 460px);
            text-align: center;
            background: var(--card);
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 28px;
            backdrop-filter: blur(18px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, .32);
            padding: 34px 28px 28px;
        }

        .brand-ring {
            width: 132px;
            height: 132px;
            margin: 0 auto 22px;
            border-radius: 36px;
            position: relative;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, rgba(255, 255, 255, .18), rgba(255, 255, 255, .04));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
            animation: float 3.8s ease-in-out infinite;
        }

        .brand-ring::before,
        .brand-ring::after {
            content: "";
            position: absolute;
            inset: -12px;
            border-radius: 42px;
            border: 1px solid rgba(255, 255, 255, .08);
            animation: pulse 2.6s ease-out infinite;
        }

        .brand-ring::after {
            inset: -26px;
            animation-delay: .5s;
        }

        .brand-logo {
            width: 92px;
            height: 92px;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, .25));
        }

        .title {
            margin: 0;
            font-size: clamp(2rem, 5vw, 2.7rem);
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: linear-gradient(135deg, #fff 0%, #dbeafe 40%, #86efac 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            margin: 10px 0 0;
            font-size: .98rem;
            line-height: 1.6;
            color: rgba(226, 232, 240, .88);
        }

        .loader-row {
            margin-top: 26px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .loader-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #86efac;
            box-shadow: 0 0 0 0 rgba(134, 239, 172, .65);
            animation: beat 1.2s infinite;
        }

        .loader-dot:nth-child(2) {
            animation-delay: .15s;
            background: #93c5fd;
        }

        .loader-dot:nth-child(3) {
            animation-delay: .3s;
            background: #fcd34d;
        }

        .loader-label {
            margin: 0;
            font-size: .8rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .78);
        }

        .enter-card {
            width: min(92vw, 520px);
            background: rgba(255, 255, 255, .96);
            color: #0f172a;
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 30px 90px rgba(0, 0, 0, .35);
            transform: translateY(14px) scale(.985);
            opacity: 0;
            pointer-events: none;
            transition: opacity .55s ease, transform .55s ease;
        }

        .enter-stage.ready .enter-card {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .login-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #0f766e;
            background: #ecfeff;
            border-radius: 999px;
            padding: 8px 12px;
        }

        .brand-line {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 18px;
        }

        .brand-badge {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1d4ed8, #10b981);
            display: grid;
            place-items: center;
            box-shadow: 0 16px 30px rgba(29, 78, 216, .24);
        }

        .brand-badge img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .brand-text h1 {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .brand-text p {
            margin: 3px 0 0;
            color: #475569;
            font-size: .92rem;
        }

        .skip-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 22px;
            padding: 11px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #1d4ed8, #10b981);
            color: white;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 14px 24px rgba(16, 185, 129, .18);
        }

        .hint {
            margin-top: 18px;
            color: #64748b;
            font-size: .86rem;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(.92);
                opacity: .7;
            }

            100% {
                transform: scale(1.1);
                opacity: 0;
            }
        }

        @keyframes beat {

            0%,
            100% {
                transform: translateY(0) scale(.88);
                opacity: .65;
            }

            50% {
                transform: translateY(-3px) scale(1.08);
                opacity: 1;
            }
        }

        @media (max-width: 576px) {

            .splash-card,
            .enter-card {
                padding: 24px 18px;
                border-radius: 24px;
            }

            .brand-ring {
                width: 112px;
                height: 112px;
                border-radius: 30px;
            }

            .brand-logo {
                width: 76px;
                height: 76px;
            }

            .brand-text h1 {
                font-size: 1.22rem;
            }
        }
    </style>
</head>

<body>
    <main class="splash-stage" id="splashStage">
        <section class="splash-card">
            <div class="brand-ring"><img src="<?= htmlspecialchars($brandLogo); ?>" alt="<?= htmlspecialchars($namaAplikasi); ?>" class="brand-logo"></div>
            <h1 class="title"><?= htmlspecialchars($namaAplikasi); ?></h1>
            <p class="subtitle">Sistem Informasi Manajemen Akademik <?= htmlspecialchars($namaSekolah); ?>.</p>
            <div class="loader-row" aria-label="Memuat aplikasi">
                <span class="loader-dot"></span><span class="loader-dot"></span><span class="loader-dot"></span>
                <p class="loader-label">Memuat</p>
            </div>
            <div class="hint">Arahkan ke halaman login dalam beberapa detik.</div>
            <noscript>
                <p class="hint"><a class="skip-btn" href="login.php">Masuk ke login</a></p>
            </noscript>
        </section>
    </main>

    <main class="enter-stage" id="enterStage" aria-hidden="true">
        <section class="enter-card">
            <span class="login-kicker"><i class="bi bi-shield-lock"></i> Login Tunggal</span>
            <div class="brand-line">
                <div class="brand-badge"><img src="<?= htmlspecialchars($brandLogo); ?>" alt="<?= htmlspecialchars($namaAplikasi); ?>"></div>
                <div class="brand-text">
                    <h1><?= htmlspecialchars($namaAplikasi); ?></h1>
                    <p>Satu pintu login untuk guru, siswa, dan admin</p>
                </div>
            </div>
            <a href="login.php" class="skip-btn"><i class="bi bi-arrow-right-circle"></i> Masuk ke login</a>
        </section>
    </main>

    <script>
        const splashStage = document.getElementById('splashStage');
        const enterStage = document.getElementById('enterStage');

        setTimeout(() => {
            splashStage.style.opacity = '0';
            splashStage.style.transform = 'scale(0.985)';
            splashStage.style.transition = 'opacity .45s ease, transform .45s ease';

            setTimeout(() => {
                splashStage.style.display = 'none';
                enterStage.classList.add('ready');
                enterStage.setAttribute('aria-hidden', 'false');
            }, 420);
        }, 1700);

        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3200);
    </script>
</body>

</html>
