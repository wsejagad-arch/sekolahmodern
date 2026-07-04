<?php
// Halaman ini di-include oleh home.php?page=monitoring-izin
// Auth & koneksi sudah dihandle home.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../koneksi.php';
}
date_default_timezone_set('Asia/Jakarta');

// Auto-migration: tambah kolom catatan_penolakan jika belum ada
@mysqli_query($conn, "ALTER TABLE tbl_izin_siswa ADD COLUMN IF NOT EXISTS catatan_penolakan TEXT DEFAULT NULL");

// Cek apakah kolom foto_selfie ada di database (hosting mungkin belum ada)
$_colFoto = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa LIKE 'foto_selfie'");
$hasFotoSelfieCol = ($_colFoto && mysqli_num_rows($_colFoto) > 0);

// Flash message dari session
$pesanAdmin = '';
if (!empty($_SESSION['admin_flash'])) {
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    $alertType = htmlspecialchars($flash['type']);
    $alertMsg  = htmlspecialchars($flash['msg']);
    $icon = ($flash['type'] === 'success') ? 'fa-check-circle' : (($flash['type'] === 'danger') ? 'fa-exclamation-circle' : 'fa-info-circle');
    $pesanAdmin = '<div class="alert alert-'.$alertType.' alert-dismissible fade show small">'
                . '<i class="fas '.$icon.' mr-1"></i> '.$alertMsg.' '
                . '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
                . '</div>';
}

// Filter
$filterKelas  = trim(isset($_GET['filter_kelas'])  ? $_GET['filter_kelas']  : '');
$filterStatus = trim(isset($_GET['filter_status']) ? $_GET['filter_status'] : '');
$filterTgl    = trim(isset($_GET['filter_tgl'])    ? $_GET['filter_tgl']    : '');

// Ambil data
$izinList     = array();
$kelasOptions = array();
$__tblIzin    = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin_siswa'");
if ($__tblIzin && mysqli_num_rows($__tblIzin) > 0) {
    $resKelas = mysqli_query($conn, "SELECT DISTINCT kelas_siswa FROM tbl_izin_siswa ORDER BY kelas_siswa");
    if ($resKelas) {
        while ($rk = mysqli_fetch_assoc($resKelas)) {
            $kelasOptions[] = $rk['kelas_siswa'];
        }
    }

    $where = array();
    if ($filterKelas  !== '') $where[] = "iz.kelas_siswa  = '".mysqli_real_escape_string($conn, $filterKelas)."'";
    if ($filterStatus !== '') $where[] = "iz.status_izin  = '".mysqli_real_escape_string($conn, $filterStatus)."'";
    if ($filterTgl    !== '') $where[] = "iz.tanggal_izin = '".mysqli_real_escape_string($conn, $filterTgl)."'";
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $res = mysqli_query($conn, "SELECT iz.*, s.nama_siswa
        FROM tbl_izin_siswa iz
        JOIN tbl_siswa s ON iz.no_induk_siswa = s.no_induk
        $whereClause
        ORDER BY iz.waktu_pengajuan DESC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $izinList[] = $row;
    }
}

// Stats
$statTotal     = count($izinList);
$statMenunggu  = 0;
$statDisetujui = 0;
$statDitolak   = 0;
foreach ($izinList as $r) {
    if ($r['status_izin'] === 'Disetujui Penuh') {
        $statDisetujui++;
    } elseif ($r['status_izin'] === 'Ditolak') {
        $statDitolak++;
    } elseif (in_array($r['status_izin'], array('Menunggu Wali Kelas','Menunggu Guru BK','Menunggu Validasi','Disetujui Sebagian'))) {
        $statMenunggu++;
    }
}

// Helper: warna badge status
function izinBadgeStyle($status) {
    switch ($status) {
        case 'Disetujui Penuh':    return 'background:#d1fae5;color:#065f46;';
        case 'Ditolak':            return 'background:#fee2e2;color:#991b1b;';
        case 'Disetujui Sebagian': return 'background:#fef3c7;color:#92400e;';
        case 'Menunggu Guru BK':   return 'background:#ede9fe;color:#5b21b6;';
        default:                   return 'background:#fef9c3;color:#854d0e;';
    }
}
?>

<style>
/* ===== Monitoring Izin — Minimalist Professional Theme ===== */
.mi-page { font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif; }
.mi-page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.mi-page-title { font-size:1.1rem; font-weight:700; color:#1e293b; margin:0; }
.mi-page-sub   { font-size:.78rem; color:#94a3b8; }

/* Stat cards */
.mi-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.25rem; }
@media (max-width:768px) { .mi-stats { grid-template-columns:repeat(2,1fr); } }
.mi-stat { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:.85rem 1rem; display:flex; flex-direction:column; gap:.15rem; }
.mi-stat-label { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; }
.mi-stat-num   { font-size:1.55rem; font-weight:700; line-height:1; color:#1e293b; }
.mi-stat.accent-blue   { border-top:3px solid #3b82f6; }
.mi-stat.accent-yellow { border-top:3px solid #f59e0b; }
.mi-stat.accent-green  { border-top:3px solid #10b981; }
.mi-stat.accent-red    { border-top:3px solid #ef4444; }

/* Filter bar */
.mi-filter { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:.7rem 1rem; margin-bottom:1.25rem; }
.mi-filter form { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
.mi-filter label { font-size:.75rem; font-weight:600; color:#64748b; white-space:nowrap; margin:0; }
.mi-filter select, .mi-filter input[type=date] {
    border:1px solid #cbd5e1; border-radius:6px; padding:.3rem .6rem;
    font-size:.8rem; color:#334155; background:#f8fafc;
    outline:none; transition:border-color .15s;
}
.mi-filter select:focus, .mi-filter input[type=date]:focus { border-color:#3b82f6; background:#fff; }
.mi-filter .mi-btn { border:none; border-radius:6px; padding:.32rem .85rem; font-size:.78rem; font-weight:600; cursor:pointer; transition:opacity .15s; }
.mi-btn-primary { background:#3b82f6; color:#fff; }
.mi-btn-secondary { background:#e2e8f0; color:#475569; }
.mi-btn:hover { opacity:.85; }

/* Table wrapper */
.mi-card { background:#fff; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; }
.mi-card-footer { padding:.55rem 1rem; border-top:1px solid #f1f5f9; font-size:.75rem; color:#94a3b8; }

/* Table */
.mi-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.mi-table thead tr { background:#f8fafc; }
.mi-table th { padding:.6rem .85rem; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
.mi-table td { padding:.6rem .85rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; color:#334155; }
.mi-table tbody tr:last-child td { border-bottom:none; }
.mi-table tbody tr:hover td { background:#f8fafc; }

/* Status badge */
.mi-badge { display:inline-block; padding:.22rem .6rem; border-radius:20px; font-size:.7rem; font-weight:600; white-space:nowrap; }

/* Validation icons */
.vi-ok   { color:#10b981; }
.vi-no   { color:#ef4444; }
.vi-wait { color:#f59e0b; }

/* Selfie thumb */
.mi-thumb { width:38px; height:38px; object-fit:cover; border-radius:5px; border:1px solid #e2e8f0; display:block; }

/* Delete button */
.mi-del-btn { border:1px solid #fca5a5; background:#fff5f5; color:#dc2626; border-radius:5px; padding:.25rem .5rem; cursor:pointer; font-size:.78rem; transition:background .15s, color .15s; }
.mi-del-btn:hover { background:#dc2626; color:#fff; border-color:#dc2626; }
.mi-del-btn:disabled { opacity:.5; cursor:not-allowed; }

/* Flash notifications */
#izinFlashArea { position:sticky; top:10px; z-index:999; }
</style>

<div class="mi-page container-fluid py-3">

  <!-- Flash area -->
  <div id="izinFlashArea"></div>
  <?= $pesanAdmin ?>

  <!-- Page Header -->
  <div class="mi-page-header">
    <div>
      <div class="mi-page-title"><i class="fas fa-clipboard-list mr-2" style="color:#3b82f6;"></i>Monitoring Izin Siswa</div>
      <div class="mi-page-sub">Seluruh pengajuan izin &mdash; <?= date('d F Y') ?></div>
    </div>
  </div>

  <!-- Stats -->
  <div class="mi-stats">
    <div class="mi-stat accent-blue">
      <div class="mi-stat-label">Total Pengajuan</div>
      <div class="mi-stat-num"><?= $statTotal ?></div>
    </div>
    <div class="mi-stat accent-yellow">
      <div class="mi-stat-label">Menunggu</div>
      <div class="mi-stat-num"><?= $statMenunggu ?></div>
    </div>
    <div class="mi-stat accent-green">
      <div class="mi-stat-label">Disetujui</div>
      <div class="mi-stat-num"><?= $statDisetujui ?></div>
    </div>
    <div class="mi-stat accent-red">
      <div class="mi-stat-label">Ditolak</div>
      <div class="mi-stat-num"><?= $statDitolak ?></div>
    </div>
  </div>

  <!-- Filter -->
  <div class="mi-filter">
    <form method="GET" action="home.php">
      <input type="hidden" name="page" value="monitoring-izin">
      <label for="f_kelas">Kelas</label>
      <select name="filter_kelas" id="f_kelas">
        <option value="">Semua</option>
        <?php foreach ($kelasOptions as $kl): ?>
        <option value="<?= htmlspecialchars($kl) ?>" <?= ($filterKelas === $kl ? 'selected' : '') ?>><?= htmlspecialchars($kl) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="f_status">Status</label>
      <select name="filter_status" id="f_status">
        <option value="">Semua</option>
        <?php
        $statusOpts = array('Menunggu Wali Kelas','Menunggu Guru BK','Disetujui Sebagian','Disetujui Penuh','Ditolak','Menunggu Validasi');
        foreach ($statusOpts as $st): ?>
        <option value="<?= $st ?>" <?= ($filterStatus === $st ? 'selected' : '') ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>

      <label for="f_tgl">Tanggal</label>
      <input type="date" name="filter_tgl" id="f_tgl" value="<?= htmlspecialchars($filterTgl) ?>">

      <button type="submit" class="mi-btn mi-btn-primary"><i class="fas fa-search" style="font-size:.7rem;"></i> Filter</button>
      <a href="home.php?page=monitoring-izin" class="mi-btn mi-btn-secondary" style="text-decoration:none;display:inline-block;">Reset</a>
    </form>
  </div>

  <!-- Table -->
  <div class="mi-card">
    <div class="table-responsive">
      <table class="mi-table" id="tblMonitoringIzin">
        <thead>
          <tr>
            <th>#</th>
            <th>Siswa</th>
            <th>Kelas</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <?php if ($hasFotoSelfieCol): ?><th>Foto</th><?php endif; ?>
            <th style="text-align:center;">Wali Kelas</th>
            <th style="text-align:center;">Guru BK</th>
            <th>Status</th>
            <th>Catatan</th>
            <th>Diajukan</th>
            <th style="text-align:center;">Hapus</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($izinList)): ?>
          <tr>
            <td colspan="<?= $hasFotoSelfieCol ? 12 : 11 ?>" style="text-align:center;padding:2rem;color:#94a3b8;">
              <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
              Tidak ada data izin
            </td>
          </tr>
          <?php else: ?>
          <?php $no = 1; foreach ($izinList as $izin): ?>
          <tr>
            <td style="color:#94a3b8;font-size:.75rem;"><?= $no++ ?></td>
            <td>
              <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($izin['nama_siswa']) ?></div>
              <div style="font-size:.72rem;color:#94a3b8;"><?= htmlspecialchars($izin['no_induk_siswa']) ?></div>
            </td>
            <td style="white-space:nowrap;"><?= htmlspecialchars($izin['kelas_siswa']) ?></td>
            <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($izin['tanggal_izin'])) ?></td>
            <td>
              <?php
                $jIcon = array('Sakit'=>'fa-thermometer-half','Izin'=>'fa-door-open','Dispensasi'=>'fa-file-alt');
                $jColor = array('Sakit'=>'#ef4444','Izin'=>'#3b82f6','Dispensasi'=>'#8b5cf6');
                $jIs = isset($jIcon[$izin['jenis_izin']]) ? $jIcon[$izin['jenis_izin']] : 'fa-tag';
                $jCo = isset($jColor[$izin['jenis_izin']]) ? $jColor[$izin['jenis_izin']] : '#64748b';
              ?>
              <span style="font-size:.75rem;font-weight:600;color:<?= $jCo ?>;white-space:nowrap;">
                <i class="fas <?= $jIs ?>" style="font-size:.65rem;margin-right:.25rem;"></i><?= htmlspecialchars($izin['jenis_izin']) ?>
              </span>
            </td>
            <?php if ($hasFotoSelfieCol): ?>
            <td style="text-align:center;">
              <?php if (!empty($izin['foto_selfie'])): ?>
              <a href="../../uploads/izin/<?= htmlspecialchars($izin['foto_selfie']) ?>" target="_blank" title="Lihat foto">
                <img src="../../uploads/izin/<?= htmlspecialchars($izin['foto_selfie']) ?>" alt="selfie" class="mi-thumb">
              </a>
              <?php else: ?>
              <span style="color:#cbd5e1;">—</span>
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <td style="text-align:center;">
              <?php if ($izin['validasi_wali_kelas'] === 'Disetujui'): ?>
                <i class="fas fa-check vi-ok" title="Disetujui oleh <?= htmlspecialchars($izin['validator_wali_kelas'] ?? '') ?>"></i>
              <?php elseif ($izin['validasi_wali_kelas'] === 'Ditolak'): ?>
                <i class="fas fa-times vi-no" title="Ditolak"></i>
              <?php else: ?>
                <i class="fas fa-clock vi-wait" title="Menunggu"></i>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($izin['validasi_guru_bk'] === 'Disetujui'): ?>
                <i class="fas fa-check vi-ok" title="Disetujui oleh <?= htmlspecialchars($izin['validator_guru_bk'] ?? '') ?>"></i>
              <?php elseif ($izin['validasi_guru_bk'] === 'Ditolak'): ?>
                <i class="fas fa-times vi-no" title="Ditolak"></i>
              <?php else: ?>
                <i class="fas fa-clock vi-wait" title="Menunggu"></i>
              <?php endif; ?>
            </td>
            <td>
              <span class="mi-badge" style="<?= izinBadgeStyle($izin['status_izin']) ?>"><?= htmlspecialchars($izin['status_izin']) ?></span>
            </td>
            <td>
              <?php if (!empty($izin['catatan_penolakan'])): ?>
              <span style="font-size:.75rem;color:#dc2626;"><?= htmlspecialchars($izin['catatan_penolakan']) ?></span>
              <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
            </td>
            <td style="white-space:nowrap;font-size:.75rem;color:#64748b;">
              <?= !empty($izin['waktu_pengajuan']) ? date('d/m/Y H:i', strtotime($izin['waktu_pengajuan'])) : '—' ?>
            </td>
            <td style="text-align:center;">
              <button type="button" class="mi-del-btn"
                      data-hapus-izin="<?= (int)$izin['id_izin'] ?>"
                      data-nama="<?= htmlspecialchars($izin['nama_siswa'], ENT_QUOTES) ?>"
                      title="Hapus pengajuan izin">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="mi-card-footer">
      <?= $statTotal ?> pengajuan ditemukan
      <?php if ($filterKelas || $filterStatus || $filterTgl): ?>
      &mdash; filter aktif &mdash; <a href="home.php?page=monitoring-izin" style="color:#3b82f6;font-size:.75rem;">Hapus filter</a>
      <?php endif; ?>
    </div>
  </div>

</div><!-- .mi-page -->

<script>
(function () {
    function hapusIzin(btn) {
        var id   = btn.getAttribute('data-hapus-izin');
        var nama = btn.getAttribute('data-nama') || 'siswa ini';
        var row  = btn.closest('tr');

        if (!window.confirm('Hapus izin dari:\n' + nama + '?\n\nTindakan ini tidak dapat dibatalkan.')) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        var fd = new FormData();
        fd.append('id_izin', id);

        fetch('api/hapus_izin.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (resp) {
            var flash = document.getElementById('izinFlashArea');
            if (resp.success) {
                if (row && row.parentNode) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity = '0';
                    setTimeout(function () { if (row.parentNode) row.parentNode.removeChild(row); }, 320);
                }
                flash.innerHTML = '<div class="alert alert-success alert-dismissible small" style="display:block;">'
                    + '<button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>'
                    + '<i class="fas fa-check-circle mr-1"></i>' + resp.msg + '</div>';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i>';
                flash.innerHTML = '<div class="alert alert-danger alert-dismissible small" style="display:block;">'
                    + '<button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>'
                    + '<i class="fas fa-exclamation-circle mr-1"></i>' + resp.msg + '</div>';
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i>';
            document.getElementById('izinFlashArea').innerHTML =
                '<div class="alert alert-danger alert-dismissible small" style="display:block;">'
                + '<button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>'
                + 'Koneksi gagal, coba lagi.</div>';
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-hapus-izin]');
        if (btn) hapusIzin(btn);
    });
})();
</script>
