<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION["no_induk"])) { header("location: ../../index.php?haruslogin"); exit; }
if($_SESSION['hak_akses'] != 2) { echo '<script>window.location="../../404.html";</script>'; exit; }
include '../../koneksi.php';
include '../../functions.php';
date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];

// Pastikan tabel ada (untuk instalasi lama)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_penilaian_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  id_mapel INT NOT NULL,
  kelas VARCHAR(50) NOT NULL,
  mapel VARCHAR(100) NOT NULL,
  no_induk_guru VARCHAR(50) NOT NULL,
  kode_penilaian VARCHAR(20) NOT NULL,
  materi VARCHAR(255) NOT NULL,
  UNIQUE KEY uniq_item (tanggal, id_mapel, kode_penilaian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_item INT NOT NULL,
  no_induk_siswa VARCHAR(50) NOT NULL,
  nilai FLOAT DEFAULT 0,
  UNIQUE KEY uniq_nilai_item (id_item, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? '');
$kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
$idmapel = (int)($_GET['idmapel'] ?? 0);

// Ambil daftar pertemuan untuk guru ini (dengan filter opsional)
$where = " WHERE no_induk_guru='".$nipguru."'";
if ($tanggal !== '') { $where .= " AND tanggal='".$tanggal."'"; }
if ($kelas !== '') { $where .= " AND kelas='".$kelas."'"; }
if ($idmapel > 0) { $where .= " AND id_mapel=".$idmapel; }

$pertemuan = mysqli_query($conn, "SELECT tanggal, id_mapel, kelas, mapel FROM tbl_penilaian_item ".$where." GROUP BY tanggal, id_mapel, kelas, mapel ORDER BY tanggal DESC, kelas ASC, mapel ASC");

// Data untuk filter dropdown
$kelasOpts = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY kelas ASC");
$mapelOpts = mysqli_query($conn, "SELECT DISTINCT id_mapel, nama_mapel, kelas FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY nama_mapel ASC, kelas ASC");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nilai Siswa</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body { background: #f5f7fb; }
    .page-header {
      background: linear-gradient(135deg, #0d6efd, #6f42c1);
      color: #fff;
      padding: 1.25rem 1rem;
      border-radius: 0 0 18px 18px;
      box-shadow: 0 4px 12px rgba(13,110,253,0.25);
      margin-bottom: 1rem;
    }
    .filter-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .meeting-card { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    .meeting-card .card-header { border-radius: 14px 14px 0 0; }
    .badge-pill { border-radius: 999px; }
    .empty-state { color: #6c757d; }
    .table thead th { white-space: nowrap; }
    .table tbody td { vertical-align: middle; }
    .th-item { position: relative; }
    .th-item .btn-del-item { position:absolute; top:6px; right:6px; }
  </style>
</head>
<body>
<div class="page-header">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
      <i class="bi bi-bar-chart-steps fs-4 me-2"></i>
      <div>
        <h5 class="mb-0 fw-semibold">Nilai Siswa</h5>
        <small>Rekap penilaian per pertemuan</small>
      </div>
    </div>
    <a href="guru.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
  </div>
</div>

<div class="container pb-4">
  <div class="card filter-card mb-3">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get">
        <div class="col-12 col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>" class="form-control" />
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Kelas</label>
          <select name="kelas" class="form-select">
            <option value="">Semua Kelas</option>
            <?php while($ko = mysqli_fetch_assoc($kelasOpts)) { $k = $ko['kelas']; ?>
              <option value="<?= htmlspecialchars($k); ?>" <?= $kelas === $k ? 'selected' : '' ?>><?= htmlspecialchars($k); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Mapel (opsional)</label>
          <select name="idmapel" class="form-select">
            <option value="">Semua Mapel</option>
            <?php while($mo = mysqli_fetch_assoc($mapelOpts)) { ?>
              <option value="<?= (int)$mo['id_mapel']; ?>" <?= $idmapel === (int)$mo['id_mapel'] ? 'selected' : '' ?>><?= htmlspecialchars($mo['nama_mapel'].' ('.$mo['kelas'].')'); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <div class="d-grid gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
            <a href="nilai.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php if (mysqli_num_rows($pertemuan) === 0) { ?>
    <div class="text-center py-5 empty-state">
      <i class="bi bi-clipboard-check fs-1 mb-2"></i>
      <h6 class="fw-semibold">Belum ada penilaian pada tanggal ini</h6>
      <p class="mb-0">Silakan isi penilaian dari halaman Dashboard Guru.</p>
    </div>
  <?php } ?>

  <?php while ($p = mysqli_fetch_assoc($pertemuan)) {
    $tgl = $p['tanggal'];
    $idm = (int)$p['id_mapel'];
    $kls = $p['kelas'];
    $mpl = $p['mapel'];

    // Ambil items untuk pertemuan ini
    $items = mysqli_query($conn, "SELECT * FROM tbl_penilaian_item WHERE tanggal='".$tgl."' AND id_mapel=".$idm." ORDER BY id ASC");
    $itemList = [];
    while ($it = mysqli_fetch_assoc($items)) { $itemList[] = $it; }

    // Ambil siswa kelas tsb dan simpan dalam array
    $siswaQuery = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='".mysqli_real_escape_string($conn,$kls)."' AND status='Aktif' ORDER BY nama_siswa ASC");
    $siswaList = [];
    while ($s = mysqli_fetch_assoc($siswaQuery)) { $siswaList[] = $s; }

    // Ambil nilai existing untuk semua item
    $nilaiMap = [];
    if (count($itemList) > 0) {
      $ids = array_map(function($x){ return (int)$x['id']; }, $itemList);
      $idStr = implode(',', $ids);
      $qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_item IN (".$idStr.")");
      while ($nv = mysqli_fetch_assoc($qNil)) {
        $nilaiMap[$nv['id_item']][$nv['no_induk_siswa']] = $nv['nilai'];
      }
    }
  ?>
  <div class="card meeting-card mb-4">
    <div class="card-header bg-white">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2">Kelas <?= htmlspecialchars($kls); ?></span>
          <span class="fw-semibold"><?= htmlspecialchars($mpl); ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php $jmlItem = count($itemList); if ($jmlItem>0) { ?>
            <span class="badge rounded-pill text-bg-secondary"><?= $jmlItem; ?> Item</span>
          <?php } ?>
          <span class="text-muted small"><i class="bi bi-calendar3"></i> <?= htmlspecialchars($tgl); ?></span>
          <a href="download_nilai_kelas_pdf.php?tanggal=<?= urlencode($tgl); ?>&idmapel=<?= (int)$idm; ?>&kelas=<?= urlencode($kls); ?>" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> Download PDF Kelas</a>
          <button class="btn btn-sm btn-outline-danger btn-mass-clear" data-tanggal="<?= htmlspecialchars($tgl); ?>" data-idmapel="<?= (int)$idm; ?>" data-kelas="<?= htmlspecialchars($kls); ?>"><i class="bi bi-trash3"></i> Bersihkan Nilai</button>
        </div>
      </div>
    </div>
    <div class="card-body">
      <?php if (count($itemList) === 0) { ?>
        <div class="alert alert-secondary d-flex align-items-center"><i class="bi bi-info-circle me-2"></i> Belum ada kolom penilaian ditambahkan untuk pertemuan ini.</div>
      <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-striped table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Nama Siswa</th>
                <?php foreach ($itemList as $it) { ?>
                  <th class="text-center th-item" data-item-id="<?= (int)$it['id']; ?>">
                    <div class="fw-semibold"><?= htmlspecialchars($it['kode_penilaian']); ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($it['materi']); ?></div>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-del-item" title="Hapus kolom" data-item-id="<?= (int)$it['id']; ?>">
                      <i class="bi bi-x"></i>
                    </button>
                  </th>
                <?php } ?>
                <th class="text-center">Rata-rata UH</th>
                <th class="text-center">Rata-rata ASAS/ASAT</th>
                <th class="text-center">Aksi</th>
                <th class="text-center">Download</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($siswaList as $s) { $ni = $s['no_induk']; ?>
                <tr>
                  <td><?= htmlspecialchars($s['nama_siswa']); ?></td>
                  <?php
                    $uhSum=0; $uhCnt=0; $asSum=0; $asCnt=0;
                    foreach ($itemList as $it) {
                      $valRaw = $nilaiMap[$it['id']][$ni] ?? '';
                      $display = ($valRaw === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($valRaw));
                      // tampilkan sel
                      echo '<td class="text-center"><span class="cell-value" data-item-id="'.(int)$it['id'].'" data-nis="'.htmlspecialchars($ni).'">'.$display.'</span></td>';
                      // akumulasi rata-rata
                      $kode = strtoupper($it['kode_penilaian']);
                      if ($valRaw !== '' && is_numeric($valRaw)) {
                        $num = (float)$valRaw;
                        if (strpos($kode, 'UH') === 0) { $uhSum += $num; $uhCnt++; }
                        if ($kode === 'ASAS' || $kode === 'ASAT') { $asSum += $num; $asCnt++; }
                      }
                    }
                    $avgUH = $uhCnt ? round($uhSum / $uhCnt, 2) : '';
                    $avgAS = $asCnt ? round($asSum / $asCnt, 2) : '';
                  ?>
                  <td class="text-center"><?= $avgUH === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($avgUH); ?></td>
                  <td class="text-center"><?= $avgAS === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($avgAS); ?></td>
                  <td class="text-center"><button class="btn btn-sm btn-outline-primary btn-edit-row" data-nis="<?= htmlspecialchars($ni); ?>" data-tanggal="<?= htmlspecialchars($tgl); ?>" data-idmapel="<?= (int)$idm; ?>">Edit</button></td>
                  <td class="text-center"><a href="download_nilai_pdf.php?nis=<?= urlencode($ni); ?>&tanggal=<?= urlencode($tgl); ?>&idmapel=<?= (int)$idm; ?>&kelas=<?= urlencode($kls); ?>" class="btn btn-sm btn-danger" target="_blank" title="NIS: <?= htmlspecialchars($ni); ?>"><i class="bi bi-file-pdf"></i> PDF</a></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </div>
  </div>
  <?php } // end while pertemuan ?>
</div>

<!-- Modal Edit Nilai Per Orang -->
<div class="modal fade" id="modalEditNilai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Nilai Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditOrang">
          <input type="hidden" name="tanggal" />
          <input type="hidden" name="idmapel" />
          <input type="hidden" name="no_induk_siswa" />
          <div class="row" id="listInputNilai"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnSimpanOrang">Simpan</button>
      </div>
    </div>
  </div>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (function(){
    function reloadPage(){ window.location.reload(); }
    // Hapus kolom nilai
    $(document).on('click', '.btn-del-item', function(){
      const id = $(this).data('item-id');
      if (!confirm('Hapus kolom penilaian ini beserta semua nilainya?')) return;
      $.post('hapus_item_penilaian.php', { id_item: id }, function(res){
        reloadPage();
      }).fail(function(){ alert('Gagal menghapus kolom'); });
    });

    // Bersihkan nilai masal (per pertemuan)
    $(document).on('click', '.btn-mass-clear', function(){
      const tanggal = $(this).data('tanggal');
      const idmapel = $(this).data('idmapel');
      const kelas = $(this).data('kelas');
      if (!confirm('Hapus semua nilai pada pertemuan ini?')) return;
      $.post('hapus_nilai_masal.php', { tanggal, idmapel, kelas }, function(){
        reloadPage();
      }).fail(function(){ alert('Gagal menghapus nilai'); });
    });

    // Edit nilai per orang: rakit form berdasarkan header kolom dan nilai sel di baris tsb
    $(document).on('click', '.btn-edit-row', function(){
      const btn = $(this);
      const tr = btn.closest('tr');
      const modal = new bootstrap.Modal(document.getElementById('modalEditNilai'));
      const nis = btn.data('nis');
      const tanggal = btn.data('tanggal');
      const idmapel = btn.data('idmapel');
      const ths = btn.closest('.card').find('thead th.th-item');
      const inputs = [];
      ths.each(function(index){
        const th = $(this);
        const itemId = th.data('item-id');
        const code = th.find('.fw-semibold').text().trim();
        const materi = th.find('.text-muted.small').text().trim();
        const td = tr.find('td').eq(1+index); // kolom 0 = Nama Siswa
        let val = td.find('.cell-value').text().trim();
        if (val === '-' || val === '') val = '';
        inputs.push(`
          <div class="col-12 col-md-6 mb-3">
            <label class="form-label">${code} <small class="text-muted d-block">${materi}</small></label>
            <input type="number" name="nilai[${itemId}]" class="form-control" min="0" max="100" step="1" value="${val}">
          </div>`);
      });
      $('#formEditOrang [name=tanggal]').val(tanggal);
      $('#formEditOrang [name=idmapel]').val(idmapel);
      $('#formEditOrang [name=no_induk_siswa]').val(nis);
      $('#listInputNilai').html(inputs.join(''));
      modal.show();
    });

    // Simpan nilai per orang (batch)
    $('#btnSimpanOrang').on('click', function(){
      const form = $('#formEditOrang');
      const btn = $(this);
      btn.prop('disabled', true).text('Menyimpan...');
      $.post('update_nilai_perorang.php', form.serialize(), function(){
        // Setelah simpan, reload untuk sinkron tampilan
        window.location.reload();
      }).fail(function(){
        alert('Gagal menyimpan');
        btn.prop('disabled', false).text('Simpan');
      });
    });
  })();
</script>
</body>
</html>

