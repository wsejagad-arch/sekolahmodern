<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <a href="?page=tambah-siswa" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Data
  </a>

  <a href="export-siswa.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
    <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Data Siswa
  </a>
  <a href="?page=import-siswa" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
  <i class="fas fa-file-upload fa-sm text-white-50"></i> Import Data Siswa
</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Siswa <?= $lembaga['nmsekolah']; ?></h6>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>NO. INDUK SISWA</th>
						<th>NAMA SISWA</th>
						<th>KELAS</th>
						<th>STATUS KEAKTIFAN</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    include "koneksi.php";
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_siswa ORDER BY kelas ASC");
                    while ($data = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['no_induk']; ?></td>
								<td><?= $data['nama_siswa']; ?></td>
								<td><?= $data['kelas']; ?></td>
								<td><?= $data['status']; ?></td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-circle btn-info" href="?page=edit-siswa&no_induk=<?= $data['no_induk']; ?>" title="Edit Data Siswa"><i class="fas fa-edit"></i></a>
                                    &nbsp;
                                    <button type="button" class="btn btn-sm btn-circle btn-danger btn-delete-siswa"
                                        data-no-induk="<?= htmlspecialchars($data['no_induk'], ENT_QUOTES); ?>"
                                        data-nama="<?= htmlspecialchars($data['nama_siswa'], ENT_QUOTES); ?>"
                                        title="Hapus Data Siswa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                    <?php
                    // ini penutup while 
                    }
                    ?>
                </tbody>

            </table>
        </div>
		</div>
		
<script>
document.addEventListener('DOMContentLoaded', function(){
    var table = document.getElementById('dataTable');
    if (!table) return;

    table.addEventListener('click', function(e){
        var btn = e.target.closest('.btn-delete-siswa');
        if (!btn) return;

        var noInduk = btn.getAttribute('data-no-induk');
        var nama    = btn.getAttribute('data-nama');

        Swal.fire({
            title: 'Hapus Siswa?',
            html: 'Jika dihapus, semua data terkait <strong>' + nama + '</strong> akan dihapus.<br><small>NIS: ' + noInduk + '</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(function(result){
            if (result.isConfirmed) {
                Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });

                fetch('delete-siswa.php?no_induk=' + encodeURIComponent(noInduk) + '&mode=json', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(r){ return r.json(); })
                .then(function(json){
                    if (json.success) {
                        Swal.fire({ icon:'success', title:'Terhapus', text:'Data siswa berhasil dihapus', timer:1500, showConfirmButton:false })
                        .then(function(){ window.location.href = 'home.php?page=data-siswa'; });
                    } else {
                        Swal.fire('Gagal', json.error || 'Tidak dapat menghapus data', 'error');
                    }
                })
                .catch(function(err){
                    Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
                });
            }
        });
    });
});
</script>

</div>

</div>
