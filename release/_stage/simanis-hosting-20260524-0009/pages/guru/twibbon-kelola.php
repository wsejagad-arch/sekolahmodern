<?php
// Halaman ini di-include oleh home.php?page=kelola-twibbon
// Autentikasi & koneksi sudah ditangani oleh home.php / bootstrap.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../koneksi.php';
}
if (!function_exists('data_lembaga')) {
    require_once __DIR__ . '/../../functions.php';
}

$hakAkses = (int)($_SESSION['hak_akses'] ?? 0);
$noInduk  = $_SESSION['no_induk'] ?? $_SESSION['username'] ?? 'admin';
$namaUser = $_SESSION['nama'] ?? $noInduk;

$uploadDir = __DIR__ . '/../../uploads/twibbon/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

// Pastikan tabel ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_twibbon (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  filename VARCHAR(255) NOT NULL,
  created_by VARCHAR(50) NOT NULL,
  nama_pembuat VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  aktif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = ''; $err = '';

// â”€â”€ HAPUS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    $chk = mysqli_query($conn, "SELECT filename, created_by FROM tbl_twibbon WHERE id=$idHapus LIMIT 1");
    if ($chk && ($rHapus = mysqli_fetch_assoc($chk))) {
        // Admin bisa hapus semua; guru hanya miliknya
        if ($hakAkses === 1 || $rHapus['created_by'] === $noInduk) {
            @unlink($uploadDir . $rHapus['filename']);
            mysqli_query($conn, "DELETE FROM tbl_twibbon WHERE id=$idHapus");
            echo '<script>window.location.href="../../home.php?page=kelola-twibbon&ok=hapus";</script>';
            exit;
        } else {
            $err = 'Anda hanya bisa menghapus template yang Anda upload.';
        }
    }
}

// â”€â”€ TOGGLE AKTIF â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (isset($_GET['toggle'])) {
    $idTog = (int)$_GET['toggle'];
    mysqli_query($conn, "UPDATE tbl_twibbon SET aktif = IF(aktif=1,0,1) WHERE id=$idTog LIMIT 1");
    echo '<script>window.location.href="../../home.php?page=kelola-twibbon";</script>';
    exit;
}

// â”€â”€ UPLOAD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    if ($judul === '') { $err = 'Judul wajib diisi.'; }
    elseif (empty($_FILES['template_file']['name'])) { $err = 'File template wajib dipilih.'; }
    else {
        $fname   = $_FILES['template_file']['name'];
        $tmp     = $_FILES['template_file']['tmp_name'];
        $size    = $_FILES['template_file']['size'];
        $ext     = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($ext, $allowed)) {
            $err = 'Format file harus PNG, JPG, atau WEBP. Disarankan PNG untuk frame transparan.';
        } elseif ($size > 5 * 1024 * 1024) {
            $err = 'Ukuran file maksimal 5 MB.';
        } else {
            $newName = 'twibbon_' . date('Ymd_His') . '_' . substr(md5($fname . microtime()), 0, 6) . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                // Get guru name
                $namaGuru = $namaUser;
                if ($hakAkses === 2) {
                    $qG = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='".mysqli_real_escape_string($conn,$noInduk)."' LIMIT 1");
                    if ($qG && ($rG = mysqli_fetch_assoc($qG))) $namaGuru = $rG['nama_guru'];
                }
                $judulEsc = mysqli_real_escape_string($conn, $judul);
                $descEsc  = mysqli_real_escape_string($conn, $deskripsi);
                $nameEsc  = mysqli_real_escape_string($conn, $newName);
                $niEsc    = mysqli_real_escape_string($conn, $noInduk);
                $ngEsc    = mysqli_real_escape_string($conn, $namaGuru);
                mysqli_query($conn, "INSERT INTO tbl_twibbon(judul,deskripsi,filename,created_by,nama_pembuat)
                    VALUES('$judulEsc','$descEsc','$nameEsc','$niEsc','$ngEsc')");
                $msg = 'Template twibbon berhasil diupload!';
            } else {
                $err = 'Gagal menyimpan file. Periksa permission folder uploads/twibbon.';
            }
        }
    }
}

// Ambil daftar template
$res = mysqli_query($conn, "SELECT * FROM tbl_twibbon ORDER BY created_at DESC");
$templates = [];
if ($res) while ($r = mysqli_fetch_assoc($res)) $templates[] = $r;

$_cbg = "background-image:linear-gradient(45deg,#ccc 25%,transparent 25%),linear-gradient(-45deg,#ccc 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#ccc 75%),linear-gradient(-45deg,transparent 75%,#ccc 75%);background-size:12px 12px;background-position:0 0,0 6px,6px -6px,-6px 0px;background-color:#fff;";
?>

<div class="container-fluid">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-camera-retro mr-2" style="color:#db2777;"></i>Kelola Twibbon</h1>
    <a href=<?= siswa_page('twibbon') ?> target="_blank" class="btn btn-sm btn-outline-primary">
      <i class="fas fa-eye mr-1"></i> Preview Halaman Pengguna
    </a>
  </div>

  <!-- Alerts -->
  <?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle mr-2"></i>
    <?= $_GET['ok'] === 'hapus' ? 'Template berhasil dihapus.' : 'Berhasil.' ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php endif; ?>
  <?php if ($msg): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($msg) ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($err) ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php endif; ?>

  <div class="row">

    <!-- Upload Form -->
    <div class="col-lg-5 mb-4">
      <div class="card shadow h-100">
        <div class="card-header py-3" style="background:linear-gradient(135deg,#db2777,#a21caf);color:#fff;">
          <span class="font-weight-bold"><i class="fas fa-upload mr-2"></i>Upload Template Baru</span>
        </div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data" id="twUploadForm">
            <input type="hidden" name="upload_template" value="1">
            <div class="form-group">
              <label class="small font-weight-bold">Judul Template <span class="text-danger">*</span></label>
              <input type="text" name="judul" maxlength="150" required placeholder="cth: HUT Kemerdekaan 2026" class="form-control">
            </div>
            <div class="form-group">
              <label class="small font-weight-bold">Deskripsi</label>
              <input type="text" name="deskripsi" maxlength="255" placeholder="Opsional" class="form-control">
            </div>
            <div id="twDropZone" onclick="document.getElementById('twFileInput').click()"
                 style="border:2px dashed #d1d5db;border-radius:10px;padding:28px;text-align:center;cursor:pointer;transition:all .2s;">
              <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-2 d-block"></i>
              <p class="mb-1 font-weight-semibold text-muted">Klik atau drag &amp; drop file template</p>
              <small class="text-muted">PNG (transparan), JPG, WEBP &middot; Maks 5 MB &middot; Ideal 1080&times;1080px</small>
              <p id="twFileName" class="text-danger font-weight-bold mt-2 mb-0" style="display:none;"></p>
              <input type="file" id="twFileInput" name="template_file" accept=".png,.jpg,.jpeg,.webp" class="d-none" required>
            </div>
            <div id="twPreviewWrap" style="display:none;" class="mt-2">
              <small class="font-weight-bold text-muted">Preview:</small><br>
              <div style="width:120px;height:120px;<?= $_cbg ?>border-radius:8px;overflow:hidden;margin-top:4px;">
                <img id="twPreviewImg" src="" alt="preview" style="width:100%;height:100%;object-fit:contain;">
              </div>
            </div>
            <button type="submit" class="btn btn-danger btn-sm mt-3 px-4">
              <i class="fas fa-upload mr-1"></i> Upload Template
            </button>
          </form>
        </div>
        <div class="card-footer bg-light">
          <small class="font-weight-bold"><i class="fas fa-lightbulb text-warning mr-1"></i>Tips:</small>
          <ul class="small text-muted mb-0 mt-1 pl-3">
            <li>Gunakan <strong>PNG transparan</strong> untuk frame</li>
            <li>Ukuran ideal <strong>1080&times;1080 px</strong></li>
            <li>Buat area tengah transparan untuk foto siswa</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Template List -->
    <div class="col-lg-7 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,#7c3aed,#a21caf);color:#fff;">
          <span class="font-weight-bold"><i class="fas fa-images mr-2"></i>Daftar Template</span>
          <span class="badge badge-light text-muted"><?= count($templates) ?> template</span>
        </div>
        <div class="card-body">
          <?php if (empty($templates)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-images fa-4x mb-3 d-block"></i>
            <p>Belum ada template. Upload template pertama!</p>
          </div>
          <?php else: ?>
          <div class="row">
            <?php foreach ($templates as $t):
              $fileUrl  = 'uploads/twibbon/' . rawurlencode($t['filename']);
              $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                        . '://' . $_SERVER['HTTP_HOST'] . '/jurnal/pages/siswa/twibbon.php?id=' . $t['id'];
            ?>
            <div class="col-6 col-md-4 mb-3">
              <div class="card border">
                <div style="<?= $_cbg ?>aspect-ratio:1/1;overflow:hidden;position:relative;">
                  <img src="<?= htmlspecialchars($fileUrl) ?>" alt="<?= htmlspecialchars($t['judul']) ?>"
                       style="width:100%;height:100%;object-fit:contain;display:block;">
                  <?php if (!$t['aktif']): ?>
                  <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;">
                    <span class="badge badge-dark">NONAKTIF</span>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="card-body p-2">
                  <p class="font-weight-bold small mb-0 text-truncate" title="<?= htmlspecialchars($t['judul']) ?>">
                    <?= htmlspecialchars($t['judul']) ?>
                  </p>
                  <small class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?> &middot; <?= htmlspecialchars($t['nama_pembuat'] ?? '-') ?></small>
                  <div class="btn-group w-100 mt-2" role="group">
                    <a href="pages/siswa/twibbon.php?id=<?= $t['id'] ?>" target="_blank"
                       class="btn btn-outline-secondary btn-xs" title="Preview"><i class="fas fa-eye"></i></a>
                    <button onclick="twCopyLink('<?= htmlspecialchars(addslashes($shareUrl)) ?>')"
                            class="btn btn-outline-primary btn-xs" title="Copy link"><i class="fas fa-link"></i></button>
                    <a href="home.php?page=kelola-twibbon&toggle=<?= $t['id'] ?>"
                       class="btn <?= $t['aktif'] ? 'btn-outline-success' : 'btn-outline-secondary' ?> btn-xs"
                       title="<?= $t['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fas fa-<?= $t['aktif'] ? 'toggle-on' : 'toggle-off' ?>"></i></a>
                    <a href="home.php?page=kelola-twibbon&hapus=<?= $t['id'] ?>"
                       onclick="return confirm('Hapus template ini?')"
                       class="btn btn-outline-danger btn-xs" title="Hapus"><i class="fas fa-trash"></i></a>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Toast -->
<div id="twToast" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1f2937;color:#fff;padding:10px 24px;border-radius:999px;font-size:.85rem;box-shadow:0 4px 16px rgba(0,0,0,.2);opacity:0;transition:opacity .3s;pointer-events:none;z-index:9999;">
  Link disalin! &#10003;
</div>

<style>.btn-xs{padding:.15rem .4rem;font-size:.75rem;}</style>

<script>
(function(){
  var dz  = document.getElementById('twDropZone');
  var fi  = document.getElementById('twFileInput');
  var fn  = document.getElementById('twFileName');
  var pw  = document.getElementById('twPreviewWrap');
  var pi  = document.getElementById('twPreviewImg');

  fi.addEventListener('change', twHandleFile);
  ['dragenter','dragover'].forEach(function(ev){ dz.addEventListener(ev, function(e){ e.preventDefault(); dz.style.borderColor='#6366f1'; dz.style.background='#eef2ff'; }); });
  ['dragleave','drop'].forEach(function(ev){ dz.addEventListener(ev, function(e){ e.preventDefault(); dz.style.borderColor='#d1d5db'; dz.style.background=''; }); });
  dz.addEventListener('drop', function(e){
    if (e.dataTransfer.files.length) {
      var dt = new DataTransfer(); dt.items.add(e.dataTransfer.files[0]);
      fi.files = dt.files; twHandleFile();
    }
  });

  function twHandleFile() {
    var f = fi.files[0]; if (!f) return;
    fn.textContent = f.name + ' (' + (f.size/1024).toFixed(0) + ' KB)';
    fn.style.display = 'block';
    var reader = new FileReader();
    reader.onload = function(e){ pi.src = e.target.result; pw.style.display = 'block'; };
    reader.readAsDataURL(f);
  }

  window.twCopyLink = function(url) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(twShowToast);
    } else {
      var el = document.createElement('textarea');
      el.value = url; document.body.appendChild(el); el.select();
      document.execCommand('copy'); document.body.removeChild(el); twShowToast();
    }
  };

  function twShowToast() {
    var t = document.getElementById('twToast');
    t.style.opacity = '1';
    setTimeout(function(){ t.style.opacity = '0'; }, 2000);
  }
})();
</script>
