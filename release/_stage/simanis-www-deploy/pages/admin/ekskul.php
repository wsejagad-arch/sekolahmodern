<?php
// pages/admin/ekskul.php
if (!isset($_SESSION['hak_akses'])) {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_ekskul') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_ekskul']);
        $desc = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $res = mysqli_query($conn, "INSERT INTO tbl_ekskul (nama_ekskul, deskripsi) VALUES ('$nama', '$desc')");
        if(!$res) { echo "<script>alert('Error: ".mysqli_error($conn)."');</script>"; }
        else { echo "<script>window.location.href='home.php?page=ekskul';</script>"; exit; }
    } elseif ($_POST['action'] == 'edit_ekskul') {
        $id = (int)$_POST['id_ekskul'];
        $nama = mysqli_real_escape_string($conn, $_POST['nama_ekskul']);
        $desc = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $res = mysqli_query($conn, "UPDATE tbl_ekskul SET nama_ekskul='$nama', deskripsi='$desc' WHERE id_ekskul=$id");
        if(!$res) { echo "<script>alert('Error: ".mysqli_error($conn)."');</script>"; }
        else { echo "<script>window.location.href='home.php?page=ekskul';</script>"; exit; }
    } elseif ($_POST['action'] == 'delete_ekskul') {
        $id = (int)$_POST['id_ekskul'];
        mysqli_query($conn, "DELETE FROM tbl_ekskul WHERE id_ekskul=$id");
        echo "<script>window.location.href='home.php?page=ekskul';</script>"; exit;
    } elseif ($_POST['action'] == 'add_pembina') {
        $id = (int)$_POST['id_ekskul'];
        $nip = mysqli_real_escape_string($conn, $_POST['no_induk_guru']);
        mysqli_query($conn, "INSERT IGNORE INTO tbl_pembina_ekskul (id_ekskul, no_induk_guru) VALUES ($id, '$nip')");
        echo "<script>window.location.href='home.php?page=ekskul';</script>"; exit;
    } elseif ($_POST['action'] == 'delete_pembina') {
        $id = (int)$_POST['id_pembina'];
        mysqli_query($conn, "DELETE FROM tbl_pembina_ekskul WHERE id_pembina=$id");
        echo "<script>window.location.href='home.php?page=ekskul';</script>"; exit;
    } elseif ($_POST['action'] == 'clear_ekskul_data') {
        if (trim($_POST['confirm_text'] ?? '') === 'BERSIHKAN') {
            $clear_type = $_POST['clear_type'] ?? 'riwayat_anggota';
            
            $tables = ['tbl_anggota_ekskul', 'tbl_presensi_ekskul', 'tbl_jurnal_ekskul', 'tbl_tugas_ekskul'];
            if ($clear_type === 'semua') {
                $tables = array_merge($tables, ['tbl_ekskul', 'tbl_pembina_ekskul', 'tbl_jadwal_ekskul', 'tbl_ekskul_eraport', 'tbl_ekskul_siswa_eraport']);
            }
            
            foreach($tables as $tbl) {
                @mysqli_query($conn, "TRUNCATE TABLE $tbl");
            }
            
            echo "<script>alert('Data Ekstrakurikuler berhasil dibersihkan!'); window.location.href='home.php?page=ekskul';</script>"; exit;
        } else {
            echo "<script>alert('Konfirmasi teks tidak cocok! Pembatalan dilakukan.'); window.location.href='home.php?page=ekskul';</script>"; exit;
        }
    }
}

$gurus = [];
$qg = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru ORDER BY nama_guru ASC");
while($rg = mysqli_fetch_assoc($qg)) {
    $gurus[] = $rg;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Ekstrakurikuler</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Ekstrakurikuler</h6>
            <div>
                <button class="btn btn-sm btn-danger me-1" data-bs-toggle="modal" data-bs-target="#modalClearData"><i class="fas fa-trash-alt"></i> Bersihkan Data</button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddEkskul"><i class="fas fa-plus"></i> Tambah Ekskul</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Ekskul</th>
                            <th>Deskripsi</th>
                            <th>Pembina</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $modals = '';
                        $q = mysqli_query($conn, "SELECT * FROM tbl_ekskul ORDER BY nama_ekskul ASC");
                        while($r = mysqli_fetch_assoc($q)) {
                            $id_ekskul = $r['id_ekskul'];
                            $qp = mysqli_query($conn, "SELECT p.id_pembina, g.nama_guru FROM tbl_pembina_ekskul p JOIN tbl_guru g ON p.no_induk_guru = g.no_induk WHERE p.id_ekskul=$id_ekskul");
                            $pembinas = [];
                            while($rp = mysqli_fetch_assoc($qp)) {
                                $pembinas[] = $rp['nama_guru'] . " <form method='post' class='d-inline' action='home.php?page=ekskul'><input type='hidden' name='action' value='delete_pembina'><input type='hidden' name='id_pembina' value='".$rp['id_pembina']."'><button type='submit' class='btn btn-sm text-danger' style='padding:0;' onclick='return confirm(\"Hapus pembina ini?\")'><i class='fas fa-times'></i></button></form>";
                            }
                            
                            $namaEkskulSafe = htmlspecialchars($r['nama_ekskul'], ENT_QUOTES);
                            $descSafe = htmlspecialchars($r['deskripsi'], ENT_QUOTES);
                            
                            $guruOptions = '';
                            foreach($gurus as $g) {
                                $guruOptions .= '<option value="' . htmlspecialchars($g['no_induk'], ENT_QUOTES) . '">' . htmlspecialchars($g['nama_guru'], ENT_QUOTES) . '</option>';
                            }

                            $modals .= '
                            <!-- Modal Add Pembina -->
                            <div class="modal fade" id="modalAddPembina' . $id_ekskul . '" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                  <form method="post" action="home.php?page=ekskul">
                                    <div class="modal-header">
                                    <h5 class="modal-title">Tambah Pembina Ekskul ' . $namaEkskulSafe . '</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                    <input type="hidden" name="action" value="add_pembina">
                                    <input type="hidden" name="id_ekskul" value="' . $id_ekskul . '">
                                    <div class="form-group">
                                        <label>Pilih Guru</label>
                                        <select name="no_induk_guru" class="form-control" required>
                                            <option value="">-- Pilih Guru --</option>
                                            ' . $guruOptions . '
                                        </select>
                                    </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                      <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                            
                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit' . $id_ekskul . '" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                  <form method="post" action="home.php?page=ekskul">
                                    <div class="modal-header">
                                    <h5 class="modal-title">Edit Ekskul</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                    <input type="hidden" name="action" value="edit_ekskul">
                                    <input type="hidden" name="id_ekskul" value="' . $id_ekskul . '">
                                    <div class="form-group">
                                        <label>Nama Ekskul</label>
                                        <input type="text" name="nama_ekskul" class="form-control" value="' . $namaEkskulSafe . '" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3">' . $descSafe . '</textarea>
                                    </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                      <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>';
                        ?>
                        <tr>
                            <td><?= $r['id_ekskul'] ?></td>
                            <td><?= htmlspecialchars($r['nama_ekskul']) ?></td>
                            <td><?= htmlspecialchars($r['deskripsi']) ?></td>
                            <td>
                                <?= implode('<br>', $pembinas) ?>
                                <button class="btn btn-sm btn-link mt-1" data-bs-toggle="modal" data-bs-target="#modalAddPembina<?= $r['id_ekskul'] ?>">+ Tambah Pembina</button>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $r['id_ekskul'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="post" class="d-inline" action="home.php?page=ekskul">
                                    <input type="hidden" name="action" value="delete_ekskul">
                                    <input type="hidden" name="id_ekskul" value="<?= $r['id_ekskul'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus ekskul ini?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Store all modals into a global variable so they can be outputted outside wrapper in footer.php
ob_start();
?>
<!-- Modal Clear Data -->
<div class="modal fade" id="modalClearData" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="home.php?page=ekskul">
        <div class="modal-header">
        <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Bersihkan Data Ekstrakurikuler</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="clear_ekskul_data">
        <p>Pilih cakupan pembersihan data. <strong>Aksi ini permanen!</strong></p>
        
        <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="clear_type" id="clearType1" value="riwayat_anggota" checked>
            <label class="form-check-label" for="clearType1">
                <strong>Kosongkan Anggota & Riwayat</strong><br>
                <small class="text-muted">Menghapus data anggota ekskul, presensi, jurnal, dan tugas. (Data Master Ekskul & Pembina tetap dipertahankan)</small>
            </label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="clear_type" id="clearType2" value="semua">
            <label class="form-check-label text-danger" for="clearType2">
                <strong>Hapus Seluruh Data (Reset Total)</strong><br>
                <small class="text-muted">Menghapus SELURUH Master Ekskul, Pembina, Anggota, Presensi, dan Relasi e-Raport. Sistem kembali seperti baru.</small>
            </label>
        </div>
        
        <div class="form-group mt-3">
            <label class="fw-bold">Ketik "BERSIHKAN" untuk konfirmasi:</label>
            <input type="text" name="confirm_text" class="form-control text-center text-danger fw-bold" required autocomplete="off" pattern="BERSIHKAN" placeholder="BERSIHKAN" title="Ketik BERSIHKAN dengan huruf besar">
        </div>
      </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Ya, Bersihkan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAddEkskul" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="home.php?page=ekskul">
        <div class="modal-header">
        <h5 class="modal-title">Tambah Ekskul Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add_ekskul">
        <div class="form-group">
            <label>Nama Ekskul</label>
            <input type="text" name="nama_ekskul" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
        </div>
      </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
$modalAddEkskulHtml = ob_get_clean();
$GLOBALS['custom_modals'] = $modals . $modalAddEkskulHtml;
?>

