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

  <!-- Modern Page Header -->
  <div class="mb-4">
    <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; overflow: auto;">
      <div class="card-body p-4 text-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div>
            <h1 class="h4 mb-2 font-weight-bold">
              <i class="fas fa-chalkboard-teacher me-3"></i>
              Data Guru
            </h1>
            <p class="mb-0 opacity-75">Kelola informasi guru di <?= $lembaga['nmsekolah']; ?></p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="?page=data-guru&toggle_edit=1" class="btn <?= $isIzinEdit ? 'btn-warning' : 'btn-info' ?> btn-sm px-4 shadow-sm" style="border-radius: 25px; font-weight: 600;">
              <i class="fas <?= $isIzinEdit ? 'fa-lock-open' : 'fa-lock' ?> me-2"></i><?= $isIzinEdit ? 'Tutup Akses Edit' : 'Buka Akses Edit' ?>
            </a>
            <a href="?page=tambah-guru" class="btn btn-light btn-sm px-4 shadow-sm" style="border-radius: 25px; font-weight: 600;">
              <i class="fas fa-plus me-2"></i>Tambah Guru
            </a>
            <button class="btn btn-outline-light btn-sm px-4" style="border-radius: 25px; font-weight: 600;" onclick="exportData()">
              <i class="fas fa-download me-2"></i>Export
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 15px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <div class="card-body text-white text-center p-4">
          <i class="fas fa-users fa-2x mb-3 opacity-75"></i>
          <h3 class="font-weight-bold mb-1"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE {$tenantGuru}")); ?></h3>
          <p class="mb-0 small opacity-75">Total Guru</p>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 15px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
        <div class="card-body text-white text-center p-4">
          <i class="fas fa-user-check fa-2x mb-3 opacity-75"></i>
          <h3 class="font-weight-bold mb-1"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE {$tenantGuru} AND status='Aktif'")); ?></h3>
          <p class="mb-0 small opacity-75">Guru Aktif</p>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 15px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
        <div class="card-body text-white text-center p-4">
          <i class="fas fa-user-times fa-2x mb-3 opacity-75"></i>
          <h3 class="font-weight-bold mb-1"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE {$tenantGuru} AND status!='Aktif'")); ?></h3>
          <p class="mb-0 small opacity-75">Tidak Aktif</p>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 15px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
        <div class="card-body text-dark text-center p-4">
          <i class="fas fa-chalkboard-teacher fa-2x mb-3 opacity-75"></i>
          <h3 class="font-weight-bold mb-1">
            <?php
            $qMengajar = mysqli_query($conn, "SELECT COUNT(DISTINCT no_induk) AS jumlah FROM tbl_mapel_ampu WHERE {$tenantMapelAmpu}");
            $rowMengajar = $qMengajar ? mysqli_fetch_assoc($qMengajar) : null;
            echo $rowMengajar ? (int)$rowMengajar['jumlah'] : 0;
            ?>
          </h3>
          <p class="mb-0 small opacity-75">Guru Mengajar</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Search and Filter -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div class="text-muted small">
          <i class="fas fa-sync-alt me-2"></i>
          Sinkron status terakhir: <span id="userStatusSyncInfo">Memuat...</span>
        </div>
      </div>
      <div class="row align-items-center">
        <div class="col-md-3 mb-3 mb-md-0">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text bg-transparent border-right-0" style="border-radius: 25px 0 0 25px;">
                <i class="fas fa-search text-muted"></i>
              </span>
            </div>
            <input type="text" class="form-control border-left-0" placeholder="Cari nama guru atau NIP..." style="border-radius: 0 25px 25px 0;" id="searchGuru">
          </div>
        </div>
        <div class="col-md-2 mb-3 mb-md-0">
          <select class="form-control" style="border-radius: 25px;" id="filterStatus">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Non-Aktif">Non-Aktif</option>
          </select>
        </div>
        <div class="col-md-2 mb-3 mb-md-0">
          <select class="form-control" style="border-radius: 25px;" id="filterUserStatus">
            <option value="">Semua User</option>
            <option value="Online">Online</option>
            <option value="Offline">Offline</option>
          </select>
        </div>
        <div class="col-md-2 mb-3 mb-md-0">
          <select class="form-control" style="border-radius: 25px;" id="filterKepegawaian">
            <option value="">Semua Kepegawaian</option>
            <option value="ASN">ASN</option>
            <option value="NON_ASN">Non-ASN</option>
            <option value="PNS">PNS</option>
            <option value="CPNS">CPNS</option>
            <option value="GTT/PTT">GTT/PTT</option>
            <option value="Honorer">Honorer</option>
          </select>
        </div>
        <div class="col-md-3 mt-3 mt-md-0">
          <select class="form-control" style="border-radius: 25px;" id="filterJabatan">
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
    </div>
  </div>

  <!-- Main Data Card -->
  <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: auto;">
    <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
      <div class="d-flex justify-content-between align-items-center text-white">
        <div>
          <h5 class="m-0 font-weight-bold">
            <i class="fas fa-table me-3"></i>Daftar Guru
          </h5>
          <p class="mb-0 mt-1 opacity-75 small">Kelola data guru sekolah</p>
        </div>
        <span class="badge badge-light px-3 py-2" style="border-radius: 20px; font-size: 0.85rem;">
          <i class="fas fa-users me-2"></i>
          <span id="totalCount"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE {$tenantGuru}")); ?></span> guru
        </span>
        <span class="badge px-3 py-2 ml-2" style="border-radius: 20px; font-size: 0.85rem; background: rgba(16, 185, 129, 0.18); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);">
          <i class="fas fa-circle mr-1" style="font-size: 8px;"></i>
          <span id="onlineCount">0</span> online
        </span>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="dataTable" width="100%" cellspacing="0" style="border: none;">
          <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <tr>
              <th class="border-0 py-3 px-4" style="border-radius: 15px 0 0 0; font-weight: 600;">NO.</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;">NIP/NUPTK</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;">NAMA GURU</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;">STATUS KEPEGAWAIAN</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;">JABATAN WKS</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;">STATUS KEAKTIFAN</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;"><i class="fab fa-whatsapp text-success mr-1"></i>NO. WA</th>
              <th class="border-0 py-3 px-4" style="font-weight: 600;">STATUS USER</th>
              <th class="border-0 py-3 px-4 text-center" style="border-radius: 0 15px 0 0; font-weight: 600;">AKSI</th>
            </tr>
          </thead>

          <tbody>
            <?php
            // ini isi dari tabel
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
              <tr class="guru-row" data-name="<?= strtolower($data['nama_guru']); ?>" data-nip="<?= $data['no_induk']; ?>" data-status="<?= $sttaktif; ?>" data-kepegawaian="<?= $data['status_kepegawaian']; ?>" data-jabatan="<?= htmlspecialchars($jabatanGuru, ENT_QUOTES); ?>" data-userstatus="<?= $userStatus; ?>" data-userstate="<?= $userState; ?>" style="transition: all 0.3s ease;">
                <td class="py-3 px-4 align-middle">
                  <span class="badge badge-light" style="border-radius: 20px; font-weight: 600; font-size: 0.75rem; padding: 8px 12px;">
                    <?= $no++; ?>
                  </span>
                </td>
                <td class="py-3 px-4 align-middle">
                  <span class="text-muted small"><?= $data['no_induk'] ?: '-'; ?></span>
                </td>
                <td class="py-3 px-4 align-middle">
                  <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.1rem;">
                      <?= strtoupper(substr($data['nama_guru'], 0, 1)); ?>
                    </div>
                    <div>
                      <div class="font-weight-semibold text-dark"><?= $data['nama_guru']; ?></div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 align-middle">
                  <span class="badge" style="
                                    border-radius: 20px; 
                                    padding: 8px 16px; 
                                    font-weight: 600; 
                                    font-size: 0.75rem;
                                    <?php
                                    if ($data['status_kepegawaian'] == 'PNS') echo 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;';
                                    else if ($data['status_kepegawaian'] == 'CPNS') echo 'background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;';
                                    else if ($data['status_kepegawaian'] == 'GTT/PTT') echo 'background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;';
                                    else echo 'background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;';
                                    ?>
                                  ">
                    <?= $data['status_kepegawaian'] ?: 'Honorer'; ?>
                  </span>
                </td>
                <td class="py-3 px-4 align-middle">
                  <?php if (!empty($jabatanGuru)) : ?>
                    <span class="badge" style="border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                      <?= htmlspecialchars($jabatanGuru); ?>
                    </span>
                  <?php else : ?>
                    <span class="text-muted small">Guru Biasa</span>
                  <?php endif; ?>
                </td>
                <?php if ($sttaktif === "Aktif") { ?>
                  <td class="py-3 px-4 align-middle">
                    <span class="badge badge-success" style="border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important; border: none;">
                      <i class="fas fa-check-circle me-2"></i><?= $sttaktif; ?>
                    </span>
                  </td>
                <?php
                } else { ?>
                  <td class="py-3 px-4 align-middle">
                    <span class="badge badge-danger" style="border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #fd746c 0%, #ff9068 100%) !important; border: none;">
                      <i class="fas fa-times-circle me-2"></i><?= $sttaktif; ?>
                    </span>
                  </td>
                <?php } ?>
                <td class="py-3 px-4 align-middle">
                  <?php if (!empty($data['no_wa'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $data['no_wa'])) ?>" target="_blank" rel="noopener"
                      class="d-inline-flex align-items-center text-decoration-none"
                      style="background:#25d366;color:#fff;border-radius:20px;padding:5px 12px;font-size:.75rem;font-weight:600;">
                      <i class="fab fa-whatsapp mr-1"></i><?= htmlspecialchars($data['no_wa']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 align-middle user-status-cell">
                  <?php if ($userState === 'fresh'): ?>
                    <span class="badge badge-primary user-status-badge" style="border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%) !important; border: none;">
                      <i class="fas fa-bolt mr-1"></i>Baru aktif
                    </span>
                    <div class="small mt-1 user-status-text text-primary">baru aktif kurang dari 1 menit</div>
                  <?php elseif ($isOnline): ?>
                    <span class="badge badge-success user-status-badge" style="border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #10b981 0%, #34d399 100%) !important; border: none;">
                      <i class="fas fa-circle mr-1" style="font-size: 8px;"></i>Online
                    </span>
                    <div class="small mt-1 user-status-text text-success">aktif sekarang</div>
                  <?php else: ?>
                    <span class="badge badge-secondary user-status-badge" style="border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: #6c757d !important; border: none;">
                      Offline
                    </span>
                    <div class="small mt-1 user-status-text text-muted"><?= htmlspecialchars($lastActiveLabel); ?></div>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 align-middle text-center">
                  <div class="btn-group" role="group">
                    <a class="btn btn-sm shadow-sm me-1" href="?page=detail-guru&id=<?= $data['id_guru']; ?>&no_induk=<?= $data['no_induk']; ?>"
                      style="border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 8px 12px;"
                      data-toggle="tooltip" title="Lihat Detail">
                      <i class="fas fa-eye"></i>
                    </a>
                    <?php if ($sttaktif === "Aktif") { ?>
                      <a class="btn btn-sm shadow-sm me-1" href="?page=tambah-mapel-guru&id=<?= $data['id_guru']; ?>&no_induk=<?= $data['no_induk']; ?>"
                        style="border-radius: 10px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border: none; padding: 8px 12px;"
                        data-toggle="tooltip" title="Tambah Jadwal Mengajar">
                        <i class="fas fa-plus"></i>
                      </a>
                    <?php } else { ?>
                      <button class="btn btn-sm me-1" disabled
                        style="border-radius: 10px; background: #e9ecef; color: #6c757d; border: none; padding: 8px 12px;"
                        data-toggle="tooltip" title="Guru tidak aktif">
                        <i class="fas fa-plus"></i>
                      </button>
                    <?php } ?>
                    <a class="btn btn-sm shadow-sm" href="?page=edit-guru&id_guru=<?= $data['id_guru']; ?>"
                      style="border-radius: 10px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; padding: 8px 12px;"
                      data-toggle="tooltip" title="Edit Data">
                      <i class="fas fa-edit"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php
              // ini penutup while 
            }
            ?>
          </tbody>

        </table>
      </div>
    </div>

  </div>

</div>

<!-- Custom Styles -->
<style>
  .guru-row:hover {
    background-color: #f8f9fa !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .avatar-circle {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
  }

  .card {
    transition: all 0.3s ease;
  }

  .input-group-text {
    background: transparent !important;
    border: 1px solid #ced4da;
  }

  .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  }
</style>

<!-- JavaScript for Search and Filter -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

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
      let badgeStyle = 'border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: #6c757d !important; border: none;';
      let textClass = 'small mt-1 user-status-text text-muted';
      let badgeContent = 'Offline';

      if (state === 'fresh') {
        badgeClass = 'badge badge-primary user-status-badge';
        badgeStyle = 'border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%) !important; border: none;';
        textClass = 'small mt-1 user-status-text text-primary';
        badgeContent = '<i class="fas fa-bolt mr-1"></i>Baru aktif';
      } else if (state === 'online') {
        badgeClass = 'badge badge-success user-status-badge';
        badgeStyle = 'border-radius: 20px; padding: 8px 16px; font-weight: 600; font-size: 0.75rem; background: linear-gradient(135deg, #10b981 0%, #34d399 100%) !important; border: none;';
        textClass = 'small mt-1 user-status-text text-success';
        badgeContent = '<i class="fas fa-circle mr-1" style="font-size: 8px;"></i>Online';
      }

      cell.innerHTML =
        '<span class="' + badgeClass + '" style="' + badgeStyle + '">' + badgeContent + '</span>' +
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
        .catch(() => {
          // Silent fail: keep current UI when polling request fails.
        });
    }

    // Add event listeners
    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    userStatusFilter.addEventListener('change', filterTable);
    kepegawaianFilter.addEventListener('change', filterTable);
    jabatanFilter.addEventListener('change', filterTable);

    // Realtime user status refresh every 30 seconds without full page reload.
    setInterval(refreshUserStatusRealtime, 30000);

    // Export function
    window.exportData = function() {
      // Simple export to CSV functionality
      let csvContent = "data:text/csv;charset=utf-8,";
      csvContent += "No,NIP/NUPTK,Nama Guru,Status Kepegawaian,Jabatan WKS,Status Keaktifan,Status User\n";

      const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
      visibleRows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        const rowData = [
          index + 1,
          cells[1].textContent.trim(),
          cells[2].querySelector('div div').textContent.trim(),
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
