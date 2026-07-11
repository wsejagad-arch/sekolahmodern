<?php
// Proteksi Admin Pusat
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    echo "<div class='alert alert-danger m-4'>Akses ditolak. Halaman ini khusus Admin Pusat.</div>";
    exit;
}

$success_msg = "";
$error_msg = "";

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_reset'])) {
    $tables = $_POST['tables'] ?? [];
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Verifikasi password admin
    $username_admin = $_SESSION['username'] ?? '';
    if (!empty($username_admin) && !empty($password_confirm)) {
        $password_md5 = md5($password_confirm);
        $username_esc = mysqli_real_escape_string($conn, $username_admin);
        
        $cek_pass = mysqli_query($conn, "SELECT id_user FROM tbl_user WHERE username='$username_esc' AND password='$password_md5' AND hak_akses='1'");
        if ($cek_pass && mysqli_num_rows($cek_pass) > 0) {
            
            if (!empty($tables)) {
                $truncated = 0;
                $failed = 0;
                mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
                foreach ($tables as $t) {
                    // Validasi list whitelist keamanan
                    $whitelist = [
                        'tbl_kehadiran', 'tbl_mapel_ampu', 'tbl_izin_siswa', 
                        'tbl_pelanggaran_siswa', 'tbl_literasi_misi', 
                        'tbl_literasi_progress', 'tbl_tugas_siswa', 'tbl_nilai_tugas'
                    ];
                    if (in_array($t, $whitelist)) {
                        $check = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
                        if (mysqli_num_rows($check) > 0) {
                            $q = mysqli_query($conn, "TRUNCATE TABLE `$t`");
                            if ($q) { $truncated++; } else { $failed++; }
                        } else {
                            // Jika tabel belum ada di database, lewati saja tanpa error
                        }
                    }
                }
                mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
                
                if ($truncated > 0) {
                    $success_msg = "Berhasil menghapus/mengosongkan data dari $truncated tabel pilihan.";
                }
                if ($failed > 0) {
                    $error_msg = "Ada error pada saat mengosongkan $failed tabel (mungkin tabel belum dibuat di database).";
                }
            } else {
                $error_msg = "Pilih minimal satu data/tabel yang ingin dikosongkan.";
            }
        } else {
            $error_msg = "Password Anda salah. Reset data dibatalkan.";
        }
    } else {
        $error_msg = "Harap masukkan password Admin Anda untuk konfirmasi.";
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-trash-alt mr-2 text-danger"></i> Reset Data Semester Baru</h1>
    </div>

    <?php if(!empty($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Sukses!</strong> <?= $success_msg; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal!</strong> <?= $error_msg; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Card Panduan -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow border-left-danger">
                <div class="card-header py-3 bg-danger">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-exclamation-triangle mr-2"></i> Peringatan Penting!</h6>
                </div>
                <div class="card-body">
                    <p class="text-danger font-weight-bold">Fitur ini digunakan untuk mengosongkan data transaksi dari semester sebelumnya agar tidak menumpuk dan memperberat sistem.</p>
                    <p>Tindakan ini <strong>TIDAK DAPAT DIBATALKAN</strong> dan data yang dihapus akan hilang secara permanen. Mohon lakukan backup database terlebih dahulu sebelum melakukan penghapusan data.</p>
                    <a href="backup-db.php" class="btn btn-primary shadow-sm" target="_blank">
                        <i class="fas fa-download fa-sm text-white-50 mr-2"></i> Download Backup Database (.sql)
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Form -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pilih Data yang Akan Dihapus</h6>
                </div>
                <div class="card-body">
                    <form action="home.php?page=reset-semester" method="POST" onsubmit="return confirm('APAKAH ANDA YAKIN? Data yang dihapus tidak bisa dikembalikan.');">
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="checkAll"></th>
                                        <th width="30%">Jenis Data</th>
                                        <th>Keterangan / Nama Tabel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_kehadiran" class="checkItem"></td>
                                        <td><strong>Jurnal Guru & Kehadiran KBM</strong></td>
                                        <td>Mengosongkan semua isian jurnal mengajar dan absensi KBM.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_mapel_ampu" class="checkItem"></td>
                                        <td><strong>Jadwal Pelajaran</strong></td>
                                        <td>Mengosongkan semua jadwal mengajar guru di setiap kelas.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_izin_siswa" class="checkItem"></td>
                                        <td><strong>Ajuan Izin Siswa</strong></td>
                                        <td>Mengosongkan data pengajuan izin, sakit, alpa siswa.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_pelanggaran_siswa" class="checkItem"></td>
                                        <td><strong>Jurnal 7K / Pelanggaran</strong></td>
                                        <td>Mengosongkan catatan pelanggaran / 7K siswa.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_literasi_misi" class="checkItem"></td>
                                        <td><strong>Misi Literasi</strong></td>
                                        <td>Mengosongkan riwayat tugas/misi LENTERA literasi.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_literasi_progress" class="checkItem"></td>
                                        <td><strong>Progress Literasi Siswa</strong></td>
                                        <td>Mengosongkan progress membaca siswa di program literasi.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_tugas_siswa" class="checkItem"></td>
                                        <td><strong>Tugas Siswa (E-Learning)</strong></td>
                                        <td>Mengosongkan bank tugas.</td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" name="tables[]" value="tbl_nilai_tugas" class="checkItem"></td>
                                        <td><strong>Nilai Tugas Siswa</strong></td>
                                        <td>Mengosongkan hasil nilai yang sudah diinput.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold text-danger">Password Konfirmasi:</label>
                            <div class="col-sm-6">
                                <input type="password" name="password_confirm" class="form-control border-danger" placeholder="Masukkan password admin pusat Anda" required>
                                <small class="text-muted">Diperlukan otorisasi password untuk menghapus data.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="btn_reset" class="btn btn-danger btn-lg shadow-sm">
                                <i class="fas fa-trash-alt mr-2"></i> Eksekusi Hapus Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('checkAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.checkItem');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = this.checked;
    }
});
</script>
