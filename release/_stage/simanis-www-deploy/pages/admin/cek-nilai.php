<?php
// Akses admin sudah diverifikasi melalui header.php dari home.php
// Pastikan koneksi tersedia di scope file ini.
if (!isset($conn) || !($conn instanceof mysqli)) {
  // Gunakan require (tanpa _once) agar tetap dieksekusi walau pernah di-include di dalam fungsi lain.
  require __DIR__.'/../../koneksi.php';
}

date_default_timezone_set('Asia/Jakarta');

// Ambil opsi filter
$kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
$idmapel = (int)($_GET['idmapel'] ?? 0);
$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? '');

// Dropdown data
$idSekolah = mt_current_school_id();
$kelasOpts = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE id_sekolah=$idSekolah ORDER BY kelas ASC");
$mapelOpts = mysqli_query($conn, "SELECT DISTINCT id_mapel, nama_mapel, kelas FROM tbl_mapel_ampu WHERE id_sekolah=$idSekolah ORDER BY nama_mapel ASC, kelas ASC");

// Pastikan tabel penilaian dinamis tersedia
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

$idSekolah = mt_current_school_id();
$wherePert = " WHERE id_sekolah=$idSekolah ";
if ($tanggal !== '') { $wherePert .= " AND tanggal='".$tanggal."'"; }
if ($kelas !== '') { $wherePert .= " AND kelas='".$kelas."'"; }
if ($idmapel > 0) { $wherePert .= " AND id_mapel=".$idmapel; }

$pertemuan = mysqli_query($conn, "SELECT tanggal, id_mapel, kelas, mapel FROM tbl_penilaian_item $wherePert GROUP BY tanggal, id_mapel, kelas, mapel ORDER BY tanggal DESC, kelas ASC, mapel ASC");
?>
<div class="container-fluid">
  <h5 class="mb-3">Cek Nilai</h5>
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-3" method="get">
        <input type="hidden" name="page" value="cek-nilai" />
        <div class="col-12 col-md-3">
          <label class="form-label">Tanggal (opsional)</label>
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
          <label class="form-label">Mapel</label>
          <select name="idmapel" class="form-select">
            <option value="">Semua Mapel</option>
            <?php while($mo = mysqli_fetch_assoc($mapelOpts)) { ?>
              <option value="<?= (int)$mo['id_mapel']; ?>" <?= $idmapel === (int)$mo['id_mapel'] ? 'selected' : '' ?>><?= htmlspecialchars($mo['nama_mapel'].' ('.$mo['kelas'].')'); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-md-2 align-self-end">
          <button class="btn btn-primary w-100" type="submit">Terapkan</button>
        </div>
      </form>
    </div>
  </div>

  <?php 
  $isFiltered = ($tanggal !== '' || $kelas !== '' || $idmapel > 0);
  if (!$isFiltered) {
?>
  <div class="alert alert-secondary">Silakan pilih filter (Tanggal, Kelas, atau Mapel) terlebih dahulu untuk menampilkan data nilai.</div>
<?php } else { ?>
  <?php if (mysqli_num_rows($pertemuan) === 0) { ?>
    <div class="alert alert-info">Belum ada data penilaian untuk filter tersebut.</div>
  <?php } ?>

  <?php while ($p = mysqli_fetch_assoc($pertemuan)) {
    $tgl = $p['tanggal'];
    $idm = (int)$p['id_mapel'];
    $kls = $p['kelas'];
    $mpl = $p['mapel'];

    $idSekolah = mt_current_school_id();
    $items = mysqli_query($conn, "SELECT * FROM tbl_penilaian_item WHERE id_sekolah=$idSekolah AND tanggal='".$tgl."' AND id_mapel=".$idm." ORDER BY id ASC");
    $itemList = [];
    while ($it = mysqli_fetch_assoc($items)) { $itemList[] = $it; }

    $siswa = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE id_sekolah=$idSekolah AND kelas='".mysqli_real_escape_string($conn,$kls)."' AND status='Aktif' ORDER BY nama_siswa ASC");

    $nilaiMap = [];
    if (count($itemList) > 0) {
      $ids = array_map(function($x){ return (int)$x['id']; }, $itemList);
      $idStr = implode(',', $ids);
      $qNil = mysqli_query($conn, "SELECT * FROM tbl_nilai_item WHERE id_sekolah=$idSekolah AND id_item IN (".$idStr.")");
      while ($nv = mysqli_fetch_assoc($qNil)) {
        $nilaiMap[$nv['id_item']][$nv['no_induk_siswa']] = $nv['nilai'];
      }
    }
  ?>
  <div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <div>
        <strong><?= htmlspecialchars($kls); ?></strong> • <?= htmlspecialchars($mpl); ?>
      </div>
      <span class="text-muted small">Tanggal: <?= htmlspecialchars($tgl); ?></span>
    </div>
    <div class="card-body">
      <?php if (count($itemList) === 0) { ?>
        <div class="alert alert-secondary">Belum ada kolom penilaian ditambahkan untuk pertemuan ini.</div>
      <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-striped table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Nama Siswa</th>
                <?php foreach ($itemList as $it) { ?>
                  <th class="text-center">
                    <div class="fw-semibold"><?= htmlspecialchars($it['kode_penilaian']); ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($it['materi']); ?></div>
                  </th>
                <?php } ?>
                <th class="text-center">Rata-rata UH</th>
                <th class="text-center">Rata-rata ASAS/ASAT</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($s = mysqli_fetch_assoc($siswa)) { $ni = $s['no_induk']; ?>
                <tr>
                  <td><?= htmlspecialchars($s['nama_siswa']); ?></td>
                  <?php
                    $uhSum = 0; $uhCount = 0;
                    $aaSum = 0; $aaCount = 0; // ASAS/ASAT combined
                    foreach ($itemList as $it) {
                      $val = $nilaiMap[$it['id']][$ni] ?? '';
                      // Tampilkan nilai sel
                      echo '<td class="text-center">'.($val === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($val)).'</td>';
                      // Kumpulkan untuk rata-rata
                      $kode = strtoupper(trim($it['kode_penilaian']));
                      if ($val !== '' && is_numeric($val)) {
                        $num = (float)$val;
                        if (strpos($kode, 'UH') === 0) {
                          $uhSum += $num; $uhCount++;
                        } elseif ($kode === 'ASAS' || $kode === 'ASAT') {
                          $aaSum += $num; $aaCount++;
                        }
                      }
                    }
                    $avgUH = $uhCount > 0 ? round($uhSum / $uhCount, 2) : '';
                    $avgAA = $aaCount > 0 ? round($aaSum / $aaCount, 2) : '';
                  ?>
                  <td class="text-center"><?= $avgUH === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($avgUH); ?></td>
                  <td class="text-center"><?= $avgAA === '' ? '<span class="text-muted">-</span>' : htmlspecialchars($avgAA); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </div>
  </div>
  <?php } // end while ?>
<?php } // end if isFiltered ?>
</div>

