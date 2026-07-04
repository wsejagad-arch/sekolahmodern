<?php 
include "functions.php";
$lembaga = data_lembaga();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>e-Jurnal <?= htmlspecialchars($lembaga['nmsekolah']); ?></title>
    <link rel="icon" href="img/<?= htmlspecialchars($lembaga['logo']); ?>" type="image/x-icon">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: url('img/foto.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
             width: 100%;
            max-width: 420px;
            padding: 2rem;
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.3); /* putih transparan 30% */
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .logo-img {
            width: 90px;
            height: auto;
        }
        .fade-alert {
            animation: fadeOut 1s ease-in-out 4s forwards;
        }
        @keyframes fadeOut {
            to { opacity: 0; height: 0; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <img src="img/<?= htmlspecialchars($lembaga['logo']); ?>" class="logo-img mb-2" alt="Logo">
        <h4 class="fw-bold text-dark">LOGIN e-Jurnal</h4>
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
        <div class="mb-3">
            <select class="form-select" id="hakAkses" name="hak_akses" required>
                <option value="" selected disabled>-- Login sebagai --</option>
                <option value="1">Admin</option>
                <option value="2">Guru</option>
                <option value="3">Siswa</option>
            </select>
        </div>
        <div class="mb-3">
            <input type="text" class="form-control" id="usernameField" name="username" placeholder="Masukkan Username ..." required autocomplete="off">
            <div class="form-text" id="usernameHint" style="display:none">Masukkan NIP Anda.</div>
        </div>
        <div class="mb-3" id="passwordGroup">
            <input type="password" class="form-control" id="passwordField" name="password" placeholder="Password" required autocomplete="off">
        </div>
        <div class="d-grid">
            <button type="submit" name="submit" class="btn btn-success btn-lg">Login</button>
        </div>
    </form>
    <hr>
    <p class="text-muted small text-center mb-0">
        &copy; <?= date("Y"); ?> <?= htmlspecialchars($lembaga['nmsekolah']); ?> - All Rights Reserved
    </p>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function(){
        const hak = document.getElementById('hakAkses');
        const passGroup = document.getElementById('passwordGroup');
        const passField = document.getElementById('passwordField');
        const userField = document.getElementById('usernameField');
        const userHint = document.getElementById('usernameHint');

        function updateForm() {
            const v = hak.value;
            if (v === '2') { // Guru: login hanya dengan NIP
                passGroup.style.display = 'none';
                        if (passField) {
                            passField.removeAttribute('required');
                            passField.value = '';
                            passField.setAttribute('disabled','disabled');
                        }
                userField.placeholder = 'Masukkan NIP ...';
                userHint.style.display = 'block';
            } else {
                passGroup.style.display = '';
                        if (passField) {
                            passField.removeAttribute('disabled');
                            passField.setAttribute('required','required');
                        }
                userField.placeholder = 'Masukkan Username ...';
                userHint.style.display = 'none';
            }
        }

        hak.addEventListener('change', updateForm);
        // Initialize on load in case of preselection
        updateForm();
    })();
    </script>

</body>
</html>

