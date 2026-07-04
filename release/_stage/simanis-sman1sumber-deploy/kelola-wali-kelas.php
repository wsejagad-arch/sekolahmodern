<?php
if (!isset($_SESSION["username"])) {
  header("location: index.php?haruslogin");
  exit;
} else if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) { ?>
  <script>
    window.location = '404.html';
  </script>
<?php }

include 'koneksi.php';
require_once __DIR__ . '/multi_tenant.php';

date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s');

// Get current school ID
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

$errors = [];
$messages = [];

// Ensure tbl_wali_kelas exists
function ensure_tbl_wali_kelas($conn)
{
  $exists = false;
  if ($res = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'")) {
    $exists = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);
  }
  if (!$exists) {
    $sql = "CREATE TABLE IF NOT EXISTS `tbl_wali_kelas` (
      `id_wali` INT(11) NOT NULL AUTO_INCREMENT,
      `id_kelas` INT(11) NOT NULL,
      `nip_wali` VARCHAR(64) NOT NULL,
      `nama_wali` VARCHAR(255) DEFAULT NULL,
      `id_sekolah` INT(11) NOT NULL DEFAULT 1,
      `created_at` DATETIME DEFAULT NULL,
      `updated_at` DATETIME DEFAULT NULL,
      PRIMARY KEY (`id_wali`),
      KEY `idx_kelas` (`id_kelas`),
      KEY `idx_nip` (`nip_wali`),
      KEY `idx_sekolah` (`id_sekolah`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql);
    // ignore errors here; will be surfaced later if needed
  }
}

ensure_tbl_wali_kelas($conn);

function get_guru_options($conn, $tenantId, $selected_nip = null)
{
  $opts = '';
  $tenantEsc = (int)$tenantId;
  $q = "SELECT g.no_induk AS nip, g.nama_guru
          FROM tbl_guru g
          WHERE g.status = 'Aktif' AND g.id_sekolah = $tenantEsc
          ORDER BY g.nama_guru";
  if ($res = mysqli_query($conn, $q)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $sel = ($selected_nip !== null && $selected_nip == $row['nip']) ? 'selected' : '';
      $opts .= '<option value="' . htmlspecialchars($row['nip']) . '" ' . $sel . '>' . htmlspecialchars($row['nama_guru']) . ' (' . htmlspecialchars($row['nip']) . ')</option>';
    }
  }
  return $opts;
}

function guru_nama_by_nip($conn, $tenantId, $nip)
{
  $nip = mysqli_real_escape_string($conn, $nip);
  $tenantEsc = (int)$tenantId;
  $res = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='$nip' AND id_sekolah=$tenantEsc LIMIT 1");
  if ($res && $r = mysqli_fetch_assoc($res)) return $r['nama_guru'];
  return null;
}

function kelas_list($conn, $tenantId)
{
  $tenantEsc = (int)$tenantId;
  $sql = "SELECT k.id_kelas, k.kelas, wk.id_wali, wk.nip_wali, wk.nama_wali
            FROM tbl_kelas k
            LEFT JOIN tbl_wali_kelas wk ON wk.id_kelas = k.id_kelas AND wk.id_sekolah = $tenantEsc
            WHERE k.id_sekolah = $tenantEsc
            ORDER BY k.kelas";
  $data = [];
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
  }
  return $data;
}

// Constraint: setiap guru hanya bisa jadi wali untuk satu kelas (per sekolah)
function guru_sudah_wali_di_kelas_lain($conn, $tenantId, $nip, $exclude_id_kelas = null)
{
  $nip = mysqli_real_escape_string($conn, $nip);
  $tenantEsc = (int)$tenantId;
  $cond = "nip_wali='$nip' AND id_sekolah=$tenantEsc";
  if ($exclude_id_kelas !== null) {
    $exclude = (int)$exclude_id_kelas;
    $cond .= " AND id_kelas<>$exclude";
  }
  $q = "SELECT COUNT(*) c FROM tbl_wali_kelas WHERE $cond";
  $res = mysqli_query($conn, $q);
  if ($res && $r = mysqli_fetch_assoc($res)) return (int)$r['c'] > 0;
  return false;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['act'] ?? '';

  if ($act === 'set') {
    $id_kelas = (int)($_POST['id_kelas'] ?? 0);
    $nip = trim($_POST['nip'] ?? '');

    // Validate that kelas belongs to current school
    $klasVerify = mysqli_query($conn, "SELECT id_kelas FROM tbl_kelas WHERE id_kelas=$id_kelas AND id_sekolah=$tenantId LIMIT 1");
    $kelasValid = $klasVerify && mysqli_num_rows($klasVerify) > 0;

    if (!$id_kelas || $nip === '') {
      $errors[] = 'Kelas dan guru wajib dipilih.';
    } else if (!$kelasValid) {
      $errors[] = 'Kelas tidak ditemukan atau bukan milik sekolah Anda.';
    } else if (guru_sudah_wali_di_kelas_lain($conn, $tenantId, $nip, $id_kelas)) {
      $errors[] = 'Guru ini sudah menjadi wali di kelas lain. Setiap guru hanya boleh menjadi wali untuk satu kelas.';
    } else {
      $nama = guru_nama_by_nip($conn, $tenantId, $nip);
      if (!$nama) {
        $errors[] = 'Guru tidak ditemukan di sekolah Anda.';
      } else {
        // upsert: jika sudah ada record untuk kelas ini, update; kalau belum insert
        $cek = mysqli_query($conn, "SELECT id_wali FROM tbl_wali_kelas WHERE id_kelas=$id_kelas AND id_sekolah=$tenantId LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
          $r = mysqli_fetch_assoc($cek);
          $id_wali = (int)$r['id_wali'];
          $sql = "UPDATE tbl_wali_kelas SET nip_wali='$nip', nama_wali='" . mysqli_real_escape_string($conn, $nama) . "', updated_at='$now' WHERE id_wali=$id_wali AND id_sekolah=$tenantId";
        } else {
          $sql = "INSERT INTO tbl_wali_kelas(id_kelas, nip_wali, nama_wali, id_sekolah, created_at, updated_at) VALUES($id_kelas, '$nip', '" . mysqli_real_escape_string($conn, $nama) . "', $tenantId, '$now', '$now')";
        }
        if (mysqli_query($conn, $sql)) {
          // Coba sinkronkan ke tbl_kelas jika kolom tersedia
          @mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas='" . mysqli_real_escape_string($conn, $nama) . "', nip_wali='$nip' WHERE id_kelas=$id_kelas AND id_sekolah=$tenantId");
          $messages[] = 'Berhasil menyimpan wali kelas.';
        } else {
          $errors[] = 'Gagal menyimpan wali kelas: ' . mysqli_error($conn);
        }
      }
    }
  }

  if ($act === 'hapus') {
    $id_kelas = (int)($_POST['id_kelas'] ?? 0);

    // Validate that kelas belongs to current school
    $klasVerify = mysqli_query($conn, "SELECT id_kelas FROM tbl_kelas WHERE id_kelas=$id_kelas AND id_sekolah=$tenantId LIMIT 1");
    $kelasValid = $klasVerify && mysqli_num_rows($klasVerify) > 0;

    if (!$id_kelas) {
      $errors[] = 'Kelas tidak valid.';
    } else if (!$kelasValid) {
      $errors[] = 'Kelas tidak ditemukan atau bukan milik sekolah Anda.';
    } else {
      if (mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas=$id_kelas AND id_sekolah=$tenantId")) {
        // Sinkronkan ke tbl_kelas (set null)
        @mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas=NULL, nip_wali=NULL WHERE id_kelas=$id_kelas AND id_sekolah=$tenantId");
        $messages[] = 'Wali kelas berhasil dihapus untuk kelas terpilih.';
      } else {
        $errors[] = 'Gagal menghapus: ' . mysqli_error($conn);
      }
    }
  }
}

// Optional: add unique indexes if table exists; guard to avoid fatal errors
if ($res = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'")) {
  if (mysqli_num_rows($res) > 0) {
    // Check existing indexes first
    $existing = [];
    if ($idxRes = mysqli_query($conn, "SHOW INDEX FROM tbl_wali_kelas")) {
      while ($idx = mysqli_fetch_assoc($idxRes)) {
        if (!empty($idx['Key_name'])) $existing[$idx['Key_name']] = true;
      }
      mysqli_free_result($idxRes);
    }
    if (empty($existing['uniq_kelas'])) {
      @mysqli_query($conn, "ALTER TABLE tbl_wali_kelas ADD UNIQUE KEY uniq_kelas(id_kelas, id_sekolah)");
    }
  }
  mysqli_free_result($res);
}

$dataKelas = kelas_list($conn, $tenantId);
?>

<div class="container-fluid">
  <div class="container">
    <div class="alert" style="background-color:#ffffff; outline:1px solid lightgrey">
      <h4>Kelola Wali Kelas</h4>
      <div class="small text-muted">Admin dapat menambah, mengubah, atau menghapus wali kelas. Setiap guru hanya boleh menjadi wali untuk satu kelas.</div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>
    <?php if ($messages): ?>
      <div class="alert alert-success"><?php echo implode('<br>', array_map('htmlspecialchars', $messages)); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm" style="border:0; border-radius:12px;">
      <div class="card-body table-responsive">
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th style="width:120px;">ID Kelas</th>
              <th>Kelas</th>
              <th>Wali Kelas (Saat Ini)</th>
              <th style="width:320px;">Ubah/Set Wali</th>
              <th style="width:100px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dataKelas as $row): ?>
              <tr>
                <td><?php echo (int)$row['id_kelas']; ?></td>
                <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                <td>
                  <?php
                  if (!empty($row['nama_wali'])) {
                    echo htmlspecialchars($row['nama_wali']) . ' (' . htmlspecialchars($row['nip_wali']) . ')';
                  } else {
                    echo '<em>Belum ditentukan</em>';
                  }
                  ?>
                </td>
                <td>
                  <form method="post" class="form-inline" onsubmit="return confirm('Simpan wali kelas untuk kelas ini?');">
                    <input type="hidden" name="act" value="set">
                    <input type="hidden" name="id_kelas" value="<?php echo (int)$row['id_kelas']; ?>">
                    <select name="nip" class="form-control form-control-sm" style="min-width:220px;">
                      <option value="">-- pilih guru aktif --</option>
                      <?php echo get_guru_options($conn, $row['nip_wali']); ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary ml-2">Simpan</button>
                  </form>
                </td>
                <td>
                  <?php if (!empty($row['id_wali'])): ?>
                    <form method="post" onsubmit="return confirm('Hapus wali kelas ini?');" style="display:inline-block;">
                      <input type="hidden" name="act" value="hapus">
                      <input type="hidden" name="id_kelas" value="<?php echo (int)$row['id_kelas']; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">-</span>
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