<?php
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if(isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $legacyDefaultPasswords = ['admin123', 'Admin123'];
    if ($username === 'admin' && in_array($password, $legacyDefaultPasswords, true)) {
        $error = 'Password default lama tidak berlaku. Gunakan password baru: W@hyu123';
    } else {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if(password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SekolahKu</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--background) 0%, #e2e8f0 100%);
        }
        .login-box {
            background: var(--card-bg);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-box h2 {
            margin-bottom: 2rem;
            text-align: center;
            color: var(--text-main);
        }
        .btn-full {
            width: 100%;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Admin</h2>
        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
            </div>
            <button type="submit" class="btn btn-full">Masuk</button>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="../index.php" style="color: var(--text-muted); text-decoration: none;">&larr; Kembali ke Beranda</a>
            </div>
        </form>
    </div>
</body>
</html>
