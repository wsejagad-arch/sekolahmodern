<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Debug flag optional (?debug_tugas=1) only reveals details on localhost or when explicitly requested
$DEBUG_TUGAS = isset($_GET['debug_tugas']) || (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']==='localhost' || $_SERVER['SERVER_NAME']==='127.0.0.1'));
if (!isset($_SESSION['no_induk'])) { 
    http_response_code(401);
    exit('Akses ditolak: Harus login terlebih dahulu'); 
}

if ($_SESSION['hak_akses'] != 2) { 
    http_response_code(403);
    exit('Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini'); 
}

try {
    include '../../koneksi.php'; // expects $conn
    include '../../functions.php';
    @include_once '../../nocache.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal memuat dependensi.';
    if ($DEBUG_TUGAS) {
        echo '<pre style="white-space:pre-wrap">'.htmlspecialchars($e->getMessage())."\n".htmlspecialchars($e->getTraceAsString()).'</pre>';
    }
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo 'Koneksi database tidak tersedia.';
    if ($DEBUG_TUGAS) echo '<div class="small text-muted">$conn invalid.</div>';
    exit;
}
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo 'Gagal konek DB.';
    if ($DEBUG_TUGAS) echo '<div class="small text-muted">'.htmlspecialchars(mysqli_connect_error()).'</div>';
    exit;
}

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
    if(!$result){
        error_log('[TUGAS] Query mapel gagal: '.mysqli_error($conn).' SQL='.$query);
        echo '<div class="alert alert-danger">Gagal mengambil data mapel.</div>';
        if ($DEBUG_TUGAS) echo '<div class="small text-muted">'.htmlspecialchars(mysqli_error($conn)).'</div>';
        exit;
    }
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

    // Buat tabel tugas dinamis jika belum ada
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_tugas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        id_mapel INT NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        mapel VARCHAR(100) NOT NULL,
        no_induk_guru VARCHAR(50) NOT NULL,
        judul_tugas VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        tanggal_pengumpulan DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('aktif', 'selesai', 'dihapus') DEFAULT 'aktif',
        INDEX (tanggal, id_mapel),
        INDEX (no_induk_guru)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ambil tugas yang sudah ada untuk hari ini dan mapel ini
    $tanggal_escaped = mysqli_real_escape_string($conn, $tanggal);
    $idmapel_escaped = mysqli_real_escape_string($conn, $idmapel);
    $queryT = "SELECT * FROM tbl_tugas WHERE tanggal = '$tanggal_escaped' AND id_mapel = '$idmapel_escaped' AND no_induk_guru = '$nipguru_escaped' AND status = 'aktif' ORDER BY id DESC LIMIT 1";
    $tugasResult = mysqli_query($conn, $queryT);
    
    $existingTugas = null;
    if($tugasResult && mysqli_num_rows($tugasResult) > 0) {
        $existingTugas = mysqli_fetch_assoc($tugasResult);
    }

    // Jika standalone, tampilkan minimal HTML header dan CSS agar halaman tampil rapi saat diakses langsung
    if ($standalone) {
        echo '<!DOCTYPE html>'
            .'<html lang="id">'
            .'<head>'
            .'<meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Input Tugas - '.htmlspecialchars($mapel).' ('.htmlspecialchars($kelas).')</title>'
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
        }

        .tugas-form-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .tugas-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 1.5rem;
            color: white;
        }

        .tugas-body {
            background: white;
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e3e6f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #6f42c1);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
        }

        .btn-danger {
            background: linear-gradient(45deg, #e74a3b, #dc3545);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 74, 59, 0.4);
        }

        .existing-tugas {
            background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
            border-radius: 15px;
            color: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.5rem;
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

        @media (max-width: 768px) {
            .tugas-body {
                padding: 1rem;
            }
            
            .tugas-header {
                padding: 1rem;
            }
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="tugas-form-container">
        <div class="tugas-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Input Tugas</h5>
                <div class="small opacity-75"><?= htmlspecialchars($mapel) ?> • Kelas <?= htmlspecialchars($kelas) ?> • <?= date('d/m/Y') ?></div>
            </div>
            <?php if ($standalone): ?>
            <a href="nilai.php" class="btn btn-sm btn-outline-light"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
            <?php endif; ?>
        </div>
        
        <div class="tugas-body">
            <?php if ($existingTugas): ?>
                <div class="existing-tugas">
                    <h6><i class="fas fa-check-circle me-2"></i>Tugas Sudah Dibuat</h6>
                    <div><strong>Judul:</strong> <?= htmlspecialchars($existingTugas['judul_tugas']) ?></div>
                    <?php if ($existingTugas['deskripsi']): ?>
                        <div><strong>Deskripsi:</strong> <?= htmlspecialchars($existingTugas['deskripsi']) ?></div>
                    <?php endif; ?>
                    <?php if ($existingTugas['link_tugas']): ?>
                        <div><strong>Link:</strong> <a href="<?= htmlspecialchars($existingTugas['link_tugas']) ?>" target="_blank" class="text-white"><?= htmlspecialchars($existingTugas['link_tugas']) ?></a></div>
                    <?php endif; ?>
                    <?php if ($existingTugas['file_tugas']): ?>
                        <div><strong>File:</strong> <a href="<?= htmlspecialchars($existingTugas['file_tugas']) ?>" target="_blank" class="text-white"><?= basename($existingTugas['file_tugas']) ?></a></div>
                    <?php endif; ?>
                    <?php if ($existingTugas['tanggal_pengumpulan']): ?>
                        <div><strong>Pengumpulan:</strong> <?= date('d/m/Y', strtotime($existingTugas['tanggal_pengumpulan'])) ?></div>
                    <?php endif; ?>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-danger" onclick="hapusTugas(<?= $existingTugas['id'] ?>)">
                            <i class="fas fa-trash me-2"></i>Hapus Tugas
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <form id="formTugas" onsubmit="return submitTugas(event)" enctype="multipart/form-data">
                    <input type="hidden" name="id_mapel" value="<?= htmlspecialchars($idmapel) ?>">
                    <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
                    <input type="hidden" name="mapel" value="<?= htmlspecialchars($mapel) ?>">
                    <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
                    
                    <div class="form-group">
                        <label for="judul_tugas" class="form-label">Judul Tugas *</label>
                        <input type="text" class="form-control" id="judul_tugas" name="judul_tugas" required maxlength="255" placeholder="Masukkan judul tugas">
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi" class="form-label">Deskripsi Tugas *</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required placeholder="Masukkan deskripsi atau instruksi tugas"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="link_tugas" class="form-label">Link Tugas</label>
                        <input type="url" class="form-control" id="link_tugas" name="link_tugas" placeholder="https://contoh.com/link-tugas (opsional)">
                        <div class="form-text">Link referensi, materi, atau platform pengumpulan tugas</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="file_tugas" class="form-label">File Tugas</label>
                        <input type="file" class="form-control" id="file_tugas" name="file_tugas" accept=".pdf,.doc,.docx,.ppt,.pptx,.xlsx,.xls,.jpg,.jpeg,.png,.zip,.rar">
                        <div class="form-text">Upload file soal, materi, atau template tugas (Maks: 10MB)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_pengumpulan" class="form-label">Tanggal Pengumpulan</label>
                        <input type="date" class="form-control" id="tanggal_pengumpulan" name="tanggal_pengumpulan" min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Tugas
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function submitTugas(event) {
            event.preventDefault();
            
            const form = document.getElementById('formTugas');
            const formData = new FormData(form);
            formData.append('action', 'simpan');
            
            // Show loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            fetch('simpan_tugas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    // Reload the modal content to show the created task
                    if (typeof openInputTugas === 'function') {
                        const idMapel = formData.get('id_mapel');
                        openInputTugas(idMapel);
                        
                        // Also refresh the schedule modal to update button states
                        setTimeout(() => {
                            $('#modalTugas').modal('hide');
                            setTimeout(() => {
                                $('#selectJadwalModal').modal('show');
                            }, 300);
                        }, 1000);
                    } else {
                        location.reload();
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (data.message || 'Gagal menyimpan tugas'), 'error');
                    } else {
                        alert('Error: ' + (data.message || 'Gagal menyimpan tugas'));
                    }
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    showToast('Terjadi kesalahan saat menyimpan tugas', 'error');
                } else {
                    alert('Terjadi kesalahan saat menyimpan tugas');
                }
            });
            
            return false;
        }
        
        function hapusTugas(tugasId) {
            // non-blocking confirm
            showConfirm('Yakin ingin menghapus tugas ini?').then(function(ok){
                if (!ok) return;
            
            // Show loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            const formData = new FormData();
            formData.append('action', 'hapus');
            formData.append('tugas_id', tugasId);
            
            fetch('simpan_tugas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    // Reload the modal content to show the form again
                    if (typeof openInputTugas === 'function') {
                        // Get id_mapel from existing data
                        const idMapel = '<?= $idmapel ?>';
                        openInputTugas(idMapel);
                        
                        // Also refresh the schedule modal to update button states
                        setTimeout(() => {
                            $('#modalTugas').modal('hide');
                            setTimeout(() => {
                                $('#selectJadwalModal').modal('show');
                            }, 300);
                        }, 1000);
                    } else {
                        location.reload();
                    }
                } else {
                    showToast('Error: ' + (data.message || 'Gagal menghapus tugas'), 'error');
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    showToast('Terjadi kesalahan saat menghapus tugas', 'error');
                } else {
                    alert('Terjadi kesalahan saat menghapus tugas');
                }
            });
        });
    }

    // Polyfill for showToast and showConfirm if not defined
    if (typeof showToast !== 'function') {
        window.showToast = function(msg, type) {
            alert(msg);
        };
    }
    if (typeof showConfirm !== 'function') {
        window.showConfirm = function(msg) {
            return Promise.resolve(confirm(msg));
        };
    }
    </script>

    <?php
    if ($standalone) {
        echo '</div>';
        include __DIR__ . '/guru_common_footer.php';
        echo '</body></html>';
    }

} else {
    echo '<div class="alert alert-danger">Parameter tidak valid.</div>';
}
?>
