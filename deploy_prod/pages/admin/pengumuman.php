<?php
// Halaman Kelola Pengumuman - Admin Only
// File: pages/admin/pengumuman.php

// Debug: Check session
if (!isset($_SESSION)) {
    session_start();
}

// Debug output (comment out after fixing)
// echo "<!-- DEBUG: Session hak_akses = " . ($_SESSION['hak_akses'] ?? 'NOT SET') . " -->";

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    echo '<div class="alert alert-danger">Akses ditolak. Halaman ini khusus untuk administrator.</div>';
    echo '<div class="alert alert-info">Session hak_akses: ' . ($_SESSION['hak_akses'] ?? 'NOT SET') . '</div>';
    return; // Changed from exit() to return so page still shows
}

// Database connection check
if (!isset($conn)) {
    // Try to include koneksi from root directory
    if (file_exists(__DIR__ . "/../../koneksi.php")) {
        require_once __DIR__ . "/../../koneksi.php";
    } else {
        echo '<div class="alert alert-danger">Error: koneksi.php not found at ' . __DIR__ . "/../../koneksi.php" . '</div>';
        return;
    }
}

// Check connection is valid
if (!$conn || mysqli_connect_errno()) {
    echo '<div class="alert alert-danger">Error: Database connection failed - ' . mysqli_connect_error() . '</div>';
    return;
}

// Ensure table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  penting TINYINT(1) DEFAULT 0,
  mulai DATE NOT NULL,
  selesai DATE NOT NULL,
  target_scope ENUM('SEMUA','KELAS','TINGKAT','GURU') DEFAULT 'SEMUA',
  target_value VARCHAR(100) DEFAULT NULL,
  lampiran VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4");

// File upload handling
function handle_pengumuman_upload($field){
  if(empty($_FILES[$field]['name'])) return null;
  $fname = $_FILES[$field]['name'];
  $tmp = $_FILES[$field]['tmp_name'];
  $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
  $allowed = ['pdf'];
  if(!in_array($ext,$allowed)) return null;
  $new = 'ANN_'.date('Ymd_His').'_' . substr(md5($fname.microtime()),0,6) . '.' . $ext;
  $destDir = __DIR__.'/../../materi'; 
  if(!is_dir($destDir)) @mkdir($destDir,0775,true);
  if(move_uploaded_file($tmp, $destDir.'/'.$new)) return $new;
  return null;
}

$msg=''; $err='';

// Handle form submission
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='save_pengumuman'){
  $mode = $_POST['mode'] ?? 'add';
  $judul = trim($_POST['judul'] ?? '');
  $isi = trim($_POST['isi'] ?? '');
  $penting = isset($_POST['penting']) ? 1 : 0;
  $mulai = $_POST['mulai'] ?? '';
  $selesai = $_POST['selesai'] ?? '';
  $target_scope = $_POST['target_scope'] ?? 'SEMUA';
  $target_value = trim($_POST['target_value'] ?? '');
  
  if($target_scope==='SEMUA') $target_value=null;
  if($target_value==='') $target_value=null;
  
  if($judul==='' || $isi==='' || !$mulai || !$selesai){ 
    $err='Lengkapi semua field'; 
  } else {
    $judulEsc = mysqli_real_escape_string($conn,$judul);
    $isiEsc = mysqli_real_escape_string($conn,$isi);
    $mulaiEsc = mysqli_real_escape_string($conn,$mulai);
    $selesaiEsc = mysqli_real_escape_string($conn,$selesai);
    $tScopeEsc = mysqli_real_escape_string($conn,$target_scope);
    $tValueEsc = $target_value? "'".mysqli_real_escape_string($conn,$target_value)."'" : 'NULL';
    
    $lampiranFile = handle_pengumuman_upload('lampiran');
    
    if($mode==='edit') {
      $idEdit = (int)($_POST['id_edit'] ?? 0);
      if($idEdit>0){
        $setLamp = $lampiranFile? ", lampiran='".mysqli_real_escape_string($conn,$lampiranFile)."'" : '';
        $qUpd = "UPDATE tbl_pengumuman SET judul='$judulEsc', isi='$isiEsc', penting=$penting, mulai='$mulaiEsc', selesai='$selesaiEsc', target_scope='$tScopeEsc', target_value=$tValueEsc $setLamp WHERE id=$idEdit LIMIT 1";
        if(mysqli_query($conn,$qUpd)) $msg='Pengumuman berhasil diperbarui'; 
        else $err='Gagal update: '.mysqli_error($conn);
      }
    } else {
      $lampiranEsc = $lampiranFile? "'".mysqli_real_escape_string($conn,$lampiranFile)."'" : 'NULL';
      $qIns = "INSERT INTO tbl_pengumuman(judul,isi,penting,mulai,selesai,target_scope,target_value,lampiran) VALUES('$judulEsc','$isiEsc',$penting,'$mulaiEsc','$selesaiEsc','$tScopeEsc',$tValueEsc,$lampiranEsc)";
      if(mysqli_query($conn,$qIns)) $msg='Pengumuman berhasil disimpan'; 
      else $err='Gagal simpan: '.mysqli_error($conn);
    }
  }
}

// Handle delete
if(isset($_GET['hapus_pengumuman'])){
  $id = (int)$_GET['hapus_pengumuman'];
  mysqli_query($conn, "DELETE FROM tbl_pengumuman WHERE id=$id LIMIT 1");
  echo '<script>window.location.href="?page=pengumuman&deleted=1";</script>';
  exit;
}

$today = date('Y-m-d');
$res = mysqli_query($conn, "SELECT * FROM tbl_pengumuman ORDER BY penting DESC, created_at DESC");
$rows=[]; 
while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
?>

<style>
.card { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.05); }
.card-header { background:linear-gradient(135deg,#f8fafc,#e2e8f0); font-weight:600; }
.badge-penting { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
.ann-row-aktif { background-color:#fef3c7 !important; }
textarea { resize:vertical; }
</style>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">📢 Manajemen Pengumuman</h1>
  
  <?php if(isset($_GET['deleted'])): ?>
  <div class="alert alert-warning alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    Pengumuman berhasil dihapus.
  </div>
  <?php endif; ?>
  
  <?php if($msg): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <?= htmlspecialchars($msg); ?>
  </div>
  <?php endif; ?>
  
  <?php if($err): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <?= htmlspecialchars($err); ?>
  </div>
  <?php endif; ?>
  
  <!-- Form Tambah/Edit -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Tambah / Edit Pengumuman</h6>
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" id="formPengumuman">
        <input type="hidden" name="action" value="save_pengumuman">
        <input type="hidden" name="mode" value="add" id="modeField">
        <input type="hidden" name="id_edit" id="idEditField">
        
        <div class="row">
          <div class="col-md-8">
            <div class="form-group">
              <label class="font-weight-bold">Judul Pengumuman</label>
              <input type="text" name="judul" class="form-control" maxlength="150" required>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label class="font-weight-bold">Tanggal Mulai</label>
              <input type="date" name="mulai" class="form-control" required>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label class="font-weight-bold">Tanggal Selesai</label>
              <input type="date" name="selesai" class="form-control" required>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label class="font-weight-bold">Isi Pengumuman</label>
          <textarea name="isi" class="form-control" rows="5" required placeholder="Tulis isi pengumuman di sini..."></textarea>
        </div>
        
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label class="font-weight-bold">Target Penerima</label>
              <select name="target_scope" id="targetScope" class="form-control">
                <option value="SEMUA">Semua</option>
                <option value="KELAS">Kelas Tertentu</option>
                <option value="TINGKAT">Tingkat Tertentu</option>
                <option value="GURU">Guru (No Induk)</option>
              </select>
            </div>
          </div>
          <div class="col-md-3 d-none" id="targetValueWrap">
            <div class="form-group">
              <label class="font-weight-bold">Nilai Target</label>
              <input type="text" name="target_value" class="form-control" placeholder="XI IPA 1 / 11 / NIP">
              <small class="form-text text-muted">Contoh: XI IPA 1, atau 11, atau 198712345</small>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="font-weight-bold">Lampiran (PDF)</label>
              <input type="file" name="lampiran" accept="application/pdf" class="form-control-file">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="font-weight-bold d-block">&nbsp;</label>
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" name="penting" id="pentingChk">
                <label class="custom-control-label" for="pentingChk">Tandai Penting</label>
              </div>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan Pengumuman
        </button>
        <button type="button" class="btn btn-secondary" onclick="resetForm()">
          <i class="fas fa-redo"></i> Reset
        </button>
      </form>
    </div>
  </div>
  
  <!-- Daftar Pengumuman -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Daftar Pengumuman</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
          <thead>
            <tr>
              <th width="40">#</th>
              <th>Judul & Isi</th>
              <th width="150">Periode</th>
              <th width="100">Target</th>
              <th width="80">Status</th>
              <th width="100">Lampiran</th>
              <th width="150">Dibuat</th>
              <th width="100">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">Belum ada pengumuman.</td>
            </tr>
            <?php else: 
            $no=1; 
            foreach($rows as $r): 
              $aktif = ($today >= $r['mulai'] && $today <= $r['selesai']);
            ?>
            <tr class="<?= $aktif ? 'ann-row-aktif':''; ?>">
              <td><?= $no++; ?></td>
              <td>
                <div class="font-weight-bold mb-1"><?= htmlspecialchars($r['judul']); ?></div>
                <div class="text-muted small" style="max-height:50px; overflow:hidden;">
                  <?= htmlspecialchars(substr($r['isi'],0,150)); ?><?= strlen($r['isi'])>150?'...':''; ?>
                </div>
              </td>
              <td class="small">
                <?= date('d/m/Y', strtotime($r['mulai'])); ?><br>
                s/d<br>
                <?= date('d/m/Y', strtotime($r['selesai'])); ?>
              </td>
              <td class="small">
                <?= htmlspecialchars($r['target_scope']); ?>
                <?php if($r['target_value']): ?>
                <br><span class="text-muted"><?= htmlspecialchars($r['target_value']); ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($r['penting']): ?>
                <span class="badge badge-danger">Penting</span>
                <?php else: ?>
                <span class="badge badge-secondary">Biasa</span>
                <?php endif; ?>
                <?php if($aktif): ?>
                <br><span class="badge badge-success mt-1">Aktif</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($r['lampiran']): ?>
                <a href="materi/<?= htmlspecialchars($r['lampiran']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-file-pdf"></i> Lihat
                </a>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="small text-muted">
                <?= date('d/m/Y H:i', strtotime($r['created_at'])); ?>
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-warning btn-edit mb-1" 
                        data-id='<?= $r['id']; ?>' 
                        data-json='<?= htmlspecialchars(json_encode($r),ENT_QUOTES); ?>'>
                  <i class="fas fa-edit"></i>
                </button>
                <a href="?page=pengumuman&hapus_pengumuman=<?= $r['id']; ?>" 
                   class="btn btn-sm btn-danger mb-1"
                   onclick="return confirm('Yakin hapus pengumuman ini?')">
                  <i class="fas fa-trash"></i>
                </a>
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

<script>
document.addEventListener('DOMContentLoaded', function(){
  const targetScope = document.getElementById('targetScope');
  const targetWrap = document.getElementById('targetValueWrap');
  
  // Check if elements exist before attaching handlers
  if (!targetScope || !targetWrap) {
    console.warn('Pengumuman form elements not found');
    return;
  }
  
  function toggleTarget(){
    if(['KELAS','TINGKAT','GURU'].includes(targetScope.value)) {
      targetWrap.classList.remove('d-none');
    } else {
      targetWrap.classList.add('d-none');
    }
  }
  
  targetScope.addEventListener('change', toggleTarget);
  toggleTarget();
  
  // Edit button handler
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function(){
      const data = JSON.parse(this.getAttribute('data-json'));
      document.querySelector('#formPengumuman [name=judul]').value = data.judul;
      document.querySelector('#formPengumuman [name=mulai]').value = data.mulai;
      document.querySelector('#formPengumuman [name=selesai]').value = data.selesai;
      document.querySelector('#formPengumuman [name=isi]').value = data.isi;
      document.querySelector('#formPengumuman [name=penting]').checked = data.penting == 1;
      document.getElementById('modeField').value = 'edit';
      document.getElementById('idEditField').value = data.id;
      document.getElementById('targetScope').value = data.target_scope || 'SEMUA';
      toggleTarget();
      if(data.target_value) {
        document.querySelector('#formPengumuman [name=target_value]').value = data.target_value;
      } else {
        document.querySelector('#formPengumuman [name=target_value]').value = '';
      }
      window.scrollTo({top: 0, behavior: 'smooth'});
    });
  });
});

function resetForm(){
  document.getElementById('formPengumuman').reset();
  document.getElementById('modeField').value = 'add';
  document.getElementById('idEditField').value = '';
  document.getElementById('targetValueWrap').classList.add('d-none');
}
</script>
