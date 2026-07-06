<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\walikelas.php';
$content = file_get_contents($file);

// 1. Add logic to handle Validasi Izin Action at the top
$validasiLogic = <<<PHP

// Handle Validasi Izin Action
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action'])) {
    \$action = \$_POST['action'];
    if (\$action === 'acc_wali' || \$action === 'tolak_wali') {
        \$id_izin = (int)\$_POST['id_izin'];
        \$nama_guru = \$_SESSION['nama'];
        
        if (\$action === 'acc_wali') {
            \$q = "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Disetujui', validator_wali_kelas = '\$nama_guru', waktu_validasi_wali_kelas = NOW() WHERE id_izin = \$id_izin";
            mysqli_query(\$conn, \$q);
            \$msg_validasi = "Izin berhasil disetujui.";
        } elseif (\$action === 'tolak_wali') {
            \$q = "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Ditolak', validator_wali_kelas = '\$nama_guru', waktu_validasi_wali_kelas = NOW(), status_izin = 'Ditolak' WHERE id_izin = \$id_izin";
            mysqli_query(\$conn, \$q);
            \$msg_validasi = "Izin berhasil ditolak.";
        }
    }
}

// Fetch pending izin for the selected class (\$kelasFilter)
\$list_izin = [];
if (!empty(\$kelasFilter)) {
    \$kelas_esc = mysqli_real_escape_string(\$conn, \$kelasFilter);
    \$qIzin = mysqli_query(\$conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE s.kelas = '\$kelas_esc' AND i.validasi_wali_kelas = 'Menunggu' ORDER BY i.waktu_pengajuan DESC");
    if (\$qIzin) {
        while (\$row = mysqli_fetch_assoc(\$qIzin)) {
            \$list_izin[] = \$row;
        }
    }
}
PHP;

// Find a good spot at the top, maybe after getting $kelasFilter
$content = str_replace('// Get filter parameters', $validasiLogic . "\n\n// Get filter parameters", $content);

// 2. Add Tab Button
$tabBtn = <<<HTML
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-pill px-4 me-2 shadow-sm" id="validasi-tab" data-bs-toggle="pill" data-bs-target="#validasi" type="button" role="tab" aria-controls="validasi" aria-selected="false"><i class="bi bi-patch-check"></i> Validasi Izin</button>
          </li>
HTML;
$content = str_replace('<li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-pill px-4 me-2 shadow-sm" id="jurnal-tab"', $tabBtn . "\n" . '<li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-pill px-4 me-2 shadow-sm" id="jurnal-tab"', $content);

// 3. Add Tab Content
$tabContent = <<<HTML
          <!-- Tab Validasi Izin -->
          <div class="tab-pane fade" id="validasi" role="tabpanel" aria-labelledby="validasi-tab">
            <section class="panel">
                <div class="panel-pad border-bottom">
                    <h2 class="h5 mb-1">Daftar Pengajuan Izin Menunggu Persetujuan</h2>
                    <p class="text-muted mb-0">Kelas <?= guru_wk_h(\$kelasFilter); ?></p>
                </div>
                <div class="panel-pad">
                    <?php if(isset(\$msg_validasi)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars(\$msg_validasi) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if(empty(\$list_izin)): ?>
                    <div class="alert alert-info">Tidak ada pengajuan izin yang menunggu validasi Anda untuk kelas ini.</div>
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
            </section>
          </div>
HTML;
$content = str_replace('<div class="tab-pane fade show active" id="monitoring" role="tabpanel" aria-labelledby="monitoring-tab">', $tabContent . "\n" . '<div class="tab-pane fade show active" id="monitoring" role="tabpanel" aria-labelledby="monitoring-tab">', $content);

file_put_contents($file, $content);
echo "Added tab Validasi Izin in walikelas.php\n";
?>
