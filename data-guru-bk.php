<?php
if (!isset($_SESSION["username"])) {
  header("location: index.php?haruslogin");
  exit;
} else if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) { ?>
  <script>window.location = '404.html';</script>
<?php }

include 'koneksi.php';

// Handle POST request to add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_bk'])) {
    $no_induk = mysqli_real_escape_string($conn, $_POST['no_induk'] ?? '');
    $kelas_arr = $_POST['kelas'] ?? [];
    
    if (!empty($no_induk) && !empty($kelas_arr)) {
        mysqli_autocommit($conn, FALSE);
        try {
            // Hapus mapping kelas untuk guru bk ini sebelumnya
            mysqli_query($conn, "DELETE FROM tbl_guru_bk WHERE no_induk = '$no_induk'");
            
            // Insert mapping baru
            foreach ($kelas_arr as $k) {
                $k = mysqli_real_escape_string($conn, $k);
                mysqli_query($conn, "INSERT IGNORE INTO tbl_guru_bk (no_induk, kelas) VALUES ('$no_induk', '$k')");
            }
            mysqli_commit($conn);
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire('Berhasil!', 'Data Guru BK berhasil disimpan.', 'success').then(() => {
                        window.location.href = window.location.pathname;
                    });
                });
            </script>";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
                });
            </script>";
        }
    }
}

// Handle Delete
if (isset($_GET['hapus_bk'])) {
    $no_induk_hapus = mysqli_real_escape_string($conn, $_GET['hapus_bk']);
    mysqli_query($conn, "DELETE FROM tbl_guru_bk WHERE no_induk = '$no_induk_hapus'");
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire('Berhasil!', 'Data Guru BK berhasil dihapus.', 'success').then(() => {
                var url = new URL(window.location.href);
                url.searchParams.delete('hapus_bk');
                window.location.href = url.toString();
            });
        });
    </script>";
}

// Ambil data mapping Guru BK
$sql = "SELECT b.no_induk, g.nama_guru, GROUP_CONCAT(b.kelas ORDER BY b.kelas ASC SEPARATOR ', ') as daftar_kelas
        FROM tbl_guru_bk b
        JOIN tbl_guru g ON b.no_induk = g.no_induk
        GROUP BY b.no_induk, g.nama_guru
        ORDER BY g.nama_guru ASC";
$res = mysqli_query($conn, $sql);
$rows = [];
if($res){
  while($r = mysqli_fetch_assoc($res)) $rows[] = $r;
}

// Ambil list guru untuk dropdown (hanya yang jabatannya BK atau ditandai sebagai Guru BK)
$guruList = [];
$res_guru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE (jabatan LIKE '%BK%' OR is_guru_bk = 1) ORDER BY nama_guru ASC");
if($res_guru){
  while($r = mysqli_fetch_assoc($res_guru)) $guruList[] = $r;
}

// Ambil list kelas untuk dropdown
$kelasList = [];
$res_kelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas ORDER BY kelas ASC");
if($res_kelas){
  while($r = mysqli_fetch_assoc($res_kelas)) $kelasList[] = $r['kelas'];
}
?>

<!-- Include Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #d1d3e2; padding-top: 4px; }
    .kelas-checkbox-container {
        max-height: 250px; 
        overflow-y: auto; 
        border: 1px solid #d1d3e2; 
        border-radius: 8px; 
        padding: 12px; 
        background-color: #f8f9fc;
    }
    .custom-control-label { cursor: pointer; font-size: 14px; padding-top: 2px;}
</style>

<div class="container-fluid">
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Kelola Guru BK & Kelas Dampingan</h1>
  </div>

  <div class="row">
    <!-- Form Tambah/Edit -->
    <div class="col-lg-4 mb-4">
      <div class="card shadow mb-4" style="border:0; border-radius:12px;">
        <div class="card-header py-3" style="background:#f8f9fc; border-top-left-radius:12px; border-top-right-radius:12px;">
          <h6 class="m-0 font-weight-bold text-primary">Set Guru BK Baru / Edit</h6>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <div class="form-group">
                <label class="font-weight-bold text-gray-800">Pilih Guru</label>
                <select name="no_induk" class="form-control select2" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php foreach($guruList as $g): ?>
                    <option value="<?= htmlspecialchars($g['no_induk']) ?>"><?= htmlspecialchars($g['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="font-weight-bold text-gray-800">Pilih Kelas Dampingan <br><small class="text-muted">(Bisa pilih lebih dari satu)</small></label>
                
                <div class="mb-2">
                    <strong>Kelas Terpilih: </strong>
                    <span id="selectedClassesText" class="text-primary font-weight-bold">-</span>
                </div>

                <div class="kelas-checkbox-container">
                    <div class="row">
                        <?php foreach($kelasList as $k): ?>
                        <div class="col-6 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input class-checkbox" name="kelas[]" id="chk_<?= htmlspecialchars(str_replace(' ', '_', $k)) ?>" value="<?= htmlspecialchars($k) ?>">
                                <label class="custom-control-label" for="chk_<?= htmlspecialchars(str_replace(' ', '_', $k)) ?>"><?= htmlspecialchars($k) ?></label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button type="submit" name="simpan_bk" class="btn btn-primary btn-block" style="border-radius:20px; box-shadow: 0 4px 6px rgba(78,115,223,0.3);"><i class="fas fa-save mr-1"></i> Simpan Data</button>
          </form>
          <div class="mt-3 text-muted" style="font-size:12px;">
            <i class="fas fa-info-circle text-primary"></i> <b>Catatan:</b> Jika Anda memilih guru yang sudah ada, daftar kelasnya akan ditimpa (di-update) dengan pilihan terbaru ini.
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Daftar Guru BK -->
    <div class="col-lg-8 mb-4">
      <div class="card shadow mb-4" style="border:0; border-radius:12px;">
        <div class="card-header py-3" style="background:#f8f9fc; border-top-left-radius:12px; border-top-right-radius:12px;">
          <h6 class="m-0 font-weight-bold text-primary">Daftar Guru BK yang Sedang Aktif</h6>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-bordered table-striped table-hover" id="dataTableBK" width="100%" cellspacing="0">
            <thead style="background:#4e73df; color:white;">
              <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Nama Guru BK</th>
                <th width="50%">Kelas yang Didampingi</th>
                <th width="15%" class="text-center"><i class="fas fa-cog"></i></th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($rows)): ?>
              <tr><td colspan="4" class="text-center text-muted" style="padding: 20px;">Belum ada data Guru BK. Silakan set pada form di sebelah kiri.</td></tr>
            <?php else: ?>
              <?php $no=1; foreach($rows as $r): ?>
              <tr>
                <td class="text-center align-middle"><?= $no++ ?></td>
                <td class="align-middle"><strong><?= htmlspecialchars($r['nama_guru']) ?></strong></td>
                <td class="align-middle">
                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                    <?php
                    $kls = explode(', ', $r['daftar_kelas']);
                    foreach($kls as $k) {
                        echo '<span class="badge badge-primary shadow-sm" style="font-size:12px; font-weight:normal; padding:6px 10px; border-radius:12px;">'.htmlspecialchars($k).'</span>';
                    }
                    ?>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <button class="btn btn-sm btn-danger btn-hapus shadow-sm" style="border-radius:8px;" data-id="<?= htmlspecialchars($r['no_induk']) ?>" data-nama="<?= htmlspecialchars($r['nama_guru']) ?>" title="Hapus Semua Dampingan Kelas"><i class="fas fa-trash"></i></button>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select2 Initialization
    if(typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2({
            width: '100%'
        });
    }

    if(typeof $ !== 'undefined') {
        // Update selected classes text when checkboxes change
        function updateSelectedClasses() {
            var selected = [];
            $('.class-checkbox:checked').each(function() {
                selected.push($(this).val());
            });
            if (selected.length > 0) {
                $('#selectedClassesText').text(selected.join(', '));
            } else {
                $('#selectedClassesText').text('-');
            }
        }

        $('.class-checkbox').on('change', updateSelectedClasses);

        $(document).on('click', '.btn-hapus', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Guru BK?',
                    html: 'Yakin ingin menghapus <strong>' + nama + '</strong> dari daftar Guru BK? Seluruh kelas yang didampingi akan dilepas.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74a3b',
                    cancelButtonColor: '#858796',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = new URL(window.location.href);
                        url.searchParams.set('hapus_bk', id);
                        window.location.href = url.toString();
                    }
                });
            } else {
                if (confirm('Yakin ingin menghapus ' + nama + '?')) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('hapus_bk', id);
                    window.location.href = url.toString();
                }
            }
        });
    } else {
        console.error("jQuery is not loaded properly.");
    }
});
</script>
