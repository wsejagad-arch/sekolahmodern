<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
        }
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            backdrop-filter: blur(10px);
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: #dc3545;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }
        .icon-container {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon-container">
            <i class="fas fa-ban"></i>
        </div>
        <div class="error-code">403</div>
        <div class="error-title">Akses Ditolak</div>
        <div class="error-message">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            <br>Silakan hubungi administrator sistem untuk bantuan.
        </div>
        <a href="javascript:history.back()" class="btn-home">
            <i class="fas fa-arrow-left"></i>
            Kembali
        </a>
        <a href="index.php" class="btn-home ml-2">
            <i class="fas fa-home"></i>
            Halaman Utama
        </a>
    </div>
    
    <script>
        // Auto redirect after 10 seconds if no interaction
        setTimeout(function() {
            if (document.visibilityState === 'visible') {
                window.location.href = 'index.php';
            }
        }, 10000);
    </script>
</body>
</html>