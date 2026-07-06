<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$DEBUG_JURNAL = isset($_GET['debug_jurnal']) || (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']==='localhost' || $_SERVER['SERVER_NAME']==='127.0.0.1'));

include "../../koneksi.php"; 
include "../../functions.php";

if (!$conn) {
    echo '<div class="alert alert-danger">Koneksi database tidak tersedia.</div>';
    exit();
}

date_default_timezone_set('Asia/Jakarta');
if (!isset($_SESSION['no_induk'])) {
    echo '<div class="alert alert-danger">Akses ditolak: Harus login terlebih dahulu.</div>';
    exit;
}

$nipguru = $_SESSION['no_induk'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);

// --- LOGIC PENGAMBILAN DATA ---
if(isset($_POST['getDetail'])) {
    try {
        $id = (int)$_POST['getDetail'];
        
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID jadwal tidak valid.</div>';
            exit;
        }
        
        $id_escaped = mysqli_real_escape_string($conn, $id);
        $nipguru_escaped = mysqli_real_escape_string($conn, $nipguru);
        
        // Ambil Data Mapel
        $query = "SELECT * FROM tbl_mapel_ampu WHERE id_mapel = '$id_escaped' AND no_induk = '$nipguru_escaped' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            echo '<div class="alert alert-danger">Gagal mengambil data jadwal.</div>';
            exit;
        }
        
        $dat = mysqli_fetch_assoc($result);
        
        if (!$dat) {
            echo '<div class="alert alert-danger">Jadwal tidak ditemukan atau Anda tidak berhak mengaksesnya.</div>';
            exit;
        }

        // Cek Apakah Jurnal Sudah Diisi
        $tglskr_escaped = mysqli_real_escape_string($conn, $tglskr);

        // Deteksi nama kolom tanggal di tabel tbl_materi (beberapa instalasi pakai `date`, beberapa pakai `tanggal`)
        $dateCol = null;
        $colCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'date'");
        if ($colCheck && mysqli_num_rows($colCheck) > 0) {
            $dateCol = 'date';
        } else {
            $colCheck2 = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'tanggal'");
            if ($colCheck2 && mysqli_num_rows($colCheck2) > 0) {
                $dateCol = 'tanggal';
            }
        }

        if (!$dateCol) {
            echo '<div class="alert alert-danger">Tabel jurnal tidak memiliki kolom tanggal yang valid.</div>';
            exit;
        }

        $queryJ = "SELECT id_materi, file_materi, materi, kegiatan, keterangan FROM tbl_materi WHERE id_mapel = '$id_escaped' AND no_induk = '$nipguru_escaped' AND `" . $dateCol . "` = '$tglskr_escaped'";
        $resJ = mysqli_query($conn, $queryJ);

        // Jika query gagal, tampilkan pesan (jangan biarkan mysqli_num_rows menerima boolean)
        if ($resJ === false) {
            // Untuk keamanan, hindari menampilkan query atau password; tampilkan pesan umum dan log error jika tersedia
            error_log('detailmateri.php - queryJ failed: ' . mysqli_error($conn));
            echo '<div class="alert alert-danger">Gagal memeriksa status jurnal (database error).</div>';
            exit;
        }

        // --- TAMPILAN JIKA SUDAH ADA JURNAL (READ ONLY) ---
        if (mysqli_num_rows($resJ) > 0) {
            // Ambil data jurnal untuk edit
            $mj = mysqli_fetch_assoc($resJ); // Asumsi satu jurnal per hari per mapel
            $idMateri = (int)$mj['id_materi'];
            $file = $mj['file_materi'];
            $existingMateri = $mj['materi'];
            $existingKegiatan = $mj['kegiatan'];
            $existingKeterangan = $mj['keterangan'];
            
            // Ambil data absen existing
            $existingAbsen = [];
            $queryAbsen = "SELECT no_induk, status FROM tbl_absen WHERE id_mapel = '$id_escaped' AND tanggal = '$tglskr_escaped' AND (no_induk_guru = '$nipguru_escaped' OR no_induk_guru IS NULL OR no_induk_guru = '')";
            $resAbsen = mysqli_query($conn, $queryAbsen);
            if($resAbsen) {
                while($abs = mysqli_fetch_assoc($resAbsen)) {
                    $existingAbsen[$abs['no_induk']] = $abs['status'];
                }
            }
            
            $hapusUrl = 'pages/guru/delete-materi.php?id=' . $idMateri . ($file ? ('&file=' . rawurlencode($file)) : '');
            echo '<div id="ringkasanJurnal" class="card mb-3">'
              .'<div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">'
              .'<span class="fw-semibold">Ringkasan Jurnal Hari Ini</span>'
              .'<div class="d-flex gap-2">'
              .'<button type="button" id="btnEditJurnal" class="btn btn-sm btn-primary text-white" style="color: #fff !important;">Edit Jurnal</button>'
              .'<a class="btn btn-sm btn-outline-danger" href="'.$hapusUrl.'" onclick="return confirm(\'Yakin mau menghapus isian jurnal ini?\');">Hapus Jurnal</a>'
              .'</div>'
              .'</div><div class="card-body">';
            echo '<div class="mb-3">';
            if (!empty($file)) {
                echo '<div class="mb-1"><i class="bi bi-file-earmark-pdf text-danger"></i> '
                  .'<a target="_blank" href="../../file_materi/'.htmlspecialchars($file).'">'.htmlspecialchars($file).'</a></div>';
            }
            if (!empty($mj['materi'])) echo '<div><strong>Materi:</strong> '.htmlspecialchars($mj['materi']).'</div>';
            if (!empty($mj['kegiatan'])) echo '<div><strong>Kegiatan:</strong> '.htmlspecialchars($mj['kegiatan']).'</div>';
            if (!empty($mj['keterangan'])) echo '<div><strong>Catatan:</strong> '.htmlspecialchars($mj['keterangan']).'</div>';
            echo '</div><hr class="my-2">';
            echo '</div></div>';
            
            echo '<div class="alert alert-success d-flex align-items-start" role="alert">'
              .'<div class="me-2"><i class="bi bi-check2-circle fs-5"></i></div>'
              .'<div>'
              .'<div class="fw-semibold mb-1">Anda sudah mengisi jurnal untuk jadwal ini hari ini.</div>'
              .'<div class="small">Untuk mengisi ulang atau memperbaiki, silakan edit atau hapus isian jurnal yang ada.</div>'
              .'</div>'
              .'</div>';
            echo '<div class="text-end"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>';
            
            // Form edit (hidden initially)
            echo '<div id="formEditJurnal" style="display:none;">';
        }

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Terjadi kesalahan internal.</div>';
        exit;
    }
}

$hasExistingJurnal = isset($idMateri) && (int)$idMateri > 0;
$existingAbsen = $existingAbsen ?? [];
$existingMateri = $existingMateri ?? '';
$existingKegiatan = $existingKegiatan ?? '';
$existingKeterangan = $existingKeterangan ?? '';
?>

<div>
    <a id="formJurnalSectionStart"></a>
    <h5 class="mt-2 border-bottom pb-2"><?= $hasExistingJurnal ? 'Edit Jurnal' : 'Form Jurnal Baru'; ?></h5>
    
    <p class="text-muted small mt-2">Mapel: <b><?= htmlspecialchars($dat['nama_mapel']); ?></b> | Kelas: <b><?= htmlspecialchars($dat['kelas']); ?></b></p>

    <form id="formJurnalInput" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tanggal" value="<?= $tglskr; ?>">
        <input type="hidden" name="nip" value="<?= $nipguru; ?>">
        <input type="hidden" name="idmapel" value="<?= $id; ?>">
        <input type="hidden" name="namamapel" value="<?= htmlspecialchars($dat['nama_mapel']); ?>">
        <input type="hidden" name="kelas" value="<?= htmlspecialchars($dat['kelas']); ?>">

        <div class="mb-3">
            <label for="materi" class="form-label fw-bold">Materi Pembelajaran</label>
            <textarea name="materi" id="materi" class="form-control" rows="2" required placeholder="Pokok bahasan..."><?= htmlspecialchars($existingMateri, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        
        <div class="mb-3">
            <label for="kegiatan" class="form-label fw-bold">Kegiatan Pembelajaran</label>
            <textarea name="kegiatan" id="kegiatan" class="form-control" rows="2" required placeholder="Contoh: Diskusi, Praktikum..."><?= htmlspecialchars($existingKegiatan, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold d-block">Presensi Siswa</label>
            
            <div class="mb-2 d-flex gap-2">
                <button type="button" id="btnAllHadir" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-check-all"></i> Semua Hadir
                </button>
                <button type="button" id="btnResetAll" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Reset
                </button>
            </div>

            <style>
                .absen-radio { appearance:none; width:24px; height:24px; border:1px solid #dee2e6; border-radius:50%; position:relative; cursor:pointer; background:#fff; transition:.2s; display:inline-block; margin: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
                .absen-radio::after { content: attr(data-letter); position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:11px; font-weight:bold; color:#adb5bd; text-transform:uppercase; }
                .absen-radio:checked { transform: scale(1.15); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .absen-radio[value="Hadir"]:checked { background-color:#198754; border-color:#198754; } 
                .absen-radio[value="Ijin"]:checked { background-color:#0dcaf0; border-color:#0dcaf0; }
                .absen-radio[value="Sakit"]:checked { background-color:#ffc107; border-color:#ffc107; }
                .absen-radio[value="Alpha"]:checked { background-color:#dc3545; border-color:#dc3545; }
                .absen-radio[value="Dispen"]:checked { background-color:#6f42c1; border-color:#6f42c1; }
                .absen-radio[value="Telat"]:checked { background-color:#fd7e14; border-color:#fd7e14; }
                .absen-radio:checked::after { color:#fff; }
                
                /* Base (Mobile) Compact table styling */
                .table-absen { table-layout: fixed; width: 100%; word-wrap: break-word; }
                .table-absen th, .table-absen td { padding: 6px 2px !important; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
                .table-absen th.col-radio, .table-absen td.col-radio { width: 32px; text-align: center; overflow: hidden; }
                .table-absen th.col-nama, .table-absen td.col-nama { width: auto; font-size: 12px; padding-left: 8px !important; line-height: 1.3; word-break: break-word; }
                
                /* Desktop Larger Columns */
                @media (min-width: 768px) {
                    .table-absen th, .table-absen td { padding: 10px 4px !important; }
                    .table-absen th.col-radio, .table-absen td.col-radio { width: 85px; }
                    .table-absen th.col-nama, .table-absen td.col-nama { font-size: 14px; padding-left: 16px !important; font-weight: 500; }
                    .absen-radio { width: 28px; height: 28px; }
                    .absen-radio::after { font-size: 12px; }
                }
            </style>

            <?php
            $kelas = isset($dat['kelas']) ? $dat['kelas'] : '';
            if ($kelas !== '') {
                $kelas_escaped = mysqli_real_escape_string($conn, $kelas);
                $queryS = "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas = '$kelas_escaped' AND status='Aktif' ORDER BY nama_siswa ASC";
                $siswaQuery = mysqli_query($conn, $queryS);

                if($siswaQuery && mysqli_num_rows($siswaQuery) > 0) {
                    echo "<div class='border rounded' style='max-height: 400px; overflow-y:auto; overflow-x:hidden;'>
                          <table class='table table-striped table-hover align-middle mb-0 table-absen'>
                          <thead class='table-light sticky-top'>
                            <tr>
                              <th class='col-nama'>Nama Siswa</th>
                              <th class='col-radio'>H</th><th class='col-radio'>I</th>
                              <th class='col-radio'>S</th><th class='col-radio'>D</th>
                              <th class='col-radio'>A</th><th class='col-radio'>T</th>
                            </tr>
                          </thead>
                          <tbody>";
                    while($s = mysqli_fetch_assoc($siswaQuery)) {
                        $nis = $s['no_induk'];
                        $statusSiswa = strtolower(trim((string)($existingAbsen[$nis] ?? '')));
                        if ($statusSiswa === 'izin') {
                            $statusSiswa = 'ijin';
                        }
                        $checkedHadir = $statusSiswa === 'hadir' ? ' checked' : '';
                        $checkedIjin = $statusSiswa === 'ijin' ? ' checked' : '';
                        $checkedSakit = $statusSiswa === 'sakit' ? ' checked' : '';
                        $checkedDispen = $statusSiswa === 'dispen' ? ' checked' : '';
                        $checkedAlpha = $statusSiswa === 'alpha' ? ' checked' : '';
                        $checkedTelat = $statusSiswa === 'telat' ? ' checked' : '';
                        echo "<tr>
                                <td class='col-nama'>" . htmlspecialchars($s['nama_siswa']) . "</td>
                                <td class='col-radio'><input class='absen-radio' type='radio' data-letter='h' name='absen[".$nis."]' value='Hadir'".$checkedHadir."></td>
                                <td class='col-radio'><input class='absen-radio' type='radio' data-letter='i' name='absen[".$nis."]' value='Ijin'".$checkedIjin."></td>
                                <td class='col-radio'><input class='absen-radio' type='radio' data-letter='s' name='absen[".$nis."]' value='Sakit'".$checkedSakit."></td>
                                <td class='col-radio'><input class='absen-radio' type='radio' data-letter='d' name='absen[".$nis."]' value='Dispen'".$checkedDispen."></td>
                                <td class='col-radio'><input class='absen-radio' type='radio' data-letter='a' name='absen[".$nis."]' value='Alpha'".$checkedAlpha."></td>
                                <td class='col-radio'><input class='absen-radio' type='radio' data-letter='t' name='absen[".$nis."]' value='Telat'".$checkedTelat."></td>
                              </tr>";
                    }
                    echo "</tbody></table></div>";
                } else {
                    echo "<div class='alert alert-warning'>Data siswa tidak ditemukan di kelas ini.</div>";
                }
            }
            ?>
        </div>

        <div class="mb-3">
            <label for="keterangan" class="form-label">Catatan Tambahan</label>
            <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Opsional..."><?= htmlspecialchars($existingKeterangan, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div id="msgBox"></div>

        <button type="submit" id="btnSimpan" class="btn btn-primary w-100 py-2 text-white" style="color: #fff !important;">
            <i class="bi bi-save me-2" style="color: #fff !important;"></i> Simpan Jurnal
        </button>
    </form>
</div>
<?php if ($hasExistingJurnal): ?>
</div>
<?php endif; ?>

<script>
(function() {
    // 0. Logic Tombol Edit Jurnal
    var btnEdit = document.getElementById('btnEditJurnal');
    if (btnEdit) {
        btnEdit.onclick = function() {
            var ringkasan = document.getElementById('ringkasanJurnal');
            var formEdit = document.getElementById('formEditJurnal');
            if (ringkasan) ringkasan.style.display = 'none';
            if (formEdit) formEdit.style.display = 'block';
        };
    }

    // 1. Logic Tombol "Semua Hadir"
    var btnAll = document.getElementById('btnAllHadir');
    if(btnAll) {
        btnAll.onclick = function() {
            var radios = document.querySelectorAll('input[type="radio"][value="Hadir"]');
            for(var i=0; i<radios.length; i++) { radios[i].checked = true; }
        };
    }

    // 2. Logic Tombol "Reset"
    var btnReset = document.getElementById('btnResetAll');
    if(btnReset) {
        btnReset.onclick = function() {
            var radios = document.querySelectorAll('.absen-radio');
            for(var i=0; i<radios.length; i++) { radios[i].checked = false; }
        };
    }

    // 3. Logic Tombol Edit Jurnal
    var btnEdit = document.getElementById('btnEditJurnal');
    if(btnEdit) {
        btnEdit.onclick = function() {
            document.getElementById('ringkasanJurnal').style.display = 'none';
            document.getElementById('formEditJurnal').style.display = 'block';
            document.getElementById('formJurnalSectionStart').scrollIntoView({behavior:'smooth', block:'start'});
        };
    }

    // 4. Logic Simpan AJAX (Tanpa Reload)
    var form = document.getElementById('formJurnalInput');
    if(form) {
        form.onsubmit = function(e) {
            e.preventDefault(); // Stop reload standar

            var btn = document.getElementById('btnSimpan');
            var msg = document.getElementById('msgBox');
            
            // Validasi Sederhana
            var materi = document.getElementById('materi').value;
            if(materi.trim() === "") { showToast('Materi wajib diisi!', 'warning'); return; }

            // UI Loading
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
            btn.disabled = true;
            msg.innerHTML = '';

            // Kirim Data
            var formData = new FormData(form);
            fetch('pages/guru/simpanmateri.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                var payload = null;
                try {
                    payload = JSON.parse(data);
                } catch (err) {
                    payload = null;
                }

                if (payload && payload.success) {
                    msg.innerHTML = '<div class="alert alert-success">' + payload.message + '</div>';
                } else if (payload && !payload.success) {
                    msg.innerHTML = '<div class="alert alert-danger">' + payload.message + '</div>';
                } else {
                    msg.innerHTML = data; // Fallback untuk pesan HTML lama dari PHP
                }

                // Cek jika sukses
                if((payload && payload.success) || data.includes('alert-success') || data.includes('Berhasil') || data.includes('berhasil')) {
                    form.reset();
                    btn.disabled = true;
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                    btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i> <span class="blink">Sudah upload jurnal</span>';
                    setTimeout(function() {
                        var modal = document.getElementById('journalInputModal');
                        if (modal) {
                            modal.classList.remove('is-open');
                            modal.setAttribute('aria-hidden', 'true');
                        }
                        document.body.classList.remove('modal-open-dashboard');
                        window.location.href = '../../home.php?sukses=jurnal';
                    }, 900);
                } else {
                    btn.innerHTML = 'Coba Lagi';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                msg.innerHTML = '<div class="alert alert-danger">Gagal menghubungi server.</div>';
                btn.innerHTML = 'Simpan Jurnal';
                btn.disabled = false;
            });
        };
    }

    // 5. Auto Scroll
    setTimeout(function(){
        var el = document.getElementById('formJurnalSectionStart');
        if(el) el.scrollIntoView({behavior:'smooth', block:'start'});
    }, 300);
})();
</script>
