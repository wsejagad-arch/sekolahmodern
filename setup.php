<?php
/**
 * Setup Script - Database Initialization & Admin Password Reset
 * SECURITY: This file auto-deletes after first run
 * Delete manually if needed: rm setup.php
 */

session_start();

// Security: Only allow one-time execution per session
if (isset($_SESSION['setup_completed'])) {
    die('Setup sudah pernah dijalankan. File ini akan dihapus otomatis.');
}

require_once __DIR__ . '/config/database.php';

$output = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup') {
    try {
        // Create admin table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS `admin` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `password` varchar(255) NOT NULL,
          `role` ENUM('superadmin', 'author') NOT NULL DEFAULT 'superadmin',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Update admin password (W@hyu123)
        $hash = '$2y$10$AjIJVQqB7kXjEIob77Wut.HzFGLmh5cK/hObFc9ihyNzj462F1ffi';
        $conn->query("UPDATE `admin` SET `password` = '$hash', `role` = 'superadmin' WHERE `username` = 'admin'");

        // If no admin user exists, insert one
        $checkAdmin = $conn->query("SELECT id FROM admin WHERE username='admin' LIMIT 1");
        if ($checkAdmin->num_rows === 0) {
            $conn->query("INSERT INTO `admin` (`username`, `password`, `role`) VALUES ('admin', '$hash', 'superadmin')");
            $output = '✅ Tabel admin dibuat dan admin user ditambahkan dengan password: w@wahyu123';
        } else {
            $output = '✅ Password admin berhasil diupdate ke: w@wahyu123';
        }

        $_SESSION['setup_completed'] = true;
        $success = true;

        // Self-delete this file
        if (file_exists(__FILE__)) {
            unlink(__FILE__);
        }
    } catch (Throwable $e) {
        $output = '❌ Error: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }
        .credentials {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .credentials strong {
            display: block;
            margin-top: 8px;
            color: #000;
        }
        .credentials code {
            background: #fff;
            padding: 3px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #764ba2;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .output {
            margin-top: 25px;
            padding: 15px;
            border-radius: 5px;
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            color: #155724;
            font-size: 14px;
            line-height: 1.6;
        }
        .output.error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .next-steps {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Setup Database</h1>
        <p class="subtitle">Inisialisasi database dan reset password admin</p>

        <?php if (!$success): ?>
            <div class="info">
                ℹ️ Skrip ini akan membuat tabel <strong>admin</strong> dan mengatur ulang password admin ke <strong>w@wahyu123</strong>.
            </div>

            <div class="credentials">
                <strong>✅ Login Credentials:</strong>
                Username: <code>admin</code><br>
                Password: <code>w@wahyu123</code><br>
                URL: <code>https://sman1sumber.sch.id/logsman1s</code>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="setup">
                <button type="submit">Setup Database Sekarang</button>
            </form>
        <?php else: ?>
            <div class="output">
                <?= $output ?>
            </div>

            <div class="next-steps">
                <strong>✅ Langkah Selanjutnya:</strong><br>
                1. Kunjungi: <strong>https://sman1sumber.sch.id/logsman1s</strong><br>
                2. Login dengan:<br>
                &nbsp;&nbsp;- Username: <code>admin</code><br>
                &nbsp;&nbsp;- Password: <code>w@wahyu123</code><br>
                3. File setup.php akan dihapus otomatis dalam beberapa saat.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
