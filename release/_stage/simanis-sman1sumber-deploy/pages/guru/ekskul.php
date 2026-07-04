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
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .nav-pills .nav-link.active { background-color: #EC4899; }
        .nav-pills .nav-link { color: #495057; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><a href="guru_2026.php" class="text-decoration-none text-dark"><i class="bi bi-arrow-left"></i></a> Manajemen Ekstrakurikuler</h4>
    </div>

    <?php if (empty($my_ekskul)): ?>
        <div class="alert alert-warning">
            Anda belum menjadi pembina ekstrakurikuler manapun. Silakan pilih ekskul yang Anda bina.
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white">Pilih Ekstrakurikuler</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="join_ekskul">
                    <select name="id_ekskul" class="form-select mb-3" required>
                        <option value="">-- Pilih Ekskul --</option>
                        <?php 
                        $q_all = mysqli_query($conn, "SELECT * FROM tbl_ekskul ORDER BY nama_ekskul ASC");
                        while($all = mysqli_fetch_assoc($q_all)) {
                            echo "<option value='".$all['id_ekskul']."'>".$all['nama_ekskul']."</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="background-color: #EC4899; border-color:#EC4899;">Jadi Pembina</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-2">
                        <select class="form-select border-0 shadow-none fw-bold" onchange="window.location='ekskul?id_ekskul='+this.value">
                            <?php foreach($my_ekskul as $ekskul): ?>
                                <option value="<?= $ekskul['id_ekskul'] ?>" <?= $ekskul['id_ekskul'] == $id_ekskul_active ? 'selected' : '' ?>><?= htmlspecialchars($ekskul['nama_ekskul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="nav flex-column nav-pills card shadow-sm p-2">
                    <a class="nav-link <?= $tab == 'dashboard' ? 'active' : '' ?>" href="ekskul?tab=dashboard&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link <?= $tab == 'anggota' ? 'active' : '' ?>" href="ekskul?tab=anggota&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-people"></i> Anggota Siswa</a>
                    <a class="nav-link <?= $tab == 'jadwal' ? 'active' : '' ?>" href="ekskul?tab=jadwal&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-calendar-event"></i> Jadwal Ekskul</a>
                    <a class="nav-link <?= $tab == 'jurnal' ? 'active' : '' ?>" href="ekskul?tab=jurnal&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-journal-check"></i> Jurnal & Kehadiran</a>
                    <a class="nav-link <?= $tab == 'tugas' ? 'active' : '' ?>" href="ekskul?tab=tugas&id_ekskul=<?= $id_ekskul_active ?>"><i class="bi bi-list-task"></i> Tugas Ekskul</a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($tab == 'dashboard'): ?>
                            <h5>Selamat Datang di Ekskul Anda</h5>
                            <p>Gunakan menu di samping untuk mengelola anggota, mengatur jadwal, dan menginput jurnal kehadiran ekstrakurikuler.</p>
                            
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

                            <table class="table table-striped">
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
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus siswa ini dari ekskul?')"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>

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

                            <table class="table table-bordered">
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
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>

                        <?php elseif ($tab == 'tugas'): ?>
                            <h5>Tugas Ekskul / Histori Tugas</h5>
                            <button class="btn btn-sm btn-primary mb-3" style="background-color: #EC4899; border-color:#EC4899;" data-bs-toggle="modal" data-bs-target="#modalTugas">+ Berikan Tugas Baru</button>
                            
                            <table class="table table-striped">
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
                                
                                <table class="table table-bordered">
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

                                <button type="submit" class="btn btn-primary" style="background-color: #EC4899; border-color:#EC4899;">Simpan Jurnal & Presensi</button>
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
