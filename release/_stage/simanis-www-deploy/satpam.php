<?php
require_once 'bootstrap.php';

// Check access
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != '4') {
    // If not logged in as satpam, redirect
    header("Location: login.php?haruslogin");
    exit;
}

$satpam_name = $_SESSION['nama'] ?? 'Satpam SMAN 1';

// Handle Catat Pelanggaran Form Submission
$msg = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'catat_pelanggaran') {
    $no_induk = mysqli_real_escape_string($conn, $_POST['no_induk']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal_pelanggaran']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_pelanggaran']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_pelanggaran']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi_pelanggaran']);
    $tindakan = mysqli_real_escape_string($conn, $_POST['tindakan_yang_diambil']);
    
    // Get student details
    $q_s = mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk='$no_induk'");
    $s_data = mysqli_fetch_assoc($q_s);
    $nama_siswa = $s_data ? mysqli_real_escape_string($conn, $s_data['nama_siswa']) : '';
    $kelas = $s_data ? mysqli_real_escape_string($conn, $s_data['kelas']) : '';
    
    // Satpam info
    $no_induk_guru = mysqli_real_escape_string($conn, $_SESSION['username']);
    $nama_guru = mysqli_real_escape_string($conn, $satpam_name);
    
    $query = "INSERT INTO tbl_pelanggaran (no_induk, nama_siswa, kelas, tanggal_pelanggaran, kategori_pelanggaran, jenis_pelanggaran, deskripsi_pelanggaran, tindakan_yang_diambil, no_induk_guru, nama_guru) VALUES ('$no_induk', '$nama_siswa', '$kelas', '$tanggal', '$kategori', '$jenis', '$deskripsi', '$tindakan', '$no_induk_guru', '$nama_guru')";
    
    if (mysqli_query($conn, $query)) {
        $msg = "Pelanggaran berhasil dicatat!";
    } else {
        $error = "Gagal mencatat pelanggaran: " . mysqli_error($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validasi_satpam') {
    $id_izin = (int)$_POST['id_izin'];
    $nama_satpam = $_SESSION['nama'] ?? 'Satpam';
    $q = "UPDATE tbl_izin_siswa SET validasi_satpam = 'Disetujui', status_izin = 'Izin Keluar Aktif', validator_satpam = '$nama_satpam', waktu_keluar = NOW(), waktu_validasi_satpam = NOW() WHERE id_izin = $id_izin";
    if (mysqli_query($conn, $q)) {
        $msg = "Izin keluar berhasil divalidasi. Waktu keluar dicatat.";
        
        // Insert into tbl_absen with I
        $qData = mysqli_query($conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE i.id_izin = $id_izin");
        if ($qData && $rowData = mysqli_fetch_assoc($qData)) {
            $nis = $rowData['no_induk_siswa'];
            $kls = $rowData['kelas_siswa'];
            $tgl = $rowData['tanggal_izin'];
            
            $cekAbsen = mysqli_query($conn, "SELECT id FROM tbl_absen WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL LIMIT 1");
            if (mysqli_num_rows($cekAbsen) > 0) {
                mysqli_query($conn, "UPDATE tbl_absen SET status = 'I', sumber = 'Sistem Izin' WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL");
            } else {
                mysqli_query($conn, "INSERT INTO tbl_absen (id_sekolah, tanggal, kelas, no_induk, status, sumber, created_at) VALUES (1, '$tgl', '$kls', '$nis', 'I', 'Sistem Izin', NOW())");
            }
        }
    } else {
        $error = "Gagal memvalidasi izin: " . mysqli_error($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'satpam_masuk') {
    $id_izin = (int)$_POST['id_izin'];
    $q = "UPDATE tbl_izin_siswa SET waktu_kembali = NOW(), status_izin = 'Selesai' WHERE id_izin = $id_izin";
    if (mysqli_query($conn, $q)) {
        $msg = "Waktu kembali berhasil dicatat.";
        
        // Netralkan Izin!
        $qData = mysqli_query($conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE i.id_izin = $id_izin");
        if ($qData && $rowData = mysqli_fetch_assoc($qData)) {
            $nis = $rowData['no_induk_siswa'];
            $tgl = $rowData['tanggal_izin'];
            
            // Update tbl_absen to H (Hadir/Netral)
            mysqli_query($conn, "UPDATE tbl_absen SET status = 'H', sumber = 'Sistem Izin' WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL");
        }
    } else {
        $error = "Gagal mencatat masuk: " . mysqli_error($conn);
    }
}


// Fetch lembaga setting for logo
$lembaga = data_lembaga();
$logoFile = (!empty($lembaga['logo']) && file_exists(__DIR__ . '/img/' . $lembaga['logo'])) ? $lembaga['logo'] : '6695f027d063a.png';

// Fetch data
$today = date('Y-m-d');
$filter_tgl = isset($_GET['tgl']) ? mysqli_real_escape_string($conn, $_GET['tgl']) : $today;

// 1. Validasi (Riwayat Izin)
$q_validasi = mysqli_query($conn, "SELECT i.*, s.nama_siswa, s.kelas FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE i.tanggal_izin = '$filter_tgl' ORDER BY i.waktu_pengajuan DESC");
$list_validasi = [];
while ($row = mysqli_fetch_assoc($q_validasi)) {
    $list_validasi[] = $row;
}

// 2. Daftar Siswa Izin (Sudah disetujui penuh)
$q_izin = mysqli_query($conn, "SELECT i.*, s.nama_siswa, s.kelas FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE i.tanggal_izin = '$filter_tgl' AND i.status_izin = 'Disetujui Penuh' ORDER BY i.waktu_pengajuan DESC");
$list_izin = [];
while ($row = mysqli_fetch_assoc($q_izin)) {
    $list_izin[] = $row;
}

// 3. Daftar Siswa
$q_siswa = mysqli_query($conn, "SELECT no_induk, nisn, nama_siswa, kelas, no_wa FROM tbl_siswa WHERE status='Aktif' ORDER BY kelas, nama_siswa");
$list_siswa = [];
while ($row = mysqli_fetch_assoc($q_siswa)) {
    $list_siswa[] = $row;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIMANIS - Satpam</title>
    <!-- Fonts and Icons -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,600,700,800" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        :root {
            --satpam-bg: #E8F5E9;
            --satpam-card: #C8E6C9;
            --satpam-dark: #1B5E20;
            --satpam-btn: #81C784;
            --satpam-btn-dark: #4CAF50;
        }
        body {
            background-color: var(--satpam-bg);
            font-family: 'Inter', sans-serif;
            color: #333;
            padding-bottom: 70px; /* Space for bottom nav */
        }
        .topbar-app {
            background-color: #fff;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            margin-bottom: 20px;
        }
        .topbar-app img.logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        .topbar-app .app-title {
            color: var(--satpam-dark);
            font-weight: 800;
            font-size: 1.2rem;
            line-height: 1;
        }
        .topbar-app .app-subtitle {
            font-size: 0.7rem;
            color: #666;
        }
        .topbar-user {
            text-align: right;
            font-size: 0.8rem;
        }
        .topbar-user .satpam-name {
            font-weight: 700;
            color: var(--satpam-dark);
            font-size: 0.9rem;
        }
        .section-title {
            color: var(--satpam-dark);
            font-weight: 800;
            font-size: 1.4rem;
            margin: 0 20px 15px;
        }
        
        /* Filters */
        .filter-container {
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .filter-container input, .filter-container select {
            background-color: #fff;
            border: 1px solid #A5D6A7;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.85rem;
            width: 100%;
        }
        .filter-actions {
            grid-column: span 2;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-satpam {
            background-color: var(--satpam-btn);
            color: var(--satpam-dark);
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 6px 15px;
            font-size: 0.85rem;
        }
        .btn-satpam-reset {
            background-color: #9E9E9E;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 6px 15px;
            font-size: 0.85rem;
        }

        /* Cards */
        .list-container {
            padding: 0 20px;
        }
        .student-card {
            background-color: var(--satpam-card);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .student-card img.avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #fff;
            margin-right: 15px;
        }
        .student-info {
            flex-grow: 1;
        }
        .student-info h6 {
            margin: 0;
            font-weight: 800;
            color: #111;
        }
        .student-info p {
            margin: 0;
            font-size: 0.75rem;
            color: #444;
        }
        .student-status {
            text-align: right;
            font-size: 0.8rem;
            font-weight: 600;
            color: #333;
        }
        .card-actions {
            margin-top: 8px;
            display: flex;
            justify-content: flex-end;
            gap: 5px;
        }
        .badge-status {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #fff;
            border: 1px solid var(--satpam-btn);
        }

        /* Approval Workflow (Alur Persetujuan) */
        .approval-flow {
            margin-top: 10px;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.5);
            padding: 8px;
            border-radius: 8px;
        }
        .approval-flow p {
            margin: 0;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .flow-step {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        .flow-step i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        .flow-step.approved { color: var(--satpam-btn-dark); }
        .flow-step.pending { color: #F57F17; }
        .flow-step.rejected { color: #D32F2F; }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #fff;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            z-index: 1000;
        }
        .nav-item {
            text-align: center;
            color: #888;
            font-size: 0.8rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            width: 50%;
        }
        .nav-item i {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }
        .nav-item.active {
            color: var(--satpam-dark);
            font-weight: 700;
        }

        /* Dashboard Grid */
        .satpam-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 0 20px;
            margin-top: 10px;
        }
        .grid-item {
            background: #fff;
            border-radius: 15px;
            padding: 20px 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            border: 1px solid #C8E6C9;
            transition: transform 0.2s;
        }
        .grid-item:active {
            transform: scale(0.95);
        }
        .grid-item i {
            font-size: 2.2rem;
            color: var(--satpam-btn-dark);
            margin-bottom: 12px;
            display: block;
        }
        .grid-item span {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--satpam-dark);
        }

        /* Views */
        .view-content {
            display: none;
        }
        .view-content.active {
            display: block;
        }

        table.satpam-table {
            width: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            font-size: 0.8rem;
        }
        table.satpam-table th {
            background: var(--satpam-card);
            color: var(--satpam-dark);
            padding: 10px;
        }
        table.satpam-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <div class="topbar-app">
        <div class="d-flex align-items-center">
            <img src="img/<?= htmlspecialchars($logoFile) ?>" alt="Logo" class="logo" onerror="this.onerror=null; this.src='img/6695f027d063a.png'">
            <div>
                <div class="app-title"><?= htmlspecialchars($lembaga['nama_aplikasi'] ?? 'SIMANIS') ?></div>
                <div class="app-subtitle">Sistem Manajemen Sekolah</div>
            </div>
        </div>
            <div class="d-flex align-items-center">
                <div class="mr-2 text-right">
                    <div style="font-size: 0.7rem; color: #666;">Login sebagai</div>
                    <div class="satpam-name"><?= htmlspecialchars($satpam_name) ?></div>
                </div>
                <img src="img/avatar_male_3d.png" alt="User" class="img-profile rounded-circle" style="width:35px;height:35px;border:2px solid var(--satpam-dark);">
                <a href="login.php?logout=1" class="ml-2 text-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
    </div>

    <!-- VIEW: Dashboard -->
    <div id="view-dashboard" class="view-content active">
        <h2 class="section-title" style="margin-bottom: 5px;">Menu Utama</h2>
        <p style="padding: 0 20px; font-size: 0.85rem; color: #555; margin-bottom: 20px;">Silakan pilih menu layanan keamanan berikut:</p>
        
        <div class="satpam-grid">
            <div class="grid-item" onclick="switchView('view-siswa-izin', document.getElementById('nav-home'))">
                <i class="fas fa-user-friends"></i>
                <span>Siswa Izin</span>
            </div>
            <div class="grid-item" onclick="switchView('view-daftar-siswa', document.getElementById('nav-home'))">
                <i class="fas fa-list"></i>
                <span>Daftar Siswa</span>
            </div>
            <div class="grid-item" onclick="switchView('view-catat-pelanggaran', document.getElementById('nav-home'))">
                <i class="fas fa-edit text-warning"></i>
                <span>Catat Pelanggaran</span>
            </div>
            <div class="grid-item" onclick="switchView('view-validasi', document.getElementById('nav-home'))">
                <i class="fas fa-history text-info"></i>
                <span>Riwayat Izin</span>
            </div>
        </div>
    </div>

    <!-- VIEW: Daftar Siswa Izin -->
    <div id="view-siswa-izin" class="view-content">
        <h2 class="section-title">Daftar Siswa Izin</h2>
        
        <div class="filter-container">
            <div>
                <label style="font-size:0.7rem;font-weight:bold;margin:0;">Pencarian (Nama/NIS)</label>
                <input type="text" id="filter1-nama" placeholder="Nama/NIS">
            </div>
            <div>
                <label style="font-size:0.7rem;font-weight:bold;margin:0;">Filter Kelas</label>
                <select id="filter1-kelas">
                    <option value="">Semua Kelas</option>
                    <option value="10">Kelas 10</option>
                    <option value="11">Kelas 11</option>
                    <option value="12">Kelas 12</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.7rem;font-weight:bold;margin:0;">Tanggal Izin</label>
                <input type="date" id="filter1-tgl" value="<?= htmlspecialchars($filter_tgl) ?>" onchange="window.location.href='satpam.php?tgl='+this.value">
            </div>
            <div>
                <label style="font-size:0.7rem;font-weight:bold;margin:0;">Keterangan Izin</label>
                <select id="filter1-ket">
                    <option value="">Semua</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Izin">Izin Keluarga</option>
                    <option value="Dispensasi">Dispensasi</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn-satpam" onclick="applyFilterIzin()">Terapkan Filter</button>
                <button class="btn-satpam-reset" onclick="resetFilterIzin()">Reset</button>
            </div>
        </div>

        <div class="list-container">
            <?php if(empty($list_izin)): ?>
                <div class="text-center mt-4">
                    <p class="text-muted">Tidak ada data siswa izin saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach($list_izin as $izin): ?>
                <div class="student-card izin-item" data-nama="<?= htmlspecialchars(strtolower($izin['nama_siswa'])) ?>" data-kelas="<?= htmlspecialchars(strtolower($izin['kelas'])) ?>" data-jenis="<?= htmlspecialchars(strtolower($izin['jenis_izin'])) ?>" data-tgl="<?= htmlspecialchars($izin['tanggal_izin']) ?>">
                    <img src="img/avatar_male_3d.png" class="avatar" alt="Avatar">
                    <div class="student-info">
                        <h6><?= htmlspecialchars($izin['nama_siswa']) ?></h6>
                        <p>Kelas <?= htmlspecialchars($izin['kelas']) ?></p>
                        <div class="card-actions">
                            <span class="badge-status"><i class="fas fa-check text-success"></i> <?= htmlspecialchars($izin['status_izin']) ?></span>
                            <button class="btn-satpam" style="padding: 3px 8px; font-size: 0.7rem;" onclick="alert('Alasan Izin: <?= htmlspecialchars(addslashes($izin['detail_izin'])) ?>')">Detail</button>
                        </div>
                    </div>
                    <div class="student-status">
                        <?= htmlspecialchars($izin['jenis_izin']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- VIEW: Catat Pelanggaran -->
    <div id="view-catat-pelanggaran" class="view-content">
        <h2 class="section-title">Catat Pelanggaran Siswa</h2>
        <div class="list-container" style="padding: 15px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="catat_pelanggaran">
                
                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:0.85rem;">Pilih Siswa</label>
                    <select name="no_induk" class="form-control" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach($list_siswa as $s): ?>
                            <option value="<?= htmlspecialchars($s['no_induk']) ?>"><?= htmlspecialchars($s['nama_siswa']) ?> (<?= htmlspecialchars($s['kelas']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:0.85rem;">Tanggal Pelanggaran</label>
                    <input type="date" name="tanggal_pelanggaran" class="form-control" value="<?= $today ?>" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:0.85rem;">Kategori Pelanggaran</label>
                    <select name="kategori_pelanggaran" class="form-control" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
                        <option value="Ringan">Ringan</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Berat">Berat</option>
                    </select>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:0.85rem;">Jenis Pelanggaran</label>
                    <input type="text" name="jenis_pelanggaran" class="form-control" placeholder="Contoh: Terlambat, Atribut tidak lengkap" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:0.85rem;">Deskripsi / Kronologi</label>
                    <textarea name="deskripsi_pelanggaran" class="form-control" rows="3" placeholder="Jelaskan detail pelanggaran" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;"></textarea>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-weight:bold; font-size:0.85rem;">Tindakan yang Diambil</label>
                    <textarea name="tindakan_yang_diambil" class="form-control" rows="2" placeholder="Contoh: Diberi peringatan, Dicatat di buku" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;"></textarea>
                </div>

                <button type="submit" class="btn-satpam" style="width:100%; font-size:1rem; padding:10px;"><i class="fas fa-save"></i> Simpan Pelanggaran</button>
            </form>
        </div>
    </div>

    <!-- VIEW: Riwayat Validasi -->
    <div id="view-validasi" class="view-content">
        <h2 class="section-title">Riwayat Perjalanan Izin</h2>
        
        <div class="list-container">
            <?php if(empty($list_validasi)): ?>
                <div class="text-center mt-4">
                    <p class="text-muted">Tidak ada permohonan izin hari ini.</p>
                </div>
            <?php else: ?>
                <?php 
                foreach($list_validasi as $val): 
                    $is_done = ($val['status_izin'] == 'Disetujui Penuh');
                    $mapel_ok = ($val['validasi_guru_mapel'] == 'Disetujui');
                    $wakel_ok = ($val['validasi_wali_kelas'] == 'Disetujui');
                    $bk_ok = ($val['validasi_guru_bk'] == 'Disetujui' || $val['status_izin'] == 'Disetujui Penuh');
                ?>
                <div class="student-card" id="card-validasi-<?= $val['id_izin'] ?>">
                    <img src="img/avatar_male_3d.png" class="avatar" alt="Avatar">
                    <div class="student-info" style="width: 100%;">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6><?= htmlspecialchars($val['nama_siswa']) ?></h6>
                                <p><?= htmlspecialchars($val['kelas']) ?> | <?= date('d M H:i', strtotime($val['waktu_pengajuan'])) ?></p>
                            </div>
                            <div class="text-right">
                                <div><button class="btn-satpam-reset" style="padding: 3px 8px; font-size: 0.7rem;">Detail</button></div>
                            </div>
                        </div>
                        
                        <div class="approval-flow">
                            <p>Alur Persetujuan</p>
                            <div class="flow-step <?= $mapel_ok ? 'approved' : 'pending' ?>">
                                <i class="fas <?= $mapel_ok ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i> Guru Mapel
                            </div>
                            <div class="flow-step <?= $wakel_ok ? 'approved' : 'pending' ?>">
                                <i class="fas <?= $wakel_ok ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i> Wali Kelas
                            </div>
                            <div class="flow-step <?= $bk_ok ? 'approved' : 'pending' ?>">
                                <i class="fas <?= $bk_ok ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i> Kesiswaan / BK
                            </div>
                                                <div class="mt-3">
                            <?php if ($val['kategori_pengajuan'] === 'Keluar Sekolah'): ?>
                                <?php if ($wakel_ok && $val['validasi_satpam'] === 'Menunggu'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="validasi_satpam">
                                        <input type="hidden" name="id_izin" value="<?= $val['id_izin'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm w-100 mb-2" onclick="return confirm('Beri izin keluar?')"><i class="fas fa-sign-out-alt"></i> Validasi Keluar</button>
                                    </form>
                                <?php elseif ($val['validasi_satpam'] === 'Disetujui' && $val['opsi_kembali'] === 'Kembali ke Sekolah' && empty($val['waktu_kembali'])): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="satpam_masuk">
                                        <input type="hidden" name="id_izin" value="<?= $val['id_izin'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2" onclick="return confirm('Konfirmasi siswa masuk kembali?')"><i class="fas fa-sign-in-alt"></i> Masuk Lagi</button>
                                    </form>
                                <?php elseif (!$wakel_ok && $val['validasi_satpam'] === 'Menunggu'): ?>
                                    <button class="btn btn-secondary btn-sm w-100 mb-2" disabled><i class="fas fa-lock"></i> Menunggu Wali Kelas</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- VIEW: Daftar Siswa (Master Data) -->
    <div id="view-daftar-siswa" class="view-content">
        <h2 class="section-title">Daftar Siswa Aktif</h2>
        
        <div class="filter-container">
            <div>
                <input type="text" id="filter2-nama" placeholder="Cari Nama / NIS..." onkeyup="filterSiswa()">
            </div>
            <div>
                <select id="filter2-kelas" onchange="filterSiswa()">
                    <option value="">Semua Kelas</option>
                    <option value="10">Kelas 10</option>
                    <option value="11">Kelas 11</option>
                    <option value="12">Kelas 12</option>
                </select>
            </div>
        </div>

        <div class="list-container">
            <table class="satpam-table">
                <thead>
                    <tr>
                        <th>NIS/NISN</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Kontak WA</th>
                    </tr>
                </thead>
                <tbody id="tbody-siswa">
                    <?php foreach($list_siswa as $s): ?>
                    <tr class="siswa-row">
                        <td><?= htmlspecialchars($s['no_induk'] ?? $s['nisn']) ?></td>
                        <td class="s-nama"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                        <td class="s-kelas"><?= htmlspecialchars($s['kelas']) ?></td>
                        <td>
                            <?php if (!empty($s['no_wa'])): ?>
                                <?php $cleanHp = preg_replace('/\D/', '', (string) $s['no_wa']); ?>
                                <?php if ($cleanHp !== ''): ?>
                                    <a href="https://wa.me/62<?= ltrim($cleanHp, '0') ?>" target="_blank" class="btn-satpam" style="padding: 4px 8px; font-size: 0.75rem; text-decoration: none;">
                                        <i class="fab fa-whatsapp"></i> Hubungi
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <div class="nav-item active" id="nav-home" onclick="switchView('view-dashboard', this)">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </div>
        <div class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Pengaturan</span>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script>
        function switchView(viewId, navElement) {
            // Hide all views
            document.querySelectorAll('.view-content').forEach(el => el.classList.remove('active'));
            // Remove active from all nav items
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            // Show selected view if exists (some might be placeholder)
            const targetView = document.getElementById(viewId);
            if (targetView) targetView.classList.add('active');
            
            // Activate nav
            navElement.classList.add('active');
        }

        function filterSiswa() {
            let nama = document.getElementById('filter2-nama').value.toLowerCase();
            let kelas = document.getElementById('filter2-kelas').value.toLowerCase();
            
            document.querySelectorAll('.siswa-row').forEach(row => {
                let sNama = row.querySelector('.s-nama').innerText.toLowerCase();
                let sKelas = row.querySelector('.s-kelas').innerText.toLowerCase();
                
                let matchNama = nama === '' || sNama.includes(nama);
                let matchKelas = kelas === '' || sKelas.includes(kelas);
                
                if(matchNama && matchKelas) row.style.display = '';
                else row.style.display = 'none';
            });
        }

        function applyFilterIzin() {
            let nama = document.getElementById('filter1-nama').value.toLowerCase();
            let kelas = document.getElementById('filter1-kelas').value.toLowerCase();
            let ket = document.getElementById('filter1-ket').value.toLowerCase();
            
            document.querySelectorAll('.izin-item').forEach(item => {
                let sNama = item.getAttribute('data-nama');
                let sKelas = item.getAttribute('data-kelas');
                let sKet = item.getAttribute('data-jenis');
                
                let matchNama = nama === '' || sNama.includes(nama);
                let matchKelas = kelas === '' || sKelas.includes(kelas);
                let matchKet = ket === '' || sKet.includes(ket);
                
                if(matchNama && matchKelas && matchKet) item.style.display = 'flex';
                else item.style.display = 'none';
            });
        }

        function resetFilterIzin() {
            document.getElementById('filter1-nama').value = '';
            document.getElementById('filter1-kelas').value = '';
            document.getElementById('filter1-ket').value = '';
            applyFilterIzin();
        }

    </script>
</body>
</html>
