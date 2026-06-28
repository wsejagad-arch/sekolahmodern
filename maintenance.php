<?php
/**
 * maintenance.php
 * Halaman maintenance mode
 */

include "bootstrap.php";

// Jika admin dan sudah bypass, lanjutkan
if (is_admin() && isset($_SESSION['bypass_maintenance'])) {
    // Admin bypass, redirect ke home
    header('Location: home.php');
    exit;
}

// Jika maintenance mode aktif, tampilkan halaman ini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - <?php echo data_lembaga()['nmsekolah'] ?? 'Sistem'; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .maintenance-container {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .maintenance-icon {
            font-size: 5rem;
            color: #ffc107;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            ⚙️
        </div>
        <h1 class="h3 mb-3 font-weight-normal">Sistem Sedang Dalam Perbaikan</h1>
        <p class="text-muted">Maaf, sistem sedang dalam mode maintenance. Silakan kembali lagi nanti.</p>
        <?php if (is_admin()): ?>
            <form method="POST" action="">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="bypass" name="bypass" onchange="this.form.submit()">
                    <label class="form-check-label" for="bypass">
                        Saya admin, izinkan saya masuk untuk memperbaiki sistem
                    </label>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
if (isset($_POST['bypass']) && is_admin()) {
    $_SESSION['bypass_maintenance'] = true;
    header('Location: home.php');
    exit;
}
?>