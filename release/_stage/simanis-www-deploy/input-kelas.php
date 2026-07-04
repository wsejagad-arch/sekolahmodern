<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";

// Proses tambah kelas
if (isset($_POST['submit_tambah_kelas'])) {
    $nama_kelas = trim(mysqli_real_escape_string($conn, $_POST['nama_kelas']));
    
    if (!empty($nama_kelas)) {
        // Cek apakah kelas sudah ada
        $cek = mysqli_query($conn, "SELECT * FROM tbl_kelas WHERE kelas = '$nama_kelas'");
        if (mysqli_num_rows($cek) == 0) {
            $sql = "INSERT INTO tbl_kelas (kelas) VALUES ('$nama_kelas')";
            if (mysqli_query($conn, $sql)) {
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Kelas berhasil ditambahkan',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        window.location.href = '?page=input-kelas';
                    });
                </script>";
            }
        } else {
            echo "<script>Swal.fire('Gagal', 'Kelas sudah ada!', 'error')</script>";
        }
    }
}

// Proses hapus kelas
if (isset($_GET['hapus_kelas'])) {
    $id_kelas = (int)$_GET['hapus_kelas'];
    
    // Ambil nama kelas terlebih dahulu
    $get_kelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
    if (!$get_kelas || mysqli_num_rows($get_kelas) == 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Kelas tidak ditemukan!'
            });
        </script>";
    } else {
        $kelas_data = mysqli_fetch_assoc($get_kelas);
        $nama_kelas = mysqli_real_escape_string($conn, $kelas_data['kelas']);
        
        // Cek apakah kelas masih digunakan dengan query yang lebih akurat
        $cek_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas' AND (status IS NULL OR status = '' OR UPPER(status) = 'AKTIF')");
        $row_siswa = mysqli_fetch_assoc($cek_siswa);
        
        $cek_mapel = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu WHERE kelas = '$nama_kelas'");
        $row_mapel = mysqli_fetch_assoc($cek_mapel);
        
        // Cek jadwal jika tabel ada
        $cek_jadwal_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jadwal'");
        $row_jadwal = ['total' => 0];
        if ($cek_jadwal_table && mysqli_num_rows($cek_jadwal_table) > 0) {
            $cek_jadwal = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_jadwal WHERE kelas = '$nama_kelas'");
            if ($cek_jadwal) {
                $row_jadwal = mysqli_fetch_assoc($cek_jadwal);
            }
        }
        
        // Cek jurnal/tugas jika ada
        $cek_jurnal_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jurnal'");
        $row_jurnal = ['total' => 0];
        if ($cek_jurnal_table && mysqli_num_rows($cek_jurnal_table) > 0) {
            $cek_jurnal = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_jurnal WHERE kelas = '$nama_kelas'");
            if ($cek_jurnal) {
                $row_jurnal = mysqli_fetch_assoc($cek_jurnal);
            }
        }
        
        $total_penggunaan = $row_siswa['total'] + $row_mapel['total'] + $row_jadwal['total'] + $row_jurnal['total'];
        
        if ($total_penggunaan > 0) {
            $pesan = "Kelas '$nama_kelas' masih digunakan oleh: ";
            $alasan = [];
            if ($row_siswa['total'] > 0) $alasan[] = $row_siswa['total'] . " siswa aktif";
            if ($row_mapel['total'] > 0) $alasan[] = $row_mapel['total'] . " mata pelajaran";
            if ($row_jadwal['total'] > 0) $alasan[] = $row_jadwal['total'] . " jadwal";
            if ($row_jurnal['total'] > 0) $alasan[] = $row_jurnal['total'] . " jurnal";
            
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Kelas Masih Digunakan!',
                    html: '" . $pesan . implode(", ", $alasan) . "<br><br><small class=\"text-danger\">💡 <strong>Solusi:</strong><br>1. Pindahkan siswa ke kelas lain<br>2. Hapus mata pelajaran untuk kelas ini<br>3. Hapus jadwal yang menggunakan kelas ini</small><br><br><strong>Atau klik tombol di bawah jika yakin data sudah tidak digunakan:</strong>',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Batal',
                    denyButtonText: 'Paksa Hapus',
                    cancelButtonText: 'Debug Info',
                    confirmButtonColor: '#6c757d',
                    denyButtonColor: '#dc3545',
                    cancelButtonColor: '#007bff'
                }).then((result) => {
                    if (result.isDenied) {
                        Swal.fire({
                            title: 'Konfirmasi Paksa Hapus',
                            text: 'Yakin ingin menghapus paksa kelas ini? Semua data terkait akan dihapus!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Paksa Hapus!',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#dc3545'
                        }).then((confirmResult) => {
                            if (confirmResult.isConfirmed) {
                                window.location.href = '?page=input-kelas&paksa_hapus=' + $id_kelas;
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        window.open('debug-hapus-kelas.php?test_id=$id_kelas', '_blank');
                    }
                });
            </script>";
        } else {
            // Kelas tidak digunakan, boleh dihapus
            mysqli_autocommit($conn, FALSE);
            
            try {
                // Hapus data wali kelas terlebih dahulu (jika ada)
                $sql_wali = "DELETE FROM tbl_wali_kelas WHERE id_kelas = $id_kelas";
                mysqli_query($conn, $sql_wali); // Tidak perlu cek error karena mungkin tidak ada wali kelas
                
                // Hapus kelas
                $sql = "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas";
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception("Gagal menghapus kelas: " . mysqli_error($conn));
                }
                
                // Commit transaksi
                mysqli_commit($conn);
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Kelas \"$nama_kelas\" berhasil dihapus',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function() {
                        // Force reload dengan cache busting
                        const url = new URL(window.location);
                        url.searchParams.set('_refresh', Date.now());
                        window.location.href = url.toString();
                    });
                </script>";
                
            } catch (Exception $e) {
                // Rollback jika ada error
                mysqli_rollback($conn);
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: '" . addslashes($e->getMessage()) . "'
                    });
                </script>";
            }
            
            // Kembalikan autocommit
            mysqli_autocommit($conn, TRUE);
        }
    }
}

// Proses paksa hapus kelas (menghapus semua data terkait)
if (isset($_GET['paksa_hapus'])) {
    $id_kelas = (int)$_GET['paksa_hapus'];
    
    // Ambil nama kelas
    $get_kelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
    if (!$get_kelas || mysqli_num_rows($get_kelas) == 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Kelas tidak ditemukan!'
            });
        </script>";
    } else {
        $kelas_data = mysqli_fetch_assoc($get_kelas);
        $nama_kelas = mysqli_real_escape_string($conn, $kelas_data['kelas']);
        
        mysqli_autocommit($conn, FALSE);
        
        try {
            // 1. Hapus data jurnal/tugas (jika tabel ada)
            $check_jurnal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jurnal'");
            if ($check_jurnal && mysqli_num_rows($check_jurnal) > 0) {
                mysqli_query($conn, "DELETE FROM tbl_jurnal WHERE kelas = '$nama_kelas'");
            }
            
            // 2. Hapus jadwal (jika tabel ada)
            $check_jadwal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jadwal'");
            if ($check_jadwal && mysqli_num_rows($check_jadwal) > 0) {
                mysqli_query($conn, "DELETE FROM tbl_jadwal WHERE kelas = '$nama_kelas'");
            }
            
            // 3. Hapus mata pelajaran ampu
            mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE kelas = '$nama_kelas'");
            
            // 4. Update siswa (set kelas menjadi NULL)
            mysqli_query($conn, "UPDATE tbl_siswa SET kelas = NULL WHERE kelas = '$nama_kelas'");
            
            // 5. Hapus wali kelas
            mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas = $id_kelas");
            
            // 6. Hapus kelas
            $sql = "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas";
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Gagal menghapus kelas: " . mysqli_error($conn));
            }
            
            mysqli_commit($conn);
            
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Paksa Hapus!',
                    html: 'Kelas \"$nama_kelas\" dan semua data terkait berhasil dihapus<br><small class=\"text-info\">📋 Data yang dihapus: jurnal, jadwal, mata pelajaran, dan wali kelas.<br>👥 Siswa dipindah ke status tanpa kelas.</small>',
                    showConfirmButton: true,
                    timer: 4000
                }).then(function() {
                    // Refresh tabel secara dinamis dulu
                    refreshTableContent();
                    
                    // Delay reload untuk melihat effect refresh
                    setTimeout(() => {
                        const url = new URL(window.location);
                        url.searchParams.delete('hapus_kelas');
                        url.searchParams.delete('paksa_hapus');
                        url.searchParams.delete('hapus_batch');
                        url.searchParams.set('_refresh', Date.now());
                        window.location.replace(url.toString());
                    }, 2000);
                });
            </script>";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Paksa Hapus!',
                    text: '" . addslashes($e->getMessage()) . "'
                });
            </script>";
        }
        
        mysqli_autocommit($conn, TRUE);
    }
}

// Proses hapus batch kelas
if (isset($_POST['hapus_batch_kelas'])) {
    echo "<script>console.log('POST hapus_batch_kelas diterima');</script>";
    
    if (!empty($_POST['kelas_terpilih']) && is_array($_POST['kelas_terpilih'])) {
        $kelas_ids = array_map('intval', $_POST['kelas_terpilih']); // Sanitasi input
        $kelas_ids_str = implode(',', $kelas_ids);
        
        echo "<script>console.log('Kelas ID yang dipilih: " . implode(', ', $kelas_ids) . "');</script>";
        
        // Ambil data kelas yang dipilih
        $query_kelas = "SELECT id_kelas, kelas FROM tbl_kelas WHERE id_kelas IN ($kelas_ids_str)";
        $result_kelas = mysqli_query($conn, $query_kelas);
        
        $kelas_bisa_dihapus = [];
        $kelas_tidak_bisa = [];
        
        while ($row_kelas = mysqli_fetch_assoc($result_kelas)) {
            $id_kelas = $row_kelas['id_kelas'];
            $nama_kelas = mysqli_real_escape_string($conn, $row_kelas['kelas']);
            
            // Cek apakah kelas memiliki murid aktif
            // Simplified query untuk testing
            $cek_murid = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas'");
            $row_murid = mysqli_fetch_assoc($cek_murid);
            
            echo "<script>console.log('Kelas $nama_kelas memiliki " . $row_murid['total'] . " murid total');</script>";
            
            // Debug: cek juga dengan kondisi status
            $cek_murid_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas' AND (status IS NULL OR status = '' OR UPPER(status) = 'AKTIF')");
            $row_murid_aktif = mysqli_fetch_assoc($cek_murid_aktif);
            
            echo "<script>console.log('Kelas $nama_kelas memiliki " . $row_murid_aktif['total'] . " murid aktif (dengan kondisi status)');</script>";
            
            // Gunakan total murid tanpa filter status untuk keamanan
            if ($row_murid['total'] == 0) {
                $kelas_bisa_dihapus[] = ['id' => $id_kelas, 'nama' => $nama_kelas];
            } else {
                $kelas_tidak_bisa[] = ['id' => $id_kelas, 'nama' => $nama_kelas, 'murid' => $row_murid['total']];
            }
        }
        
        echo "<script>console.log('Kelas bisa dihapus: " . count($kelas_bisa_dihapus) . ", Tidak bisa: " . count($kelas_tidak_bisa) . "');</script>";
        
        // Proses hapus kelas yang bisa dihapus
        $berhasil_dihapus = [];
        $gagal_dihapus = [];
        
        if (!empty($kelas_bisa_dihapus)) {
            mysqli_autocommit($conn, FALSE);
            
            try {
                foreach ($kelas_bisa_dihapus as $kelas) {
                    $id_kelas = $kelas['id'];
                    $nama_kelas = mysqli_real_escape_string($conn, $kelas['nama']);
                    
                    echo "<script>console.log('Memproses hapus kelas: $nama_kelas (ID: $id_kelas)');</script>";
                    
                    // Hapus wali kelas terlebih dahulu
                    $del_wali = mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas = $id_kelas");
                    echo "<script>console.log('Hapus wali kelas: " . ($del_wali ? "berhasil" : "gagal") . "');</script>";
                    
                    // Hapus mata pelajaran ampu untuk kelas ini
                    $del_mapel = mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE kelas = '$nama_kelas'");
                    echo "<script>console.log('Hapus mapel ampu: " . ($del_mapel ? "berhasil" : "gagal") . "');</script>";
                    
                    // Hapus jadwal jika tabel ada
                    $check_jadwal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jadwal'");
                    if ($check_jadwal && mysqli_num_rows($check_jadwal) > 0) {
                        $del_jadwal = mysqli_query($conn, "DELETE FROM tbl_jadwal WHERE kelas = '$nama_kelas'");
                        echo "<script>console.log('Hapus jadwal: " . ($del_jadwal ? "berhasil" : "gagal") . "');</script>";
                    }
                    
                    // Hapus jurnal jika tabel ada
                    $check_jurnal = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jurnal'");
                    if ($check_jurnal && mysqli_num_rows($check_jurnal) > 0) {
                        $del_jurnal = mysqli_query($conn, "DELETE FROM tbl_jurnal WHERE kelas = '$nama_kelas'");
                        echo "<script>console.log('Hapus jurnal: " . ($del_jurnal ? "berhasil" : "gagal") . "');</script>";
                    }
                    
                    // Hapus kelas
                    $del_kelas = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");
                    echo "<script>console.log('Hapus kelas: " . ($del_kelas ? "berhasil" : "gagal - " . mysqli_error($conn)) . "');</script>";
                    
                    if ($del_kelas) {
                        $berhasil_dihapus[] = $nama_kelas;
                    } else {
                        $gagal_dihapus[] = $nama_kelas . " (Error: " . mysqli_error($conn) . ")";
                    }
                }
                
                mysqli_commit($conn);
                echo "<script>console.log('Transaction committed');</script>";
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo "<script>console.log('Transaction rolled back: " . addslashes($e->getMessage()) . "');</script>";
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Batch Delete!',
                        text: 'Terjadi error: " . addslashes($e->getMessage()) . "'
                    });
                </script>";
            }
            
            mysqli_autocommit($conn, TRUE);
        }
        
        // Tampilkan hasil
        $pesan_hasil = "";
        if (!empty($berhasil_dihapus)) {
            $pesan_hasil .= "✅ <strong>Berhasil dihapus (" . count($berhasil_dihapus) . " kelas):</strong><br>" . implode(", ", $berhasil_dihapus) . "<br><br>";
        }
        if (!empty($kelas_tidak_bisa)) {
            $pesan_hasil .= "❌ <strong>Tidak bisa dihapus (" . count($kelas_tidak_bisa) . " kelas):</strong><br>";
            foreach ($kelas_tidak_bisa as $kelas) {
                $pesan_hasil .= "• " . $kelas['nama'] . " (ada " . $kelas['murid'] . " murid)<br>";
            }
            $pesan_hasil .= "<br>";
        }
        if (!empty($gagal_dihapus)) {
            $pesan_hasil .= "⚠️ <strong>Gagal dihapus:</strong><br>" . implode(", ", $gagal_dihapus);
        }
        
        if (empty($pesan_hasil)) {
            $pesan_hasil = "Tidak ada kelas yang diproses.";
        }
        
        echo "<script>
            Swal.fire({
                icon: '" . (empty($kelas_tidak_bisa) && empty($gagal_dihapus) && !empty($berhasil_dihapus) ? "success" : "info") . "',
                title: 'Hasil Hapus Batch',
                html: '$pesan_hasil',
                confirmButtonText: 'OK',
                width: '500px'
            }).then(function() {
                // Refresh tabel secara dinamis dulu
                refreshTableContent();
                
                // Delay reload untuk melihat effect refresh
                setTimeout(() => {
                    const url = new URL(window.location);
                    url.searchParams.delete('hapus_kelas');
                    url.searchParams.delete('paksa_hapus');
                    url.searchParams.delete('hapus_batch');
                    url.searchParams.set('_refresh', Date.now());
                    window.location.replace(url.toString());
                }, 2500);
            });
        </script>";
        
    } else {
        echo "<script>console.log('Tidak ada kelas dipilih atau data kosong');</script>";
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Kelas Dipilih!',
                text: 'Silakan pilih kelas yang ingin dihapus terlebih dahulu.'
            });
        </script>";
    }
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- JavaScript untuk mencegah cache -->
<script>
// Mencegah cache browser
if (performance.navigation.type === 2) {
    location.reload(true);
}
// Force no-cache untuk request AJAX
$(document).ajaxSetup({
    cache: false,
    beforeSend: function(xhr) {
        xhr.setRequestHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        xhr.setRequestHeader('Pragma', 'no-cache');
        xhr.setRequestHeader('Expires', '0');
    }
});
</script>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Input Kelas & Wali Kelas</h1>
</div>

<div class="row">
    <!-- Form Tambah Kelas -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tambah Kelas Baru</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nama_kelas">Nama Kelas:</label>
                        <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" placeholder="Contoh: X-A, XI-IPA-1, XII-IPS-2" required>
                        <small class="form-text text-muted">Format: Tingkat-Jurusan-Nomor (mis: X-A, XI-IPA-1)</small>
                    </div>
                    <button type="submit" name="submit_tambah_kelas" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Kelas
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Daftar Kelas -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
                <div class="float-right">
                    <button type="button" class="btn btn-sm btn-danger mr-2" id="btn-hapus-batch" style="display: none;">
                        <i class="fas fa-trash-alt"></i> Hapus Batch
                    </button>
                    <button type="button" class="btn btn-sm btn-info mr-2" onclick="forceRefreshPage()">
                        🔄 Refresh Tabel
                    </button>
                    <button type="button" class="btn btn-sm btn-warning mr-2" onclick="testDirectPost()">
                        🚀 Test POST
                    </button>
                    <button type="button" class="btn btn-sm btn-success mr-2" onclick="debugForm()">
                        🔧 Debug Form
                    </button>
                    <button type="button" class="btn btn-sm btn-info" onclick="testSweetAlert()">
                        🧪 Test SweetAlert
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="form-batch-delete" method="POST" action="">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="select-all" title="Pilih Semua">
                                    </th>
                                    <th width="5%">No</th>
                                    <th width="30%">Nama Kelas</th>
                                    <th width="30%">Wali Kelas</th>
                                    <th width="30%">Aksi</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $sql = "SELECT k.*, w.nip_wali, g.nama_guru 
                                   FROM tbl_kelas k 
                                   LEFT JOIN tbl_wali_kelas w ON k.id_kelas = w.id_kelas 
                                   LEFT JOIN tbl_guru g ON w.nip_wali = g.no_induk 
                                   ORDER BY k.kelas ASC";
                            $result = mysqli_query($conn, $sql);
                            while ($data = mysqli_fetch_array($result)) {
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="kelas_terpilih[]" value="<?= $data['id_kelas']; ?>" class="checkbox-kelas">
                                    </td>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <?= htmlspecialchars($data['kelas']); ?>
                                        <?php
                                        // Cek jumlah murid untuk display info
                                        $nama_kelas_escaped = mysqli_real_escape_string($conn, $data['kelas']);
                                        $cek_murid = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas_escaped'");
                                        $row_murid = mysqli_fetch_assoc($cek_murid);
                                        
                                        echo "<script>console.log('Display - Kelas {$data['kelas']} memiliki " . $row_murid['total'] . " murid');</script>";
                                        
                                        if ($row_murid['total'] > 0) {
                                            echo '<br><small class="text-info"><i class="fas fa-users"></i> ' . $row_murid['total'] . ' murid</small>';
                                        } else {
                                            echo '<br><small class="text-success"><i class="fas fa-inbox"></i> Kosong (bisa dihapus)</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($data['nama_guru'])): ?>
                                            <span class="badge badge-success"><?= htmlspecialchars($data['nama_guru']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Belum ditentukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?page=kelola-wali-kelas&edit=<?= $data['id_kelas']; ?>" class="btn btn-sm btn-info" title="Atur Wali Kelas">
                                            <i class="fas fa-user-tie"></i> Wali Kelas
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-hapus-kelas" 
                                            data-id="<?= $data['id_kelas']; ?>" 
                                            data-nama="<?= htmlspecialchars($data['kelas']); ?>"
                                            title="Hapus Kelas"
                                            onclick="console.log('Button clicked directly:', this.dataset.id, this.dataset.nama);">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="hapus_batch_kelas" value="1">
            </form>
            </div>
        </div>
    </div>
</div>

</div>

<script>
// Tunggu SweetAlert2 dan jQuery loaded
function initHapusKelas() {
    if (typeof Swal === 'undefined' || typeof $ === 'undefined') {
        setTimeout(initHapusKelas, 100);
        return;
    }
    
    console.log('SweetAlert2 dan jQuery siap, inisialisasi hapus kelas...');
    
    // Check jika ada flag refresh dari batch delete
    if (sessionStorage.getItem('refreshAfterBatchDelete') === 'true') {
        sessionStorage.removeItem('refreshAfterBatchDelete');
        console.log('Auto refresh setelah batch delete');
        refreshTableAfterDelete();
        return;
    }
    
    // Hapus event listener lama jika ada
    $(document).off('click', '.btn-hapus-kelas');
    
    // Handler untuk hapus kelas dengan jQuery
    $(document).on('click', '.btn-hapus-kelas', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const idKelas = $(this).data('id');
        const namaKelas = $(this).data('nama');
        
        console.log('Tombol hapus diklik - ID:', idKelas, 'Nama:', namaKelas);
        
        if (!idKelas || !namaKelas) {
            console.error('Data ID atau Nama kelas tidak ditemukan!');
            Swal.fire('Error', 'Data kelas tidak valid!', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Hapus Kelas?',
            html: 'Apakah Anda yakin ingin menghapus kelas <strong>' + namaKelas + '</strong>?<br><small class="text-danger">⚠️ Kelas yang masih digunakan tidak dapat dihapus!</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '🗑️ Ya, Hapus',
            cancelButtonText: '❌ Batal',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            backdrop: true,
            allowOutsideClick: true
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Konfirmasi hapus - redirect ke:', '?page=input-kelas&hapus_kelas=' + idKelas);
                
                // Tampilkan loading
                Swal.fire({
                    title: 'Menghapus Kelas...',
                    html: 'Sedang memproses penghapusan kelas <strong>' + namaKelas + '</strong>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Set flag untuk update setelah hapus individual
                sessionStorage.setItem('deletedKelasId', idKelas);
                
                // Redirect ke URL hapus dengan delay kecil
                setTimeout(() => {
                    window.location.href = '?page=input-kelas&hapus_kelas=' + idKelas;
                }, 200);
            } else {
                console.log('Hapus kelas dibatalkan');
            }
        });
    });
    
    console.log('Event listener hapus kelas berhasil diinisialisasi');
    
    // Inisialisasi checkbox functionality
    initCheckboxes();
    
    // Inisialisasi batch delete functionality
    initBatchDelete();
}

// Function untuk handle checkbox selection
function initCheckboxes() {
    // Handle select all checkbox
    $('#select-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.checkbox-kelas').prop('checked', isChecked);
        updateBatchDeleteButton();
    });
    
    // Handle individual checkbox
    $(document).on('change', '.checkbox-kelas', function() {
        const totalCheckboxes = $('.checkbox-kelas').length;
        const checkedCheckboxes = $('.checkbox-kelas:checked').length;
        
        // Update select all checkbox state
        $('#select-all').prop('checked', checkedCheckboxes === totalCheckboxes);
        $('#select-all').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        
        updateBatchDeleteButton();
    });
}

// Function untuk update tombol batch delete
function updateBatchDeleteButton() {
    const checkedCount = $('.checkbox-kelas:checked').length;
    if (checkedCount > 0) {
        $('#btn-hapus-batch').show().text(`🗑️ Hapus Batch (${checkedCount})`);
    } else {
        $('#btn-hapus-batch').hide();
    }
}

// Function untuk handle batch delete
function initBatchDelete() {
    $('#btn-hapus-batch').on('click', function() {
        console.log('Tombol hapus batch diklik');
        
        const checkedCheckboxes = $('.checkbox-kelas:checked');
        console.log('Jumlah checkbox terpilih:', checkedCheckboxes.length);
        
        if (checkedCheckboxes.length === 0) {
            Swal.fire('Peringatan', 'Silakan pilih kelas yang ingin dihapus terlebih dahulu!', 'warning');
            return;
        }
        
        // Kumpulkan data kelas yang dipilih
        const selectedClasses = [];
        checkedCheckboxes.each(function() {
            const row = $(this).closest('tr');
            const kelasCell = row.find('td:nth-child(3)');
            const namaKelas = kelasCell.clone().children().remove().end().text().trim();
            const muridInfo = kelasCell.find('small').text().trim();
            const isKosong = muridInfo.includes('Kosong');
            
            console.log(`Kelas: ${namaKelas}, Info: ${muridInfo}, Kosong: ${isKosong}`);
            
            selectedClasses.push({
                id: $(this).val(),
                nama: namaKelas,
                kosong: isKosong,
                muridInfo: muridInfo
            });
        });
        
        console.log('Data kelas terpilih:', selectedClasses);
        
        // Pisahkan kelas kosong dan berisi murid
        const kelasKosong = selectedClasses.filter(k => k.kosong);
        const kelasBerisi = selectedClasses.filter(k => !k.kosong);
        
        console.log('Kelas kosong:', kelasKosong);
        console.log('Kelas berisi:', kelasBerisi);
        
        let pesanKonfirmasi = `Anda akan menghapus <strong>${selectedClasses.length} kelas</strong>:<br><br>`;
        
        if (kelasKosong.length > 0) {
            pesanKonfirmasi += `✅ <strong>Bisa dihapus (${kelasKosong.length} kelas kosong):</strong><br>`;
            kelasKosong.forEach(k => {
                pesanKonfirmasi += `• ${k.nama}<br>`;
            });
            pesanKonfirmasi += '<br>';
        }
        
        if (kelasBerisi.length > 0) {
            pesanKonfirmasi += `❌ <strong>Tidak bisa dihapus (${kelasBerisi.length} kelas berisi murid):</strong><br>`;
            kelasBerisi.forEach(k => {
                pesanKonfirmasi += `• ${k.nama} (${k.muridInfo})<br>`;
            });
            pesanKonfirmasi += '<br>';
        }
        
        if (kelasKosong.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Kelas yang Bisa Dihapus!',
                html: pesanKonfirmasi + '<small class="text-danger">💡 Hanya kelas kosong (tanpa murid) yang bisa dihapus secara batch.</small>',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        pesanKonfirmasi += '<small class="text-info">💡 Kelas kosong akan dihapus beserta data wali kelas, mata pelajaran, dan jadwal terkait.</small>';
        
        Swal.fire({
            title: 'Konfirmasi Hapus Batch',
            html: pesanKonfirmasi,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Ya, Hapus ${kelasKosong.length} Kelas Kosong`,
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Konfirmasi hapus batch - akan submit form');
                
                Swal.fire({
                    title: 'Memproses Hapus Batch...',
                    html: `Sedang menghapus ${kelasKosong.length} kelas kosong...`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Debug: cek form sebelum submit
                console.log('Form data sebelum submit:');
                const formData = new FormData($('#form-batch-delete')[0]);
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                // Submit form dengan callback untuk refresh
                setTimeout(() => {
                    // Set flag untuk refresh setelah submit
                    sessionStorage.setItem('refreshAfterBatchDelete', 'true');
                    $('#form-batch-delete').submit();
                }, 500);
            } else {
                console.log('Hapus batch dibatalkan');
            }
        });
    });
}

// Function untuk refresh tabel setelah hapus
function refreshTableAfterDelete() {
    console.log('Refreshing table...');
    
    // Reset semua checkbox
    $('.checkbox-kelas, #select-all').prop('checked', false);
    $('#btn-hapus-batch').hide();
    
    // Force reload dengan cache busting
    const url = new URL(window.location);
    url.searchParams.set('_refresh', Date.now());
    
    setTimeout(() => {
        window.location.href = url.toString();
    }, 500);
}

// Function untuk force reload yang lebih aggressive
function forceReloadPage() {
    console.log('Force reloading page...');
    
    // Hapus semua cache yang mungkin ada
    if ('caches' in window) {
        caches.keys().then(function(names) {
            names.forEach(function(name) {
                caches.delete(name);
            });
        });
    }
    
    // Multiple methods untuk memastikan reload
    setTimeout(() => {
        // Method 1: location.reload dengan force
        window.location.reload(true);
    }, 100);
    
    setTimeout(() => {
        // Method 2: redirect dengan timestamp
        if (window.location.href === window.location.href) {
            const url = new URL(window.location);
            url.searchParams.set('_t', Date.now());
            window.location.href = url.toString();
        }
    }, 1000);
}

// Function untuk update tampilan setelah hapus individual
function updateTableAfterSingleDelete(deletedKelasId) {
    console.log('Updating table after single delete:', deletedKelasId);
    
    // Hapus row dari tabel
    $(`input[value="${deletedKelasId}"]`).closest('tr').fadeOut(300, function() {
        $(this).remove();
        
        // Update nomor urut
        let no = 1;
        $('tbody tr').each(function() {
            $(this).find('td:nth-child(2)').text(no++);
        });
        
        // Reset checkbox state
        updateBatchDeleteButton();
    });
}
function testSweetAlert() {
    console.log('Test SweetAlert dipanggil');
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '✅ SweetAlert2 Bekerja!',
            text: 'Library SweetAlert2 sudah loaded dengan benar.',
            icon: 'success',
            timer: 2000
        });
    } else {
        alert('❌ SweetAlert2 belum loaded!');
    }
}

// Debug function untuk test form
function debugForm() {
    console.log('=== DEBUG FORM ===');
    
    // Test jQuery
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    
    // Test checkbox
    const checkboxes = $('.checkbox-kelas');
    console.log('Jumlah checkbox kelas:', checkboxes.length);
    
    const checkedBoxes = $('.checkbox-kelas:checked');
    console.log('Jumlah checkbox checked:', checkedBoxes.length);
    
    // Test form
    const form = $('#form-batch-delete');
    console.log('Form ditemukan:', form.length > 0);
    
    if (checkedBoxes.length === 0) {
        Swal.fire({
            title: 'Debug Info',
            html: `
                <div style="text-align: left;">
                    <strong>Status Debug:</strong><br>
                    • jQuery: ${typeof $ !== 'undefined' ? '✅' : '❌'}<br>
                    • SweetAlert: ${typeof Swal !== 'undefined' ? '✅' : '❌'}<br>
                    • Form: ${form.length > 0 ? '✅' : '❌'}<br>
                    • Total Checkbox: ${checkboxes.length}<br>
                    • Checkbox Checked: ${checkedBoxes.length}<br><br>
                    <small class="text-info">Silakan pilih kelas terlebih dahulu untuk test submit.</small>
                </div>
            `,
            icon: 'info'
        });
    } else {
        // Test submit dengan kelas terpilih
        console.log('Kelas yang dipilih:');
        checkedBoxes.each(function() {
            console.log('ID:', $(this).val(), 'Name:', $(this).attr('name'));
        });
        
        Swal.fire({
            title: 'Test Submit Form?',
            html: `Ditemukan <strong>${checkedBoxes.length} kelas</strong> terpilih.<br>Mau test submit form?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Test Submit',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('=== SUBMIT FORM TEST ===');
                $('#form-batch-delete').submit();
            }
        });
    }
}

// Test function untuk POST langsung
function testDirectPost() {
    console.log('=== TEST DIRECT POST ===');
    
    const checkedBoxes = $('.checkbox-kelas:checked');
    if (checkedBoxes.length === 0) {
        Swal.fire('Warning', 'Pilih kelas terlebih dahulu!', 'warning');
        return;
    }
    
    // Buat form data manual
    const formData = new FormData();
    formData.append('hapus_batch_kelas', '1');
    
    checkedBoxes.each(function() {
        formData.append('kelas_terpilih[]', $(this).val());
        console.log('Adding to form:', $(this).val());
    });
    
    // Log form data
    console.log('Form data yang akan dikirim:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    Swal.fire({
        title: 'Test Direct POST',
        html: `Akan mengirim POST request untuk ${checkedBoxes.length} kelas terpilih.<br>Check console untuk detail.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim POST',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Sending POST request...');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => {
                console.log('Response status:', response.status);
                return response.text();
            }).then(data => {
                console.log('Response data:', data);
                Swal.fire('Success', 'POST request berhasil dikirim. Check console untuk response.', 'success');
                
                // Reload page setelah response dengan force refresh
                setTimeout(() => {
                    const url = new URL(window.location);
                    url.searchParams.delete('hapus_kelas');
                    url.searchParams.delete('paksa_hapus');
                    url.searchParams.delete('hapus_batch');
                    url.searchParams.set('_refresh', Date.now());
                    window.location.replace(url.toString());
                }, 1500);
            }).catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi error: ' + error.message, 'error');
            });
        }
    });
}

// Mulai inisialisasi
document.addEventListener('DOMContentLoaded', initHapusKelas);

// Fungsi untuk refresh tabel tanpa reload halaman
function refreshTableContent() {
    console.log('🔄 Refreshing table content...');
    
    // Fade out tabel
    const tbody = document.querySelector('tbody');
    if (tbody) {
        tbody.style.opacity = '0.5';
        
        // Simulasi loading dan refresh
        setTimeout(() => {
            // Hapus baris yang sudah dihapus (yang ter-checked)
            const checkedBoxes = document.querySelectorAll('input[name="kelas_ids[]"]:checked');
            checkedBoxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row) {
                    row.style.background = '#ffcccc';
                    row.style.textDecoration = 'line-through';
                    setTimeout(() => {
                        row.remove();
                        updateRowNumbers();
                    }, 500);
                }
            });
            
            tbody.style.opacity = '1';
        }, 1000);
    }
}

// Update nomor urut setelah hapus baris
function updateRowNumbers() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        const numberCell = row.querySelector('td:nth-child(2)');
        if (numberCell) {
            numberCell.textContent = index + 1;
        }
    });
    
    // Update counter
    updateSelectionCount();
}

// Fungsi untuk refresh paksa halaman
function forceRefreshPage() {
    console.log('🔄 Force refresh page...');
    Swal.fire({
        title: 'Refresh Tabel',
        text: 'Memuat ulang data tabel kelas...',
        icon: 'info',
        timer: 1500,
        showConfirmButton: false
    }).then(() => {
        const url = new URL(window.location);
        url.searchParams.delete('hapus_kelas');
        url.searchParams.delete('paksa_hapus');
        url.searchParams.delete('hapus_batch');
        url.searchParams.set('_refresh', Date.now());
        window.location.replace(url.toString());
    });
}
</script>