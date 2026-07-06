<?php
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    echo "Akses Ditolak";
    exit;
}

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$flash = '';
$flashType = 'success';

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_mapping'])) {
        $no_induk_guru = mysqli_real_escape_string($conn, $_POST['no_induk_guru']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        
        if (empty($no_induk_guru) || empty($kelas)) {
            $flash = 'Pilih guru dan kelas terlebih dahulu!';
            $flashType = 'danger';
        } else {
            $cek = mysqli_query($conn, "SELECT id FROM tbl_literasi_ampuh WHERE no_induk_guru='$no_induk_guru' AND kelas='$kelas' AND id_sekolah=$idSekolah");
            if (mysqli_num_rows($cek) == 0) {
                mysqli_query($conn, "INSERT INTO tbl_literasi_ampuh (no_induk_guru, kelas, id_sekolah) VALUES ('$no_induk_guru', '$kelas', $idSekolah)");
                $flash = 'Guru berhasil ditambahkan sebagai Pembina Literasi untuk kelas ' . htmlspecialchars($_POST['kelas']) . '!';
                $flashType = 'success';
            } else {
                $flash = 'Mapping sudah ada! Guru ini sudah menjadi pembina untuk kelas tersebut.';
                $flashType = 'warning';
            }
        }
    }
    
    if (isset($_POST['delete_mapping'])) {
        $id = (int)$_POST['id_mapping'];
        mysqli_query($conn, "DELETE FROM tbl_literasi_ampuh WHERE id=$id AND id_sekolah=$idSekolah");
        $flash = 'Hak akses Pembina Literasi berhasil dihapus.';
        $flashType = 'warning';
    }
}

// Fetch Data
$gurus = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE status='Aktif' AND id_sekolah=$idSekolah ORDER BY nama_guru");
$kelases = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_sekolah=$idSekolah ORDER BY kelas");

// Mappings grouped by guru
$mappings = mysqli_query($conn, "
    SELECT a.id, a.kelas, g.nama_guru, g.no_induk 
    FROM tbl_literasi_ampuh a
    JOIN tbl_guru g ON a.no_induk_guru = g.no_induk
    WHERE a.id_sekolah=$idSekolah
    ORDER BY g.nama_guru ASC, a.kelas ASC
");
$mappingRows = [];
while ($m = mysqli_fetch_assoc($mappings)) {
    $mappingRows[] = $m;
}

// Get guru IDs that are already pembina
$pembina_nips = array_unique(array_column($mappingRows, 'no_induk'));
$totalPembina = count($pembina_nips);
$totalMapping = count($mappingRows);
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flashType ?> alert-dismissible fade show shadow-sm" role="alert" style="border-radius:12px;">
    <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : ($flashType === 'danger' ? 'times-circle' : 'exclamation-triangle') ?> mr-2"></i>
    <?= $flash ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                <i class="fas fa-book-reader text-primary mr-2"></i> LENTERA: Pembina Literasi
            </h1>
            <p class="text-muted mb-0" style="font-size:13px;">Atur guru-guru yang berhak mengakses dan mengelola modul LENTERA Literasi</p>
        </div>
        <div class="d-flex gap-2" style="gap:10px;">
            <div class="card border-0 shadow-sm px-3 py-2 text-center" style="border-radius:12px; min-width:100px; border-left:4px solid #3b82f6 !important;">
                <div style="font-size:22px; font-weight:900; color:#1d4ed8;"><?= $totalPembina ?></div>
                <div style="font-size:11px; color:#64748b; font-weight:700;">Guru Pembina</div>
            </div>
            <div class="card border-0 shadow-sm px-3 py-2 text-center" style="border-radius:12px; min-width:100px; border-left:4px solid #10b981 !important;">
                <div style="font-size:22px; font-weight:900; color:#047857;"><?= $totalMapping ?></div>
                <div style="font-size:11px; color:#64748b; font-weight:700;">Total Penugasan</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Tambah -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0" style="border-radius:16px; overflow:hidden;">
                <div style="background:linear-gradient(135deg,#3b82f6,#1d4ed8); padding:18px 20px;">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-plus-circle mr-2"></i>Tambah Pembina Literasi</h6>
                    <p class="mb-0 mt-1" style="font-size:11px; color:rgba(255,255,255,0.8);">Pilih guru dan kelas ampuhan literasinya</p>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label style="font-size:12px; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Guru Pembina</label>
                            <select name="no_induk_guru" class="form-control" required style="border-radius:10px; border-color:#e5e7eb; font-size:14px;">
                                <option value="">-- Pilih Guru --</option>
                                <?php mysqli_data_seek($gurus, 0); while ($g = mysqli_fetch_assoc($gurus)): ?>
                                    <option value="<?= htmlspecialchars($g['no_induk']) ?>">
                                        <?= htmlspecialchars($g['nama_guru']) ?>
                                        <?= in_array($g['no_induk'], $pembina_nips) ? ' ✓' : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Guru bertanda ✓ sudah menjadi pembina di kelas lain</small>
                        </div>
                        <div class="form-group">
                            <label style="font-size:12px; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Kelas Ampuhan</label>
                            <select name="kelas" class="form-control" required style="border-radius:10px; border-color:#e5e7eb; font-size:14px;">
                                <option value="">-- Pilih Kelas --</option>
                                <?php mysqli_data_seek($kelases, 0); while ($k = mysqli_fetch_assoc($kelases)): ?>
                                    <option value="<?= htmlspecialchars($k['kelas']) ?>"><?= htmlspecialchars($k['kelas']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_mapping" class="btn btn-primary btn-block font-weight-bold" style="border-radius:10px; padding:10px;">
                            <i class="fas fa-user-plus mr-2"></i>Tetapkan Sebagai Pembina
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card border-0 mt-3" style="border-radius:16px; background:linear-gradient(135deg,#ecfdf5,#d1fae5); border:1px solid #a7f3d0;">
                <div class="card-body py-3">
                    <h6 class="font-weight-bold mb-2" style="color:#065f46; font-size:13px;"><i class="fas fa-info-circle mr-1"></i> Cara Kerja</h6>
                    <ul class="mb-0" style="font-size:12px; color:#047857; padding-left:16px; line-height:1.8;">
                        <li>Menu <strong>LENTERA Literasi</strong> hanya muncul pada guru yang terdaftar di sini</li>
                        <li>Satu guru dapat mengampu lebih dari satu kelas</li>
                        <li>Guru yang tidak terdaftar <strong>tidak bisa</strong> mengakses halaman literasi</li>
                        <li>Hapus baris untuk mencabut hak akses</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pembina -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0" style="border-radius:16px; overflow:hidden;">
                <div class="card-header py-3 bg-white border-bottom" style="border-bottom:1px solid #f3f4f6;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-gray-800"><i class="fas fa-list-ul mr-2 text-primary"></i>Daftar Guru Pembina & Kelas Ampuhan</h6>
                        <span class="badge badge-primary px-3 py-2" style="border-radius:20px;"><?= $totalMapping ?> Penugasan</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mappingRows)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book-open fa-3x mb-3 text-muted" style="opacity:0.4;"></i>
                        <h6 class="text-muted font-weight-bold">Belum ada Pembina Literasi</h6>
                        <p class="text-muted mb-0" style="font-size:13px;">Tambahkan guru sebagai pembina literasi menggunakan form di samping.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background:#f8fafc;">
                                <tr>
                                    <th style="border-top:none; font-size:11px; font-weight:800; text-transform:uppercase; color:#6b7280; padding:12px 16px;">No</th>
                                    <th style="border-top:none; font-size:11px; font-weight:800; text-transform:uppercase; color:#6b7280;">Guru Pembina</th>
                                    <th style="border-top:none; font-size:11px; font-weight:800; text-transform:uppercase; color:#6b7280;">Kelas Ampuhan</th>
                                    <th style="border-top:none; font-size:11px; font-weight:800; text-transform:uppercase; color:#6b7280; text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($mappingRows as $m): ?>
                                <tr>
                                    <td style="color:#9ca3af; font-size:13px; vertical-align:middle; padding:12px 16px;"><?= $no++ ?></td>
                                    <td style="vertical-align:middle;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#1d4ed8); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                <i class="fas fa-chalkboard-teacher text-white" style="font-size:14px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:700; font-size:14px; color:#111827;"><?= htmlspecialchars($m['nama_guru']) ?></div>
                                                <div style="font-size:11px; color:#9ca3af;"><?= htmlspecialchars($m['no_induk']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <span class="badge" style="background:#dbeafe; color:#1d4ed8; border-radius:8px; padding:6px 14px; font-size:13px; font-weight:700;">
                                            <i class="fas fa-door-open mr-1"></i><?= htmlspecialchars($m['kelas']) ?>
                                        </span>
                                    </td>
                                    <td style="vertical-align:middle; text-align:center;">
                                        <form method="post" onsubmit="return confirm('Hapus hak akses literasi guru <?= htmlspecialchars($m['nama_guru']) ?> untuk kelas <?= htmlspecialchars($m['kelas']) ?>?');" style="display:inline;">
                                            <input type="hidden" name="id_mapping" value="<?= $m['id'] ?>">
                                            <button type="submit" name="delete_mapping" class="btn btn-sm" style="background:#fef2f2; color:#ef4444; border:1px solid #fca5a5; border-radius:8px; padding:5px 12px; font-weight:700; font-size:12px;">
                                                <i class="fas fa-trash mr-1"></i>Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
