<?php
// pages/guru/ekskul.php
require_once __DIR__ . '/../../koneksi.php';
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] == '') {
    die("Akses ditolak");
}

$nipguru = $_SESSION['no_induk'];
$dataguru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipguru'"));

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'join_ekskul') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        mysqli_query($conn, "INSERT IGNORE INTO tbl_pembina_ekskul (id_ekskul, no_induk_guru) VALUES ($id_ekskul, '$nipguru')");
        echo "<script>alert('Berhasil menjadi pembina!'); window.location='ekskul';</script>";
        exit;
    }
    
    if ($_POST['action'] == 'add_anggota') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $nis = mysqli_real_escape_string($conn, $_POST['no_induk_siswa']);
        mysqli_query($conn, "INSERT IGNORE INTO tbl_anggota_ekskul (id_ekskul, no_induk_siswa) VALUES ($id_ekskul, '$nis')");
        echo "<script>window.location='ekskul?tab=anggota&id_ekskul=$id_ekskul';</script>";
        exit;
    }
    
    if ($_POST['action'] == 'remove_anggota') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $nis = mysqli_real_escape_string($conn, $_POST['no_induk_siswa']);
        mysqli_query($conn, "DELETE FROM tbl_anggota_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$nis'");
        echo "<script>window.location='ekskul?tab=anggota&id_ekskul=$id_ekskul';</script>";
        exit;
    }

    if ($_POST['action'] == 'sync_eraport_anggota') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $ekskul_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_ekskul FROM tbl_ekskul WHERE id_ekskul = $id_ekskul"));
        $count_imported = 0;
        if ($ekskul_row) {
            $nama_ekskul = $ekskul_row['nama_ekskul'];
            $nama_ekskul_esc = mysqli_real_escape_string($conn, $nama_ekskul);
            $q_sync = mysqli_query($conn, "SELECT DISTINCT nis FROM tbl_ekskul_siswa_eraport WHERE nama_ekskul = '$nama_ekskul_esc' OR nama_ekskul LIKE '%$nama_ekskul_esc%' OR '$nama_ekskul_esc' LIKE CONCAT('%', nama_ekskul, '%')");
            if ($q_sync && mysqli_num_rows($q_sync) > 0) {
                while ($rs = mysqli_fetch_assoc($q_sync)) {
                    $nis = mysqli_real_escape_string($conn, $rs['nis']);
                    if ($nis !== '') {
                        mysqli_query($conn, "INSERT IGNORE INTO tbl_anggota_ekskul (id_ekskul, no_induk_siswa) VALUES ($id_ekskul, '$nis')");
                        if (mysqli_affected_rows($conn) > 0) {
                            $count_imported++;
                        }
                    }
                }
            }
        }
        echo "<script>alert('Sinkronisasi selesai! Berhasil menambahkan $count_imported siswa baru.'); window.location='ekskul?tab=anggota&id_ekskul=$id_ekskul';</script>";
        exit;
    }
    
    if ($_POST['action'] == 'save_jadwal') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $hari = mysqli_real_escape_string($conn, $_POST['hari']);
        $mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
        $selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
        mysqli_query($conn, "INSERT INTO tbl_jadwal_ekskul (id_ekskul, hari, jam_mulai, jam_selesai) VALUES ($id_ekskul, '$hari', '$mulai', '$selesai')");
        echo "<script>window.location='ekskul?tab=jadwal&id_ekskul=$id_ekskul';</script>";
        exit;
    }
    
    if ($_POST['action'] == 'delete_jadwal') {
        $id_jadwal = (int)$_POST['id_jadwal'];
        $id_ekskul = (int)$_POST['id_ekskul'];
        mysqli_query($conn, "DELETE FROM tbl_jadwal_ekskul WHERE id_jadwal=$id_jadwal");
        echo "<script>window.location='ekskul?tab=jadwal&id_ekskul=$id_ekskul';</script>";
        exit;
    }

    if ($_POST['action'] == 'add_tugas') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        mysqli_query($conn, "INSERT INTO tbl_tugas_ekskul (id_ekskul, judul, deskripsi, tanggal) VALUES ($id_ekskul, '$judul', '$deskripsi', '$tanggal')");
        echo "<script>window.location='ekskul?tab=tugas&id_ekskul=$id_ekskul';</script>";
        exit;
    }

    if ($_POST['action'] == 'input_jurnal') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        $materi = mysqli_real_escape_string($conn, $_POST['materi']);
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
        
        // Simpan jurnal guru
        mysqli_query($conn, "INSERT INTO tbl_jurnal_ekskul (id_ekskul, no_induk_guru, tanggal, materi, keterangan) VALUES ($id_ekskul, '$nipguru', '$tanggal', '$materi', '$keterangan') ON DUPLICATE KEY UPDATE materi='$materi', keterangan='$keterangan'");
        
        // Simpan konfirmasi presensi dari array POST jika ada
        if (isset($_POST['presensi'])) {
            foreach ($_POST['presensi'] as $nis => $status) {
                $status = mysqli_real_escape_string($conn, $status);
                mysqli_query($conn, "INSERT INTO tbl_presensi_ekskul (id_ekskul, no_induk_siswa, tanggal, waktu, status) VALUES ($id_ekskul, '$nis', '$tanggal', CURTIME(), '$status') ON DUPLICATE KEY UPDATE status='$status'");
            }
        }
        
        echo "<script>alert('Jurnal berhasil disimpan!'); window.location='ekskul?tab=jurnal&id_ekskul=$id_ekskul';</script>";
        exit;
    }

    if ($_POST['action'] == 'save_nilai') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        if (isset($_POST['nilai']) && is_array($_POST['nilai'])) {
            foreach ($_POST['nilai'] as $nis => $nilai_val) {
                $nis_esc = mysqli_real_escape_string($conn, $nis);
                $nilai_val_esc = mysqli_real_escape_string($conn, $nilai_val);
                mysqli_query($conn, "UPDATE tbl_anggota_ekskul SET nilai = " . ($nilai_val_esc === '' ? "NULL" : "'$nilai_val_esc'") . " WHERE id_ekskul = $id_ekskul AND no_induk_siswa = '$nis_esc'");
            }
        }
        echo "<script>alert('Nilai berhasil disimpan!'); window.location='ekskul?tab=nilai&id_ekskul=$id_ekskul';</script>";
        exit;
    }
}

// Fetch Ekskul yang dibina oleh guru ini
$q_myekskul = mysqli_query($conn, "SELECT e.* FROM tbl_ekskul e JOIN tbl_pembina_ekskul p ON e.id_ekskul = p.id_ekskul WHERE p.no_induk_guru = '$nipguru'");
$my_ekskul = [];
while($r = mysqli_fetch_assoc($q_myekskul)) {
    $my_ekskul[] = $r;
}

$tab = $_GET['tab'] ?? 'dashboard';
$id_ekskul_active = isset($_GET['id_ekskul']) ? (int)$_GET['id_ekskul'] : ($my_ekskul[0]['id_ekskul'] ?? 0);

// Auto sync/import from e-Raport synced table (tbl_ekskul_siswa_eraport) if currently empty
if ($id_ekskul_active > 0) {
    // Check if local members are empty
    $check_members = mysqli_query($conn, "SELECT 1 FROM tbl_anggota_ekskul WHERE id_ekskul = $id_ekskul_active LIMIT 1");
    if ($check_members && mysqli_num_rows($check_members) === 0) {
        $ekskul_active_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_ekskul FROM tbl_ekskul WHERE id_ekskul = $id_ekskul_active"));
        if ($ekskul_active_row) {
            $nama_ekskul_active = $ekskul_active_row['nama_ekskul'];
            $nama_ekskul_esc = mysqli_real_escape_string($conn, $nama_ekskul_active);
            $q_sync = mysqli_query($conn, "SELECT DISTINCT nis FROM tbl_ekskul_siswa_eraport WHERE nama_ekskul = '$nama_ekskul_esc' OR nama_ekskul LIKE '%$nama_ekskul_esc%' OR '$nama_ekskul_esc' LIKE CONCAT('%', nama_ekskul, '%')");
            if ($q_sync && mysqli_num_rows($q_sync) > 0) {
                while ($rs = mysqli_fetch_assoc($q_sync)) {
                    $nis = mysqli_real_escape_string($conn, $rs['nis']);
                    if ($nis !== '') {
                        mysqli_query($conn, "INSERT IGNORE INTO tbl_anggota_ekskul (id_ekskul, no_induk_siswa) VALUES ($id_ekskul_active, '$nis')");
                    }
                }
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
    <title>Manajemen Ekstrakurikuler - Guru</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; color: #333; }
        .nav-pills .nav-link.active { background-color: #EC4899; color: #fff; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3); }
        .nav-pills .nav-link { color: #495057; font-weight: 500; border-radius: 8px; padding: 10px 16px; transition: all 0.2s; }
        .nav-pills .nav-link:hover:not(.active) { background-color: #e9ecef; }
        .card { border-radius: 12px; border: none; }
        .btn-outline-pink { color: #EC4899; border-color: #EC4899; }
        .btn-outline-pink:hover { background-color: #EC4899; color: #fff; }
        @media (max-width: 991.98px) {
            .nav-pills-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr);
                gap: 8px !important;
            }
            .nav-pills-grid .nav-link {
                font-size: 10px !important;
                padding: 8px 2px !important;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                height: 100%;
                line-height: 1.2;
            }
            .nav-pills-grid .nav-link i {
                font-size: 1.25rem !important;
                display: block;
                margin-bottom: 2px;
            }
        }
        @media (min-width: 992px) {
            .nav-pills-grid {
                display: flex !important;
                flex-direction: column !important;
            }
            .nav-pills-grid .nav-link {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        }
        
        @media print {
            .no-print, .btn, button, nav, .navbar, .mobile-nav, .desktop-sidebar, .col-md-3, .col-lg-3, select, form {
                display: none !important;
            }
            .col-lg-9, .col-md-9 {
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
            body { background: #fff !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><a href="../../home.php" class="text-decoration-none text-dark"><i class="bi bi-arrow-left"></i></a> Manajemen Ekstrakurikuler</h4>
    </div>

    <?php if (empty($my_ekskul)): ?>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mt-2">
            <div class="card-body p-5 text-center">
                <div class="mb-4 text-warning">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 4rem;"></i>
                </div>
                <h5 class="fw-bold mb-2">Akses Terbatas</h5>
                <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                    Anda belum ditugaskan oleh Administrator sebagai pembina ekstrakurikuler manapun. 
                    Silakan hubungi pihak Admin sekolah untuk melakukan pengaturan pembina ekstrakurikuler.
                </p>
                <a href="../../home.php" class="btn btn-primary px-4 py-2 rounded-pill border-0" style="background-color: #EC4899;">
                    <i class="bi bi-house-door"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-3 mb-4 no-print">
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-2">
                        <select class="form-select border-0 shadow-none fw-bold" onchange="window.location='ekskul?id_ekskul='+this.value">
                            <?php foreach($my_ekskul as $ekskul): ?>
                                <option value="<?= $ekskul['id_ekskul'] ?>" <?= $ekskul['id_ekskul'] == $id_ekskul_active ? 'selected' : '' ?>><?= htmlspecialchars($ekskul['nama_ekskul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-2 mb-3">
                    <div class="nav nav-pills nav-pills-grid gap-1">
                        <a class="nav-link <?= $tab == 'dashboard' ? 'active' : '' ?>" href="ekskul?tab=dashboard&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
                        <a class="nav-link <?= $tab == 'anggota' ? 'active' : '' ?>" href="ekskul?tab=anggota&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-people"></i> <span>Anggota</span></a>
                        <a class="nav-link <?= $tab == 'jadwal' ? 'active' : '' ?>" href="ekskul?tab=jadwal&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-calendar-event"></i> <span>Jadwal</span></a>
                        <a class="nav-link <?= $tab == 'jurnal' ? 'active' : '' ?>" href="ekskul?tab=jurnal&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-journal-check"></i> <span>Jurnal</span></a>
                        <a class="nav-link <?= $tab == 'tugas' ? 'active' : '' ?>" href="ekskul?tab=tugas&id_ekskul=<?=$id_ekskul_active?>"><i class="bi bi-list-task"></i> <span>Tugas</span></a>
                        <a class="nav-link <?= $tab == 'nilai' ? 'active' : '' ?>" href="ekskul?tab=nilai&id_ekskul=<?=$id_ekskul_active?>"><i class="bi bi-patch-check"></i> <span>Nilai</span></a>
                        <a class="nav-link <?= $tab == 'cetak' ? 'active' : '' ?>" href="ekskul?tab=cetak&id_ekskul=<?=$id_ekskul_active?>"><i class="bi bi-printer"></i> <span>Cetak</span></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($tab == 'dashboard'): ?>
                            <?php
                            // Fetch active ekskul data
                            $ekskul_active_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_ekskul WHERE id_ekskul = $id_ekskul_active"));
                            
                            // Member count
                            $count_members_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_anggota_ekskul WHERE id_ekskul = $id_ekskul_active");
                            $count_members = mysqli_fetch_assoc($count_members_res)['total'] ?? 0;
                            
                            // Jurnal count
                            $count_jurnal_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_jurnal_ekskul WHERE id_ekskul = $id_ekskul_active");
                            $count_jurnal = mysqli_fetch_assoc($count_jurnal_res)['total'] ?? 0;
                            
                            // Jadwal
                            $q_jadwal_dashboard = mysqli_query($conn, "SELECT * FROM tbl_jadwal_ekskul WHERE id_ekskul = $id_ekskul_active");
                            $jadwals_dashboard = [];
                            while ($j = mysqli_fetch_assoc($q_jadwal_dashboard)) {
                                $jadwals_dashboard[] = $j['hari'] . " (" . $j['jam_mulai'] . " - " . $j['jam_selesai'] . ")";
                            }
                            $jadwal_str_dashboard = empty($jadwals_dashboard) ? "Belum diatur" : implode(", ", $jadwals_dashboard);
                            ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="p-4 rounded-4 text-white shadow-sm mb-4" style="background: linear-gradient(135deg, #EC4899, #F43F5E); border-radius: 12px;">
                                        <span class="badge text-bg-light mb-2">Pembina Aktif</span>
                                        <h3 class="fw-bold mb-1"><?= htmlspecialchars($ekskul_active_data['nama_ekskul'] ?? '') ?></h3>
                                        <p class="mb-0 opacity-75"><?= htmlspecialchars($ekskul_active_data['deskripsi'] ?? 'Ekstrakurikuler resmi sekolah.') ?></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-3 bg-light h-100">
                                        <div class="card-body p-4 d-flex align-items-center">
                                            <div class="rounded-circle p-3 me-3" style="background-color: #fce7f3; color: #db2777; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-people-fill fs-3"></i>
                                            </div>
                                            <div>
                                                <span class="text-muted small d-block">Anggota Siswa</span>
                                                <h4 class="fw-bold mb-0"><?= $count_members ?> Siswa</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-3 bg-light h-100">
                                        <div class="card-body p-4 d-flex align-items-center">
                                            <div class="rounded-circle p-3 me-3" style="background-color: #dbeafe; color: #2563eb; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-journal-text fs-3"></i>
                                            </div>
                                            <div>
                                                <span class="text-muted small d-block">Pertemuan Jurnal</span>
                                                <h4 class="fw-bold mb-0"><?= $count_jurnal ?> Pertemuan</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-3 bg-light h-100">
                                        <div class="card-body p-4 d-flex align-items-center">
                                            <div class="rounded-circle p-3 me-3" style="background-color: #fef9c3; color: #ca8a04; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-clock-fill fs-3"></i>
                                            </div>
                                            <div>
                                                <span class="text-muted small d-block">Jadwal Rutin</span>
                                                <h6 class="fw-bold mb-0 text-wrap"><?= $jadwal_str_dashboard ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <div class="card border-0 shadow-sm rounded-4">
                                        <div class="card-header bg-white py-3 border-0">
                                            <h5 class="fw-bold mb-0">Menu Cepat Pembina</h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="row g-2">
                                                <div class="col-6 col-md-3">
                                                    <a href="ekskul?tab=anggota&id_ekskul=<?= $id_ekskul_active ?>" class="btn btn-outline-secondary w-100 py-3 rounded-3"><i class="bi bi-people d-block fs-3 mb-1"></i> Anggota</a>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <a href="ekskul?tab=jurnal&id_ekskul=<?= $id_ekskul_active ?>" class="btn btn-outline-secondary w-100 py-3 rounded-3"><i class="bi bi-journal-check d-block fs-3 mb-1"></i> Isi Jurnal</a>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <a href="ekskul?tab=nilai&id_ekskul=<?= $id_ekskul_active ?>" class="btn btn-outline-secondary w-100 py-3 rounded-3"><i class="bi bi-patch-check d-block fs-3 mb-1"></i> Input Nilai</a>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <a href="ekskul?tab=cetak&id_ekskul=<?= $id_ekskul_active ?>" class="btn btn-outline-secondary w-100 py-3 rounded-3"><i class="bi bi-printer d-block fs-3 mb-1"></i> Ekspor Data</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($tab == 'cetak'): ?>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 shadow-sm border-light">
                                        <div class="card-header bg-white pb-0 border-0 pt-4">
                                            <h5 class="card-title fw-bold text-primary"><i class="bi bi-award"></i> Cetak Nilai Akhir</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">Cetak dokumen resmi Daftar Nilai Akhir Ekstrakurikuler yang dilengkapi dengan format Kop Surat, tanda tangan Pembina, dan Kepala Sekolah.</p>
                                        </div>
                                        <div class="card-footer bg-white border-0 pb-4">
                                            <a href="cetak-ekskul-nilai.php?id_ekskul=<?= $id_ekskul_active ?>" target="_blank" class="btn btn-primary w-100" style="background-color: #EC4899; border-color: #EC4899;">
                                                <i class="bi bi-printer"></i> Cetak Nilai Akhir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 shadow-sm border-light">
                                        <div class="card-header bg-white pb-0 border-0 pt-4">
                                            <h5 class="card-title fw-bold text-success"><i class="bi bi-calendar-check"></i> Cetak Daftar Hadir</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">Cetak rekapitulasi kehadiran siswa dalam ekstrakurikuler pada rentang waktu (bulan) tertentu secara resmi.</p>
                                            <form action="cetak-ekskul-hadir.php" method="GET" target="_blank" class="mt-3">
                                                <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <label class="form-label small text-muted mb-1">Mulai Tanggal</label>
                                                        <input type="date" name="tglAwal" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                                                        <input type="date" name="tglAkhir" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="bi bi-printer"></i> Cetak Daftar Hadir
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($tab == 'anggota'): ?>
                            <h5>Daftar Anggota Siswa</h5>
                            <div class="d-flex justify-content-between align-items-center mb-4 mt-3 flex-wrap" style="gap:10px;">
                                <form method="post" class="d-flex gap-2 flex-grow-1" style="max-width: 600px;">
                                    <input type="hidden" name="action" value="add_anggota">
                                    <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                    <select id="select_kelas" class="form-select" style="max-width: 180px;">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php
                                        $q_kelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa WHERE status='Aktif' AND kelas != '' ORDER BY kelas ASC");
                                        while($k = mysqli_fetch_assoc($q_kelas)) {
                                            echo "<option value='".htmlspecialchars($k['kelas'])."'>".htmlspecialchars($k['kelas'])."</option>";
                                        }
                                        ?>
                                    </select>
                                    <select id="select_siswa" name="no_induk_siswa" class="form-select" required>
                                        <option value="">-- Pilih Siswa --</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary" style="background-color: #EC4899; border-color:#EC4899;">Tambah</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="sync_eraport_anggota">
                                    <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                    <button type="submit" class="btn btn-outline-success"><i class="bi bi-sync"></i> Sinkron Anggota e-Raport</button>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr><th>No Induk</th><th>Nama Siswa</th><th>Kelas</th><th>Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $q_anggota = mysqli_query($conn, "SELECT a.*, s.nama_siswa, s.kelas FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $id_ekskul_active");
                                        while($a = mysqli_fetch_assoc($q_anggota)):
                                        ?>
                                        <tr>
                                            <td><?= $a['no_induk_siswa'] ?></td>
                                            <td><?= htmlspecialchars($a['nama_siswa']) ?></td>
                                            <td><?= htmlspecialchars($a['kelas']) ?></td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="remove_anggota">
                                                    <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                                    <input type="hidden" name="no_induk_siswa" value="<?= $a['no_induk_siswa'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger border-0" onclick="return confirm('Hapus siswa ini dari ekskul?')"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif ($tab == 'jadwal'): ?>
                            <h5>Jadwal Ekskul</h5>
                            <form method="post" class="row g-2 mb-4 mt-3 align-items-end">
                                <input type="hidden" name="action" value="save_jadwal">
                                <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                <div class="col-md-3">
                                    <label>Hari</label>
                                    <select name="hari" class="form-select" required>
                                        <option value="Senin">Senin</option><option value="Selasa">Selasa</option>
                                        <option value="Rabu">Rabu</option><option value="Kamis">Kamis</option>
                                        <option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option>
                                        <option value="Minggu">Minggu</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Jam Mulai</label>
                                    <input type="time" name="jam_mulai" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Jam Selesai</label>
                                    <input type="time" name="jam_selesai" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100" style="background-color: #EC4899; border-color:#EC4899;">Tambah Jadwal</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead><tr><th>Hari</th><th>Jam</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $q_jadwal = mysqli_query($conn, "SELECT * FROM tbl_jadwal_ekskul WHERE id_ekskul = $id_ekskul_active ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')");
                                        while($j = mysqli_fetch_assoc($q_jadwal)):
                                        ?>
                                        <tr>
                                            <td><?= $j['hari'] ?></td>
                                            <td><?= $j['jam_mulai'] ?> - <?= $j['jam_selesai'] ?></td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_jadwal">
                                                    <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                                    <input type="hidden" name="id_jadwal" value="<?= $j['id_jadwal'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger border-0"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif ($tab == 'tugas'): ?>
                            <h5>Tugas Ekskul / Histori Tugas</h5>
                            <button class="btn btn-sm btn-primary mb-3" style="background-color: #EC4899; border-color:#EC4899;" data-bs-toggle="modal" data-bs-target="#modalTugas">+ Berikan Tugas Baru</button>
                            
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>Tanggal</th><th>Judul Tugas</th><th>Deskripsi</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $q_tugas = mysqli_query($conn, "SELECT * FROM tbl_tugas_ekskul WHERE id_ekskul = $id_ekskul_active ORDER BY tanggal DESC");
                                        while($t = mysqli_fetch_assoc($q_tugas)):
                                        ?>
                                        <tr>
                                            <td><?= $t['tanggal'] ?></td>
                                            <td><?= htmlspecialchars($t['judul']) ?></td>
                                            <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal fade" id="modalTugas" tabindex="-1">
                              <div class="modal-dialog">
                                <form method="post" class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Berikan Tugas Baru</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <div class="modal-body">
                                    <input type="hidden" name="action" value="add_tugas">
                                    <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                    <div class="mb-3">
                                        <label>Judul Tugas</label>
                                        <input type="text" name="judul" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label>Tanggal</label>
                                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" style="background-color: #EC4899; border-color:#EC4899;">Simpan</button>
                                  </div>
                                </form>
                              </div>
                            </div>

                        <?php elseif ($tab == 'jurnal'): ?>
                            <?php $tgl_input = $_GET['tgl'] ?? date('Y-m-d'); ?>
                            <h5>Input Jurnal & Validasi Kehadiran</h5>
                            <form method="get" class="d-flex gap-2 mb-4 align-items-center mt-3">
                                <input type="hidden" name="tab" value="jurnal">
                                <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                <label class="fw-bold">Tanggal Jurnal:</label>
                                <input type="date" name="tgl" class="form-control w-auto" value="<?= $tgl_input ?>" onchange="this.form.submit()">
                            </form>

                            <?php
                            $q_jurnal_exist = mysqli_query($conn, "SELECT * FROM tbl_jurnal_ekskul WHERE id_ekskul=$id_ekskul_active AND tanggal='$tgl_input'");
                            $jurnal_exist = mysqli_fetch_assoc($q_jurnal_exist);
                            ?>

                            <form method="post">
                                <input type="hidden" name="action" value="input_jurnal">
                                <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                <input type="hidden" name="tanggal" value="<?= $tgl_input ?>">
                                
                                <div class="mb-3">
                                    <label>Materi / Kegiatan Hari Ini</label>
                                    <textarea name="materi" class="form-control" rows="2" required><?= htmlspecialchars($jurnal_exist['materi'] ?? '') ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label>Keterangan Tambahan</label>
                                    <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($jurnal_exist['keterangan'] ?? '') ?></textarea>
                                </div>

                                <h6 class="mt-4 fw-bold">Konfirmasi Kehadiran Siswa</h6>
                                <p class="text-muted small">Tanda <i class="bi bi-check-circle-fill text-success"></i> berarti siswa telah melakukan presensi mandiri (selfie).</p>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Siswa</th>
                                                <th>Presensi Mandiri</th>
                                                <th>Status Akhir (Konfirmasi Guru)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $q_anggota = mysqli_query($conn, "SELECT a.no_induk_siswa, s.nama_siswa FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $id_ekskul_active");
                                            while($a = mysqli_fetch_assoc($q_anggota)):
                                                $nis = $a['no_induk_siswa'];
                                                // Cek presensi siswa di tanggal tsb
                                                $q_presensi = mysqli_query($conn, "SELECT * FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul_active AND no_induk_siswa='$nis' AND tanggal='$tgl_input'");
                                                $presensi = mysqli_fetch_assoc($q_presensi);
                                                $status = $presensi ? $presensi['status'] : 'Alpa';
                                                $selfie_done = ($presensi && $presensi['foto_bukti'] != '') ? true : false;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($a['nama_siswa']) ?></td>
                                                <td class="text-center">
                                                    <?php if($selfie_done): ?>
                                                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-dash-circle text-secondary fs-5"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <select name="presensi[<?= $nis ?>]" class="form-select form-select-sm">
                                                        <option value="Hadir" <?= $status == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                                                        <option value="Sakit" <?= $status == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                                                        <option value="Izin" <?= $status == 'Izin' ? 'selected' : '' ?>>Izin</option>
                                                        <option value="Alpa" <?= $status == 'Alpa' ? 'selected' : '' ?>>Alpa</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3" style="background-color: #EC4899; border-color:#EC4899;">Simpan Jurnal & Presensi</button>
                            </form>

                        <?php elseif ($tab == 'nilai'): ?>
                            <h5 class="fw-bold mb-3"><i class="bi bi-patch-check"></i> Input Nilai Ekstrakurikuler</h5>
                            <p class="text-muted small">Input nilai akhir siswa untuk ekstrakurikuler aktif. Anda dapat menggunakan rekomendasi nilai yang dihitung otomatis dari persentase kehadiran sebagai acuan.</p>
                            
                            <?php
                            // Hitung total hari kehadiran untuk rekomendasi nilai
                            $total_meetings_res = mysqli_query($conn, "SELECT COUNT(DISTINCT tanggal) as total FROM tbl_presensi_ekskul WHERE id_ekskul = $id_ekskul_active");
                            $total_meetings = mysqli_fetch_assoc($total_meetings_res)['total'] ?? 0;
                            
                            $q_anggota_nilai = mysqli_query($conn, "SELECT a.no_induk_siswa, a.nilai, s.nama_siswa, s.kelas FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $id_ekskul_active ORDER BY s.nama_siswa ASC");
                            ?>
                            
                            <form method="post" class="mt-3">
                                <input type="hidden" name="action" value="save_nilai">
                                <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>NIS</th>
                                                <th>Nama Siswa</th>
                                                <th>Kelas</th>
                                                <th>Kehadiran</th>
                                                <th>% Hadir</th>
                                                <th class="text-center">Rekomendasi</th>
                                                <th style="width: 180px;">Nilai Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            if (mysqli_num_rows($q_anggota_nilai) === 0): 
                                            ?>
                                                <tr><td colspan="8" class="text-center text-muted">Belum ada anggota siswa.</td></tr>
                                            <?php 
                                            else:
                                                while ($a = mysqli_fetch_assoc($q_anggota_nilai)):
                                                    $nis = $a['no_induk_siswa'];
                                                    
                                                    // Count Hadir
                                                    $q_present = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_presensi_ekskul WHERE id_ekskul = $id_ekskul_active AND no_induk_siswa = '$nis' AND status = 'Hadir'");
                                                    $present = mysqli_fetch_assoc($q_present)['total'] ?? 0;
                                                    
                                                    $pct = $total_meetings > 0 ? round(($present / $total_meetings) * 100, 1) : 0;
                                                    
                                                    // Dynamic recommendation
                                                    if ($pct >= 90) $rec_grade = 'A';
                                                    elseif ($pct >= 80) $rec_grade = 'B';
                                                    elseif ($pct >= 70) $rec_grade = 'C';
                                                    else $rec_grade = 'D';
                                                    
                                                    $current_val = $a['nilai'] ?? '';
                                            ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= htmlspecialchars($nis) ?></td>
                                                    <td class="fw-medium"><?= htmlspecialchars($a['nama_siswa']) ?></td>
                                                    <td><?= htmlspecialchars($a['kelas']) ?></td>
                                                    <td><?= $present ?> / <?= $total_meetings ?></td>
                                                    <td><?= $pct ?>%</td>
                                                    <td class="text-center"><span class="badge text-bg-secondary"><?= $rec_grade ?></span></td>
                                                    <td>
                                                        <select name="nilai[<?= htmlspecialchars($nis) ?>]" class="form-select form-select-sm">
                                                            <option value="" <?= $current_val === '' || $current_val === null ? 'selected' : '' ?>>-- Pilih --</option>
                                                            <option value="A" <?= $current_val === 'A' ? 'selected' : '' ?>>A (Sangat Baik)</option>
                                                            <option value="B" <?= $current_val === 'B' ? 'selected' : '' ?>>B (Baik)</option>
                                                            <option value="C" <?= $current_val === 'C' ? 'selected' : '' ?>>C (Cukup)</option>
                                                            <option value="D" <?= $current_val === 'D' ? 'selected' : '' ?>>D (Kurang)</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php 
                                                endwhile;
                                            endif; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <button type="submit" class="btn btn-primary px-4 py-2 mt-3 border-0 shadow-sm" style="background-color: #EC4899;">
                                    <i class="bi bi-save"></i> Simpan Nilai
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Group students by class
const studentsByClass = {
   <?php
   $q_siswa = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE status='Aktif' ORDER BY kelas, nama_siswa ASC");
   $grouped = [];
   while($s = mysqli_fetch_assoc($q_siswa)) {
       $grouped[$s['kelas']][] = [
           'no_induk' => $s['no_induk'],
           'nama_siswa' => $s['nama_siswa']
       ];
   }
   foreach($grouped as $kelas => $students) {
       echo json_encode((string)$kelas) . ": " . json_encode($students) . ",\n";
   }
   ?>
};

const selectKelas = document.getElementById('select_kelas');
if (selectKelas) {
    selectKelas.addEventListener('change', function() {
        const kelas = this.value;
        const selectSiswa = document.getElementById('select_siswa');
        if (selectSiswa) {
            selectSiswa.innerHTML = '<option value="">-- Pilih Siswa --</option>';
            if (kelas && studentsByClass[kelas]) {
                studentsByClass[kelas].forEach(student => {
                    const opt = document.createElement('option');
                    opt.value = student.no_induk;
                    opt.textContent = student.nama_siswa;
                    selectSiswa.appendChild(opt);
                });
            }
        }
    });
}
</script>
</body>
</html>
