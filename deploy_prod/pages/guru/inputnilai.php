<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk'])) { 
    http_response_code(401);
    exit('Akses ditolak: Harus login terlebih dahulu'); 
}

if ($_SESSION['hak_akses'] != 2) { 
    http_response_code(403);
    exit('Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini'); 
}

include '../../koneksi.php';
include '../../functions.php';
// Disable caching for this page (standalone and modal requests)
include_once '../../nocache.php';

date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];

// Standalone mode (full page layout) apabila dipanggil via GET langsung
$standalone = isset($_GET['getDetail']) && !isset($_POST['getDetail']);

if (isset($_POST['getDetail']) || isset($_GET['getDetail'])) {
    $raw = isset($_POST['getDetail']) ? $_POST['getDetail'] : $_GET['getDetail'];
    $idmapel = mysqli_real_escape_string($conn, $raw);
    
    // Query biasa untuk kompatibilitas hosting
    $nipguru_escaped = mysqli_real_escape_string($conn, $nipguru);
    $query = "SELECT * FROM tbl_mapel_ampu WHERE id_mapel = '$idmapel' AND no_induk = '$nipguru_escaped' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $m = mysqli_fetch_assoc($result);
    
    if (!$m) { 
        echo '<div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>Mata pelajaran tidak ditemukan atau Anda tidak memiliki akses.
              </div>'; 
        exit; 
    }
    
    $kelas = $m['kelas'];
    $mapel = $m['nama_mapel'];
    $tanggal = date('Y-m-d');

    // Buat tabel penilaian dinamis jika belum ada
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_penilaian_item (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        id_mapel INT NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        mapel VARCHAR(100) NOT NULL,
        no_induk_guru VARCHAR(50) NOT NULL,
        kode_penilaian VARCHAR(20) NOT NULL,
        materi VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_item (tanggal, id_mapel, kode_penilaian)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai_item (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_item INT NOT NULL,
        no_induk_siswa VARCHAR(50) NOT NULL,
        nilai FLOAT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_nilai_item (id_item, no_induk_siswa),
        INDEX (id_item),
        FOREIGN KEY (id_item) REFERENCES tbl_penilaian_item(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ambil siswa - gunakan query biasa untuk kompatibilitas
    $kelas_escaped = mysqli_real_escape_string($conn, $kelas);
    $queryS = "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas = '$kelas_escaped' AND status = 'Aktif' ORDER BY nama_siswa ASC";
    $siswa = mysqli_query($conn, $queryS);

    // Ambil item penilaian untuk tanggal & mapel ini
    $tanggal_escaped = mysqli_real_escape_string($conn, $tanggal);
    $idmapel_escaped = mysqli_real_escape_string($conn, $idmapel);
    $queryI = "SELECT * FROM tbl_penilaian_item WHERE tanggal = '$tanggal_escaped' AND id_mapel = '$idmapel_escaped' ORDER BY id ASC";
    $items = mysqli_query($conn, $queryI);
    
    $itemList = [];
    while ($it = mysqli_fetch_assoc($items)) { 
        $itemList[] = $it; 
    }

    // Jika standalone, tampilkan minimal HTML header dan CSS agar halaman tampil rapi saat diakses langsung
    if ($standalone) {
        echo '<!DOCTYPE html>'
            .'<html lang="id">'
            .'<head>'
            .'<meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Input Nilai - '.htmlspecialchars($mapel).' ('.htmlspecialchars($kelas).')</title>'
            // CSS yang diperlukan
            .'<link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">'
            .'<link href="../../css/sb-admin-2.min.css" rel="stylesheet">'
            .'</head>'
            .'<body class="bg-light">'
            .'<div class="container-fluid p-4">';
    }

    ?>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --th-height: 64px;
        }
        
        .badge-soft { 
            background: rgba(78, 115, 223, .1); 
            color: var(--primary-color); 
            border: 1px solid rgba(78, 115, 223, .2); 
            font-weight: 500;
            padding: 0.5em 0.8em;
            border-radius: 0.35rem;
        }
        
        .badge-soft-secondary { 
            background: rgba(108, 117, 125, .12); 
            color: var(--secondary-color); 
            border: 1px solid rgba(108, 117, 125, .2); 
            font-weight: 500;
            padding: 0.5em 0.8em;
            border-radius: 0.35rem;
        }
        
        .sticky-actions { 
            position: sticky; 
            bottom: 0; 
            z-index: 1030; 
            background: #fff; 
            border-top: 1px solid #e9ecef; 
            padding: 1rem; 
            text-align: right; 
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        
        .table thead th { 
            position: sticky; 
            top: 0; 
            background: #f8f9fc; 
            z-index: 5; 
            vertical-align: bottom;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
            height: var(--th-height);
            padding-bottom: .5rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.03);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #3a5ecc;
            border-color: #3a5ecc;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .input-nilai {
            width: 70px;
            text-align: center;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .input-nilai:focus {
            width: 85px;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .header-penilaian {
            min-width: 120px;
            text-align: center;
            cursor: help;
            padding: 0.5rem 0.25rem;
        }
        .header-penilaian .fw-semibold { 
            line-height: 1; 
            margin-bottom: 0.25rem;
        }
        .header-penilaian .materi-tooltip {
            font-size: 0.8rem;
            opacity: 0.9;
            /* multi-line clamp for neat header */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
            margin: 0 auto;
        }
        .header-rata {
            text-align: center;
            padding: 0.5rem 0.25rem;
            min-width: 120px;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .input-nilai {
                width: 60px;
                padding: 0.25rem;
            }
            
            .sticky-actions {
                padding: 0.75rem;
            }
            
            .sticky-actions .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-1">Input Nilai Harian</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge badge-soft">
                    <i class="fas fa-book me-1"></i> <?= htmlspecialchars($mapel); ?>
                </span>
                <span class="badge badge-soft-secondary">
                    <i class="fas fa-users me-1"></i> <?= htmlspecialchars($kelas); ?>
                </span>
                <span class="badge badge-soft-secondary">
                    <i class="fas fa-calendar-day me-1"></i> <?= htmlspecialchars(tgl_indo($tanggal)); ?>
                </span>
            </div>
        </div>
        
        <div class="d-none d-md-block">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefresh">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Form tambah kolom penilaian -->
    <div class="card mb-4">
        <div class="card-header bg-light py-3">
            <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Kolom Penilaian</h6>
        </div>
        <div class="card-body">
            <form class="row g-3 align-items-end" method="post" action="buat_item_nilai.php" id="formBuatItem">
                <input type="hidden" name="idmapel" value="<?= htmlspecialchars($idmapel); ?>"/>
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas); ?>"/>
                <input type="hidden" name="mapel" value="<?= htmlspecialchars($mapel); ?>"/>
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>"/>
                
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Jenis Penilaian</label>
                    <select name="kode_penilaian" class="form-select" required>
                        <option value="" disabled selected>Pilih Jenis...</option>
                        <option value="UH1">UH1 (Ulangan Harian 1)</option>
                        <option value="UH2">UH2 (Ulangan Harian 2)</option>
                        <option value="UH3">UH3 (Ulangan Harian 3)</option>
                        <option value="UH4">UH4 (Ulangan Harian 4)</option>
                        <option value="UH5">UH5 (Ulangan Harian 5)</option>
                        <option value="ASAS">ASAS (Asesmen Sumatif Akhir Semester)</option>
                        <option value="ASAT">ASAT (Asesmen Sumatif Akhir Tahun)</option>
                        <option value="TUGAS">TUGAS</option>
                        <option value="PROYEK">PROYEK</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Materi Penilaian</label>
                    <input type="text" name="materi" class="form-control" 
                           placeholder="Contoh: Persamaan Linear, Trigonometri, dll" required />
                </div>
                
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-1"></i> Tambah Kolom
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (count($itemList) === 0) { ?>
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle me-2 fa-lg"></i>
            <div>
                <h6 class="alert-heading mb-1">Belum ada penilaian</h6>
                <p class="mb-0">Tambahkan kolom penilaian untuk pertemuan hari ini menggunakan form di atas.</p>
            </div>
        </div>
    <?php } else { ?>
    <div class="card">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-table me-2"></i>Daftar Nilai Siswa</h6>
            <span class="badge bg-primary"><?= count($itemList); ?> Jenis Penilaian</span>
        </div>
        
        <div class="card-body p-0">
            <form method="post" action="simpan_nilai_dinamis.php" id="formNilaiDinamis">
                <input type="hidden" name="idmapel" value="<?= htmlspecialchars($idmapel); ?>"/>
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>"/>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 250px; min-width: 200px;">Nama Siswa</th>
                                <?php foreach ($itemList as $it) { ?>
                                    <th class="text-center header-penilaian" title="<?= htmlspecialchars($it['materi']); ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($it['kode_penilaian']); ?></div>
                                        <div class="text-muted small materi-tooltip">
                                            <?= htmlspecialchars($it['materi']); ?>
                                        </div>
                                    </th>
                                <?php } ?>
                                <th class="text-center fw-semibold header-rata" style="min-width: 120px;">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Map nilai existing: [id_item][no_induk] => nilai
                                $nilaiMap = [];
                                if (count($itemList) > 0) {
                                    $ids = array_map(function($x){ return (int)$x['id']; }, $itemList);
                                    $idStr = implode(',', $ids);
                                    
                                    $qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_item IN (".$idStr.")");
                                    while ($nv = mysqli_fetch_assoc($qNil)) {
                                        $nilaiMap[$nv['id_item']][$nv['no_induk_siswa']] = $nv['nilai'];
                                    }
                                }
                                
                                // Reset pointer untuk iterasi ulang
                                mysqli_data_seek($siswa, 0);
                            ?>
                            <?php while($s = mysqli_fetch_assoc($siswa)) { 
                                $ni = $s['no_induk']; 
                                $totalNilai = 0;
                                $jumlahNilai = 0;
                            ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($s['nama_siswa']); ?></td>
                                    <?php foreach ($itemList as $it) { 
                                        $val = isset($nilaiMap[$it['id']][$ni]) ? $nilaiMap[$it['id']][$ni] : '';
                                        
                                        // Hitung untuk rata-rata
                                        if (is_numeric($val) && $val !== '') {
                                            $totalNilai += (float)$val;
                                            $jumlahNilai++;
                                        }
                                    ?>
                                        <td class="text-center" style="width: 120px; min-width: 120px">
                                            <input type="number" min="0" max="100" step="0.01" 
                                                   class="form-control form-control-sm input-nilai" 
                                                   name="nilai[<?= (int)$it['id']; ?>][<?= htmlspecialchars($ni); ?>]" 
                                                   value="<?= htmlspecialchars($val); ?>"
                                                   data-item="<?= (int)$it['id']; ?>"
                                                   data-siswa="<?= htmlspecialchars($ni); ?>">
                                        </td>
                                    <?php } ?>
                                    
                                    <td class="text-center fw-bold">
                                        <?php
                                            $rataRata = $jumlahNilai > 0 ? number_format($totalNilai / $jumlahNilai, 2) : '-';
                                            echo $rataRata;
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th class="text-end">Rata-rata Kelas:</th>
                                <?php
                                foreach ($itemList as $it) {
                                    $iid = (int)$it['id'];
                                    $sum = 0; $cnt = 0;
                                    if (!empty($nilaiMap[$iid])) {
                                        foreach ($nilaiMap[$iid] as $nv) {
                                            if ($nv !== '' && $nv !== null && is_numeric($nv)) { 
                                                $sum += (float)$nv; 
                                                $cnt++; 
                                            }
                                        }
                                    }
                                    $avg = $cnt > 0 ? number_format($sum / $cnt, 2) : '-';
                                    echo '<th class="text-center fw-semibold">'.$avg.'</th>';
                                }
                                ?>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="sticky-actions">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small" id="saveStatus">
                            <i class="fas fa-info-circle me-1"></i> Pastikan untuk menyimpan perubahan
                        </div>
                        <div>
                            <?php if (!$standalone) { ?>
                                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Tutup
                                </button>
                            <?php } ?>
                            <button type="submit" class="btn btn-primary" id="btnSimpan">
                                <i class="fas fa-save me-1"></i> Simpan Nilai
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php if ($standalone) { ?>
    <!-- Library JS untuk mode standalone -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>
    <?php } ?>
    
    <script>
        (function(){
            // Tampilkan loading overlay
            function showLoading() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            }
            
            // Sembunyikan loading overlay
            function hideLoading() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
            
            // Fungsi untuk refresh data
            document.getElementById('btnRefresh')?.addEventListener('click', function() {
                showLoading();
                const idmapel = '<?= $idmapel; ?>';
                
                if (<?= $standalone ? 'true' : 'false' ?>) {
                    window.location.search = '?getDetail=' + encodeURIComponent(idmapel);
                } else {
                    $.post('inputnilai.php?ts=' + Date.now(), { getDetail: idmapel }, function(html){
                        $('.modal-nilai-body').html(html);
                        hideLoading();
                    }).fail(function(){
                        hideLoading();
                        alert('Gagal memuat ulang data');
                    });
                }
            });
            
            // Ajax submit untuk simpan nilai dinamis
            const formNilai = document.getElementById('formNilaiDinamis');
            if (formNilai) {
                formNilai.addEventListener('submit', function(ev){
                    ev.preventDefault();
                    const btn = document.getElementById('btnSimpan');
                    const saveStatus = document.getElementById('saveStatus');
                    
                    if (btn) { 
                        btn.disabled = true; 
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...'; 
                    }
                    
                    saveStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan perubahan...';
                    saveStatus.className = 'text-info small';
                    
                    showLoading();
                    
                    // Kirim data via AJAX
                    const formData = new FormData(formNilai);
                    
                    // Add cache buster to action URL
                    const actionUrl = formNilai.action + (formNilai.action.includes('?') ? '&' : '?') + 'ts=' + Date.now();
                    fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Berhasil disimpan
                        saveStatus.innerHTML = '<i class="fas fa-check-circle me-1"></i> Perubahan berhasil disimpan';
                        saveStatus.className = 'text-success small';
                        
                        if (<?= $standalone ? 'true' : 'false' ?>) {
                            // Jika standalone, reload halaman
                            window.location.reload();
                        } else {
                            // Jika modal, tutup modal setelah simpan
                            try {
                                const modalEl = document.getElementById('modalNilai');
                                if (modalEl) {
                                    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                                        const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                        instance.hide();
                                    } else if (window.$ && typeof $(modalEl).modal === 'function') {
                                        // Fallback untuk Bootstrap 4
                                        $(modalEl).modal('hide');
                                    }
                                }
                            } catch (e) {
                                console.warn('Gagal menutup modal secara programatik:', e);
                            } finally {
                                hideLoading();
                            }
                        }
                    })
                    .catch(error => {
                        // Gagal menyimpan
                        if (btn) { 
                            btn.disabled = false; 
                            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Nilai'; 
                        }
                        
                        saveStatus.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Gagal menyimpan perubahan';
                        saveStatus.className = 'text-danger small';
                        
                        hideLoading();
                        alert('Terjadi kesalahan saat menyimpan data: ' + error);
                    });
                });
            }

            // Ajax submit untuk buat item penilaian
            const formItem = document.getElementById('formBuatItem');
            if (formItem) {
                formItem.addEventListener('submit', function(ev){
                    ev.preventDefault();
                    const btn = formItem.querySelector('button[type="submit"]');
                    
                    if (btn) { 
                        btn.disabled = true; 
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...'; 
                    }
                    
                    showLoading();
                    
                    // Kirim data via AJAX
                    const formData = new FormData(formItem);
                    
                    // Add cache buster to form action
                    const postUrl = formItem.action + (formItem.action.includes('?') ? '&' : '?') + 'ts=' + Date.now();
                    fetch(postUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Berhasil dibuat
                        const idmapel = '<?= $idmapel; ?>';
                        
                        if (<?= $standalone ? 'true' : 'false' ?>) {
                            window.location.search = '?getDetail=' + encodeURIComponent(idmapel);
                        } else {
                            $.post('inputnilai.php?ts=' + Date.now(), { getDetail: idmapel }, function(html){
                                $('.modal-nilai-body').html(html);
                                hideLoading();
                            });
                        }
                    })
                    .catch(error => {
                        // Gagal membuat
                        if (btn) { 
                            btn.disabled = false; 
                            btn.innerHTML = '<i class="fas fa-plus me-1"></i> Tambah Kolom'; 
                        }
                        
                        hideLoading();
                        alert('Gagal menambahkan kolom penilaian: ' + error);
                    });
                });
            }
            
            // Auto-save ketika input kehilangan fokus (opsional)
            document.querySelectorAll('.input-nilai').forEach(input => {
                input.addEventListener('blur', function() {
                    const saveStatus = document.getElementById('saveStatus');
                    saveStatus.innerHTML = '<i class="fas fa-pencil-alt me-1"></i> Perubahan belum disimpan';
                    saveStatus.className = 'text-warning small';
                });
            });
        })();
    </script>
    <?php if ($standalone) { echo '</div></body></html>'; } ?>
    <?php } ?>
    <?php
}
