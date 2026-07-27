<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk'])) { 
    http_response_code(401);
    exit('Akses ditolak: Harus login terlebih dahulu'); 
}

if ($_SESSION['hak_akses'] != 2) { 
    http_response_code(403);
    exit('Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini'); 
}

include '../../koneksi.php';
include '../../functions.php';
// Disable caching for this page (standalone and modal requests)
include_once '../../nocache.php';

date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantMapelAmpu = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantSiswa = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantPenilaian = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_penilaian_item', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

// Standalone mode (full page layout) apabila dipanggil via GET langsung
$standalone = isset($_GET['getDetail']) && !isset($_POST['getDetail']);

if (isset($_POST['getDetail']) || isset($_GET['getDetail'])) {
    $raw = isset($_POST['getDetail']) ? $_POST['getDetail'] : $_GET['getDetail'];
    $idmapel = mysqli_real_escape_string($conn, $raw);
    

    // Query biasa untuk kompatibilitas hosting
    $nipguru_escaped = mysqli_real_escape_string($conn, $nipguru);
    $query = "SELECT * FROM tbl_mapel_ampu WHERE {$tenantMapelAmpu} AND id_mapel = '$idmapel' AND no_induk = '$nipguru_escaped' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $m = mysqli_fetch_assoc($result);
    
    if (!$m) { 
        echo '<div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>Mata pelajaran tidak ditemukan atau Anda tidak memiliki akses.
              </div>'; 
        exit; 
    }
    
    $kelas = $m['kelas'];
    $mapel = $m['nama_mapel'];
    $tanggal = date('Y-m-d');

    // Buat tabel penilaian dinamis jika belum ada
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_penilaian_item (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        id_mapel INT NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        mapel VARCHAR(100) NOT NULL,
        no_induk_guru VARCHAR(50) NOT NULL,
        kode_penilaian VARCHAR(20) NOT NULL,
        materi VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_item (tanggal, id_mapel, kode_penilaian)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai_item (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_item INT NOT NULL,
        no_induk_siswa VARCHAR(50) NOT NULL,
        nilai FLOAT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_nilai_item (id_item, no_induk_siswa),
        INDEX (id_item),
        FOREIGN KEY (id_item) REFERENCES tbl_penilaian_item(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ambil siswa - gunakan query biasa untuk kompatibilitas
    $kelas_escaped = mysqli_real_escape_string($conn, $kelas);
    $queryS = "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE {$tenantSiswa} AND (kelas = '$kelas_escaped' OR REPLACE(kelas, ' ', '') = REPLACE('$kelas_escaped', ' ', '')) AND (status = 'Aktif' OR status = 'aktif' OR status IS NULL OR status = '') ORDER BY nama_siswa ASC";
    $siswa = mysqli_query($conn, $queryS);

    // Ambil item penilaian untuk tanggal & mapel ini
    $tanggal_escaped = mysqli_real_escape_string($conn, $tanggal);
    $idmapel_escaped = mysqli_real_escape_string($conn, $idmapel);
    $queryI = "SELECT * FROM tbl_penilaian_item WHERE {$tenantPenilaian} AND tanggal = '$tanggal_escaped' AND id_mapel = '$idmapel_escaped' ORDER BY id ASC";
    $items = mysqli_query($conn, $queryI);
    
    $itemList = [];
    while ($it = mysqli_fetch_assoc($items)) { 
        $itemList[] = $it; 
    }
    $jumlahSiswa = $siswa ? mysqli_num_rows($siswa) : 0;
    $kelasDetailUrl = 'data-siswa?kelas=' . rawurlencode((string)$kelas);

    // Jika standalone, tampilkan minimal HTML header dan CSS agar halaman tampil rapi saat diakses langsung
    if ($standalone) {
        echo '<!DOCTYPE html>'
            .'<html lang="id">'
            .'<head>'
            .'<meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Input Nilai - '.htmlspecialchars($mapel).' ('.htmlspecialchars($kelas).')</title>'
            // CSS yang diperlukan
            .'<link rel="preconnect" href="https://fonts.googleapis.com">'
            .'<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            .'<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">'
            .'<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            .'<link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">'
            .'<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">'
            .'<link href="../../css/sb-admin-2.min.css" rel="stylesheet">'
            .'</head>'
            .'<body class="nilai-standalone-page">'
            .'<div class="background">'
            .'<div class="shape one"></div>'
            .'<div class="shape two"></div>'
            .'<div class="shape three"></div>'
            .'<div class="shape four"></div>'
            .'<div class="wave"></div>'
            .'<div class="dots"></div>'
            .'</div>'
            .'<main class="nilai-shell">';
    }

    ?>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --th-height: 64px;
            --nilai-primary: #2563eb;
            --nilai-primary-dark: #1d4ed8;
            --nilai-accent: #14b8a6;
            --nilai-bg: #f4f7fb;
            --nilai-card: #ffffff;
            --nilai-text: #0f172a;
            --nilai-muted: #64748b;
            --nilai-border: #e2e8f0;
        }

        .nilai-standalone-page {
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: var(--nilai-text);
            background:
                radial-gradient(circle at top right,
                    rgba(223,255,154,0.35) 0%,
                    transparent 35%),
                radial-gradient(circle at bottom left,
                    rgba(0,107,47,0.35) 0%,
                    transparent 35%),
                linear-gradient(
                    135deg,
                    rgba(11,122,50,0.75),
                    rgba(126,217,87,0.55),
                    rgba(217,255,159,0.45)
                );
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
        }

        /* Beautiful Green App Background Overlays */
        .background {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: -10;
            pointer-events: none;
            backdrop-filter: blur(4px);
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(10px);
        }

        .shape.one {
            width: 420px;
            height: 420px;
            background: rgba(47,168,79,0.35);
            top: -120px;
            left: -130px;
        }

        .shape.two {
            width: 520px;
            height: 520px;
            background: rgba(184,240,106,0.28);
            top: -180px;
            right: -160px;
        }

        .shape.three {
            width: 620px;
            height: 620px;
            background: rgba(13,111,45,0.38);
            bottom: -230px;
            right: -190px;
        }

        .shape.four {
            width: 460px;
            height: 460px;
            background: rgba(105,201,74,0.25);
            bottom: -120px;
            left: -160px;
        }

        .wave {
            position: absolute;
            width: 100%;
            height: 100%;
            background:
                repeating-radial-gradient(
                    ellipse at bottom right,
                    transparent 0 12px,
                    rgba(255,255,255,0.08) 13px 14px
                );
            opacity: 0.2;
        }

        .dots {
            position: absolute;
            width: 220px;
            height: 300px;
            background-image:
                radial-gradient(
                    rgba(255,255,255,0.18) 3px,
                    transparent 3px
                );
            background-size: 22px 22px;
            right: 30px;
            top: 90px;
        }

        .nilai-shell {
            width: min(1180px, calc(100% - 28px));
            margin: 0 auto;
            padding: 22px 0 132px;
        }

        .nilai-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .75);
            border-radius: 30px;
            padding: 20px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .92), rgba(37, 99, 235, .88)),
                radial-gradient(circle at top right, rgba(20, 184, 166, .4), transparent 36%);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .16);
        }

        .nilai-hero::after {
            content: "";
            position: absolute;
            right: -90px;
            top: -120px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .12);
        }

        .nilai-hero-top,
        .nilai-hero-main {
            position: relative;
            z-index: 1;
        }

        .nilai-hero-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .nilai-back-link,
        .nilai-refresh-btn {
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 700;
            backdrop-filter: blur(12px);
        }

        .nilai-back-link:hover,
        .nilai-refresh-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, .2);
        }

        .nilai-hero-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 170px;
            gap: 18px;
            align-items: end;
        }

        .nilai-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .nilai-title {
            margin: 8px 0 8px;
            color: #fff;
            font-size: clamp(28px, 4vw, 44px);
            line-height: 1.05;
            font-weight: 800;
        }

        .nilai-subtitle {
            max-width: 640px;
            margin: 0 0 16px;
            color: rgba(255, 255, 255, .78);
            font-size: 14px;
        }

        .nilai-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .nilai-summary-card {
            border-radius: 24px;
            padding: 18px;
            text-align: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .2);
            backdrop-filter: blur(14px);
        }

        .nilai-summary-card strong {
            display: block;
            color: #fff;
            font-size: 34px;
            line-height: 1;
            font-weight: 800;
        }

        .nilai-summary-card span,
        .nilai-summary-card small {
            display: block;
            color: rgba(255, 255, 255, .8);
        }

        .score-card {
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: 24px;
            overflow: hidden;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 18px 44px rgba(15, 23, 42, .08);
        }

        .score-card-header {
            border-bottom: 1px solid var(--nilai-border);
            background: linear-gradient(180deg, #ffffff, #f8fafc);
        }

        .score-card-header h6 {
            color: var(--nilai-text);
            font-weight: 800;
        }
        
        .badge-soft { 
            background: rgba(255, 255, 255, .16); 
            color: #fff; 
            border: 1px solid rgba(255, 255, 255, .22); 
            font-weight: 700;
            padding: 0.5em 0.8em;
            border-radius: 999px;
        }
        
        .badge-soft-secondary { 
            background: rgba(255, 255, 255, .12); 
            color: rgba(255, 255, 255, .88); 
            border: 1px solid rgba(255, 255, 255, .18); 
            font-weight: 700;
            padding: 0.5em 0.8em;
            border-radius: 999px;
        }
        
        .sticky-actions { 
            position: sticky; 
            bottom: 0; 
            z-index: 1030; 
            background: rgba(255, 255, 255, .96); 
            border-top: 1px solid var(--nilai-border); 
            padding: 1rem; 
            text-align: right; 
            box-shadow: 0 -12px 32px rgba(15, 23, 42, .08);
            backdrop-filter: blur(16px);
        }

        .nilai-standalone-page .sticky-actions {
            bottom: 92px;
            border-radius: 18px 18px 0 0;
        }
        
        .table thead th { 
            position: sticky; 
            top: 0; 
            background: #f8fafc; 
            z-index: 5; 
            vertical-align: bottom;
            box-shadow: 0 2px 2px -1px rgba(15, 23, 42, .12);
            height: var(--th-height);
            padding-bottom: .5rem;
            color: #334155;
            font-weight: 800;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.04);
        }
        
        .form-control,
        .form-select {
            border-color: var(--nilai-border);
            border-radius: 14px;
            min-height: 44px;
            color: var(--nilai-text);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--nilai-primary);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.13);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--nilai-primary), var(--nilai-primary-dark));
            border-color: var(--nilai-primary);
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .22);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            border-color: #1d4ed8;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .input-nilai {
            width: 76px;
            text-align: center;
            font-weight: 700;
            transition: all 0.2s;
            border-radius: 12px;
        }
        
        .input-nilai:focus {
            width: 92px;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }
        
        .header-penilaian {
            min-width: 120px;
            text-align: center;
            cursor: help;
            padding: 0.5rem 0.25rem;
        }
        .header-penilaian .fw-semibold { 
            line-height: 1; 
            margin-bottom: 0.25rem;
        }
        .header-penilaian .materi-tooltip {
            font-size: 0.8rem;
            opacity: 0.9;
            /* multi-line clamp for neat header */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
            margin: 0 auto;
        }
        .header-rata {
            text-align: center;
            padding: 0.5rem 0.25rem;
            min-width: 120px;
        }

        .nilai-empty-state {
            border: 0;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(20, 184, 166, .12), rgba(37, 99, 235, .08));
            color: #334155;
            padding: 18px;
        }

        .score-table {
            min-width: 720px;
        }

        .score-table td {
            padding: .9rem .75rem;
            vertical-align: middle;
        }

        .score-table tbody td:first-child {
            color: var(--nilai-text);
            font-weight: 700;
        }

        .bottom-nav-wrap {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            padding: 12px 16px 20px;
            pointer-events: none;
        }

        .bottom-nav {
            max-width: 440px;
            margin: 0 auto;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(20px);
            border-radius: 35px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -10px 40px rgba(15,23,42,.12);
            border: 1px solid rgba(255,255,255,.65);
            pointer-events: auto;
            font-family: 'Poppins', sans-serif;
        }

        .bottom-nav .nav-link {
            text-decoration: none;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        .bottom-nav .nav-link i {
            font-size: 20px;
        }

        .bottom-nav .nav-link.active {
            color: var(--nilai-primary);
        }

        .bottom-nav .nav-center {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, #14b8a6, var(--nilai-primary));
            margin-top: -45px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 34px;
            box-shadow: 0 10px 25px rgba(37,99,235,.35);
            border: 5px solid #f8fafc;
            text-decoration: none;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        @media (max-width: 768px) {
            .nilai-shell {
                width: min(100% - 22px, 1180px);
                padding-top: 12px;
            }

            .nilai-hero {
                border-radius: 24px;
                padding: 16px;
            }

            .nilai-hero-top {
                align-items: stretch;
            }

            .nilai-hero-main {
                grid-template-columns: 1fr;
            }

            .nilai-summary-card {
                text-align: left;
            }

            .nilai-refresh-btn {
                justify-content: center;
            }

            .table-responsive {
                font-size: 0.875rem;
            }
            
            .input-nilai {
                width: 60px;
                padding: 0.25rem;
            }
            
            .sticky-actions {
                padding: 0.75rem;
            }
            
            .sticky-actions .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <section class="nilai-hero mb-4">
        <div class="nilai-hero-top">
            <?php if ($standalone) { ?>
                <a href="../../home.php" class="nilai-back-link"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
            <?php } else { ?>
                <span class="nilai-eyebrow"><i class="fas fa-chart-line"></i> Penilaian Harian</span>
            <?php } ?>
            <button type="button" class="nilai-refresh-btn" id="btnRefresh">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="nilai-hero-main">
            <div>
                <span class="nilai-eyebrow"><i class="fas fa-chart-line"></i> Penilaian Harian</span>
                <h1 class="nilai-title">Input Nilai Siswa</h1>
                <p class="nilai-subtitle">Kelola kolom penilaian, isi skor siswa, dan pantau rata-rata kelas dalam satu tampilan yang lebih ringan dibaca.</p>
                <div class="nilai-badges">
                    <span class="badge badge-soft">
                        <i class="fas fa-book me-1"></i> <?= htmlspecialchars($mapel); ?>
                    </span>
                    <span class="badge badge-soft-secondary">
                        <i class="fas fa-users me-1"></i> <?= htmlspecialchars($kelas); ?>
                    </span>
                    <span class="badge badge-soft-secondary">
                        <i class="fas fa-calendar-day me-1"></i> <?= htmlspecialchars(tgl_indo($tanggal)); ?>
                    </span>
                </div>
            </div>
            <div class="nilai-summary-card">
                <strong><?= (int)$jumlahSiswa; ?></strong>
                <span>Siswa aktif</span>
                <small><?= count($itemList); ?> kolom penilaian</small>
            </div>
        </div>
    </section>

    <!-- Form tambah kolom penilaian -->
    <section class="card score-card add-score-card mb-4">
        <div class="card-header score-card-header py-3">
            <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Kolom Penilaian</h6>
        </div>
        <div class="card-body">
            <form class="row g-3 align-items-end" method="post" action="buat_item_nilai" id="formBuatItem">
                <input type="hidden" name="idmapel" value="<?= htmlspecialchars($idmapel); ?>"/>
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas); ?>"/>
                <input type="hidden" name="mapel" value="<?= htmlspecialchars($mapel); ?>"/>
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>"/>
                
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Jenis Penilaian</label>
                    <select name="kode_penilaian" class="form-select" required>
                        <option value="" disabled selected>Pilih Jenis...</option>
                        <option value="UH1">UH1 (Ulangan Harian 1)</option>
                        <option value="UH2">UH2 (Ulangan Harian 2)</option>
                        <option value="UH3">UH3 (Ulangan Harian 3)</option>
                        <option value="UH4">UH4 (Ulangan Harian 4)</option>
                        <option value="UH5">UH5 (Ulangan Harian 5)</option>
                        <option value="ASAS">ASAS (Asesmen Sumatif Akhir Semester)</option>
                        <option value="ASAT">ASAT (Asesmen Sumatif Akhir Tahun)</option>
                        <option value="TUGAS">TUGAS</option>
                        <option value="PROYEK">PROYEK</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Materi Penilaian</label>
                    <input type="text" name="materi" class="form-control" 
                           placeholder="Contoh: Persamaan Linear, Trigonometri, dll" required />
                </div>
                
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-1"></i> Tambah Kolom
                    </button>
                </div>
            </form>
        </div>
    </section>

    <?php if (count($itemList) === 0) { ?>
        <div class="alert alert-info nilai-empty-state d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle me-2 fa-lg"></i>
            <div>
                <h6 class="alert-heading mb-1">Belum ada penilaian</h6>
                <p class="mb-0">Tambahkan kolom penilaian untuk pertemuan hari ini menggunakan form di atas.</p>
            </div>
        </div>
    <?php } else { ?>
    <section class="card score-card score-table-card">
        <div class="card-header score-card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-table me-2"></i>Daftar Nilai Siswa</h6>
            <span class="badge bg-primary"><?= count($itemList); ?> Jenis Penilaian</span>
        </div>
        
        <div class="card-body p-0">
            <form method="post" action="simpan_nilai_dinamis" id="formNilaiDinamis">
                <input type="hidden" name="idmapel" value="<?= htmlspecialchars($idmapel); ?>"/>
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>"/>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0 score-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 250px; min-width: 200px;">Nama Siswa</th>
                                <?php foreach ($itemList as $it) { ?>
                                    <th class="text-center header-penilaian" title="<?= htmlspecialchars($it['materi']); ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($it['kode_penilaian']); ?></div>
                                        <div class="text-muted small materi-tooltip">
                                            <?= htmlspecialchars($it['materi']); ?>
                                        </div>
                                    </th>
                                <?php } ?>
                                <th class="text-center fw-semibold header-rata" style="min-width: 120px;">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Map nilai existing: [id_item][no_induk] => nilai
                                $nilaiMap = [];
                                if (count($itemList) > 0) {
                                    $ids = array_map(function($x){ return (int)$x['id']; }, $itemList);
                                    $idStr = implode(',', $ids);
                                    
                                    $qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_item IN (".$idStr.")");
                                    while ($nv = mysqli_fetch_assoc($qNil)) {
                                        $nilaiMap[$nv['id_item']][$nv['no_induk_siswa']] = $nv['nilai'];
                                    }
                                }
                                
                                // Reset pointer untuk iterasi ulang
                                mysqli_data_seek($siswa, 0);
                            ?>
                            <?php while($s = mysqli_fetch_assoc($siswa)) { 
                                $ni = $s['no_induk']; 
                                $totalNilai = 0;
                                $jumlahNilai = 0;
                            ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($s['nama_siswa']); ?></td>
                                    <?php foreach ($itemList as $it) { 
                                        $val = isset($nilaiMap[$it['id']][$ni]) ? $nilaiMap[$it['id']][$ni] : '';
                                        
                                        // Hitung untuk rata-rata
                                        if (is_numeric($val) && $val !== '') {
                                            $totalNilai += (float)$val;
                                            $jumlahNilai++;
                                        }
                                    ?>
                                        <td class="text-center" style="width: 120px; min-width: 120px">
                                            <input type="number" min="0" max="100" step="0.01" 
                                                   class="form-control form-control-sm input-nilai" 
                                                   name="nilai[<?= (int)$it['id']; ?>][<?= htmlspecialchars($ni); ?>]" 
                                                   value="<?= htmlspecialchars($val); ?>"
                                                   data-item="<?= (int)$it['id']; ?>"
                                                   data-siswa="<?= htmlspecialchars($ni); ?>">
                                        </td>
                                    <?php } ?>
                                    
                                    <td class="text-center fw-bold">
                                        <?php
                                            $rataRata = $jumlahNilai > 0 ? number_format($totalNilai / $jumlahNilai, 2) : '-';
                                            echo $rataRata;
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th class="text-end">Rata-rata Kelas:</th>
                                <?php
                                foreach ($itemList as $it) {
                                    $iid = (int)$it['id'];
                                    $sum = 0; $cnt = 0;
                                    if (!empty($nilaiMap[$iid])) {
                                        foreach ($nilaiMap[$iid] as $nv) {
                                            if ($nv !== '' && $nv !== null && is_numeric($nv)) { 
                                                $sum += (float)$nv; 
                                                $cnt++; 
                                            }
                                        }
                                    }
                                    $avg = $cnt > 0 ? number_format($sum / $cnt, 2) : '-';
                                    echo '<th class="text-center fw-semibold">'.$avg.'</th>';
                                }
                                ?>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="sticky-actions">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small" id="saveStatus">
                            <i class="fas fa-info-circle me-1"></i> Pastikan untuk menyimpan perubahan
                        </div>
                        <div>
                            <?php if (!$standalone) { ?>
                                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Tutup
                                </button>
                            <?php } ?>
                            <button type="submit" class="btn btn-primary" id="btnSimpan">
                                <i class="fas fa-save me-1"></i> Simpan Nilai
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <?php } ?>
    <?php if ($standalone) { ?>
    <!-- Library JS untuk mode standalone -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>
    <?php } ?>
    
    <script>
        (function(){
            // Tampilkan loading overlay
            function showLoading() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            }
            
            // Sembunyikan loading overlay
            function hideLoading() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
            
            // Fungsi untuk refresh data
            document.getElementById('btnRefresh')?.addEventListener('click', function() {
                showLoading();
                const idmapel = '<?= $idmapel; ?>';
                
                if (<?= $standalone ? 'true' : 'false' ?>) {
                    window.location.search = '?getDetail=' + encodeURIComponent(idmapel);
                } else {
                    $.post('inputnilai?ts=' + Date.now(), { getDetail: idmapel }, function(html){
                        $('.modal-nilai-body').html(html);
                        hideLoading();
                    }).fail(function(){
                        hideLoading();
                        alert('Gagal memuat ulang data');
                    });
                }
            });
            
            // Ajax submit untuk simpan nilai dinamis
            const formNilai = document.getElementById('formNilaiDinamis');
            if (formNilai) {
                formNilai.addEventListener('submit', function(ev){
                    ev.preventDefault();
                    const btn = document.getElementById('btnSimpan');
                    const saveStatus = document.getElementById('saveStatus');
                    
                    if (btn) { 
                        btn.disabled = true; 
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...'; 
                    }
                    
                    saveStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan perubahan...';
                    saveStatus.className = 'text-info small';
                    
                    showLoading();
                    
                    // Kirim data via AJAX
                    const formData = new FormData(formNilai);
                    
                    // Add cache buster to action URL
                    const actionUrl = formNilai.action + (formNilai.action.includes('?') ? '&' : '?') + 'ts=' + Date.now();
                    fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Berhasil disimpan
                        saveStatus.innerHTML = '<i class="fas fa-check-circle me-1"></i> Perubahan berhasil disimpan';
                        saveStatus.className = 'text-success small';
                        
                        if (<?= $standalone ? 'true' : 'false' ?>) {
                            // Jika standalone, reload halaman
                            window.location.reload();
                        } else {
                            // Jika modal, tutup modal setelah simpan
                            try {
                                const modalEl = document.getElementById('modalNilai');
                                if (modalEl) {
                                    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                                        const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                        instance.hide();
                                    } else if (window.$ && typeof $(modalEl).modal === 'function') {
                                        // Fallback untuk Bootstrap 4
                                        $(modalEl).modal('hide');
                                    }
                                }
                            } catch (e) {
                                console.warn('Gagal menutup modal secara programatik:', e);
                            } finally {
                                hideLoading();
                            }
                        }
                    })
                    .catch(error => {
                        // Gagal menyimpan
                        if (btn) { 
                            btn.disabled = false; 
                            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Nilai'; 
                        }
                        
                        saveStatus.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Gagal menyimpan perubahan';
                        saveStatus.className = 'text-danger small';
                        
                        hideLoading();
                        alert('Terjadi kesalahan saat menyimpan data: ' + error);
                    });
                });
            }

            // Ajax submit untuk buat item penilaian
            const formItem = document.getElementById('formBuatItem');
            if (formItem) {
                formItem.addEventListener('submit', function(ev){
                    ev.preventDefault();
                    const btn = formItem.querySelector('button[type="submit"]');
                    
                    if (btn) { 
                        btn.disabled = true; 
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...'; 
                    }
                    
                    showLoading();
                    
                    // Kirim data via AJAX
                    const formData = new FormData(formItem);
                    
                    // Add cache buster to form action
                    const postUrl = formItem.action + (formItem.action.includes('?') ? '&' : '?') + 'ts=' + Date.now();
                    fetch(postUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Berhasil dibuat
                        const idmapel = '<?= $idmapel; ?>';
                        
                        if (<?= $standalone ? 'true' : 'false' ?>) {
                            window.location.search = '?getDetail=' + encodeURIComponent(idmapel);
                        } else {
                            $.post('inputnilai?ts=' + Date.now(), { getDetail: idmapel }, function(html){
                                $('.modal-nilai-body').html(html);
                                hideLoading();
                            });
                        }
                    })
                    .catch(error => {
                        // Gagal membuat
                        if (btn) { 
                            btn.disabled = false; 
                            btn.innerHTML = '<i class="fas fa-plus me-1"></i> Tambah Kolom'; 
                        }
                        
                        hideLoading();
                        alert('Gagal menambahkan kolom penilaian: ' + error);
                    });
                });
            }

            // Auto-save ketika input kehilangan fokus (opsional)
            document.querySelectorAll('.input-nilai').forEach(input => {
                input.addEventListener('blur', function() {
                    const saveStatus = document.getElementById('saveStatus');
                    saveStatus.innerHTML = '<i class="fas fa-pencil-alt me-1"></i> Perubahan belum disimpan';
                    saveStatus.className = 'text-warning small';
                });
            });
        })();
    </script>
    <?php if ($standalone) { ?>
    </main>

    <?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
    </html>
    <?php } ?>
    <?php
}
