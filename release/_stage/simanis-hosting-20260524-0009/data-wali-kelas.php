<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include 'koneksi.php';

// Ambil data wali kelas bergabung dengan kelas
$sql = "SELECT k.id_kelas, k.kelas, wk.nama_wali, wk.nip_wali
        FROM tbl_kelas k
        LEFT JOIN tbl_wali_kelas wk ON wk.id_kelas = k.id_kelas
        ORDER BY k.kelas ASC";
$res = mysqli_query($conn, $sql);
$rows = [];
if($res){
  while($r = mysqli_fetch_assoc($res)) $rows[] = $r;
}
?>

<div class="container-fluid">
  <div class="container">
    <div class="alert" style="background-color:#ffffff; outline:1px solid lightgrey">
      <h4>Data Wali Kelas</h4>
      <div class="small text-muted">Daftar wali kelas per kelas (sumber data dari Kelola Wali Kelas).</div>
    </div>

    <div class="card shadow-sm" style="border:0; border-radius:12px;">
      <div class="card-body table-responsive">
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th style="width:60px;">No</th>
              <th style="min-width:120px;">Kelas</th>
              <th style="min-width:220px;">Nama Wali Kelas</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="3" class="text-center text-muted">Belum ada data</td></tr>
          <?php else: ?>
            <?php $no=1; foreach($rows as $r): ?>
            <tr>
              <td><?php echo $no++; ?></td>
              <td><?php echo htmlspecialchars($r['kelas']); ?></td>
              <td>
                <?php echo !empty($r['nama_wali']) ? htmlspecialchars($r['nama_wali']).' ('.htmlspecialchars($r['nip_wali']).')' : '<em>Belum ditentukan</em>'; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
