<?php
// Proteksi: hanya admin
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    echo '<p class="text-danger p-3">Akses ditolak.</p>'; return;
}
include_once __DIR__ . '/../../koneksi.php';

// Auto-migrate: pastikan kolom jabatan ada
$_jChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'jabatan'");
if ($_jChk && mysqli_num_rows($_jChk) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_siswa ADD COLUMN jabatan ENUM('Siswa','Ketua Kelas') DEFAULT 'Siswa' AFTER kelas");
}

// ── Ambil semua kelas ────────────────────────────────────────────────────────
$kelasList = [];
$rKelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas ORDER BY kelas ASC");
while ($dk = mysqli_fetch_assoc($rKelas)) {
    $kelasList[] = $dk['kelas'];
}

// ── Untuk setiap kelas: siswa aktif & ketua kelas saat ini ───────────────────
$kelasData = [];
foreach ($kelasList as $k) {
    $kEsc = mysqli_real_escape_string($conn, $k);
    $rSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa, jabatan FROM tbl_siswa
                                   WHERE kelas='$kEsc' AND status='Aktif'
                                   ORDER BY nama_siswa ASC");
    $siswaList = [];
    $ketuaSaatIni = null;
    while ($ds = mysqli_fetch_assoc($rSiswa)) {
        $siswaList[] = $ds;
        if ($ds['jabatan'] === 'Ketua Kelas') {
            $ketuaSaatIni = $ds;
        }
    }
    $kelasData[] = [
        'kelas'       => $k,
        'siswa'       => $siswaList,
        'ketua'       => $ketuaSaatIni,
        'total'       => count($siswaList),
    ];
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="fas fa-user-check text-warning mr-2"></i>Setting Ketua Kelas
    </h1>
    <a href="?page=data-siswa" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
      <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali ke Data Siswa
    </a>
  </div>

  <!-- Info Banner -->
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle mr-2"></i>
    <strong>Informasi:</strong> Setiap kelas hanya boleh memiliki <strong>satu</strong> Ketua Kelas.
    Ketua Kelas akan mendapatkan menu khusus di halaman Presensi untuk mengkonfirmasi kehadiran guru.
    Pilih <em>"— Tidak Ada —"</em> untuk menghapus ketua kelas dari suatu kelas.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>

  <!-- Summary Row -->
  <div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Kelas</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($kelasData) ?></div>
            </div>
            <div class="col-auto"><i class="fas fa-school fa-2x text-gray-300"></i></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sudah Ada Ketua</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?= count(array_filter($kelasData, function($k) { return $k['ketua'] !== null; })) ?>
              </div>
            </div>
            <div class="col-auto"><i class="fas fa-user-check fa-2x text-gray-300"></i></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Belum Ada Ketua</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?= count(array_filter($kelasData, function($k) { return $k['ketua'] === null; })) ?>
              </div>
            </div>
            <div class="col-auto"><i class="fas fa-user-times fa-2x text-gray-300"></i></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabel Ketua Kelas -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-crown mr-1 text-warning"></i>Daftar Ketua Kelas Per Kelas
      </h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th style="width:50px">No.</th>
              <th>Kelas</th>
              <th>Jumlah Siswa Aktif</th>
              <th>Ketua Kelas Saat Ini</th>
              <th style="min-width:280px">Ganti / Tetapkan Ketua Kelas</th>
              <th style="width:110px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kelasData as $i => $kd): ?>
            <tr id="row-<?= htmlspecialchars($kd['kelas']) ?>">
              <td class="text-center"><?= $i + 1 ?></td>
              <td><strong><?= htmlspecialchars($kd['kelas']) ?></strong></td>
              <td class="text-center">
                <span class="badge badge-secondary"><?= $kd['total'] ?> siswa</span>
              </td>
              <td id="ketua-label-<?= htmlspecialchars($kd['kelas']) ?>">
                <?php if ($kd['ketua']): ?>
                  <span class="badge badge-warning text-dark px-2 py-1">
                    <i class="fas fa-crown mr-1" style="font-size:10px"></i>
                    <?= htmlspecialchars($kd['ketua']['nama_siswa']) ?>
                  </span>
                  <div class="small text-muted mt-1">NIS: <?= htmlspecialchars($kd['ketua']['no_induk']) ?></div>
                <?php else: ?>
                  <span class="badge badge-light text-muted border">— Belum ditentukan —</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (empty($kd['siswa'])): ?>
                  <em class="text-muted small">Tidak ada siswa aktif</em>
                <?php else: ?>
                <select class="form-control form-control-sm select-ketua"
                        id="select-<?= htmlspecialchars($kd['kelas']) ?>"
                        data-kelas="<?= htmlspecialchars($kd['kelas']) ?>">
                  <option value="">— Tidak Ada / Hapus Ketua —</option>
                  <?php foreach ($kd['siswa'] as $s): ?>
                  <option value="<?= htmlspecialchars($s['no_induk']) ?>"
                    <?= ($kd['ketua'] && $kd['ketua']['no_induk'] === $s['no_induk']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nama_siswa']) ?> (NIS: <?= htmlspecialchars($s['no_induk']) ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if (!empty($kd['siswa'])): ?>
                <button class="btn btn-sm btn-primary btn-simpan-ketua"
                        data-kelas="<?= htmlspecialchars($kd['kelas']) ?>"
                        title="Simpan ketua kelas untuk <?= htmlspecialchars($kd['kelas']) ?>">
                  <i class="fas fa-save mr-1"></i>Simpan
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
<!-- End Page Content -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.btn-simpan-ketua').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var kelas   = this.getAttribute('data-kelas');
      var selEl   = document.getElementById('select-' + kelas);
      var noInduk = selEl ? selEl.value : '';
      var namaOpt = selEl && selEl.selectedOptions[0] ? selEl.selectedOptions[0].text : '';

      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan…';

      var fd = new FormData();
      fd.append('kelas', kelas);
      fd.append('no_induk', noInduk);

      fetch('api/set_ketua_kelas.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (j.success) {
            // Update label
            var labelEl = document.getElementById('ketua-label-' + kelas);
            if (labelEl) {
              if (noInduk) {
                var namaBersih = namaOpt.replace(/ \(NIS:.*\)$/, '');
                labelEl.innerHTML =
                  '<span class="badge badge-warning text-dark px-2 py-1">'
                  + '<i class="fas fa-crown mr-1" style="font-size:10px"></i>'
                  + namaBersih + '</span>'
                  + '<div class="small text-muted mt-1">NIS: ' + noInduk + '</div>';
              } else {
                labelEl.innerHTML = '<span class="badge badge-light text-muted border">— Belum ditentukan —</span>';
              }
            }
            if (typeof Swal !== 'undefined') {
              Swal.fire({ icon: 'success', title: 'Berhasil!', text: j.message, timer: 1800, showConfirmButton: false });
            } else {
              alert(j.message);
            }
          } else {
            if (typeof Swal !== 'undefined') {
              Swal.fire({ icon: 'error', title: 'Gagal', text: j.message || 'Terjadi kesalahan.' });
            } else {
              alert('Gagal: ' + (j.message || 'Terjadi kesalahan.'));
            }
          }
        })
        .catch(function (e) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: e.message });
          } else {
            alert('Error: ' + e.message);
          }
        })
        .finally(function () {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan';
        });
    });
  });
});
</script>
