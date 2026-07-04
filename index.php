<?php
/**
 * index.php - Entry point for SIMANIS application
 * Redirect all root requests to login.php for fast authentication flow
 */
header("Location: login.php", true, 301);
exit;
            width: 100%;
            /* Use dynamic viewport heights for mobile browsers */
            min-height: 100dvh;
            /* modern */
            min-height: 100svh;
            /* small viewport */
            min-height: 100vh;
            /* fallback */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 480px;
            padding: 2rem 2rem 1.25rem;
            border-radius: 18px;
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: cardIn .4s ease;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: .75rem;
        }

        .brand .logo-img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            border-radius: 14px;
            background: rgba(255, 255, 255, .6);
            padding: 6px;
        }

        .brand .title {
            margin: 0;
            font-weight: 800;
            letter-spacing: .5px;
            color: #0b2447;
            text-shadow: 0 1px 0 rgba(255, 255, 255, .65);
        }

        .brand .sub {
            margin: -2px 0 0;
            color: #334155;
            font-size: .8rem;
        }

        .login-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .75rem;
        }

        .login-heading h5 {
            margin: 0;
            font-weight: 800;
            letter-spacing: .4px;
            color: #0f172a;
        }

        .login-heading .badge {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
        }

        .fade-alert {
            animation: fadeOut 1s ease-in-out 4s forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                height: 0;
                padding: 0;
                margin: 0;
            }
        }

        .input-group-text {
            background: rgba(255, 255, 255, .7);
            border-color: var(--card-border);
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, .7);
            border-color: var(--card-border);
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
            border-color: #86b7fe;
        }

        .btn-login {
            background: linear-gradient(135deg, #16a34a, #0ea5e9);
            border: none;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .btn-login:hover {
            filter: brightness(1.05);
        }

        .footer-copy {
            color: #1f2937;
            text-shadow: 0 1px 0 rgba(255, 255, 255, .35);
        }

        .helper {
            font-size: .8rem;
            color: #475569;
        }

        /* Blink effect for TIM IT */
        .blink {
            animation: blinker 1s linear infinite;
        }

        @keyframes blinker {
            50% {
                opacity: 0;
            }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 1.2rem 1.2rem .8rem;
                max-width: 94vw;
            }

            .brand .title {
                font-size: 1rem;
            }
        }
    </style>
    <!-- Small inline style to safely hide the hint space when unused -->
</head>

<body>

    <div class="login-wrap">
        <div class="login-card">
            <div class="brand">
                <img src="img/<?= htmlspecialchars($lembaga['logo']); ?>" class="logo-img" alt="Logo">
                <div>
                    <h5 class="title mb-0"><?= htmlspecialchars($lembaga['nmsekolah']); ?></h5>
                    <div class="sub">e-Jurnal Sekolah | BY : TIM IT SMAN1S</div>
                </div>
            </div>
            <div class="login-heading">
                <h5>Login</h5>
                <span class="badge text-bg-light">Akses Sistem</span>
            </div>

            <?php
            if (isset($_GET["haruslogin"])) {
                echo '<div class="alert alert-danger fade-alert">Maaf! Anda belum login.</div>';
            } elseif (isset($_GET["gagallogin"])) {
                echo '<div class="alert alert-danger fade-alert">Maaf! Username atau password salah.</div>';
            } elseif (isset($_GET["logout"])) {
                echo '<div class="alert alert-success fade-alert">Berhasil logout.</div>';
            } elseif (isset($_GET["rubahpassword"])) {
                echo '<div class="alert alert-success fade-alert">Berhasil merubah password, silakan login kembali.</div>';
            }
            ?>

            <form method="post" action="login_action.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                        <select class="form-select" id="hakAkses" name="hak_akses" required>
                            <option value="" selected disabled>Login sebagai</option>
                            <option value="1">Admin</option>
                            <option value="2">Guru</option>
                            <option value="3">Siswa</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="usernameField" name="username" placeholder="Masukkan Username ..." required autocomplete="off" aria-label="Username">
                    </div>
                    <div class="form-text helper" id="usernameHint" style="display:none">Masukkan NIP Anda.</div>
                </div>
                <div class="mb-3" id="passwordGroup">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="passwordField" name="password" placeholder="Password" required autocomplete="off" aria-label="Password">
                        <button type="button" class="btn btn-outline-secondary" id="togglePass" tabindex="-1" aria-label="Tampilkan/Sembunyikan Password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" name="submit" class="btn btn-login btn-lg text-white">Masuk</button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="helper">Lupa password? <a href="forgot-password.php" class="link-dark">Reset</a></span>
                    <span class="helper">Versi: <?= date('Y'); ?></span>
                </div>
            </form>
            <hr class="mt-3 mb-2">
            <p class="small text-center mb-0 footer-copy"><span class="blink">TIM IT</span> SMANIS</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const hak = document.getElementById('hakAkses');
            const passGroup = document.getElementById('passwordGroup');
            const passField = document.getElementById('passwordField');
            const userField = document.getElementById('usernameField');
            const userHint = document.getElementById('usernameHint');
            const togglePass = document.getElementById('togglePass');

            function updateForm() {
                const v = hak.value;
                if (v === '2') { // Guru: login hanya dengan NIP
                    passGroup.style.display = 'none';
                    if (passField) {
                        passField.removeAttribute('required');
                        passField.value = '';
                        passField.setAttribute('disabled', 'disabled');
                    }
                    userField.placeholder = 'Masukkan NIP ...';
                    userHint.style.display = 'block';
                } else {
                    passGroup.style.display = '';
                    if (passField) {
                        passField.removeAttribute('disabled');
                        passField.setAttribute('required', 'required');
                    }
                    userField.placeholder = 'Masukkan Username ...';
                    userHint.style.display = 'none';
                }
            }

            // Show/Hide password
            if (togglePass && passField) {
                togglePass.addEventListener('click', function() {
                    const isText = passField.getAttribute('type') === 'text';
                    passField.setAttribute('type', isText ? 'password' : 'text');
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });
            }

            hak.addEventListener('change', updateForm);
            // Initialize on load in case of preselection
            updateForm();
        })();
    </script>

</body>

</html>