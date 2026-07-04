<?php
include "functions.php";
$lembaga = data_lembaga();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin Siswa - SIMANIS <?= htmlspecialchars($lembaga['nmsekolah']); ?></title>
    <link rel="icon" href="img/<?= htmlspecialchars($lembaga['logo']); ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 0;
            max-width: 1000px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            background: white;
            animation: slideInUp 0.6s ease;
        }

        .login-left {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -50px;
            right: -50px;
        }

        .login-left::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -30px;
            left: -30px;
        }

        .left-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .left-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        .left-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .left-subtitle {
            font-size: 0.95rem;
            opacity: 0.95;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .left-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            backdrop-filter: blur(10px);
            margin-top: 20px;
        }

        .login-right {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .login-header-logo img {
            width: 85%;
            height: 85%;
            object-fit: contain;
        }

        .login-header-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .login-header-subtitle {
            color: #718096;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            color: #1a202c;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            font-size: 0.95rem;
        }

        .form-control {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f7fafc;
        }

        .form-control:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            background: white;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            pointer-events: none;
        }

        .input-icon input {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            padding: 5px 10px;
            font-size: 1.2rem;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }

        .forgot-password a {
            color: #ff6b6b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #ee5a6f;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #ff6b6b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #ee5a6f;
            transform: translateX(-5px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
            animation: slideIn 0.4s ease;
        }

        .alert-danger {
            background: #fee;
            color: #c53030;
            padding: 12px 16px;
        }

        .alert-success {
            background: #efe;
            color: #22543d;
            padding: 12px 16px;
        }

        .footer-text {
            text-align: center;
            color: #a0aec0;
            font-size: 0.85rem;
            margin-top: 30px;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-left {
                padding: 40px 30px;
                order: 2;
                border-radius: 0;
            }

            .login-right {
                padding: 40px 30px;
                order: 1;
            }

            .left-icon {
                font-size: 3rem;
            }

            .left-title {
                font-size: 1.5rem;
            }

            .login-header-title {
                font-size: 1.5rem;
            }

            .login-header {
                margin-bottom: 30px;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                border-radius: 15px;
            }

            .login-right,
            .login-left {
                padding: 30px 20px;
            }

            .login-header-title {
                font-size: 1.3rem;
            }

            .left-title {
                font-size: 1.3rem;
            }

            .form-control {
                font-size: 16px;
            }

            .submit-btn {
                padding: 11px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-wrapper">
            <!-- Left Section -->
            <div class="login-left">
                <div class="left-content">
                    <div class="left-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h2 class="left-title">Admin Siswa</h2>
                    <p class="left-subtitle">
                        Kelola data siswa, presensi, pelanggaran, dan informasi akademik dengan sistem yang aman dan terpercaya
                    </p>
                    <span class="left-badge">
                        <i class="bi bi-check-circle"></i> Akses Terbatas
                    </span>
                </div>
            </div>

            <!-- Right Section -->
            <div class="login-right">
                <a href="login-pilihan.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>

                <div class="login-header">
                    <div class="login-header-logo">
                        <img src="img/<?= htmlspecialchars($lembaga['logo']); ?>" alt="Logo">
                    </div>
                    <h1 class="login-header-title">Admin Siswa</h1>
                    <p class="login-header-subtitle">Sistem Informasi Manajemen Akademik SMA Negeri 1 Sumber</p>
                </div>

                <?php
                if (isset($_GET["haruslogin"])) {
                    echo '<div class="alert alert-danger">Maaf! Anda belum login.</div>';
                } elseif (isset($_GET["gagallogin"])) {
                    echo '<div class="alert alert-danger">Maaf! Username atau password salah.</div>';
                } elseif (isset($_GET["logout"])) {
                    echo '<div class="alert alert-success">Berhasil logout.</div>';
                }
                ?>

                <form method="post" action="login_action.php">
                    <input type="hidden" name="hak_akses" value="1">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-icon">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autocomplete="off">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-icon" style="position: relative;">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="off">
                            <button type="button" class="toggle-password" id="togglePass" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="forgot-password.php">Lupa password?</a>
                    </div>

                    <button type="submit" name="submit" class="submit-btn">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>

                    <div class="footer-text">
                        © <?= date('Y'); ?> SIMANIS | <strong>TIM IT</strong>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        const togglePass = document.getElementById('togglePass');
        const passwordField = document.getElementById('password');

        if (togglePass && passwordField) {
            togglePass.addEventListener('click', function(e) {
                e.preventDefault();
                const isText = passwordField.getAttribute('type') === 'text';
                passwordField.setAttribute('type', isText ? 'password' : 'text');
                const icon = this.querySelector('i');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        }
    </script>

</body>

</html>