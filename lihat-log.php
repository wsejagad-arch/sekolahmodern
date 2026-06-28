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
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Log System</h6>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>WAKTU</th>
						<th>LOG</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    include "koneksi.php";
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_log ORDER BY id_log DESC");
                    while ($data = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['waktu']; ?></td>
								<td><?= $data['isi_log']; ?></td>
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
