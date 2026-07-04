<?php
if (!isset($_SESSION["username"])) { ?>
	<script>window.location="index.php?haruslogin";</script>
	<?php
	exit;
} else if ($_SESSION["hak_akses"] != 1) { ?>
	<script>window.location="404.html";</script>
<?php } else {
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800">Manajemen User</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar User Aplikasi</h6>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Hak Akses</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    include "koneksi.php";
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_user ORDER BY id_user ASC");
                    while ($duser = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td class="text-center"><?= $duser['username']; ?></td>
                                <td><?= $duser['nama']; ?></td>
                                <td><?= $duser['hak_akses']; ?></td>
								<?php if($duser['username'] != "admin") { ?>
                                <td class="text-center"><a class="btn btn-sm btn-circle btn-info" title="Reset Password" href="?page=edit-user&id_user=<?= $duser['id_user']; ?>"><i class="fas fa-lock"></i></a>&nbsp;<a class="btn btn-sm btn-circle btn-danger" title="Hapus user" href="delete-user.php?id_user=<?= $duser['id_user']; ?>" onclick="return confirm('Yakin mau menghapus user ini?');"><i class="fas fa-trash"></i></a></td>
								<?php } else { ?>
								<td class="text-center"><button class="btn btn-secondary" disabled>No Action</button></td>
								<?php } ?>
								
                            </tr>
                    <?php
                    // ini penutup while 
                    }
                    ?>
                </tbody>

            </table>
        </div>
    </div>
</div>
</div>
<?php
// ini tag penutup else paling atas
 } 
?>
