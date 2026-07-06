<?php
if (!isset($_SESSION['hak_akses'])) {
    echo '<p class="text-danger p-3">Akses ditolak.</p>';
    return;
}

include_once __DIR__ . '/../../koneksi.php';

$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

// Delete handler
if (isset($_GET['hapus_id']) && $_SESSION['hak_akses'] == 1) {
    $idHapus = (int)$_GET['hapus_id'];
    mysqli_query($conn, "DELETE FROM tbl_alumni WHERE id_alumni=$idHapus AND id_sekolah=$tenantId");
    echo "<script>alert('Data alumni berhasil dihapus!'); window.location='home.php?page=data-alumni';</script>";
}

?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-graduate text-primary mr-2"></i>Data Alumni</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Lulus (Alumni)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>No Induk</th>
                            <th>NISN</th>
                            <th>Nama Alumni</th>
                            <th>Lulus Dari Kelas</th>
                            <th>Wali Kelas</th>
                            <th>Tahun Lulus</th>
                            <th>No WA</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $query = mysqli_query($conn, "SELECT * FROM tbl_alumni WHERE id_sekolah=$tenantId ORDER BY tahun_lulus DESC, nama_siswa ASC");
                        while ($row = mysqli_fetch_assoc($query)):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['no_induk'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['nisn'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['histori_kelas'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['histori_wali_kelas'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['tahun_lulus'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['no_wa'] ?? '-') ?></td>
                                <td>
                                    <?php if ($_SESSION['hak_akses'] == 1): ?>
                                    <a href="?page=data-alumni&hapus_id=<?= $row['id_alumni'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus riwayat alumni ini?');"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#dataTable').DataTable();
        }
    });
</script>
