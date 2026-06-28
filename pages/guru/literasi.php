<?php
// pages/guru/literasi.php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$lembaga = data_lembaga();

// Check if teacher is marked as is_pendamping_literasi in tbl_guru
$qGuruLit = mysqli_query($conn, "SELECT is_pendamping_literasi FROM tbl_guru WHERE no_induk='$nipEsc' LIMIT 1");
$rowGuruLit = $qGuruLit ? mysqli_fetch_assoc($qGuruLit) : null;
$isPendampingLiterasi = $rowGuruLit && (int)$rowGuruLit['is_pendamping_literasi'] === 1;

// Handle POST/GET Actions for class management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kelas_pembina'])) {
    if (!$isPendampingLiterasi) {
        header('Location: literasi.php?error=unauthorized');
        exit;
    }
    $new_kelas = mysqli_real_escape_string($conn, $_POST['new_kelas']);
    if ($new_kelas !== '') {
        $cek = mysqli_query($conn, "SELECT id FROM tbl_literasi_ampuh WHERE no_induk_guru='$nipEsc' AND kelas='$new_kelas' AND id_sekolah=$idSekolah");
        if (mysqli_num_rows($cek) == 0) {
            mysqli_query($conn, "INSERT INTO tbl_literasi_ampuh (no_induk_guru, kelas, id_sekolah) VALUES ('$nipEsc', '$new_kelas', $idSekolah)");
        }
    }
    header('Location: literasi.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_class') {
    if (!$isPendampingLiterasi) {
        header('Location: literasi.php?error=unauthorized');
        exit;
    }
    $del_kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
    mysqli_query($conn, "DELETE FROM tbl_literasi_ampuh WHERE no_induk_guru='$nipEsc' AND kelas='$del_kelas' AND id_sekolah=$idSekolah");
    header('Location: literasi.php');
    exit;
}

// Get Classes Assigned to this Guru
$ampuh_q = mysqli_query($conn, "SELECT kelas FROM tbl_literasi_ampuh WHERE no_induk_guru='$nipEsc' AND id_sekolah=$idSekolah");
$ampuh_classes = [];
while ($row = mysqli_fetch_assoc($ampuh_q)) {
    $ampuh_classes[] = $row['kelas'];
}

// Guard: hanya pembina/pendamping literasi yang boleh mengakses halaman ini
if (!$isPendampingLiterasi && empty($ampuh_classes)) {
    header('Location: guru_2026.php?akses_ditolak=literasi');
    exit;
}

// Handle AJAX Requests
$action = $_GET['ajax'] ?? '';
if ($action === 'create_task') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe_media']);
    $durasi = (int)$_POST['durasi_minimal'];
    $batas_waktu = mysqli_real_escape_string($conn, $_POST['batas_waktu'] ?? '');
    $batas_waktu_sql = !empty($batas_waktu) ? "'$batas_waktu'" : "NULL";
    
    if (!in_array($kelas, $ampuh_classes)) {
        echo json_encode(['status'=>'error', 'message'=>'Akses kelas ditolak.']); exit;
    }
    
    $file_media = '';
    if ($tipe === 'video') {
        $file_media = mysqli_real_escape_string($conn, $_POST['url_youtube']);
    } else {
        if (isset($_FILES['file_media']) && $_FILES['file_media']['error'] === 0) {
            $ext = pathinfo($_FILES['file_media']['name'], PATHINFO_EXTENSION);
            $fname = "lit_" . time() . "_" . uniqid() . "." . $ext;
            $upload_dir = __DIR__ . '/../../uploads/literasi/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['file_media']['tmp_name'], $upload_dir . $fname);
            $file_media = 'uploads/literasi/' . $fname;
        } else {
            echo json_encode(['status'=>'error', 'message'=>'File wajib diupload']); exit;
        }
    }
    
    mysqli_query($conn, "INSERT INTO tbl_literasi_tugas (id_sekolah, no_induk_guru, kelas, judul, deskripsi, tipe_media, file_media, durasi_minimal, batas_waktu) VALUES ($idSekolah, '$nipEsc', '$kelas', '$judul', '$deskripsi', '$tipe', '$file_media', $durasi, $batas_waktu_sql)");
    $tugas_id = mysqli_insert_id($conn);
    
    // Process Soal
    if (isset($_POST['soal_pertanyaan']) && is_array($_POST['soal_pertanyaan'])) {
        foreach ($_POST['soal_pertanyaan'] as $i => $q) {
            $q = mysqli_real_escape_string($conn, $q);
            $a = mysqli_real_escape_string($conn, $_POST['soal_a'][$i]);
            $b = mysqli_real_escape_string($conn, $_POST['soal_b'][$i]);
            $c = mysqli_real_escape_string($conn, $_POST['soal_c'][$i]);
            $d = mysqli_real_escape_string($conn, $_POST['soal_d'][$i]);
            $ans = mysqli_real_escape_string($conn, $_POST['soal_ans'][$i]);
            
            mysqli_query($conn, "INSERT INTO tbl_literasi_soal (id_tugas, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar) VALUES ($tugas_id, '$q', '$a', '$b', '$c', '$d', '$ans')");
        }
    }
    
    echo json_encode(['status'=>'success']);
    exit;
}

if ($action === 'delete_task') {
    $id = (int)$_POST['id'];
    mysqli_query($conn, "DELETE FROM tbl_literasi_tugas WHERE id=$id AND no_induk_guru='$nipEsc'");
    mysqli_query($conn, "DELETE FROM tbl_literasi_soal WHERE id_tugas=$id");
    mysqli_query($conn, "DELETE FROM tbl_literasi_progress WHERE id_tugas=$id");
    echo json_encode(['status'=>'success']);
    exit;
}

if ($action === 'load_monitoring') {
    $id = (int)$_GET['id_tugas'];
    // Ambil Data Tugas
    $qTugas = mysqli_query($conn, "SELECT judul, kelas FROM tbl_literasi_tugas WHERE id=$id AND no_induk_guru='$nipEsc'");
    $tugas = mysqli_fetch_assoc($qTugas);
    if (!$tugas) { echo json_encode(['status'=>'error']); exit; }
    
    // Ambil Data Siswa di Kelas tsb + Progressnya
    $kelasEsc = mysqli_real_escape_string($conn, $tugas['kelas']);
    $qSiswa = mysqli_query($conn, "
        SELECT s.nama_siswa, p.status, p.skor_evaluasi as nilai, p.durasi_detik, p.skor_durasi, p.skor_literasi
        FROM tbl_siswa s
        LEFT JOIN tbl_literasi_progress p ON p.no_induk_siswa = s.no_induk AND p.id_tugas = $id
        WHERE s.kelas = '$kelasEsc' AND (s.status IS NULL OR UPPER(s.status)='AKTIF') AND s.id_sekolah=$idSekolah
        ORDER BY s.nama_siswa ASC
    ");
    
    $data = [];
    while($row = mysqli_fetch_assoc($qSiswa)) {
        $status = $row['status'] ?? 'Belum Mulai';
        $nilai = $row['nilai'] ?? '-';
        $durasi_detik = $row['durasi_detik'] ?? 0;
        $skor_durasi = $row['skor_durasi'] ?? 0;
        $skor_literasi = $row['skor_literasi'] ?? ($row['nilai'] ?? '-');
        
        $predikat = '-';
        $capaian = 'Belum ada data';
        
        if ($status === 'selesai') {
            if ($skor_literasi >= 90) { $predikat = 'A (Sangat Baik)'; $capaian = 'Pemahaman literasi sangat tinggi, waktu membaca cukup, mampu menyerap inti materi dengan sempurna.'; }
            else if ($skor_literasi >= 75) { $predikat = 'B (Baik)'; $capaian = 'Pemahaman literasi baik, waktu membaca mencukupi, mampu menangkap gagasan utama materi.'; }
            else if ($skor_literasi >= 60) { $predikat = 'C (Cukup)'; $capaian = 'Pemahaman literasi cukup, perlu meningkatkan fokus membaca.'; }
            else { $predikat = 'D (Kurang)'; $capaian = 'Kurang memahami isi materi literasi, perlu bimbingan membaca.'; }
        }
        
        $data[] = [
            'nama' => htmlspecialchars($row['nama_siswa']),
            'status' => strtoupper($status),
            'nilai' => $nilai,
            'durasi_detik' => (int)$durasi_detik,
            'skor_durasi' => (int)$skor_durasi,
            'skor_literasi' => $skor_literasi,
            'predikat' => $predikat,
            'capaian' => $capaian
        ];
    }
    
    echo json_encode(['status'=>'success', 'judul'=>$tugas['judul'], 'kelas'=>$tugas['kelas'], 'data'=>$data]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Guru - LENTERA Literasi</title>
  <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
  <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <style>
      .bg-lentera { background: linear-gradient(135deg, #0ea5e9, #2563eb); color: white; }
      .lentera-card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
      .progress-bar-animated { transition: width 1s ease-in-out; }
  </style>
</head>
<body id="page-top">
<div id="wrapper">
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow d-flex justify-content-between px-4">
                <a href="guru_2026.php" class="btn btn-outline-primary btn-sm rounded-pill font-weight-bold d-flex align-items-center px-3 shadow-sm" style="gap: 6px; width: fit-content;">
                    <i class="fas fa-chevron-circle-left" style="font-size: 1.1rem;"></i> Kembali
                </a>
                <h5 class="mt-2 font-weight-bold mb-0 text-right" style="color:#0ea5e9; font-size:1.1rem;"><i class="fas fa-book-reader"></i> Literasi SIMANIS</h5>
            </nav>

            <div class="container-fluid">
                <?php if ($isPendampingLiterasi): ?>
                <!-- Kelas Ampuhan Literasi Anda (Hanya untuk Pendamping Literasi) -->
                <div class="card lentera-card mb-4 border-left-info shadow">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" style="border-radius: 12px 12px 0 0;">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-tasks mr-2"></i>Kelas Ampuhan Literasi Anda</h6>
                        <small class="bg-white text-info font-weight-bold px-2 py-1" style="border-radius:8px;">Mandiri</small>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <form method="POST" action="?action=save_classes" class="form-inline">
                                    <div class="form-group mr-2">
                                        <select name="new_kelas" class="form-control form-control-sm" required style="border-radius:8px; min-width:180px;">
                                            <option value="">-- Pilih Kelas --</option>
                                            <?php
                                            $qAllKls = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_sekolah=$idSekolah ORDER BY kelas");
                                            while($k = mysqli_fetch_assoc($qAllKls)) {
                                                if (!in_array($k['kelas'], $ampuh_classes)) {
                                                    echo "<option value='".htmlspecialchars($k['kelas'])."'>".htmlspecialchars($k['kelas'])."</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="add_kelas_pembina" class="btn btn-sm btn-info" style="border-radius:8px;"><i class="fas fa-plus mr-1"></i> Tambah Kelas</button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($ampuh_classes)): ?>
                                    <div class="d-flex flex-wrap" style="gap: 8px;">
                                        <?php foreach($ampuh_classes as $c): ?>
                                            <span class="badge badge-info p-2 d-inline-flex align-items-center" style="font-size:12px; border-radius:10px; gap: 8px;">
                                                <?= htmlspecialchars($c) ?>
                                                <a href="?action=delete_class&kelas=<?= urlencode($c) ?>" class="text-white" onclick="return confirm('Hapus kelas <?= htmlspecialchars($c) ?> dari ampuhan literasi Anda?')" style="text-decoration:none;"><i class="fas fa-times-circle"></i></a>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:13px;"><i class="fas fa-info-circle mr-1"></i> Belum ada kelas yang ditambahkan. Silahkan pilih kelas di sebelah kiri untuk mulai menambahkan kelas ampuhan Anda.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($ampuh_classes)): ?>
                    <div class="alert alert-warning py-2 px-3 d-flex align-items-center gap-2" style="font-size:.85rem; border-radius:8px;">
                        <i class="fas fa-exclamation-triangle text-warning me-2" style="font-size:1rem; flex-shrink:0;"></i>
                        <div>
                            <strong>Akses Dibatasi.</strong>
                            Anda belum memiliki kelas yang diampu untuk modul Literasi. <?= $isPendampingLiterasi ? 'Silahkan pilih kelas pada modul <em>"Kelas Ampuhan Literasi Anda"</em> di atas.' : 'Hubungi Administrator.'; ?>
                        </div>
                    </div>
                <?php else: ?>
                
                <div class="row">
                    <!-- CREATE TASK FORM -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card lentera-card mb-4">
                            <div class="card-header bg-lentera d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-white">Buat Misi Literasi Baru</h6>
                            </div>
                            <div class="card-body">
                                <form id="formCreateTask" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Judul Literasi</label>
                                        <input type="text" class="form-control" name="judul" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi/Instruksi</label>
                                        <textarea class="form-control" name="deskripsi" rows="2" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Target Kelas</label>
                                        <select class="form-control" name="kelas" required>
                                            <option value="">-- Pilih Kelas --</option>
                                            <?php foreach($ampuh_classes as $c): ?>
                                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipe Media</label>
                                        <select class="form-control" name="tipe_media" id="tipeMedia" required>
                                            <option value="pdf">Dokumen PDF</option>
                                            <option value="gambar">Gambar/Infografis</option>
                                            <option value="video">Video YouTube</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="containerFile">
                                        <label>Unggah File (PDF/Gambar)</label>
                                        <input type="file" class="form-control-file" name="file_media" id="fileMediaInput">
                                    </div>
                                    <div class="form-group d-none" id="containerYt">
                                        <label>Link YouTube</label>
                                        <input type="text" class="form-control" name="url_youtube" placeholder="https://www.youtube.com/watch?v=...">
                                        <small class="text-muted">Anti-Skip Video akan aktif otomatis dengan menonaktifkan kontrol.</small>
                                    </div>
                                    <div class="form-group" id="containerDurasi">
                                        <label>Minimal Waktu Baca (Detik)</label>
                                        <input type="number" class="form-control" name="durasi_minimal" value="180">
                                        <small class="text-danger">Siswa tidak bisa mengerjakan evaluasi sebelum waktu ini habis.</small>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label>Batas Waktu Literasi (Opsional)</label>
                                        <input type="datetime-local" class="form-control" name="batas_waktu">
                                        <small class="text-muted">Tenggat pengerjaan literasi bagi siswa.</small>
                                    </div>
                                    
                                    <hr>
                                    <h6 class="font-weight-bold">Soal Evaluasi (Auto-Grading)</h6>
                                    <div id="soalContainer"></div>
                                    <button type="button" class="btn btn-sm btn-outline-info mb-3 w-100" onclick="addSoal()"><i class="fas fa-plus"></i> Tambah Soal Pilihan Ganda</button>
                                    
                                    <button type="submit" class="btn btn-primary w-100 btn-lg shadow"><i class="fas fa-paper-plane"></i> Publikasikan Misi</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- TASKS LIST & MONITORING -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card lentera-card mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Daftar Misi & Monitoring Siswa</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tableTugas">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Misi Literasi</th>
                                                <th>Kelas</th>
                                                <th>Tipe</th>
                                                <th>Progres Kelas</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $tugas_q = mysqli_query($conn, "
                                                SELECT t.*, 
                                                    (SELECT COUNT(no_induk) FROM tbl_siswa WHERE kelas=t.kelas AND (status IS NULL OR UPPER(status)='AKTIF') AND id_sekolah=$idSekolah) as total_siswa,
                                                    (SELECT COUNT(id) FROM tbl_literasi_progress WHERE id_tugas=t.id AND status='selesai') as total_selesai
                                                FROM tbl_literasi_tugas t 
                                                WHERE t.no_induk_guru='$nipEsc' AND t.id_sekolah=$idSekolah 
                                                ORDER BY t.id DESC
                                            ");
                                            while($t = mysqli_fetch_assoc($tugas_q)): 
                                                $pct = $t['total_siswa'] > 0 ? round(($t['total_selesai'] / $t['total_siswa']) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($t['judul']) ?></strong><br>
                                                    <small class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></small>
                                                </td>
                                                <td><span class="badge badge-info px-2 py-1" style="color: #000; font-weight: bold;"><?= htmlspecialchars($t['kelas']) ?></span></td>
                                                <td><span class="badge badge-secondary text-uppercase" style="color: #000; font-weight: bold;"><?= htmlspecialchars($t['tipe_media']) ?></span></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="mr-2"><?= $pct ?>%</span>
                                                        <div class="progress" style="width: 100%; height:8px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%"></div>
                                                        </div>
                                                    </div>
                                                    <small><?= $t['total_selesai'] ?> / <?= $t['total_siswa'] ?> Siswa Selesai</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="viewMonitoring(<?= $t['id'] ?>)"><i class="fas fa-chart-bar"></i> Monitor</button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteTask(<?= $t['id'] ?>)"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Realtime Monitor Panel (Hidden by Default) -->
                        <div class="card lentera-card mb-4 d-none" id="monitorPanel">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold" id="monitorTitle">Monitor Kelas</h6>
                                <button class="btn btn-sm btn-outline-light" onclick="$('#monitorPanel').addClass('d-none')"><i class="fas fa-times"></i> Tutup</button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="tableMonitorSiswa">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Nama Siswa</th>
                                                <th>Status</th>
                                                <th class="text-center">Durasi Baca</th>
                                                <th class="text-center">Nilai Kuis</th>
                                                <th class="text-center">Nilai Akhir</th>
                                                <th>Predikat</th>
                                                <th>Deskripsi Capaian</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../../vendor/jquery/jquery.min.js"></script>
<script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    $('#tipeMedia').change(function(){
        let v = $(this).val();
        if(v === 'video') {
            $('#containerFile').addClass('d-none');
            $('#fileMediaInput').removeAttr('required');
            $('#containerYt').removeClass('d-none');
            $('input[name="url_youtube"]').attr('required', true);
            $('#containerDurasi').addClass('d-none'); // video doesn't need manual timer, handled by YT API
        } else {
            $('#containerYt').addClass('d-none');
            $('input[name="url_youtube"]').removeAttr('required');
            $('#containerFile').removeClass('d-none');
            $('#fileMediaInput').attr('required', true);
            $('#containerDurasi').removeClass('d-none');
        }
    });

    let soalIdx = 0;
    function addSoal() {
        soalIdx++;
        let html = `
            <div class="p-3 border rounded mb-3 bg-light" id="soalBox_${soalIdx}">
                <div class="d-flex justify-content-between mb-2">
                    <label class="font-weight-bold text-primary">Pertanyaan ${soalIdx}</label>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="$('#soalBox_${soalIdx}').remove()"><i class="fas fa-times"></i></button>
                </div>
                <textarea class="form-control mb-2" name="soal_pertanyaan[]" placeholder="Ketik soal..." required></textarea>
                <div class="row">
                    <div class="col-6 mb-1"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">A</span></div><input type="text" class="form-control" name="soal_a[]" required></div></div>
                    <div class="col-6 mb-1"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">B</span></div><input type="text" class="form-control" name="soal_b[]" required></div></div>
                    <div class="col-6 mb-1"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">C</span></div><input type="text" class="form-control" name="soal_c[]" required></div></div>
                    <div class="col-6 mb-1"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">D</span></div><input type="text" class="form-control" name="soal_d[]" required></div></div>
                </div>
                <div class="mt-2">
                    <label>Kunci Jawaban</label>
                    <select class="form-control w-25 d-inline" name="soal_ans[]" required>
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                    </select>
                </div>
            </div>
        `;
        $('#soalContainer').append(html);
    }
    
    // Default 1 soal
    addSoal();

    $('#formCreateTask').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Menyimpan...');
        
        let formData = new FormData(this);
        $.ajax({
            url: '?ajax=create_task',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('Publikasikan Misi');
                }
            }
        });
    });

    function deleteTask(id) {
        if(confirm('Hapus misi ini? Data progres siswa juga akan terhapus.')) {
            $.post('?ajax=delete_task', {id: id}, function(res) {
                if(res.status === 'success') location.reload();
            }, 'json');
        }
    }
    
    function viewMonitoring(id_tugas) {
        let tbody = $('#tableMonitorSiswa tbody');
        tbody.html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
        $('#monitorPanel').removeClass('d-none');
        
        $.getJSON('?ajax=load_monitoring&id_tugas=' + id_tugas, function(res) {
            if(res.status === 'success') {
                $('#monitorTitle').text('Monitor: ' + res.judul + ' (' + res.kelas + ')');
                tbody.empty();
                if(res.data.length === 0) {
                    tbody.html('<tr><td colspan="7" class="text-center">Tidak ada siswa di kelas ini.</td></tr>');
                    return;
                }
                res.data.forEach(function(d) {
                    let badge = 'badge-secondary';
                    if(d.status === 'SELESAI') badge = 'badge-success';
                    if(d.status === 'MEMBACA') badge = 'badge-warning';
                    
                    let durasiStr = '-';
                    if(d.status === 'SELESAI') {
                        let mins = Math.floor(d.durasi_detik / 60);
                        let secs = d.durasi_detik % 60;
                        durasiStr = `${mins}m ${secs}s (Skor: ${d.skor_durasi})`;
                    }
                    
                    let tr = `<tr>
                        <td>${d.nama}</td>
                        <td><span class="badge ${badge}">${d.status}</span></td>
                        <td class="text-center">${durasiStr}</td>
                        <td class="font-weight-bold text-center">${d.nilai}</td>
                        <td class="font-weight-bold text-center text-primary">${d.skor_literasi}</td>
                        <td>${d.predikat}</td>
                        <td><small>${d.capaian}</small></td>
                    </tr>`;
                    tbody.append(tr);
                });
            } else {
                alert('Gagal memuat data monitoring');
            }
        });
    }
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
