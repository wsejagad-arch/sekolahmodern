<?php
if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";

// Proses hapus individual
if (isset($_POST['hapus_individual'])) {
    $id_kelas = intval($_POST['id_kelas']);
    
    // Ambil nama kelas
    $result = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
    if ($result && mysqli_num_rows($result) > 0) {
        $kelas_data = mysqli_fetch_assoc($result);
        $nama_kelas = $kelas_data['kelas'];
        
        // Cek jumlah siswa
        $check_siswa = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM tbl_siswa WHERE kelas = '$nama_kelas'");
        $siswa_count = mysqli_fetch_assoc($check_siswa)['jumlah'];
        
        if ($siswa_count == 0) {
            // Hapus kelas
            $delete_result = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");
            if ($delete_result) {
                echo "<script>
                    alert('Kelas $nama_kelas berhasil dihapus!');
                    window.location.reload();
                </script>";
            } else {
                echo "<script>alert('Gagal menghapus kelas!');</script>";
            }
        } else {
            echo "<script>alert('Kelas tidak bisa dihapus karena masih ada $siswa_count siswa!');</script>";
        }
    }
}

// Ambil data kelas
$kelas_query = "SELECT * FROM tbl_kelas ORDER BY kelas";
$kelas_result = mysqli_query($conn, $kelas_query);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">🗑️ Hapus Kelas 2 - Versi Simple</h1>
</div>

<!-- Alert Info -->
<div class="alert alert-info" role="alert">
    <i class="fas fa-info-circle"></i>
    <strong>Informasi:</strong> Halaman sederhana untuk menghapus kelas kosong (tanpa siswa).
</div>

<!-- Tabel Kelas -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Jumlah Siswa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($kelas_result && mysqli_num_rows($kelas_result) > 0) {
                        while($kelas = mysqli_fetch_assoc($kelas_result)): 
                            // Hitung siswa
                            $nama_kelas = mysqli_real_escape_string($conn, $kelas['kelas']);
                            $siswa_query = "SELECT COUNT(*) as jumlah FROM tbl_siswa WHERE kelas = '$nama_kelas'";
                            $siswa_result = mysqli_query($conn, $siswa_query);
                            $jumlah_siswa = 0;
                            if ($siswa_result) {
                                $siswa_data = mysqli_fetch_assoc($siswa_result);
                                $jumlah_siswa = $siswa_data['jumlah'];
                            }
                            
                            $bisa_hapus = ($jumlah_siswa == 0);
                            $status_class = $bisa_hapus ? 'text-success' : 'text-danger';
                            $status_text = $bisa_hapus ? 'Bisa Dihapus' : 'Ada Siswa';
                            $row_class = $bisa_hapus ? '' : 'table-warning';
                    ?>
                    <tr class="<?= $row_class ?>">
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($kelas['kelas']) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $bisa_hapus ? 'success' : 'warning' ?>">
                                <?= $jumlah_siswa ?> siswa
                            </span>
                        </td>
                        <td>
                            <span class="<?= $status_class ?>">
                                <i class="fas fa-<?= $bisa_hapus ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td>
                            <?php if($bisa_hapus): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus kelas <?= addslashes($kelas['kelas']) ?>?')">
                                <input type="hidden" name="hapus_individual" value="1">
                                <input type="hidden" name="id_kelas" value="<?= $kelas['id_kelas'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                            <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Masih ada siswa">
                                <i class="fas fa-ban"></i> Tidak Bisa
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>Tidak ada data kelas</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
<!-- /.container-fluid -->