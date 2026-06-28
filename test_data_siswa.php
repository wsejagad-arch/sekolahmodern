<?php
// test_data_siswa.php - harness debugging modul siswa
require_once __DIR__ . '/bootstrap.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Debug Data Siswa</title>
<link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
<link href="css/sb-admin-2.min.css" rel="stylesheet" />
<script src="vendor/jquery/jquery.min.js"></script>
<script src="js/sweetalert2.all.min.js"></script>
<style>
body { padding: 1rem; }
pre { background:#f8f9fa; padding:.75rem; border:1px solid #dee2e6; }
.small { font-size: .85rem; }
</style>
</head>
<body>
<h3>Harness Debug Data Siswa</h3>
<p class="small">Gunakan halaman ini untuk menguji tombol Edit / Hapus dari listing siswa tanpa melalui DataTables (untuk isolasi masalah event / CSS).</p>

<section>
<h5>Informasi Session</h5>
<table class="table table-bordered table-sm" style="max-width:500px;">
<tr><th>Key</th><th>Value</th></tr>
<?php foreach(['username','nama','hak_akses','id_user','no_induk'] as $k): ?>
<tr><td><?= htmlspecialchars($k) ?></td><td><?= htmlspecialchars($_SESSION[$k] ?? 'NULL') ?></td></tr>
<?php endforeach; ?>
</table>
</section>

<section>
<h5>Sample Data Siswa (10 pertama)</h5>
<table class="table table-striped table-sm" id="tblSample">
<thead><tr><th>No</th><th>No Induk</th><th>Nama</th><th>Kelas</th><th>Aksi</th></tr></thead>
<tbody>
<?php
$q = $conn->query("SELECT no_induk,nama_siswa,kelas FROM tbl_siswa ORDER BY kelas ASC LIMIT 10");
$i=1; while($r=$q->fetch_assoc()): ?>
<tr>
 <td><?= $i++; ?></td>
 <td><?= htmlspecialchars($r['no_induk']); ?></td>
 <td><?= htmlspecialchars($r['nama_siswa']); ?></td>
 <td><?= htmlspecialchars($r['kelas']); ?></td>
 <td>
   <a class="btn btn-sm btn-info" href="home.php?page=edit-siswa&no_induk=<?= urlencode($r['no_induk']); ?>" target="_blank">Edit</a>
   <button class="btn btn-sm btn-danger btn-del" data-no-induk="<?= htmlspecialchars($r['no_induk']); ?>" data-nama="<?= htmlspecialchars($r['nama_siswa']); ?>">Hapus</button>
 </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</section>

<section>
<h5>Console Output</h5>
<pre id="logBox">(log akan muncul di sini)</pre>
</section>

<script>
function log(msg){
  const box = document.getElementById('logBox');
  box.textContent += '['+new Date().toLocaleTimeString()+'] '+msg+'\n';
}

document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-del');
  if(!btn) return;
  const nis = btn.getAttribute('data-no-induk');
  const nama = btn.getAttribute('data-nama');
  log('Klik hapus untuk NIS='+nis);
  Swal.fire({
    title: 'Hapus?',
    text: 'Hapus siswa '+nama+' ('+nis+')',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya',
    cancelButtonText: 'Batal'
  }).then(res=>{
    if(res.isConfirmed){
      log('Konfirmasi ya -> kirim request');
      fetch('delete-siswa.php?no_induk='+encodeURIComponent(nis)+'&mode=json')
       .then(r=>r.json())
       .then(j=>{
          log('Resp: '+JSON.stringify(j));
          if(j.success){
            Swal.fire('Terhapus','Data siswa dihapus','success').then(()=>location.reload());
          } else {
            Swal.fire('Gagal', j.error || 'Error tidak diketahui','error');
          }
       })
       .catch(err=>{ log('Error fetch: '+err); Swal.fire('Error', 'Jaringan / server gagal','error'); });
    }
  });
});
</script>
</body>
</html>
