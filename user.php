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
  <h1 class="h3 mb-0 text-gray-800">Manajemen Akses User</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <ul class="nav nav-tabs card-header-tabs" id="userTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active font-weight-bold" id="staff-tab" data-toggle="tab" href="#staff" role="tab" aria-controls="staff" aria-selected="true">Staf & Admin</a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="guru-tab" data-toggle="tab" href="#guru" role="tab" aria-controls="guru" aria-selected="false">Guru</a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="siswa-tab" data-toggle="tab" href="#siswa" role="tab" aria-controls="siswa" aria-selected="false">Siswa</a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="userTabsContent">
            <!-- TAB STAFF -->
            <div class="tab-pane fade show active" id="staff" role="tabpanel" aria-labelledby="staff-tab">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered datatable" width="100%" cellspacing="0">
                        <thead>
                            <tr class="bg-light">
                                <th>No.</th>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Hak Akses</th>
                                <th>Password Terlihat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include "koneksi.php";
                            $no = 1;
                            // Check if password_plain exists in tbl_user
                            $pwd_col_user = mt_column_exists($conn, "tbl_user", "password_plain") ? "password_plain" : "NULL as password_plain";
                            $sql = mysqli_query($conn, "SELECT id_user, username, nama, hak_akses, $pwd_col_user FROM tbl_user ORDER BY id_user ASC");
                            if($sql) {
                                while ($duser = mysqli_fetch_array($sql)) {
                                    $hak = $duser["hak_akses"];
                                    $hakLabel = $hak == 1 ? "Admin" : ($hak == 4 ? "Satpam" : ($hak == 5 ? "Kepala Sekolah" : "Unknown ($hak)"));
                            ?>
                                    <tr id="row-user-<?= $duser['id_user']; ?>">
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="text-center"><strong class="cell-username"><?= htmlspecialchars($duser["username"]); ?></strong></td>
                                        <td class="cell-nama"><?= htmlspecialchars($duser["nama"]); ?></td>
                                        <td><span class="badge badge-primary"><?= $hakLabel; ?></span></td>
                                        <td class="cell-password">
                                            <?php if(empty($duser["password_plain"])) { ?>
                                                <i class="text-muted" style="font-size:0.8em">(Hanya MD5)</i>
                                            <?php } else { ?>
                                                <span class="badge badge-light border text-dark font-weight-bold" style="font-size:0.9em; letter-spacing:1px;"><?= htmlspecialchars($duser["password_plain"]); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($duser["username"] != "admin") { ?>
                                            <button class="btn btn-sm btn-circle btn-warning btn-edit-user"
                                                title="Edit User"
                                                data-id="<?= $duser['id_user']; ?>"
                                                data-nama="<?= htmlspecialchars($duser['nama'], ENT_QUOTES); ?>"
                                                data-username="<?= htmlspecialchars($duser['username'], ENT_QUOTES); ?>"
                                                data-password="<?= htmlspecialchars($duser['password_plain'] ?? '', ENT_QUOTES); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a class="btn btn-sm btn-circle btn-danger" title="Hapus"
                                               href="delete-user.php?id_user=<?= $duser['id_user']; ?>"
                                               onclick="return confirm('Yakin hapus user ini?');">
                                               <i class="fas fa-trash"></i>
                                            </a>
                                            <?php } else { ?>
                                            <button class="btn btn-sm btn-secondary" disabled>Default</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB GURU -->
            <div class="tab-pane fade" id="guru" role="tabpanel" aria-labelledby="guru-tab">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered datatable" width="100%" cellspacing="0">
                        <thead>
                            <tr class="bg-light">
                                <th>No.</th>
                                <th>No Induk (Username)</th>
                                <th>Nama Guru</th>
                                <th>Password Terlihat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $pwd_col_pengguna = mt_column_exists($conn, "tbl_pengguna", "password_plain") ? "p.password_plain" : "NULL as password_plain";
                            $sqlG = mysqli_query($conn, "SELECT g.no_induk, g.nama_guru, $pwd_col_pengguna FROM tbl_guru g JOIN tbl_pengguna p ON g.no_induk = p.no_induk WHERE p.hak_akses = 2 ORDER BY g.nama_guru ASC");
                            if($sqlG) {
                                while ($dguru = mysqli_fetch_array($sqlG)) {
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="text-center"><strong><?= $dguru["no_induk"]; ?></strong></td>
                                        <td><?= $dguru["nama_guru"]; ?></td>
                                        <td>
                                            <?php if(empty($dguru["password_plain"])) { ?>
                                                <i class="text-muted" style="font-size:0.8em">(Hanya MD5)</i>
                                            <?php } else { ?>
                                                <span class="badge badge-light border text-dark font-weight-bold" style="font-size:0.9em; letter-spacing:1px;"><?= $dguru["password_plain"]; ?></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-circle btn-info" title="Reset Password" href="?page=edit-user&id_user=<?= urlencode($dguru["no_induk"]); ?>&type=pengguna"><i class="fas fa-lock"></i></a>
                                        </td>
                                    </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB SISWA -->
            <div class="tab-pane fade" id="siswa" role="tabpanel" aria-labelledby="siswa-tab">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered datatable" width="100%" cellspacing="0">
                        <thead>
                            <tr class="bg-light">
                                <th>No.</th>
                                <th>NIS (Username)</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Password Terlihat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $sqlS = mysqli_query($conn, "SELECT s.no_induk, s.nama_siswa, s.kelas, $pwd_col_pengguna FROM tbl_siswa s JOIN tbl_pengguna p ON s.no_induk = p.no_induk WHERE p.hak_akses = 3 ORDER BY s.kelas ASC, s.nama_siswa ASC");
                            if($sqlS) {
                                while ($dsiswa = mysqli_fetch_array($sqlS)) {
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="text-center"><strong><?= $dsiswa["no_induk"]; ?></strong></td>
                                        <td><?= $dsiswa["nama_siswa"]; ?></td>
                                        <td class="text-center"><?= $dsiswa["kelas"]; ?></td>
                                        <td>
                                            <?php if(empty($dsiswa["password_plain"])) { ?>
                                                <i class="text-muted" style="font-size:0.8em">(Hanya MD5)</i>
                                            <?php } else { ?>
                                                <span class="badge badge-light border text-dark font-weight-bold" style="font-size:0.9em; letter-spacing:1px;"><?= $dsiswa["password_plain"]; ?></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-circle btn-info" title="Reset Password" href="?page=edit-user&id_user=<?= urlencode($dsiswa["no_induk"]); ?>&type=pengguna"><i class="fas fa-lock"></i></a>
                                        </td>
                                    </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>
</div>

<!-- MODAL EDIT USER STAFF - diletakkan di luar semua container agar tidak terblokir overflow -->
<div class="modal fade" id="modalEditUser" tabindex="-1" role="dialog" aria-labelledby="modalEditUserLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg" style="border-radius:12px;">
      <div class="modal-header" style="background:linear-gradient(135deg,#4e73df,#224abe); color:#fff; border-radius:12px 12px 0 0;">
        <h5 class="modal-title" id="modalEditUserLabel"><i class="fas fa-user-edit mr-2"></i>Edit Data User Staff</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
      </div>
      <div class="modal-body px-4 py-3">
        <input type="hidden" id="edit_id_user">
        <div class="form-group">
          <label for="edit_nama" class="font-weight-bold">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="edit_nama" placeholder="Nama lengkap" autocomplete="off">
        </div>
        <div class="form-group">
          <label for="edit_username" class="font-weight-bold">Username <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="edit_username" placeholder="Username login" autocomplete="off">
        </div>
        <hr class="my-2">
        <p class="text-muted small mb-2"><i class="fas fa-info-circle"></i> Kosongkan password jika tidak ingin mengubah password.</p>
        <div class="form-group">
          <label for="edit_password" class="font-weight-bold">Password Baru <span class="text-muted font-weight-normal">(opsional)</span></label>
          <div class="input-group">
            <input type="password" class="form-control" id="edit_password" placeholder="Kosongkan jika tidak ingin ubah" autocomplete="new-password">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="button" id="togglePwdBtn" title="Tampilkan/sembunyikan">
                <i class="fas fa-eye" id="togglePwdIcon"></i>
              </button>
            </div>
          </div>
        </div>
        <div id="editUserAlert" class="alert d-none py-2" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
        <button type="button" class="btn btn-primary" id="btnSimpanEditUser"><i class="fas fa-save mr-1"></i>Simpan</button>
      </div>
    </div>
  </div>
</div>
<!-- /MODAL EDIT USER -->

<script>
$(document).ready(function() {
    $(".datatable").DataTable();

    // ===== EDIT USER MODAL =====
    // Buka modal & isi data
    $(document).on('click', '.btn-edit-user', function() {
        var id       = $(this).data('id');
        var nama     = $(this).data('nama');
        var username = $(this).data('username');
        var password = $(this).data('password');

        $('#edit_id_user').val(id);
        $('#edit_nama').val(nama);
        $('#edit_username').val(username);
        $('#edit_password').val('');
        $('#editUserAlert').addClass('d-none').text('');
        $('#modalEditUser').modal('show');
    });

    // Toggle show/hide password
    $('#togglePwdBtn').on('click', function() {
        var inp = $('#edit_password');
        var icon = $('#togglePwdIcon');
        if (inp.attr('type') === 'password') {
            inp.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            inp.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Simpan perubahan via AJAX
    $('#btnSimpanEditUser').on('click', function() {
        var id_user  = $('#edit_id_user').val();
        var nama     = $.trim($('#edit_nama').val());
        var username = $.trim($('#edit_username').val());
        var password = $('#edit_password').val();

        // Validasi client
        if (!nama || !username) {
            showEditAlert('danger', 'Nama dan username tidak boleh kosong.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: 'ajax_edit_user.php',
            method: 'POST',
            dataType: 'json',
            data: {
                id_user:  id_user,
                nama:     nama,
                username: username,
                password: password
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Perubahan');
                if (res.status === 'success') {
                    // Update baris tabel langsung tanpa reload
                    var row = $('#row-user-' + id_user);
                    row.find('.cell-nama').text(res.nama);
                    row.find('.cell-username').text(res.username);
                    if (res.password) {
                        row.find('.cell-password').html('<span class="badge badge-light border text-dark font-weight-bold" style="font-size:0.9em;letter-spacing:1px;">' + res.password + '</span>');
                    }
                    // Update data-attribute tombol edit
                    row.find('.btn-edit-user')
                        .data('nama', res.nama)
                        .data('username', res.username)
                        .data('password', res.password || row.find('.btn-edit-user').data('password'));

                    $('#modalEditUser').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1800, showConfirmButton: false });
                } else {
                    showEditAlert('danger', res.message || 'Terjadi kesalahan.');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Perubahan');
                showEditAlert('danger', 'Koneksi gagal, coba lagi.');
            }
        });
    });

    function showEditAlert(type, msg) {
        $('#editUserAlert').removeClass('d-none alert-success alert-danger alert-warning')
            .addClass('alert-' + type).html('<i class="fas fa-exclamation-circle mr-1"></i>' + msg);
    }
});
</script>

<?php
}
?>
