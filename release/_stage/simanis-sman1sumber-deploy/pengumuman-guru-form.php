<?php
// Simplified form for modal - no full HTML structure
session_start();

// Check authentication
if (!isset($_SESSION["no_induk"]) || $_SESSION['hak_akses'] != 2) {
    echo '<div class="alert alert-danger">Akses ditolak. Silakan login sebagai guru.</div>';
    exit;
}

require_once 'koneksi.php';
require_once 'functions.php';

$no_induk_guru = $_SESSION['no_induk'];
$msg = '';
$err = '';

// Get teacher's assigned classes
$kelasOptions = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$no_induk_guru' ORDER BY kelas");
while ($row = mysqli_fetch_assoc($qKelas)) {
    $kelasOptions[] = $row['kelas'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $penting = isset($_POST['penting']) ? 1 : 0;
    $mulai = $_POST['mulai'] ?? '';
    $selesai = $_POST['selesai'] ?? '';
    $target_value = trim($_POST['target_value'] ?? '');
    
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
            // Clear form data
            $_POST = [];
        } else {
            $err = 'Gagal menyimpan pengumuman: ' . mysqli_error($conn);
        }
    }
}
?>

<style>
.pengumuman-form .form-control, .pengumuman-form .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
}
.pengumuman-form .form-control:focus, .pengumuman-form .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}
.guru-info-card {
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    border: 1px solid #81d4fa;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}
</style>

<div class="pengumuman-form">
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?= $err; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="guru-info-card">
        <h6 class="mb-2"><i class="bi bi-person-badge me-2"></i>Informasi Guru</h6>
        <p class="mb-1 small"><strong>No. Induk:</strong> <?= htmlspecialchars($no_induk_guru); ?></p>
        <p class="mb-0 small"><strong>Kelas yang Diampu:</strong> 
            <?php if ($kelasOptions): ?>
                <?php foreach ($kelasOptions as $kelas): ?>
                    <span class="badge bg-primary me-1"><?= htmlspecialchars($kelas); ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-warning">Tidak ada kelas yang diampu</span>
            <?php endif; ?>
        </p>
    </div>

    <form method="POST" enctype="multipart/form-data" id="formPengumumanGuru">
        <div class="row">
            <div class="col-md-8 mb-3">
                <label for="judul" class="form-label fw-semibold">Judul Pengumuman <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" 
                       value="<?= htmlspecialchars($_POST['judul'] ?? ''); ?>" 
                       placeholder="Masukkan judul pengumuman..." maxlength="150" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="target_value" class="form-label fw-semibold">Target Kelas <span class="text-danger">*</span></label>
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
                <label for="mulai" class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="mulai" name="mulai" 
                       value="<?= htmlspecialchars($_POST['mulai'] ?? date('Y-m-d')); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="selesai" class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="selesai" name="selesai" 
                       value="<?= htmlspecialchars($_POST['selesai'] ?? date('Y-m-d')); ?>" required>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="isi" class="form-label fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
            <textarea class="form-control" id="isi" name="isi" rows="4" 
                      placeholder="Tulis isi pengumuman untuk siswa..." required><?= htmlspecialchars($_POST['isi'] ?? ''); ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-8 mb-3">
                <label for="lampiran" class="form-label fw-semibold">Lampiran PDF (Opsional)</label>
                <input type="file" class="form-control" id="lampiran" name="lampiran" accept=".pdf">
                <div class="form-text">Format: PDF, maksimal 10MB</div>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="penting" name="penting" 
                           <?= isset($_POST['penting']) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="penting">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>Pengumuman Penting
                    </label>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i>Batal
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-paper-plane me-1"></i>Kirim Pengumuman
            </button>
        </div>
    </form>
</div>

<script>
// Handle form submission via AJAX to stay in modal
$('#formPengumumanGuru').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '../../pengumuman-guru-form.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Reload the form content to show success/error messages
            $('#pengumumanForm').html(response);
        },
        error: function() {
            $('#pengumumanForm').html('<div class="alert alert-danger">Terjadi kesalahan saat mengirim pengumuman.</div>');
        }
    });
});
</script>