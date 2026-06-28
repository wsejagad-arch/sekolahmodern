<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\validasi-izin.php';

$content = <<<HTML
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}
require_once __DIR__ . '/../../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

\$nip = \$_SESSION['username']; // For Guru, no_induk/username is usually their NIP

// Get classes where this teacher is Wali Kelas
\$qKelas = mysqli_query(\$conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='\$nip'");
\$kelas_wali = [];
while (\$row = mysqli_fetch_assoc(\$qKelas)) {
    \$kelas_wali[] = \$row['kelas'];
}

// Handle Validation Action
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action'])) {
    \$id_izin = (int)\$_POST['id_izin'];
    \$action = \$_POST['action'];
    \$nama_guru = \$_SESSION['nama'];
    
    if (\$action === 'acc_wali') {
        \$q = "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Disetujui', validator_wali_kelas = '\$nama_guru', waktu_validasi_wali_kelas = NOW() WHERE id_izin = \$id_izin";
        mysqli_query(\$conn, \$q);
        \$msg = "Izin berhasil disetujui.";
    } elseif (\$action === 'tolak_wali') {
        \$q = "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Ditolak', validator_wali_kelas = '\$nama_guru', waktu_validasi_wali_kelas = NOW(), status_izin = 'Ditolak' WHERE id_izin = \$id_izin";
        mysqli_query(\$conn, \$q);
        \$msg = "Izin berhasil ditolak.";
    }
}

\$kelas_in = "'" . implode("','", array_map(function(\$k) use (\$conn) { return mysqli_real_escape_string(\$conn, \$k); }, \$kelas_wali)) . "'";

// Fetch pending izin for these classes
\$list_izin = [];
if (!empty(\$kelas_wali)) {
    \$qIzin = mysqli_query(\$conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE s.kelas IN (\$kelas_in) AND i.validasi_wali_kelas = 'Menunggu' ORDER BY i.waktu_pengajuan DESC");
    while (\$row = mysqli_fetch_assoc(\$qIzin)) {
        \$list_izin[] = \$row;
    }
}

\$title = 'Validasi Izin Siswa';
include __DIR__ . '/guru_header_new.php';
?>

<div class="container py-4">
    <h2 class="fw-bold mb-4">Validasi Izin Siswa</h2>
    
    <?php if(isset(\$msg)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars(\$msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(empty(\$kelas_wali)): ?>
    <div class="alert alert-warning">Anda tidak terdaftar sebagai Wali Kelas untuk kelas manapun saat ini.</div>
    <?php elseif(empty(\$list_izin)): ?>
    <div class="alert alert-info">Tidak ada pengajuan izin yang menunggu validasi Anda.</div>
    <?php else: ?>
    <div class="row">
        <?php foreach(\$list_izin as \$izin): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title fw-bold text-primary mb-0"><?= htmlspecialchars(\$izin['nama_siswa']) ?></h5>
                        <span class="badge bg-secondary"><?= htmlspecialchars(\$izin['kelas_siswa']) ?></span>
                    </div>
                    <p class="text-muted small mb-3"><i class="fas fa-clock"></i> <?= date('d M Y, H:i', strtotime(\$izin['waktu_pengajuan'])) ?></p>
                    
                    <ul class="list-unstyled mb-3">
                        <li><strong>Kategori:</strong> <?= htmlspecialchars(\$izin['kategori_pengajuan']) ?></li>
                        <li><strong>Jenis:</strong> <?= htmlspecialchars(\$izin['jenis_izin']) ?></li>
                        <li><strong>Keterangan:</strong> <?= htmlspecialchars(\$izin['detail_izin']) ?></li>
                        <?php if (\$izin['kategori_pengajuan'] === 'Keluar Sekolah'): ?>
                        <li><strong>Opsi Kembali:</strong> <?= htmlspecialchars(\$izin['opsi_kembali'] ?: '-') ?></li>
                        <?php endif; ?>
                    </ul>

                    <?php if (!empty(\$izin['foto_selfie'])): ?>
                    <div class="mb-3">
                        <img src="../../uploads/izin/<?= htmlspecialchars(\$izin['foto_selfie']) ?>" class="img-fluid rounded" alt="Bukti Foto" style="max-height:150px; object-fit:cover;">
                    </div>
                    <?php endif; ?>
                    
                </div>
                <div class="card-footer bg-white border-top-0 d-flex gap-2">
                    <form method="POST" class="w-50">
                        <input type="hidden" name="id_izin" value="<?= \$izin['id_izin'] ?>">
                        <input type="hidden" name="action" value="acc_wali">
                        <button type="submit" class="btn btn-success w-100 fw-bold" onclick="return confirm('Setujui izin ini?')"><i class="fas fa-check"></i> Setujui</button>
                    </form>
                    <form method="POST" class="w-50">
                        <input type="hidden" name="id_izin" value="<?= \$izin['id_izin'] ?>">
                        <input type="hidden" name="action" value="tolak_wali">
                        <button type="submit" class="btn btn-danger w-100 fw-bold" onclick="return confirm('Tolak izin ini?')"><i class="fas fa-times"></i> Tolak</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/guru_footer_new.php'; ?>
HTML;

file_put_contents($file, $content);
echo "Berhasil update validasi-izin.php";
?>
