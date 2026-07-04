<?php
// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";

// Test koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Proses hapus individual
if (isset($_POST['hapus_individual'])) {
    $id_kelas = intval($_POST['id_kelas']);
    
    try {
        mysqli_autocommit($conn, FALSE);
        
        // Ambil nama kelas
        $result = mysqli_query($conn, "SELECT kelas as nama_kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
        $kelas = mysqli_fetch_assoc($result);
        $nama_kelas = $kelas['nama_kelas'];
        
        // Cek jumlah siswa
        $check_siswa = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM tbl_siswa WHERE kelas = '$nama_kelas'");
        $siswa_count = mysqli_fetch_assoc($check_siswa)['jumlah'];
        
        if ($siswa_count > 0) {
            throw new Exception("Kelas '$nama_kelas' tidak bisa dihapus karena masih ada $siswa_count siswa!");
        }
        
        // Hapus semua data terkait
        mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas = $id_kelas");
        mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE kelas = '$nama_kelas'");
        
        // Hapus jadwal jika ada
        $check_jadwal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jadwal'");
        if ($check_jadwal && mysqli_num_rows($check_jadwal) > 0) {
            mysqli_query($conn, "DELETE FROM tbl_jadwal WHERE kelas = '$nama_kelas'");
        }
        
        // Hapus jurnal jika ada
        $check_jurnal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jurnal'");
        if ($check_jurnal && mysqli_num_rows($check_jurnal) > 0) {
            mysqli_query($conn, "DELETE FROM tbl_jurnal WHERE kelas = '$nama_kelas'");
        }
        
        // Hapus kelas
        mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");
        
        mysqli_commit($conn);
        mysqli_autocommit($conn, TRUE);
        
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Kelas \"$nama_kelas\" berhasil dihapus!',
                timer: 2000
            }).then(() => {
                window.location.reload();
            });
        </script>";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_autocommit($conn, TRUE);
        
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '" . addslashes($e->getMessage()) . "'
            });
        </script>";
    }
}

// Proses hapus batch
if (isset($_POST['hapus_batch'])) {
    $kelas_ids = $_POST['kelas_ids'] ?? [];
    
    if (!empty($kelas_ids)) {
        $berhasil = [];
        $gagal = [];
        
        mysqli_autocommit($conn, FALSE);
        
        try {
            foreach ($kelas_ids as $id_kelas) {
                $id_kelas = intval($id_kelas);
                
                // Ambil nama kelas
                $result = mysqli_query($conn, "SELECT kelas as nama_kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
                $kelas = mysqli_fetch_assoc($result);
                $nama_kelas = $kelas['nama_kelas'];
                
                // Cek jumlah siswa
                $check_siswa = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM tbl_siswa WHERE kelas = '$nama_kelas'");
                $siswa_count = mysqli_fetch_assoc($check_siswa)['jumlah'];
                
                if ($siswa_count > 0) {
                    $gagal[] = "$nama_kelas (ada $siswa_count siswa)";
                    continue;
                }
                
                // Hapus semua data terkait
                mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas = $id_kelas");
                mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE kelas = '$nama_kelas'");
                
                // Hapus jadwal jika ada
                $check_jadwal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jadwal'");
                if ($check_jadwal && mysqli_num_rows($check_jadwal) > 0) {
                    mysqli_query($conn, "DELETE FROM tbl_jadwal WHERE kelas = '$nama_kelas'");
                }
                
                // Hapus jurnal jika ada
                $check_jurnal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jurnal'");
                if ($check_jurnal && mysqli_num_rows($check_jurnal) > 0) {
                    mysqli_query($conn, "DELETE FROM tbl_jurnal WHERE kelas = '$nama_kelas'");
                }
                
                // Hapus kelas
                mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");
                
                $berhasil[] = $nama_kelas;
            }
            
            mysqli_commit($conn);
            mysqli_autocommit($conn, TRUE);
            
            $pesan = "";
            if (!empty($berhasil)) {
                $pesan .= "✅ Berhasil dihapus: " . implode(", ", $berhasil) . "\\n";
            }
            if (!empty($gagal)) {
                $pesan .= "❌ Gagal dihapus: " . implode(", ", $gagal);
            }
            
            echo "<script>
                Swal.fire({
                    icon: '" . (empty($gagal) ? "success" : "warning") . "',
                    title: 'Hasil Hapus Batch',
                    text: '$pesan',
                    timer: 3000
                }).then(() => {
                    window.location.reload();
                });
            </script>";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            mysqli_autocommit($conn, TRUE);
            
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '" . addslashes($e->getMessage()) . "'
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan!',
                text: 'Pilih kelas yang ingin dihapus terlebih dahulu!'
            });
        </script>";
    }
}

// Ambil semua kelas dengan informasi siswa - Versi Simple & Robust
$data_kelas = [];
$total_kelas = 0;
$kelas_kosong = 0;
$kelas_ada_siswa = 0;

// Query sederhana untuk mendapatkan semua kelas
$query_kelas = "SELECT id_kelas, kelas FROM tbl_kelas ORDER BY kelas";
$result_kelas = mysqli_query($conn, $query_kelas);

if ($result_kelas) {
    while($kelas = mysqli_fetch_assoc($result_kelas)) {
        $total_kelas++;
        
        // Hitung jumlah siswa untuk setiap kelas
        $nama_kelas = mysqli_real_escape_string($conn, $kelas['kelas']);
        $query_siswa = "SELECT COUNT(*) as jumlah FROM tbl_siswa WHERE kelas = '$nama_kelas'";
        $result_siswa = mysqli_query($conn, $query_siswa);
        
        $jumlah_siswa = 0;
        if ($result_siswa) {
            $siswa_data = mysqli_fetch_assoc($result_siswa);
            $jumlah_siswa = (int)$siswa_data['jumlah'];
        }
        
        // Ambil wali kelas (optional - jika error, set default)
        $wali_kelas = '-';
        try {
            $query_wali = "SELECT g.nama_guru FROM tbl_wali_kelas wk LEFT JOIN tbl_guru g ON wk.id_guru = g.id_guru WHERE wk.id_kelas = " . (int)$kelas['id_kelas'];
            $result_wali = mysqli_query($conn, $query_wali);
            if ($result_wali && mysqli_num_rows($result_wali) > 0) {
                $wali_data = mysqli_fetch_assoc($result_wali);
                $wali_kelas = $wali_data['nama_guru'] ?? '-';
            }
        } catch (Exception $e) {
            // Jika ada error di wali kelas, tetap lanjut dengan default
            $wali_kelas = '-';
        }
        
        $data_kelas[] = [
            'id_kelas' => (int)$kelas['id_kelas'],
            'nama_kelas' => $kelas['kelas'],
            'jumlah_siswa' => $jumlah_siswa,
            'wali_kelas' => $wali_kelas
        ];
        
        // Hitung statistik
        if ($jumlah_siswa == 0) {
            $kelas_kosong++;
        } else {
            $kelas_ada_siswa++;
        }
    }
} else {
    echo "<div class='alert alert-danger'>Error mengambil data kelas: " . mysqli_error($conn) . "</div>";
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- JavaScript untuk no-cache -->
<script>
// Mencegah cache browser
$(document).ready(function() {
    // Setup no-cache untuk AJAX
    $.ajaxSetup({
        cache: false
    });
});
</script>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">🗑️ Hapus Kelas 2 - Versi Sederhana</h1>
    <div>
        <button type="button" class="btn btn-info btn-sm" onclick="window.location.reload();">
            🔄 Refresh Data
        </button>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <i class="fas fa-info-circle"></i>
    <strong>Informasi:</strong> Halaman ini khusus untuk menghapus kelas kosong (tanpa siswa). Kelas yang masih memiliki siswa tidak bisa dihapus.
</div>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Kelas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_kelas ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-school fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kelas Kosong (Bisa Dihapus)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $kelas_kosong ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Kelas Ada Siswa</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $kelas_ada_siswa ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Persentase Kosong</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $total_kelas > 0 ? round(($kelas_kosong / $total_kelas) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Batch Delete -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
        <div>
            <button type="button" class="btn btn-danger btn-sm" id="btn-hapus-batch" style="display: none;">
                <i class="fas fa-trash-alt"></i> Hapus Terpilih (<span id="count-selected">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="form-batch-delete" method="POST">
            <input type="hidden" name="hapus_batch" value="1">
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all" class="form-check-input">
                            </th>
                            <th width="50">No</th>
                            <th>Nama Kelas</th>
                            <th>Jumlah Siswa</th>
                            <th>Wali Kelas</th>
                            <th>Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($data_kelas as $row): 
                            $bisa_hapus = ($row['jumlah_siswa'] == 0);
                            $status_class = $bisa_hapus ? 'text-success' : 'text-danger';
                            $status_text = $bisa_hapus ? 'Bisa Dihapus' : 'Ada Siswa';
                        ?>
                        <tr class="<?= $bisa_hapus ? '' : 'table-warning' ?>">
                            <td>
                                <?php if($bisa_hapus): ?>
                                <input type="checkbox" name="kelas_ids[]" value="<?= $row['id_kelas'] ?>" class="checkbox-kelas form-check-input">
                                <?php endif; ?>
                            </td>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_kelas']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= $bisa_hapus ? 'success' : 'warning' ?>">
                                    <?= $row['jumlah_siswa'] ?> siswa
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['wali_kelas'] ?? '-') ?></td>
                            <td>
                                <span class="<?= $status_class ?>">
                                    <i class="fas fa-<?= $bisa_hapus ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                                    <?= $status_text ?>
                                </span>
                            </td>
                            <td>
                                <?php if($bisa_hapus): ?>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="hapusIndividual(<?= $row['id_kelas'] ?>, '<?= addslashes($row['nama_kelas']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-sm" disabled title="Masih ada siswa">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

</div>
<!-- /.container-fluid -->

<script>
$(document).ready(function() {
    // Handle select all checkbox
    $('#select-all').change(function() {
        $('.checkbox-kelas').prop('checked', this.checked);
        updateBatchButton();
    });
    
    // Handle individual checkbox
    $(document).on('change', '.checkbox-kelas', function() {
        updateBatchButton();
    });
    
    // Handle batch delete button
    $('#btn-hapus-batch').click(function() {
        const selected = $('.checkbox-kelas:checked').length;
        if (selected === 0) {
            Swal.fire('Peringatan!', 'Pilih kelas yang ingin dihapus!', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Konfirmasi Hapus Batch',
            text: `Yakin ingin menghapus ${selected} kelas terpilih?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#form-batch-delete').submit();
            }
        });
    });
});

function updateBatchButton() {
    const selected = $('.checkbox-kelas:checked').length;
    $('#count-selected').text(selected);
    
    if (selected > 0) {
        $('#btn-hapus-batch').show();
    } else {
        $('#btn-hapus-batch').hide();
    }
}

function hapusIndividual(id_kelas, nama_kelas) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: `Yakin ingin menghapus kelas "${nama_kelas}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit form
            const form = $('<form>', {
                'method': 'POST',
                'action': ''
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'hapus_individual',
                'value': '1'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'id_kelas',
                'value': id_kelas
            }));
            
            $('body').append(form);
            form.submit();
        }
    });
}
</script>