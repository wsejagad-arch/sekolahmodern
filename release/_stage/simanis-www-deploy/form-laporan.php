<?php
// Laporan Manajemen (Create + List + Delete)
// Note: This file is included by home.php, so auth is already handled by header.php
// Just verify admin access without redirect
if (!is_admin()) {
    echo '<div class="alert alert-danger">Akses ditolak. Halaman ini hanya untuk administrator.</div>';
    return;
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/notification_helper.php';

// Auto create table if not exists
$createSql = "CREATE TABLE IF NOT EXISTS tbl_laporan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  tgl_mulai DATE NOT NULL,
  tgl_selesai DATE NOT NULL,
  deskripsi TEXT NOT NULL,
  lampiran VARCHAR(255) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (tgl_mulai),
  INDEX (tgl_selesai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
@mysqli_query($conn, $createSql);

$msg=[]; $err=[];

// Delete handler
if(isset($_GET['hapus']) && ctype_digit($_GET['hapus'])){
    $id = (int)$_GET['hapus'];
    $q = mysqli_query($conn, "SELECT lampiran FROM tbl_laporan WHERE id=$id LIMIT 1");
    if($q && mysqli_num_rows($q)==1){
        $r = mysqli_fetch_assoc($q);
        if($r['lampiran']){
            $f = __DIR__ . '/temp/laporan/' . basename($r['lampiran']);
            if(is_file($f)) @unlink($f);
        }
        mysqli_query($conn, "DELETE FROM tbl_laporan WHERE id=$id LIMIT 1");
        $msg[] = 'Laporan dihapus.';
    } else {
        $err[] = 'Data tidak ditemukan.';
    }
}

// Insert handler
if(isset($_POST['simpan_laporan'])){
    $judul = trim($_POST['judul'] ?? '');
    $tgl_mulai = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if($judul==='') $err[]='Judul wajib.';
    if(!$tgl_mulai || !$tgl_selesai) $err[]='Periode wajib.';
    if($tgl_mulai && $tgl_selesai && $tgl_selesai < $tgl_mulai) $err[]='Tanggal selesai tidak boleh sebelum mulai.';
    if($deskripsi==='') $err[]='Deskripsi wajib.';

    $lampiranName = null;
    if(isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE){
        if($_FILES['lampiran']['error']===UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION));
            if($ext!=='pdf'){
                $err[]='Lampiran harus PDF.';
            } else {
                $dir = __DIR__ . '/temp/laporan';
                if(!is_dir($dir)) @mkdir($dir, 0777, true);
                $lampiranName = date('Ymd_His').'_'.preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES['lampiran']['name']);
                $target = $dir . '/' . $lampiranName;
                if(!move_uploaded_file($_FILES['lampiran']['tmp_name'], $target)){
                    $err[]='Gagal upload lampiran.';
                    $lampiranName=null;
                }
            }
        } else {
            $err[]='Error upload lampiran (code '.$_FILES['lampiran']['error'].').';
        }
    }

    if(!$err){
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_laporan (judul,tgl_mulai,tgl_selesai,deskripsi,lampiran,created_by) VALUES (?,?,?,?,?,?)");
        $created_by = $_SESSION['id_user'] ?? null;
        mysqli_stmt_bind_param($stmt,'sssssi',$judul,$tgl_mulai,$tgl_selesai,$deskripsi,$lampiranName,$created_by);
        if(mysqli_stmt_execute($stmt)){
            $msg[]='Laporan tersimpan.';
            notif_trigger_laporan($conn, $judul, $deskripsi);
        } else {
            $err[]='DB error: '.mysqli_error($conn);
        }
    }
}

// Fetch list
$rows=[];
$res = mysqli_query($conn, "SELECT * FROM tbl_laporan ORDER BY created_at DESC LIMIT 200");
if($res){ while($r=mysqli_fetch_assoc($res)){ $rows[]=$r; } }
?>
<div class="container-fluid">
  <h4 class="mb-3">Input Laporan</h4>
  <?php foreach($msg as $m): ?><div class="alert alert-success py-2"><?= htmlspecialchars($m); ?></div><?php endforeach; ?>
  <?php foreach($err as $e): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($e); ?></div><?php endforeach; ?>
  <div class="card shadow mb-4">
    <div class="card-body">
      <form method="post" action="?page=buat-laporan" enctype="multipart/form-data">
        <div class="form-group">
          <label>Judul Laporan</label>
          <input type="text" name="judul" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Periode</label>
          <div class="row">
            <div class="col-md-6"><input type="date" name="tgl_mulai" class="form-control" required></div>
            <div class="col-md-6"><input type="date" name="tgl_selesai" class="form-control" required></div>
          </div>
        </div>
        <div class="form-group">
          <label>Deskripsi / Catatan</label>
          <textarea name="deskripsi" class="form-control" rows="4" required></textarea>
        </div>
        <div class="form-group">
          <label>Lampiran (PDF optional)</label>
          <input type="file" name="lampiran" accept="application/pdf" class="form-control-file">
        </div>
        <button type="submit" name="simpan_laporan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </form>
    </div>
  </div>

  <div class="card shadow">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <span class="font-weight-bold">Daftar Laporan Terbaru</span>
      <small class="text-muted">Maks 200 terakhir</small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Judul</th>
              <th>Periode</th>
              <th>Dibuat</th>
              <th>Lampiran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows): ?>
              <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data.</td></tr>
            <?php else: $i=1; foreach($rows as $r): ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($r['judul']); ?></td>
                <td><?= htmlspecialchars($r['tgl_mulai']); ?> s/d <?= htmlspecialchars($r['tgl_selesai']); ?></td>
                <td><?= htmlspecialchars($r['created_at']); ?></td>
                <td>
                  <?php if($r['lampiran']): ?>
                    <a class="badge badge-info" target="_blank" href="temp/laporan/<?= urlencode($r['lampiran']); ?>">PDF</a>
                  <?php else: ?>-
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?page=buat-laporan&hapus=<?= $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus laporan ini?');"><i class="fas fa-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
