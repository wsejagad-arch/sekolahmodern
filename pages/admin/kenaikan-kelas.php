<?php
if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    echo '<p class="text-danger p-3">Akses ditolak.</p>';
    return;
}

include_once __DIR__ . '/../../koneksi.php';

$adminNama = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Admin');
$notifType = '';
$notifMsg = '';

function nk_esc($conn, $value)
{
    return mysqli_real_escape_string($conn, trim((string)$value));
}

function nk_is_active_filter_sql()
{
    return "(status IS NULL OR status = '' OR UPPER(status) = 'AKTIF')";
}

function nk_promote_xi_to_xii($kelas)
{
    $kelas = trim((string)$kelas);
    if ($kelas === '') {
        return '';
    }

    if (preg_match('/^(XI|11)\b/i', $kelas)) {
        return preg_replace('/^(XI|11)\b/i', 'XII', $kelas, 1);
    }

    return $kelas;
}

function nk_ensure_class_exists($conn, $kelas)
{
    $kelas = trim((string)$kelas);
    if ($kelas === '') {
        return;
    }

    $kelasEsc = nk_esc($conn, $kelas);
    $cek = @mysqli_query($conn, "SELECT id_kelas FROM tbl_kelas WHERE TRIM(kelas) = '$kelasEsc' LIMIT 1");
    if ($cek && mysqli_num_rows($cek) > 0) {
        return;
    }

    @mysqli_query($conn, "INSERT INTO tbl_kelas (kelas) VALUES ('$kelasEsc')");
}

function nk_update_class_sql_parts($conn, $targetClass)
{
    $parts = ["kelas = '$targetClass'"];

    $colNamaKelas = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'nama_kelas'");
    if ($colNamaKelas && mysqli_num_rows($colNamaKelas) > 0) {
        $parts[] = "nama_kelas = '$targetClass'";
    }

    $colRombel = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'rombel_saat_ini'");
    if ($colRombel && mysqli_num_rows($colRombel) > 0) {
        $parts[] = "rombel_saat_ini = '$targetClass'";
    }

    return implode(', ', $parts);
}

function nk_write_log($conn, $message)
{
    $safeMessage = nk_esc($conn, $message);
    $now = date('Y-m-d H:i:s');
    @mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES ('$now', '$safeMessage')");
}

function nk_cascade_class_update($conn, $tujuanEsc, $asalEsc = null, $noIndukEsc = null)
{
    $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $tenantFilter = function_exists('mt_column_exists') && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? " AND id_sekolah={$tenantId}" : "";
    
    if ($noIndukEsc !== null) {
        @mysqli_query($conn, "UPDATE tbl_absen SET kelas = '$tujuanEsc' WHERE no_induk = '$noIndukEsc' {$tenantFilter}");
        @mysqli_query($conn, "UPDATE tbl_izin_siswa SET kelas_siswa = '$tujuanEsc' WHERE no_induk_siswa = '$noIndukEsc' " . str_replace("id_sekolah", "id_sekolah", $tenantFilter));
        @mysqli_query($conn, "UPDATE tbl_pelanggaran_siswa SET kelas = '$tujuanEsc' WHERE no_induk = '$noIndukEsc' {$tenantFilter}");
        @mysqli_query($conn, "UPDATE tbl_siswa_eraport SET kelas = '$tujuanEsc' WHERE nis = '$noIndukEsc' {$tenantFilter}");
    } elseif ($asalEsc !== null) {
        @mysqli_query($conn, "UPDATE tbl_absen SET kelas = '$tujuanEsc' WHERE kelas = '$asalEsc' {$tenantFilter}");
        @mysqli_query($conn, "UPDATE tbl_izin_siswa SET kelas_siswa = '$tujuanEsc' WHERE kelas_siswa = '$asalEsc' " . str_replace("id_sekolah", "id_sekolah", $tenantFilter));
        @mysqli_query($conn, "UPDATE tbl_pelanggaran_siswa SET kelas = '$tujuanEsc' WHERE kelas = '$asalEsc' {$tenantFilter}");
        @mysqli_query($conn, "UPDATE tbl_siswa_eraport SET kelas = '$tujuanEsc' WHERE kelas = '$asalEsc' {$tenantFilter}");
    }
}

function nk_graduate_student($conn, $no_induk, $tenantId)
{
    $noIndukEsc = nk_esc($conn, $no_induk);
    $tenantEsc = (int)$tenantId;
    
    // Get student info
    $q_s = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$noIndukEsc' LIMIT 1");
    if (!$q_s || mysqli_num_rows($q_s) === 0) return false;
    $s = mysqli_fetch_assoc($q_s);
    
    $nisn = nk_esc($conn, $s['nisn'] ?? '');
    $nama = nk_esc($conn, $s['nama_siswa'] ?? '');
    $wa = nk_esc($conn, $s['no_wa'] ?? '');
    $alamat = nk_esc($conn, $s['alamat'] ?? '');
    $kelas = nk_esc($conn, $s['kelas'] ?? '');
    $tahun_lulus = date('Y');
    
    // Get wali kelas
    $wali_kelas = '';
    if ($kelas !== '') {
        $q_wk = mysqli_query($conn, "SELECT wali_kelas FROM tbl_kelas WHERE TRIM(kelas)='$kelas' AND id_sekolah=$tenantEsc LIMIT 1");
        if ($q_wk && $r_wk = mysqli_fetch_assoc($q_wk)) {
            $wali_kelas = nk_esc($conn, $r_wk['wali_kelas'] ?? '');
        }
    }
    
    // Insert to tbl_alumni
    $now = date('Y-m-d H:i:s');
    mysqli_query($conn, "INSERT INTO tbl_alumni (no_induk, nisn, nama_siswa, no_wa, alamat, histori_kelas, histori_wali_kelas, tahun_lulus, id_sekolah, created_at) VALUES ('$noIndukEsc', '$nisn', '$nama', '$wa', '$alamat', '$kelas', '$wali_kelas', '$tahun_lulus', $tenantEsc, '$now')");
    
    // Update status to Lulus
    mysqli_query($conn, "UPDATE tbl_siswa SET status='Lulus' WHERE no_induk='$noIndukEsc'");
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    mysqli_autocommit($conn, false);

    try {
        if ($mode === 'global_xi_to_xii') {
            $activeFilter = nk_is_active_filter_sql();
            $resKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa WHERE $activeFilter AND (kelas REGEXP '^[[:space:]]*(XI|11)\\\\b')");
            if (!$resKelas) {
                throw new Exception('Gagal membaca daftar kelas XI.');
            }

            $totalUpdated = 0;
            while ($row = mysqli_fetch_assoc($resKelas)) {
                $asal = trim((string)($row['kelas'] ?? ''));
                if ($asal === '') {
                    continue;
                }

                $tujuan = nk_promote_xi_to_xii($asal);
                if ($tujuan === '' || $tujuan === $asal) {
                    continue;
                }

                nk_ensure_class_exists($conn, $tujuan);
                $asalEsc = nk_esc($conn, $asal);
                $tujuanEsc = nk_esc($conn, $tujuan);
                $setSql = nk_update_class_sql_parts($conn, $tujuanEsc);

                $q = mysqli_query($conn, "UPDATE tbl_siswa SET $setSql WHERE $activeFilter AND TRIM(kelas) = '$asalEsc'");
                if (!$q) {
                    throw new Exception('Gagal memproses kelas ' . $asal . ': ' . mysqli_error($conn));
                }
                nk_cascade_class_update($conn, $tujuanEsc, $asalEsc);
                $totalUpdated += mysqli_affected_rows($conn);
            }

            nk_write_log($conn, $adminNama . ' menjalankan kenaikan kelas global XI ke XII, total siswa: ' . $totalUpdated);
            $notifType = 'success';
            $notifMsg = 'Kenaikan kelas global XI ke XII berhasil. Total siswa diperbarui: ' . $totalUpdated;
        } elseif ($mode === 'global_xii_lulus') {
            $activeFilter = nk_is_active_filter_sql();
            // Ambil semua siswa XII
            $resSiswa = mysqli_query($conn, "SELECT no_induk FROM tbl_siswa WHERE $activeFilter AND (kelas REGEXP '^[[:space:]]*(XII|12)\\\\b')");
            if (!$resSiswa) {
                throw new Exception('Gagal membaca daftar siswa kelas XII.');
            }
            
            $totalGraduated = 0;
            while ($row = mysqli_fetch_assoc($resSiswa)) {
                if (nk_graduate_student($conn, $row['no_induk'], $tenantId)) {
                    $totalGraduated++;
                }
            }
            
            nk_write_log($conn, $adminNama . ' menjalankan kelulusan global kelas XII, total lulus: ' . $totalGraduated);
            $notifType = 'success';
            $notifMsg = 'Kelulusan global kelas XII berhasil. Total siswa lulus: ' . $totalGraduated;
        } elseif ($mode === 'per_kelas') {
            $sourceClass = trim((string)($_POST['source_class'] ?? ''));
            $targetClass = trim((string)($_POST['target_class'] ?? ''));

            if ($sourceClass === '' || $targetClass === '') {
                throw new Exception('Kelas asal dan tujuan wajib dipilih.');
            }
            if ($sourceClass === $targetClass) {
                throw new Exception('Kelas asal dan tujuan tidak boleh sama.');
            }

            nk_ensure_class_exists($conn, $targetClass);
            $sourceEsc = nk_esc($conn, $sourceClass);
            $targetEsc = nk_esc($conn, $targetClass);
            $setSql = nk_update_class_sql_parts($conn, $targetEsc);
            $activeFilter = nk_is_active_filter_sql();

            $q = mysqli_query($conn, "UPDATE tbl_siswa SET $setSql WHERE $activeFilter AND TRIM(kelas) = '$sourceEsc'");
            if (!$q) {
                throw new Exception('Gagal memindahkan siswa per kelas: ' . mysqli_error($conn));
            }
            
            nk_cascade_class_update($conn, $targetEsc, $sourceEsc);

            $updated = mysqli_affected_rows($conn);
            nk_write_log($conn, $adminNama . ' memindahkan kelas ' . $sourceClass . ' ke ' . $targetClass . ', total siswa: ' . $updated);
            $notifType = 'success';
            $notifMsg = 'Berhasil memindahkan kelas ' . $sourceClass . ' ke ' . $targetClass . '. Total siswa: ' . $updated;
        } elseif ($mode === 'individu') {
            $noInduk = trim((string)($_POST['student_no_induk'] ?? ''));
            $targetClass = trim((string)($_POST['target_class_individual'] ?? ''));

            if ($noInduk === '' || $targetClass === '') {
                throw new Exception('Siswa dan kelas tujuan wajib dipilih.');
            }

            nk_ensure_class_exists($conn, $targetClass);
            $noIndukEsc = nk_esc($conn, $noInduk);
            $targetEsc = nk_esc($conn, $targetClass);
            $setSql = nk_update_class_sql_parts($conn, $targetEsc);

            $cek = mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk = '$noIndukEsc' LIMIT 1");
            if (!$cek || mysqli_num_rows($cek) === 0) {
                throw new Exception('Data siswa tidak ditemukan.');
            }
            $rowSiswa = mysqli_fetch_assoc($cek);

            if ($targetClass === 'LULUS') {
                nk_graduate_student($conn, $noInduk, $tenantId);
                $updated = 1;
                $notifMsg = 'Siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' berhasil diluluskan.';
            } elseif ($targetClass === 'TIDAK_LULUS') {
                $q = mysqli_query($conn, "UPDATE tbl_siswa SET status='Tidak Lulus' WHERE no_induk = '$noIndukEsc'");
                $updated = 1;
                $notifMsg = 'Status siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' diubah menjadi Tidak Lulus.';
            } else {
                nk_ensure_class_exists($conn, $targetClass);
                $targetEsc = nk_esc($conn, $targetClass);
                $setSql = nk_update_class_sql_parts($conn, $targetEsc);
                
                $q = mysqli_query($conn, "UPDATE tbl_siswa SET $setSql WHERE no_induk = '$noIndukEsc'");
                if (!$q) {
                    throw new Exception('Gagal memindahkan siswa: ' . mysqli_error($conn));
                }
                nk_cascade_class_update($conn, $targetEsc, null, $noIndukEsc);
                $updated = mysqli_affected_rows($conn);
                $notifMsg = 'Berhasil memindahkan siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' ke kelas ' . $targetClass . '. Data diperbarui: ' . $updated;
            }

            nk_write_log($conn, $adminNama . ' melakukan aksi individu pada siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' ke target ' . $targetClass);
            $notifType = 'success';
        } elseif ($mode === 'individu_tabel') {
            $noInduk = trim((string)($_POST['student_no_induk_row'] ?? ''));
            $targetClass = trim((string)($_POST['target_class_row'] ?? ''));

            if ($noInduk === '' || $targetClass === '') {
                throw new Exception('Siswa dan kelas tujuan wajib dipilih.');
            }

            nk_ensure_class_exists($conn, $targetClass);
            $noIndukEsc = nk_esc($conn, $noInduk);
            $targetEsc = nk_esc($conn, $targetClass);
            $setSql = nk_update_class_sql_parts($conn, $targetEsc);

            $cek = mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk = '$noIndukEsc' LIMIT 1");
            if (!$cek || mysqli_num_rows($cek) === 0) {
                throw new Exception('Data siswa tidak ditemukan.');
            }
            $rowSiswa = mysqli_fetch_assoc($cek);

            if ($targetClass === 'LULUS') {
                nk_graduate_student($conn, $noInduk, $tenantId);
                $updated = 1;
                $notifMsg = 'Siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' berhasil diluluskan.';
            } elseif ($targetClass === 'TIDAK_LULUS') {
                $q = mysqli_query($conn, "UPDATE tbl_siswa SET status='Tidak Lulus' WHERE no_induk = '$noIndukEsc'");
                $updated = 1;
                $notifMsg = 'Status siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' diubah menjadi Tidak Lulus.';
            } else {
                if (trim((string)($rowSiswa['kelas'] ?? '')) === $targetClass) {
                    throw new Exception('Siswa sudah berada di kelas tujuan.');
                }
                
                nk_ensure_class_exists($conn, $targetClass);
                $targetEsc = nk_esc($conn, $targetClass);
                $setSql = nk_update_class_sql_parts($conn, $targetEsc);

                $q = mysqli_query($conn, "UPDATE tbl_siswa SET $setSql WHERE no_induk = '$noIndukEsc'");
                if (!$q) {
                    throw new Exception('Gagal memindahkan siswa (tabel): ' . mysqli_error($conn));
                }
                nk_cascade_class_update($conn, $targetEsc, null, $noIndukEsc);
                $updated = mysqli_affected_rows($conn);
                $notifMsg = 'Berhasil memindahkan siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' ke kelas ' . $targetClass . '. Data diperbarui: ' . $updated;
            }

            nk_write_log($conn, $adminNama . ' melakukan aksi individu (tabel) pada ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' ke target ' . $targetClass);
            $notifType = 'success';
            $notifMsg = 'Berhasil memindahkan siswa ' . ($rowSiswa['nama_siswa'] ?? $noInduk) . ' ke kelas ' . $targetClass . '. Data diperbarui: ' . $updated;
        } elseif ($mode === 'clean_kelas') {
            // Bersihkan kelas ganda dan alumni
            $q1 = mysqli_query($conn, "UPDATE tbl_siswa SET kelas = NULL WHERE status IN ('Lulus', 'Alumni') AND kelas IS NOT NULL");
            $affected_alumni = mysqli_affected_rows($conn);

            $q2 = mysqli_query($conn, "
                SELECT kelas, id_sekolah, COUNT(*) as c, MIN(id) as min_id 
                FROM tbl_kelas 
                WHERE id_sekolah = $tenantId
                GROUP BY kelas, id_sekolah 
                HAVING c > 1
            ");
            $deleted_kelas = 0;
            if ($q2) {
                while($r = mysqli_fetch_assoc($q2)) {
                    $kelasEsc = mysqli_real_escape_string($conn, $r['kelas']);
                    $min_id = (int) $r['min_id'];
                    $q_del = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE kelas='$kelasEsc' AND id_sekolah=$tenantId AND id != $min_id");
                    if ($q_del) {
                        $deleted_kelas += mysqli_affected_rows($conn);
                    }
                }
            }
            nk_write_log($conn, $adminNama . ' membersihkan kelas ganda dan mereset kelas alumni.');
            $notifType = 'success';
            $notifMsg = "Berhasil mereset $affected_alumni kelas alumni dan menghapus $deleted_kelas kelas ganda.";
        } else {
            throw new Exception('Mode kenaikan kelas tidak valid.');
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $notifType = 'error';
        $notifMsg = $e->getMessage();
    }

    mysqli_autocommit($conn, true);
}

$kelasOptions = [];
$resKelas = @mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
if ($resKelas) {
    while ($row = mysqli_fetch_assoc($resKelas)) {
        $kelasOptions[] = $row['kelas'];
    }
}

if (empty($kelasOptions)) {
    $resDistinct = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
    if ($resDistinct) {
        while ($row = mysqli_fetch_assoc($resDistinct)) {
            $kelasOptions[] = $row['kelas'];
        }
    }
}

$students = [];
$activeFilter = nk_is_active_filter_sql();
$resSiswa = @mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE $activeFilter ORDER BY nama_siswa ASC");
if ($resSiswa) {
    while ($row = mysqli_fetch_assoc($resSiswa)) {
        $students[] = $row;
    }
}

$countX = 0;
$countXI = 0;
$studentsByClass = [];
foreach ($students as $s) {
    $k = strtoupper(trim((string)($s['kelas'] ?? '')));
    if (preg_match('/^(X)\\b/', $k)) {
        $countX += 1;
    }
    if (preg_match('/^(XI|11)\\b/', $k)) {
        $countXI += 1;
    }

    $kelasAsli = trim((string)($s['kelas'] ?? 'Tanpa Kelas'));
    if ($kelasAsli === '') {
        $kelasAsli = 'Tanpa Kelas';
    }
    if (!isset($studentsByClass[$kelasAsli])) {
        $studentsByClass[$kelasAsli] = [];
    }
    $studentsByClass[$kelasAsli][] = $s;
}

ksort($studentsByClass, SORT_NATURAL | SORT_FLAG_CASE);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-level-up-alt text-primary mr-2"></i>Naik Kelas Siswa</h1>
        <div class="d-flex gap-2">
            <form method="post" onsubmit="return confirm('Bersihkan kelas ganda dan reset kelas alumni?');" class="mr-2">
                <input type="hidden" name="mode" value="clean_kelas">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-broom mr-1"></i>Bersihkan Kelas Ganda</button>
            </form>
            <a href="?page=data-siswa" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Siswa Aktif Kelas X</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int)$countX ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Siswa Aktif Kelas XI</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int)$countXI ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Siswa Aktif</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($students) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Ketentuan:</strong> XI dapat dinaikkan ke XII secara global, per kelas, atau individu. Kelas X ke XI bisa diatur acak menggunakan mode per kelas atau individu sesuai keputusan admin. Setelah proses, kolom kelas pada database siswa langsung diperbarui.
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">1) Global XI ke XII</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Semua siswa aktif dengan kelas diawali XI/11 otomatis naik ke kelas XII.</p>
                    <form method="post" onsubmit="return confirm('Jalankan kenaikan global XI ke XII sekarang?');">
                        <input type="hidden" name="mode" value="global_xi_to_xii">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-rocket mr-1"></i>Proses XI ke XII</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">2) Global Kelulusan XII</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Meluluskan semua siswa aktif berawalan kelas XII dan memindahkannya ke tabel alumni.</p>
                    <form method="post" onsubmit="return confirm('Luluskan semua siswa kelas XII dan masukkan ke Data Alumni?');">
                        <input type="hidden" name="mode" value="global_xii_lulus">
                        <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-graduation-cap mr-1"></i>Proses Global Kelulusan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">3) Naik Kelas Per Kelas</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Pindahkan seluruh siswa aktif dari satu kelas ke kelas tujuan. Cocok untuk X ke XI atau XI ke XII sesuai hasil kenaikan.</p>
                    <form method="post" onsubmit="return confirm('Proses naik kelas per kelas?');">
                        <input type="hidden" name="mode" value="per_kelas">
                        <div class="form-group">
                            <label>Kelas Asal</label>
                            <select class="form-control" name="source_class" required>
                                <option value="">-- Pilih kelas asal --</option>
                                <?php foreach ($kelasOptions as $kelas): ?>
                                    <option value="<?= htmlspecialchars($kelas) ?>"><?= htmlspecialchars($kelas) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kelas Tujuan</label>
                            <select class="form-control" name="target_class" required>
                                <option value="">-- Pilih kelas tujuan --</option>
                                <?php foreach ($kelasOptions as $kelas): ?>
                                    <option value="<?= htmlspecialchars($kelas) ?>"><?= htmlspecialchars($kelas) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-block"><i class="fas fa-random mr-1"></i>Proses Per Kelas</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">4) Naik Kelas Individu</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Pindahkan siswa satu per satu sesuai hasil kenaikan kelas masing-masing siswa.</p>
                    <form method="post" onsubmit="return confirm('Proses naik kelas individu?');">
                        <input type="hidden" name="mode" value="individu">
                        <div class="form-group">
                            <label>Pilih Siswa</label>
                            <select class="form-control" name="student_no_induk" required>
                                <option value="">-- Pilih siswa --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= htmlspecialchars($s['no_induk']) ?>"><?= htmlspecialchars(($s['nama_siswa'] ?? '-') . ' | ' . ($s['kelas'] ?? '-')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kelas / Status Tujuan</label>
                            <select class="form-control" name="target_class_individual" required>
                                <option value="">-- Pilih tujuan / status --</option>
                                <optgroup label="Ubah Status">
                                    <option value="LULUS">Lulus (Pindah ke Alumni)</option>
                                    <option value="TIDAK_LULUS">Tidak Lulus (Tetap Aktif/Mengulang)</option>
                                </optgroup>
                                <optgroup label="Pindah Kelas">
                                <?php foreach ($kelasOptions as $kelas): ?>
                                    <option value="<?= htmlspecialchars($kelas) ?>"><?= htmlspecialchars($kelas) ?></option>
                                <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-user-edit mr-1"></i>Proses Individu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-table mr-1"></i>Tabel Nama Siswa Per Kelas</h6>
            <span class="small text-muted">Aksi naik kelas bisa dilakukan langsung per siswa</span>
        </div>
        <div class="card-body">
            <?php if (empty($studentsByClass)): ?>
                <div class="alert alert-warning mb-0">Belum ada data siswa aktif untuk ditampilkan.</div>
            <?php else: ?>
                <?php foreach ($studentsByClass as $kelasNama => $kelasSiswa): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="font-weight-bold text-gray-800 mb-0">Kelas: <?= htmlspecialchars($kelasNama) ?></h6>
                            <span class="badge badge-secondary"><?= count($kelasSiswa) ?> siswa</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:60px">No</th>
                                        <th style="width:150px">No Induk</th>
                                        <th>Nama Siswa</th>
                                        <th style="width:260px">Aksi Naik Kelas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kelasSiswa as $idx => $s): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><?= htmlspecialchars($s['no_induk'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($s['nama_siswa'] ?? '-') ?></td>
                                            <td>
                                                <form method="post" class="d-flex" onsubmit="return confirm('Pindahkan siswa ini ke kelas tujuan?');">
                                                    <input type="hidden" name="mode" value="individu_tabel">
                                                    <input type="hidden" name="student_no_induk_row" value="<?= htmlspecialchars($s['no_induk'] ?? '') ?>">
                                                    <select class="form-control form-control-sm mr-2" name="target_class_row" required style="max-width: 150px;">
                                                        <option value="">Aksi / Tujuan...</option>
                                                        <optgroup label="Ubah Status">
                                                            <option value="LULUS">🎓 Lulus</option>
                                                            <option value="TIDAK_LULUS">❌ Tidak Lulus</option>
                                                        </optgroup>
                                                        <optgroup label="Pindah Kelas">
                                                        <?php foreach ($kelasOptions as $kelasOpt): ?>
                                                            <option value="<?= htmlspecialchars($kelasOpt) ?>" <?= (($s['kelas'] ?? '') === $kelasOpt) ? 'disabled' : '' ?>>
                                                                <?= htmlspecialchars($kelasOpt) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        </optgroup>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Proses</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($notifMsg !== ''): ?>
    <script>
        (function() {
            var type = <?= json_encode($notifType) ?>;
            var msg = <?= json_encode($notifMsg) ?>;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'success' ? 'success' : 'error',
                    title: type === 'success' ? 'Berhasil' : 'Gagal',
                    text: msg
                });
            } else {
                alert(msg);
            }
        })();
    </script>
<?php endif; ?>