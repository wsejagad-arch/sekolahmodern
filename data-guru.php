<?php
if (!isset($_SESSION["username"])) {
  header("location: index.php?haruslogin");
  exit;
} else if ($hakakses != 1) { ?>
  <script>
    window.location = '404.html';
  </script>
<?php 
  exit; 
}

include "koneksi.php";
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantGuru = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantGuruAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? "g.id_sekolah={$tenantId}" : "1=1";
$tenantMapelAmpu = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$_chkJabatan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'jabatan'");
if ($_chkJabatan && mysqli_num_rows($_chkJabatan) === 0) {
  @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian");
}
if (function_exists('online_status_ensure_table')) {
  online_status_ensure_table($conn);
}

// Pastikan tabel pengaturan ada
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
    kunci VARCHAR(60) PRIMARY KEY,
    nilai VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai) VALUES ('izin_edit_profil_guru','0')");

// Toggle izin edit profil
if (isset($_GET['toggle_edit'])) {
    $currentVal = '0';
    $qCheck = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil_guru'");
    if ($qCheck && $rowCheck = mysqli_fetch_assoc($qCheck)) {
        $currentVal = $rowCheck['nilai'];
    }
    $newVal = ($currentVal === '1') ? '0' : '1';
    @mysqli_query($conn, "UPDATE tbl_pengaturan SET nilai='$newVal' WHERE kunci='izin_edit_profil_guru'");
    echo "<script>window.location.href = '?page=data-guru';</script>";
    exit;
}

$qIzin = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil_guru'");
$isIzinEdit = ($qIzin && ($rIzin = mysqli_fetch_assoc($qIzin))) ? ($rIzin['nilai'] === '1') : false;

if (!function_exists('format_last_active_label')) {
  function format_last_active_label($datetime)
  {
    if (empty($datetime)) {
      return 'Belum terdeteksi aktivitas';
    }

    $lastTs = strtotime($datetime);
    if (!$lastTs) {
      return 'Waktu aktivitas tidak valid';
    }

    $diff = time() - $lastTs;
    if ($diff < 60) {
      return 'aktif beberapa detik lalu';
    }
    if ($diff < 3600) {
      return floor($diff / 60) . ' menit lalu';
    }
    if ($diff < 86400) {
      return floor($diff / 3600) . ' jam lalu';
    }
    return floor($diff / 86400) . ' hari lalu';
  }
}

if (!function_exists('get_user_online_visual_state')) {
  function get_user_online_visual_state($lastActivity)
  {
    if (empty($lastActivity)) {
      return ['state' => 'offline', 'label' => 'Belum terdeteksi aktivitas'];
    }

    $lastTs = strtotime($lastActivity);
    if (!$lastTs) {
      return ['state' => 'offline', 'label' => 'Waktu aktivitas tidak valid'];
    }

    $diff = time() - $lastTs;
    if ($diff < 60) {
      return ['state' => 'fresh', 'label' => 'Baru aktif'];
    }
    if ($diff < 300) {
      return ['state' => 'online', 'label' => 'aktif sekarang'];
    }

    return ['state' => 'offline', 'label' => format_last_active_label($lastActivity)];
  }
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Data Guru</h1>
    <div>
      <a href="?page=data-guru&toggle_edit=1" class="d-none d-sm-inline-block btn btn-sm <?= $isIzinEdit ? 'btn-warning' : 'btn-info' ?> shadow-sm">
        <i class="fas <?= $isIzinEdit ? 'fa-lock-open' : 'fa-lock' ?> fa-sm text-white-50"></i> <?= $isIzinEdit ? 'Tutup Akses Edit' : 'Buka Akses Edit' ?>
      </a>
      <a href="?page=tambah-guru" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Data
      </a>
      <button class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm" onclick="exportData()">
        <i class="fas fa-download fa-sm text-white-50"></i> Export Data Guru
      </button>
    </div>
  </div>

  <!-- Search and Filter -->
  <div class="card shadow mb-4">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-md-3 mb-3 mb-md-0">
          <input type="text" class="form-control" placeholder="Cari nama guru atau NIP..." id="searchGuru">
        </div>
        <div class="col-md-2 mb-3 mb-md-0">
          <select class="form-control" id="filterStatus">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Non-Aktif">Non-Aktif</option>
          </select>
        </div>
        <div class="col-md-2 mb-3 mb-md-0">
          <select class="form-control" id="filterUserStatus">
            <option value="">Semua User</option>
            <option value="Online">Online</option>
            <option value="Offline">Offline</option>
          </select>
        </div>
        <div class="col-md-2 mb-3 mb-md-0">
          <select class="form-control" id="filterKepegawaian">
            <option value="">Semua Kepegawaian</option>
            <option value="ASN">ASN</option>
            <option value="NON_ASN">Non-ASN</option>
            <option value="PNS">PNS</option>
            <option value="CPNS">CPNS</option>
            <option value="GTT/PTT">GTT/PTT</option>
            <option value="Honorer">Honorer</option>
          </select>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
          <select class="form-control" id="filterJabatan">
            <option value="">Semua Jabatan</option>
            <option value="WKS Kurikulum">WKS Kurikulum</option>
            <option value="Tim WKS Kurikulum">Tim WKS Kurikulum</option>
            <option value="WKS Kesiswaan">WKS Kesiswaan</option>
            <option value="WKS Humas">WKS Humas</option>
            <option value="WKS Sarpras">WKS Sarpras</option>
            <option value="STPKS">STPKS</option>
            <option value="Kepala Sekolah">Kepala Sekolah</option>
            <option value="__GURU_BIASA__">Guru Biasa</option>
          </select>
        </div>
      </div>
      <div class="text-muted small mt-2">
        <i class="fas fa-sync-alt me-1"></i> Sinkron status terakhir: <span id="userStatusSyncInfo">Memuat...</span>
      </div>
    </div>
  </div>

  <!-- Main Data Card -->
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Data Guru <?= $lembaga['nmsekolah']; ?></h6>
        <div>
          <span class="badge badge-primary px-2 py-1"><span id="totalCount"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE {$tenantGuru}")); ?></span> Total</span>
          <span class="badge badge-success px-2 py-1 ml-1"><span id="onlineCount">0</span> Online</span>
        </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">NO.</th>
              <th>NIP/NUPTK</th>
              <th>NAMA GURU</th>
              <th>STATUS KEPEGAWAIAN</th>
              <th>JABATAN</th>
              <th>STATUS KEAKTIFAN</th>
              <th>NO. WA</th>
              <th>STATUS USER</th>
              <th class="text-center" style="width: 150px;">AKSI</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            $sql = mysqli_query($conn, "SELECT g.*, u.last_activity, u.is_online AS is_online_session
                                        FROM tbl_guru g
                                        LEFT JOIN tbl_user_online u ON u.user_key = CONCAT('school:', {$tenantId}, ':guru:', g.no_induk)
                                        WHERE {$tenantGuruAlias}
                                        ORDER BY g.nama_guru ASC");
            while ($data = mysqli_fetch_array($sql)) {
              $sttaktif = $data['status'];
              $jabatanGuru = trim($data['jabatan'] ?? '');
              $visualState = get_user_online_visual_state($data['last_activity'] ?? null);
              $isOnline = in_array($visualState['state'], ['fresh', 'online'], true);
              $userStatus = $isOnline ? 'Online' : 'Offline';
              $lastActiveLabel = $visualState['label'];
              $userState = $visualState['state'];
            ?>
              <tr class="guru-row" data-name="<?= strtolower(htmlspecialchars($data['nama_guru'], ENT_QUOTES)); ?>" data-nip="<?= htmlspecialchars($data['no_induk'], ENT_QUOTES); ?>" data-status="<?= htmlspecialchars($sttaktif, ENT_QUOTES); ?>" data-kepegawaian="<?= htmlspecialchars($data['status_kepegawaian'], ENT_QUOTES); ?>" data-jabatan="<?= htmlspecialchars($jabatanGuru, ENT_QUOTES); ?>" data-userstatus="<?= $userStatus; ?>" data-userstate="<?= $userState; ?>">
                <td class="text-center align-middle"><?= $no++; ?></td>
                <td class="align-middle"><?= $data['no_induk'] ?: '-'; ?></td>
                <td class="align-middle"><?= $data['nama_guru']; ?></td>
                <td class="align-middle"><?= $data['status_kepegawaian'] ?: 'Honorer'; ?></td>
                <td class="align-middle">
                  <?php if (!empty($jabatanGuru)) : ?>
                    <?= htmlspecialchars($jabatanGuru); ?>
                  <?php else : ?>
                    <span class="text-muted small">Guru Biasa</span>
                  <?php endif; ?>
                </td>
                <td class="align-middle text-center">
                  <?php if ($sttaktif === "Aktif") { ?>
                    <span class="badge badge-success"><?= $sttaktif; ?></span>
                  <?php } else { ?>
                    <span class="badge badge-danger"><?= $sttaktif; ?></span>
                  <?php } ?>
                </td>
                <td class="align-middle">
                  <?php if (!empty($data['no_wa'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $data['no_wa'])) ?>" target="_blank" class="text-success text-decoration-none">
                      <i class="fab fa-whatsapp mr-1"></i><?= htmlspecialchars($data['no_wa']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td class="align-middle user-status-cell">
                  <?php if ($userState === 'fresh'): ?>
                    <span class="badge badge-primary user-status-badge"><i class="fas fa-bolt mr-1"></i>Baru aktif</span>
                    <div class="small mt-1 user-status-text text-primary">baru aktif kurang dari 1 menit</div>
                  <?php elseif ($isOnline): ?>
                    <span class="badge badge-success user-status-badge"><i class="fas fa-circle mr-1" style="font-size: 8px;"></i>Online</span>
                    <div class=\"small mt-1 user-status-text text-success\">aktif sekarang</div>
                  <?php else: ?>
                    <span class="badge badge-secondary user-status-badge">Offline</span>
                    <div class="small mt-1 user-status-text text-muted"><?= htmlspecialchars($lastActiveLabel); ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-center align-middle">
                    <a class="btn btn-sm btn-circle btn-primary" href="?page=detail-guru&id=<?= $data['id_guru']; ?>&no_induk=<?= $data['no_induk']; ?>" title="Lihat Detail"><i class="fas fa-info"></i></a>
                    <?php if ($sttaktif === "Aktif") { ?>
                      <a class="btn btn-sm btn-circle btn-success" href="?page=tambah-mapel-guru&id=<?= $data['id_guru']; ?>&no_induk=<?= $data['no_induk']; ?>" title="Tambah Jadwal Mengajar"><i class="fas fa-plus"></i></a>
                    <?php } else { ?>
                      <button class="btn btn-sm btn-circle btn-secondary" disabled title="Guru tidak aktif"><i class="fas fa-plus"></i></button>
                    <?php } ?>
                    <a class="btn btn-sm btn-circle btn-info" href="?page=edit-guru&id_guru=<?= $data['id_guru']; ?>" title="Edit Data"><i class="fas fa-edit"></i></a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript for Search and Filter -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchGuru');
    const statusFilter = document.getElementById('filterStatus');
    const userStatusFilter = document.getElementById('filterUserStatus');
    const kepegawaianFilter = document.getElementById('filterKepegawaian');
    const jabatanFilter = document.getElementById('filterJabatan');
    const rows = document.querySelectorAll('.guru-row');
    const totalCount = document.getElementById('totalCount');
    const onlineCount = document.getElementById('onlineCount');

    function updateOnlineCount() {
      if (!onlineCount) return;
      const count = Array.from(rows).filter(row => (row.dataset.userstatus || '') === 'Online').length;
      onlineCount.textContent = count;
    }

    function filterTable() {
      const searchTerm = searchInput.value.toLowerCase();
      const selectedStatus = statusFilter.value;
      const selectedUserStatus = userStatusFilter.value;
      const selectedKepegawaian = kepegawaianFilter.value;
      const selectedJabatan = jabatanFilter.value;
      let visibleCount = 0;

      rows.forEach(row => {
        const name = row.dataset.name;
        const nip = row.dataset.nip;
        const status = row.dataset.status;
        const userStatus = row.dataset.userstatus;
        const kepegawaian = row.dataset.kepegawaian;
        const jabatan = row.dataset.jabatan || '';

        const matchSearch = name.includes(searchTerm) || nip.includes(searchTerm);
        const matchStatus = !selectedStatus || status === selectedStatus || (selectedStatus === 'Non-Aktif' && status === 'Tidak Aktif');
        const matchUserStatus = !selectedUserStatus || userStatus === selectedUserStatus;
        const matchKepegawaian = !selectedKepegawaian || kepegawaian === selectedKepegawaian;
        const matchJabatan = !selectedJabatan || (selectedJabatan === '__GURU_BIASA__' ? jabatan === '' : jabatan === selectedJabatan);

        if (matchSearch && matchStatus && matchUserStatus && matchKepegawaian && matchJabatan) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      totalCount.textContent = visibleCount;
      updateOnlineCount();
    }

    function renderUserStatusCell(row, status, label) {
      const cell = row.querySelector('.user-status-cell');
      if (!cell) return;

      const state = row.dataset.userstate || (status === 'Online' ? 'online' : 'offline');
      let badgeClass = 'badge badge-secondary user-status-badge';
      let textClass = 'small mt-1 user-status-text text-muted';
      let badgeContent = 'Offline';

      if (state === 'fresh') {
        badgeClass = 'badge badge-primary user-status-badge';
        textClass = 'small mt-1 user-status-text text-primary';
        badgeContent = '<i class="fas fa-bolt mr-1"></i>Baru aktif';
      } else if (state === 'online') {
        badgeClass = 'badge badge-success user-status-badge';
        textClass = 'small mt-1 user-status-text text-success';
        badgeContent = '<i class="fas fa-circle mr-1" style="font-size: 8px;"></i>Online';
      }

      cell.innerHTML =
        '<span class="' + badgeClass + '">' + badgeContent + '</span>' +
        '<div class="' + textClass + '">' + label + '</div>';
    }

    function refreshUserStatusRealtime() {
      fetch('api/user-online-status.php', {
          method: 'GET',
          cache: 'no-store'
        })
        .then(response => response.json())
        .then(payload => {
          if (!payload || !payload.success || !payload.data) return;

          rows.forEach(row => {
            const nip = row.dataset.nip;
            const info = payload.data[nip];
            if (!info) return;

            row.dataset.userstatus = info.status;
            row.dataset.userstate = info.state || (info.status === 'Online' ? 'online' : 'offline');
            renderUserStatusCell(row, info.status, info.label);
          });

          const syncInfo = document.getElementById('userStatusSyncInfo');
          if (syncInfo) {
            const now = new Date();
            syncInfo.textContent = now.toLocaleTimeString('id-ID', {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit'
            });
          }

          updateOnlineCount();
          filterTable();
        })
        .catch(() => {});
    }

    if(searchInput) searchInput.addEventListener('input', filterTable);
    if(statusFilter) statusFilter.addEventListener('change', filterTable);
    if(userStatusFilter) userStatusFilter.addEventListener('change', filterTable);
    if(kepegawaianFilter) kepegawaianFilter.addEventListener('change', filterTable);
    if(jabatanFilter) jabatanFilter.addEventListener('change', filterTable);

    setInterval(refreshUserStatusRealtime, 30000);

    window.exportData = function() {
      let csvContent = "data:text/csv;charset=utf-8,";
      csvContent += "No,NIP/NUPTK,Nama Guru,Status Kepegawaian,Jabatan WKS,Status Keaktifan,Status User\n";

      const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
      visibleRows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        const rowData = [
          index + 1,
          cells[1].textContent.trim(),
          cells[2].textContent.trim(),
          cells[3].textContent.trim(),
          cells[4].textContent.trim(),
          cells[5].textContent.trim(),
          cells[7].textContent.trim()
        ];
        csvContent += rowData.join(",") + "\n";
      });

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "data_guru.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    refreshUserStatusRealtime();
    updateOnlineCount();
  });
</script>
