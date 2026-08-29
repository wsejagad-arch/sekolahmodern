<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_name('sekolah_modern_admin');
session_start();

// Mencegah akses langsung ke file ini jika bukan lewat URL rahasia
if (strpos($_SERVER['REQUEST_URI'], 'logsman1s') === false) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Auto-migrate database (tambah kolom role jika belum ada)
try {
    $conn->query("ALTER TABLE admin ADD COLUMN role ENUM('superadmin', 'author') NOT NULL DEFAULT 'superadmin' AFTER password");
} catch (Throwable $e) {
    // Abaikan error duplicate column
}

// Redirect jika sudah login
if(isset($_SESSION['admin_logged_in'])) {
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
        header('Location: /admin/index.php');
    } else {
        header('Location: /admin/posts.php');
    }
    exit;
}

$error = '';

// Ambil setting untuk judul
$setRes = $conn->query("SELECT site_name FROM settings WHERE id=1");
$setting = $setRes ? $setRes->fetch_assoc() : null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi!';
    } else {
        $legacyDefaultPasswords = ['admin123', 'Admin123'];
        $validAdminPasswords = ['W@hyu123', 'w@wahyu123', 'W@wahyu123'];

        if ($username === 'admin' && in_array($password, $legacyDefaultPasswords, true)) {
            $error = 'Password yang digunakan tidak valid. Silakan gunakan kredensial terbaru.';
        } else {
            $effectivePassword = $password;
            if ($username === 'admin' && in_array($password, $validAdminPasswords, true)) {
                $effectivePassword = 'W@hyu123';
            }

            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                if(password_verify($effectivePassword, $admin['password'] ?? '')) {
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'superadmin';
                    
                    if ($_SESSION['admin_role'] === 'superadmin') {
                        header('Location: /admin/index.php');
                    } else {
                        header('Location: /admin/posts.php');
                    }
                    exit;
                } else {
                    $error = 'Password salah!';
                }
            } else {
                $error = 'Username tidak ditemukan!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?= htmlspecialchars($setting['site_name'] ?? 'Sekolah Modern') ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --login-primary: #2563eb;
            --login-bg: #f1f5f9;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #dbeafe 0%, #f1f5f9 50%, #e0e7ff 100%);
            margin: 0;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--login-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: var(--login-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            border: 1px solid #fee2e2;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--login-primary);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>Admin Panel</h2>
            <p>Silakan masuk untuk mengelola konten</p>
        </div>

        <?php if($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="admin" autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Masuk Sekarang</button>
            
            <a href="../index.php" class="back-link">&larr; Kembali ke Beranda</a>
        </form>
    </div>
</body>
</html>
