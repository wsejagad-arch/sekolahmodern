<?php
if (!isset($_SESSION["username"])) {
  header("location: index.php?haruslogin");
  exit;
} else if ($hakakses != 1) { ?>
  <script>
    window.location = '404.html';
  </script>
  <?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$namauser = $_SESSION['nama'];

if (isset($_POST['submit'])) {
  $noinduk = mysqli_real_escape_string($conn, trim($_POST['noinduk']));
  $cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_pengguna WHERE no_induk='$noinduk'"));
  if ($cek > 0) {
  ?>
    <script>
      Swal.fire('Gagal', 'Akun untuk siswa ini sudah ada.', 'error')
    </script>
  <?php
  } else {
    $pwd = md5('12345');
    $hak = 3; // hak akses siswa
    $tglskr = date('Y-m-d H:i:s');
    $isilog = "$namauser menambahkan akun siswa dengan NIS $noinduk";
    mysqli_query($conn, "INSERT INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$noinduk', '$pwd', '$hak')");
    mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
  ?>
    <script>
      Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: 'Berhasil membuat akun siswa!',
        showConfirmButton: false,
        timer: 1500
      }).then(function() {
        window.location.href = '?page=data-siswa';
      })
    </script>
<?php
  }
}
?>

<div class="container-fluid">
  <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
      <h4>Buat Akun Siswa</h4>
    </div>

    <div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
      <form method="POST" action="" class="needs-validation" novalidate>

        <!-- Pilih Siswa -->
        <div class="form-group col-sm-6 pt-4">
          <label for="noinduk">Pilih Siswa (belum punya akun):</label>
          <select class="form-control" id="noinduk" name="noinduk" required>
            <option selected disabled>-- pilih --</option>
            <?php
            $sql = mysqli_query($conn, "SELECT s.no_induk, s.nama_siswa FROM tbl_siswa s LEFT JOIN tbl_pengguna p ON s.no_induk=p.no_induk WHERE p.no_induk IS NULL AND s.status='Aktif' ORDER BY s.nama_siswa ASC");
            while ($r = mysqli_fetch_array($sql)) { ?>
              <option value="<?= $r['no_induk']; ?>"><?= $r['no_induk']; ?> - <?= $r['nama_siswa']; ?></option>
            <?php } ?>
          </select>
          <div class="valid-feedback">Valid.</div>
          <div class="invalid-feedback">Harap pilih siswa.</div>
        </div>
        <!-- End Pilih Siswa -->

        <!-- Informasi password (default) -->
        <div class="form-group col-sm-6">
          <label for="info">Informasi:</label>
          <input type="text" class="form-control" id="info" value="Password default adalah 12345." readonly>
        </div>

        <!-- Tombol Submit dan cancel -->
        <div class="form-group col-sm-4 pb-4">
          <table style="border: none;">
            <tr>
              <td><input type="submit" onclick="return confirm('Buat akun siswa dengan password default 12345?');" class="btn btn-success" id="submit" name="submit" value="Buat Akun"></td>
              <td><a class="btn btn-warning" href="?page=data-siswa">Cancel</a></td>
            </tr>
          </table>
        </div>
        <!-- end of submit dan cancel -->

      </form>
    </div>
  </div>
</div>