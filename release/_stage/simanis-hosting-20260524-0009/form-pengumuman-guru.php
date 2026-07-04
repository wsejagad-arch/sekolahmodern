<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'koneksi.php';
require_once 'functions.php';

// Check if user is logged in and is a guru (using same logic as guru.php)
if (!isset($_SESSION["no_induk"])) {
    header('Location: ../../index.php?haruslogin');
    exit;
} else if($_SESSION['hak_akses'] != 2) {
    header('Location: ../../403.php');
    exit;
}

$no_induk_guru = $_SESSION['no_induk'] ?? '';

// Get teacher's assigned classes for filtering
$kelasOptions = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$no_induk_guru' ORDER BY kelas");
while ($row = mysqli_fetch_assoc($qKelas)) {
    $kelasOptions[] = $row['kelas'];
}

$msg = '';
$err = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $penting = isset($_POST['penting']) ? 1 : 0;
    $mulai = $_POST['mulai'] ?? '';
    $selesai = $_POST['selesai'] ?? '';
    $target_scope = 'KELAS';
    $target_value = trim($_POST['target_value'] ?? '');
    
    // Validate input
    if (empty($judul) || empty($isi) || empty($mulai) || empty($selesai) || empty($target_value)) {
        $err = 'Semua field harus diisi';
    } elseif (!in_array($target_value, $kelasOptions)) {
        $err = 'Anda hanya dapat mengirim pengumuman ke kelas yang Anda ampu';
    } else {
        // Handle file upload
        $lampiran = null;
        if (!empty($_FILES['lampiran']['name'])) {
            $fname = $_FILES['lampiran']['name'];
            $tmp = $_FILES['lampiran']['tmp_name'];
            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            
            if ($ext === 'pdf') {
                $new = 'GURU_' . date('Ymd_His') . '_' . substr(md5($fname . microtime()), 0, 6) . '.pdf';
                $destDir = __DIR__ . '/materi';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }
                
                if (move_uploaded_file($tmp, $destDir . '/' . $new)) {
                    $lampiran = $new;
                }
            }
        }
        
        // Insert announcement
        $judulEsc = mysqli_real_escape_string($conn, $judul);
        $isiEsc = mysqli_real_escape_string($conn, $isi);
        $mulaiEsc = mysqli_real_escape_string($conn, $mulai);
        $selesaiEsc = mysqli_real_escape_string($conn, $selesai);
        $targetValueEsc = mysqli_real_escape_string($conn, $target_value);
        $createdByEsc = mysqli_real_escape_string($conn, $no_induk_guru);
        $lampiranEsc = $lampiran ? "'" . mysqli_real_escape_string($conn, $lampiran) . "'" : 'NULL';
        
        $sql = "INSERT INTO tbl_pengumuman (judul, isi, penting, mulai, selesai, target_scope, target_value, lampiran, created_by) 
                VALUES ('$judulEsc', '$isiEsc', $penting, '$mulaiEsc', '$selesaiEsc', 'KELAS', '$targetValueEsc', $lampiranEsc, '$createdByEsc')";
        
        if (mysqli_query($conn, $sql)) {
            $msg = 'Pengumuman berhasil dikirim ke kelas ' . htmlspecialchars($target_value);
            // Clear form
            $_POST = [];
        } else {
            $err = 'Gagal menyimpan pengumuman: ' . mysqli_error($conn);
        }
    }
}

// Get teacher's announcements
$announcements = [];
$qAnn = mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE created_by = '$no_induk_guru' ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($qAnn)) {
    $announcements[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengumuman Guru</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 1000px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%); }
        .guru-info { background: #e3f2fd; border: 1px solid #81d4fa; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .announcement-item { border-left: 4px solid #667eea; padding: 15px; margin-bottom: 10px; background: white; border-radius: 0 10px 10px 0; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h2 class="text-center mb-4">
                    <i class="fas fa-bullhorn me-2"></i>Form Pengumuman Guru
                </h2>
                
                <div class="guru-info">
                    <h5><i class="fas fa-user-tie me-2"></i>Informasi Guru</h5>
                    <p class="mb-2"><strong>No. Induk:</strong> <?= htmlspecialchars($no_induk_guru); ?></p>
                    <p class="mb-0"><strong>Kelas yang Diampu:</strong> 
                        <?php if ($kelasOptions): ?>
                            <span class="badge bg-primary me-1"><?= implode('</span> <span class="badge bg-primary me-1">', array_map('htmlspecialchars', $kelasOptions)); ?></span>
                        <?php else: ?>
                            <span class="text-warning">Tidak ada kelas yang diampu</span>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($err): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $err; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Buat Pengumuman Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="judul" class="form-label">Judul Pengumuman</label>
                                    <input type="text" class="form-control" id="judul" name="judul" 
                                           value="<?= htmlspecialchars($_POST['judul'] ?? ''); ?>" 
                                           placeholder="Masukkan judul pengumuman..." maxlength="150" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="target_value" class="form-label">Target Kelas</label>
                                    <select class="form-select" id="target_value" name="target_value" required>
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($kelasOptions as $kelas): ?>
                                            <option value="<?= htmlspecialchars($kelas); ?>" 
                                                    <?= (($_POST['target_value'] ?? '') === $kelas) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($kelas); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mulai" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="mulai" name="mulai" 
                                           value="<?= htmlspecialchars($_POST['mulai'] ?? date('Y-m-d')); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="selesai" class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" id="selesai" name="selesai" 
                                           value="<?= htmlspecialchars($_POST['selesai'] ?? date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="isi" class="form-label">Isi Pengumuman</label>
                                <textarea class="form-control" id="isi" name="isi" rows="5" 
                                          placeholder="Tulis isi pengumuman untuk siswa..." required><?= htmlspecialchars($_POST['isi'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="lampiran" class="form-label">Lampiran (PDF - Opsional)</label>
                                    <input type="file" class="form-control" id="lampiran" name="lampiran" accept=".pdf">
                                    <div class="form-text">Format: PDF, maksimal 10MB</div>
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="checkbox" id="penting" name="penting" 
                                               <?= isset($_POST['penting']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="penting">
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>Pengumuman Penting
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Pengumuman
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Pengumuman Saya (<?= count($announcements); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($announcements): ?>
                            <?php foreach ($announcements as $ann): ?>
                                <div class="announcement-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2">
                                                <?= htmlspecialchars($ann['judul']); ?>
                                                <?php if ($ann['penting']): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Penting</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-2 text-muted small"><?= nl2br(htmlspecialchars(substr($ann['isi'], 0, 150))); ?>...</p>
                                            <div class="small text-muted">
                                                <i class="fas fa-users me-1"></i><strong>Target:</strong> <?= htmlspecialchars($ann['target_value']); ?>
                                                <span class="ms-3"><i class="fas fa-calendar me-1"></i><?= $ann['mulai']; ?> - <?= $ann['selesai']; ?></span>
                                                <?php if ($ann['lampiran']): ?>
                                                    <span class="ms-3">
                                                        <a href="materi/<?= htmlspecialchars($ann['lampiran']); ?>" target="_blank" class="text-decoration-none">
                                                            <i class="fas fa-file-pdf text-danger me-1"></i>Lampiran
                                                        </a>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada pengumuman yang dibuat</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href=<?= guru_page('guru') ?> class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>