<?php
// pages/admin/clear-cache.php
if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    echo "<h1>Akses Ditolak</h1>";
    exit;
}

$flash = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_all') {
    $cleared_logs = 0;
    $cleared_temp = 0;
    $cleared_db   = 0;
    
    // 1. Clear database outbox (sent only)
    if (isset($conn) && $conn instanceof mysqli) {
        $q = @mysqli_query($conn, "DELETE FROM tbl_notifikasi_outbox WHERE status = 'sent'");
        if ($q) {
            $cleared_db = mysqli_affected_rows($conn);
        }
    }
    
    // 2. Clear .log files in logs/ directory
    $log_dir = __DIR__ . '/../../logs/';
    if (is_dir($log_dir)) {
        $files = glob($log_dir . '*.log');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $cleared_logs++;
                }
            }
        }
    }
    
    // 3. Clear .png files in temp/ directory
    $temp_dir = __DIR__ . '/../../temp/';
    if (is_dir($temp_dir)) {
        $files = glob($temp_dir . '*.png');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $cleared_temp++;
                }
            }
        }
    }
    
    $flash = [
        'type' => 'success', 
        'msg' => "✅ Pembersihan Berhasil! <br>🗑️ $cleared_db data WA dihapus<br>🗑️ $cleared_logs file log dihapus<br>🗑️ $cleared_temp file temp dihapus"
    ];
}
?>

<div class="container-fluid px-3 px-md-4 pb-4">
    <!-- PAGE HEADING -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0 text-gray-800">
            <i class="fas fa-broom mr-2 text-warning"></i>
            Bersihkan Log & Cache
        </h1>
    </div>

    <!-- FLASH -->
    <?php if ($flash['msg']): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $flash['msg'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="border-radius: 12px; max-width: 600px;">
        <div class="card-header bg-white border-0 py-3" style="border-radius: 12px 12px 0 0;">
            <h6 class="m-0 font-weight-bold text-primary">Pemeliharaan Sistem</h6>
        </div>
        <div class="card-body">
            <p class="text-muted" style="font-size: 0.9rem;">
                Tombol di bawah ini akan melakukan pembersihan sampah sistem untuk mengurangi beban server:
            </p>
            <ul style="font-size: 0.85rem; color: #555;">
                <li>Menghapus riwayat notifikasi WhatsApp yang <strong>sudah terkirim</strong>.</li>
                <li>Menghapus kumpulan file error (<code>.log</code>) yang ukurannya membengkak.</li>
                <li>Menghapus gambar QR code sementara (<code>.png</code>) yang sudah tidak digunakan.</li>
            </ul>
            <hr>
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan semua log dan cache sekarang?');">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="btn btn-warning shadow-sm font-weight-bold w-100" style="border-radius: 8px;">
                    <i class="fas fa-trash-alt mr-2"></i> Eksekusi Pembersihan
                </button>
            </form>
        </div>
    </div>
</div>
