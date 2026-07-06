<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"]) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    header("location: ../../index.php?haruslogin");
    exit;
}

require_once '../../koneksi.php';
require_once '../../functions.php';
$lembaga = data_lembaga(); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0ea5e9">
    <title>Media Sosial - SIMANIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #0ea5e9;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --radius: 20px;
            --bottom-h: 70px;
        }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            padding-bottom: calc(var(--bottom-h) + env(safe-area-inset-bottom) + 20px);
        }
        
        /* ── HEADER ── */
        .app-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 20px 20px 60px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .header-top {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 2;
        }
        .back-btn {
            color: #fff;
            font-size: 1.2rem;
            text-decoration: none;
            padding: 5px;
        }
        .page-title {
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* ── MAIN CONTENT ── */
        .main-wrap {
            padding: 0 20px;
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        
        .social-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .social-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--text);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .social-card:active {
            transform: scale(0.98);
        }

        .sc-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            flex-shrink: 0;
        }

        .sc-content {
            flex: 1;
        }
        .sc-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .sc-desc {
            font-size: 0.75rem;
            color: var(--muted);
        }

        .sc-arrow {
            color: #cbd5e1;
            font-size: 1.2rem;
        }

        /* Colors */
        .bg-web { background: #0ea5e9; box-shadow: 0 4px 10px rgba(14,165,233,0.3); }
        .bg-insta { background: linear-gradient(45deg, #f09433, #dc2743, #bc1888); box-shadow: 0 4px 10px rgba(220,39,67,0.3); }
        .bg-fb { background: #1877F2; box-shadow: 0 4px 10px rgba(24,119,242,0.3); }
        .bg-tiktok { background: #000000; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .bg-wa { background: #25D366; box-shadow: 0 4px 10px rgba(37,211,102,0.3); }
        .bg-email { background: #ea4335; box-shadow: 0 4px 10px rgba(234,67,53,0.3); }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--bottom-h) + env(safe-area-inset-bottom));
            background: var(--card);
            box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: flex-start;
            justify-content: space-around;
            padding-top: 12px;
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 100;
            border-radius: 24px 24px 0 0;
        }
        .bnav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: var(--muted);
            flex: 1;
        }
        .bnav-item i { font-size: 1.3rem; }
        .bnav-label { font-size: 0.65rem; font-weight: 600; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="app-header">
        <div class="header-top">
            <a href="siswa.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="page-title">Media Sosial</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-wrap">
        <div class="social-list">
            
            <a href="#" class="social-card">
                <div class="sc-icon bg-web"><i class="fas fa-globe"></i></div>
                <div class="sc-content">
                    <div class="sc-title">Website Resmi</div>
                    <div class="sc-desc">Kunjungi situs web sekolah</div>
                </div>
                <i class="fas fa-chevron-right sc-arrow"></i>
            </a>

            <a href="#" class="social-card">
                <div class="sc-icon bg-insta"><i class="fab fa-instagram"></i></div>
                <div class="sc-content">
                    <div class="sc-title">Instagram</div>
                    <div class="sc-desc">Ikuti pembaruan & kegiatan terbaru</div>
                </div>
                <i class="fas fa-chevron-right sc-arrow"></i>
            </a>

            <a href="#" class="social-card">
                <div class="sc-icon bg-fb"><i class="fab fa-facebook-f"></i></div>
                <div class="sc-content">
                    <div class="sc-title">Facebook</div>
                    <div class="sc-desc">Bergabung dengan komunitas kami</div>
                </div>
                <i class="fas fa-chevron-right sc-arrow"></i>
            </a>

            <a href="#" class="social-card">
                <div class="sc-icon bg-tiktok"><i class="fab fa-tiktok"></i></div>
                <div class="sc-content">
                    <div class="sc-title">TikTok</div>
                    <div class="sc-desc">Tonton video menarik & seru</div>
                </div>
                <i class="fas fa-chevron-right sc-arrow"></i>
            </a>

            <a href="https://wa.me/6281234567890" target="_blank" class="social-card">
                <div class="sc-icon bg-wa"><i class="fab fa-whatsapp"></i></div>
                <div class="sc-content">
                    <div class="sc-title">WhatsApp</div>
                    <div class="sc-desc">Chat langsung dengan admin</div>
                </div>
                <i class="fas fa-chevron-right sc-arrow"></i>
            </a>

            <a href="mailto:info@sekolah.sch.id" class="social-card">
                <div class="sc-icon bg-email"><i class="fas fa-envelope"></i></div>
                <div class="sc-content">
                    <div class="sc-title">Email</div>
                    <div class="sc-desc">Hubungi melalui surel</div>
                </div>
                <i class="fas fa-chevron-right sc-arrow"></i>
            </a>

        </div>
    </div>

    <!-- Bottom Nav -->
    <?php include 'siswa_footer.php'; ?>


</body>
</html>
