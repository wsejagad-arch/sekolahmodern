<?php
/**
 * SIMANIS - Template Wrapper Dashboard Guru Profesional & Modern
 * File ini bertindak sebagai template pembungkus utama dengan sidebar persisten,
 * kemampuan collapse/expand, dan perpindahan halaman berbasis AJAX/konten dinamis tanpa reload.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses (Hanya Guru / Role 2)
if (!isset($_SESSION['username']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

// Simulasi Data Guru (Sesuaikan dengan koneksi database asli Anda jika diperlukan)
$namaGuru = $_SESSION['nama'] ?? 'Guru Pengajar';
$jabatan = $_SESSION['jabatan'] ?? 'Guru Mata Pelajaran';
$fotoProfile = !empty($_SESSION['foto']) ? "../../img/guru/" . $_SESSION['foto'] : "../../img/avatar.png";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - SIMANIS</title>
    <!-- Google Fonts & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3c58b9;
            --primary-hover: #2e4492;
            --bg-canvas: #ebf1f6;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-main);
            margin: 0;
            overflow-x: hidden;
        }

        /* LAYOUT CONTAINER */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* SIDEBAR STYLING */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            transition: width var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
            overflow-x: hidden;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        /* Logo Area */
        .sidebar-logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 24px;
            margin-bottom: 32px;
            white-space: nowrap;
        }
        .sidebar-logo-area i {
            font-size: 1.8rem;
            color: #a5b4fc;
        }
        .sidebar-brand-name {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            transition: opacity var(--transition-speed);
        }
        .sidebar.collapsed .sidebar-brand-name {
            opacity: 0;
            pointer-events: none;
            width: 0;
        }

        /* Sidebar Nav Links */
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 0 12px;
            flex: 1;
        }

        .nav-item-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            color: #cbd5e1;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nav-item-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-item-btn.active {
            background-color: #ffffff;
            color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .nav-item-btn span.label {
            transition: opacity var(--transition-speed);
        }
        .sidebar.collapsed .nav-item-btn span.label {
            opacity: 0;
            pointer-events: none;
            width: 0;
        }

        .nav-item-btn i {
            font-size: 1.3rem;
            min-width: 24px;
            text-align: center;
        }

        /* Sidebar Footer / User Profile & Toggle */
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .sidebar-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
        }
        .sidebar-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .sidebar-profile-info {
            display: flex;
            flex-direction: column;
            transition: opacity var(--transition-speed);
        }
        .sidebar-profile-info strong {
            font-size: 0.85rem;
            color: #ffffff;
        }
        .sidebar-profile-info span {
            font-size: 0.75rem;
            color: #a5b4fc;
        }
        .sidebar.collapsed .sidebar-profile-info {
            opacity: 0;
            pointer-events: none;
            width: 0;
        }

        .sidebar-toggle-btn {
            background: rgba(255, 255, 255, 0.08);
            border: none;
            color: #ffffff;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .sidebar-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* MAIN CONTENT AREA */
        .main-content-wrapper {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .sidebar.collapsed + .main-content-wrapper {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* TOPBAR */
        .topbar {
            background-color: var(--card-bg);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .topbar-title {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            color: var(--text-main);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* CONTENT CANVAS */
        .content-canvas {
            padding: 32px;
            flex-grow: 1;
        }

        /* VIEW CONTAINER (HIDDEN BY DEFAULT, SHOWN BY ROUTER) */
        .content-view {
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }
        .content-view.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content-wrapper {
                margin-left: 0 !important;
            }
            .sidebar-toggle-btn.desktop-only {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- PERSISTENT LEFT SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <!-- Logo -->
        <div class="sidebar-logo-area">
            <i class="bi bi-mortarboard-fill"></i>
            <span class="sidebar-brand-name">SIMANIS</span>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <button class="nav-item-btn active" data-target="view-overview">
                <i class="bi bi-grid-1x2-fill"></i>
                <span class="label">Beranda / Overview</span>
            </button>
            <button class="nav-item-btn" data-target="view-siswa">
                <i class="bi bi-people-fill"></i>
                <span class="label">Manajemen Siswa</span>
            </button>
            <button class="nav-item-btn" data-target="view-presensi">
                <i class="bi bi-fingerprint"></i>
                <span class="label">Monitoring Presensi</span>
            </button>
            <button class="nav-item-btn" data-target="view-rekap">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span class="label">Rekap Akademik</span>
            </button>
            <button class="nav-item-btn" data-target="view-pengaturan">
                <i class="bi bi-gear-fill"></i>
                <span class="label">Pengaturan Sistem</span>
            </button>
        </nav>

        <!-- Sidebar Footer / Profile -->
        <div class="sidebar-footer">
            <div class="sidebar-profile">
                <img src="<?= htmlspecialchars($fotoProfile) ?>" alt="Avatar">
                <div class="sidebar-profile-info">
                    <strong><?= htmlspecialchars($namaGuru) ?></strong>
                    <span><?= htmlspecialchars($jabatan) ?></span>
                </div>
            </div>
            
            <!-- Toggle Sidebar Collapse -->
            <button class="sidebar-toggle-btn desktop-only" id="sidebarToggle" title="Sembunyikan Sidebar">
                <i class="bi bi-chevron-left" id="toggleIcon"></i>
            </button>
        </div>
    </aside>

    <!-- MAIN MAIN WRAPPER -->
    <div class="main-content-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <!-- Mobile Sidebar Trigger -->
                <button class="btn btn-outline-secondary d-md-none" id="mobileSidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h2 class="topbar-title" id="pageTitle">Beranda / Overview</h2>
            </div>
            <div class="topbar-actions">
                <span class="badge bg-light text-secondary border py-2 px-3 rounded-pill"><?= date('d F Y') ?></span>
                <a href="../../logout.php" onclick="return confirm('Yakin ingin keluar?')" class="btn btn-sm btn-outline-danger rounded-pill">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>
        </header>

        <!-- Content Area -->
        <main class="content-canvas">
            
            <!-- ========================================== -->
            <!-- 1. VIEW: BERANDA / OVERVIEW -->
            <!-- ========================================== -->
            <div class="content-view active" id="view-overview">
                <!-- PASTE KONTEN OVERVIEW DI SINI -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h3 class="fw-bold mb-3">Selamat Datang di Beranda Guru</h3>
                    <p class="text-muted">Ini adalah area ringkasan aktivitas Anda hari ini. Silakan pasang widget, statistik mengajar, atau log cepat jadwal mengajar Anda di bagian ini.</p>
                    
                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="text-muted d-block small">Jurnal Terisi</span>
                                <strong class="fs-4 text-primary">0%</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="text-muted d-block small">Total Siswa</span>
                                <strong class="fs-4 text-primary">0 Siswa</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="text-muted d-block small">Presensi Hari Ini</span>
                                <strong class="fs-4 text-primary">0%</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /PASTE KONTEN OVERVIEW DI SINI -->
            </div>

            <!-- ========================================== -->
            <!-- 2. VIEW: MANAJEMEN DATA SISWA -->
            <!-- ========================================== -->
            <div class="content-view" id="view-siswa">
                <!-- PASTE KONTEN DATA SISWA DI SINI -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h3 class="fw-bold mb-3">Manajemen Data Siswa</h3>
                    <p class="text-muted">Kelola data peserta didik kelas binaan atau mata pelajaran yang Anda ampu.</p>
                    <div class="table-responsive mt-3">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>No Induk</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Masukkan skrip pemanggilan tabel siswa lama Anda ke bagian pembungkus ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /PASTE KONTEN DATA SISWA DI SINI -->
            </div>

            <!-- ========================================== -->
            <!-- 3. VIEW: MONITORING PRESENSI (RFID TAP LOG) -->
            <!-- ========================================== -->
            <div class="content-view" id="view-presensi">
                <!-- PASTE KONTEN PRESENSI DI SINI -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h3 class="fw-bold mb-3">Monitoring Tap Presensi RFID</h3>
                    <p class="text-muted">Pantau log masuk kehadiran siswa secara langsung dari perangkat pemindai RFID.</p>
                    <div class="alert alert-info">
                        <strong>Info:</strong> Tempelkan kode skrip penampil log / Ajax polling RFID presensi lama Anda di sini.
                    </div>
                </div>
                <!-- /PASTE KONTEN PRESENSI DI SINI -->
            </div>

            <!-- ========================================== -->
            <!-- 4. VIEW: REKAPITULASI AKADEMIK -->
            <!-- ========================================== -->
            <div class="content-view" id="view-rekap">
                <!-- PASTE KONTEN REKAPITULASI DI SINI -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h3 class="fw-bold mb-3">Rekapitulasi Akademik</h3>
                    <p class="text-muted">Melihat rekapitulasi nilai, tugas, dan rekap persentase absensi mengajar bulanan/semester.</p>
                </div>
                <!-- /PASTE KONTEN REKAPITULASI DI SINI -->
            </div>

            <!-- ========================================== -->
            <!-- 5. VIEW: PENGATURAN SISTEM -->
            <!-- ========================================== -->
            <div class="content-view" id="view-pengaturan">
                <!-- PASTE KONTEN PENGATURAN DI SINI -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h3 class="fw-bold mb-3">Pengaturan Sistem</h3>
                    <p class="text-muted">Konfigurasi pengaturan akun profil Anda, jadwal mengajar, dan preferensi tampilan.</p>
                </div>
                <!-- /PASTE KONTEN PENGATURAN DI SINI -->
            </div>

        </main>
    </div>
</div>

<!-- JavaScript untuk Pengendalian Layout (Sidebar Collapse & Dynamic View Router) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById("sidebar");
        const sidebarToggle = document.getElementById("sidebarToggle");
        const toggleIcon = document.getElementById("toggleIcon");
        const mobileSidebarToggle = document.getElementById("mobileSidebarToggle");
        const pageTitle = document.getElementById("pageTitle");

        // 1. DYNAMIC NAVIGATION ROUTER (AJAX-like Content Swap)
        const navButtons = document.querySelectorAll(".nav-item-btn");
        const views = document.querySelectorAll(".content-view");

        navButtons.forEach(btn => {
            btn.addEventListener("click", function() {
                // Hapus kelas aktif dari tombol navigasi sebelumnya
                navButtons.forEach(b => b.classList.remove("active"));
                // Tambahkan kelas aktif pada tombol yang diklik
                this.classList.add("active");

                // Sembunyikan semua kontainer view konten
                views.forEach(v => v.classList.remove("active"));
                
                // Tampilkan kontainer target view
                const targetId = this.getAttribute("data-target");
                const targetView = document.getElementById(targetId);
                if (targetView) {
                    targetView.classList.add("active");
                }

                // Perbarui judul halaman di Topbar
                pageTitle.innerText = this.querySelector(".label").innerText;

                // Tutup sidebar di mode ponsel saat menu diklik
                sidebar.classList.remove("mobile-open");
            });
        });

        // 2. DESKTOP COLLAPSE/EXPAND SIDEBAR
        // Ambil status preferensi dari localStorage
        const isCollapsed = localStorage.getItem("sidebar-collapsed") === "true";
        if (isCollapsed) {
            sidebar.classList.add("collapsed");
            toggleIcon.classList.replace("bi-chevron-left", "bi-chevron-right");
        }

        sidebarToggle.addEventListener("click", function() {
            sidebar.classList.toggle("collapsed");
            const collapsedState = sidebar.classList.contains("collapsed");
            
            // Simpan status ke localStorage
            localStorage.setItem("sidebar-collapsed", collapsedState);

            // Ganti ikon panah collapse
            if (collapsedState) {
                toggleIcon.classList.replace("bi-chevron-left", "bi-chevron-right");
            } else {
                toggleIcon.classList.replace("bi-chevron-right", "bi-chevron-left");
            }
        });

        // 3. MOBILE SIDEBAR TOGGLE
        mobileSidebarToggle.addEventListener("click", function() {
            sidebar.classList.toggle("mobile-open");
        });

        // Klik di luar sidebar pada mode mobile untuk menutup
        document.addEventListener("click", function(event) {
            if (!sidebar.contains(event.target) && !mobileSidebarToggle.contains(event.target)) {
                sidebar.classList.remove("mobile-open");
            }
        });
    });
</script>
</body>
</html>
