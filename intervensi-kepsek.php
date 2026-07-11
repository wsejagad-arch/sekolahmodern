<?php
if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
}
if ($hakakses != 1 && $hakakses != 5) { ?>
    <script>window.location='404.html';</script>
<?php exit; }

include "koneksi.php";

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_intervensi'])) {
    $penerima_tipe = mysqli_real_escape_string($conn, $_POST['penerima_tipe']);
    $penerima_id = mysqli_real_escape_string($conn, $_POST['penerima_id']);
    $pesan = mysqli_real_escape_string($conn, $_POST['pesan']);

    $q = "INSERT INTO tbl_notifikasi (penerima_id, penerima_tipe, pesan, is_read, created_at) VALUES ('$penerima_id', '$penerima_tipe', '$pesan', 0, NOW())";
    if (mysqli_query($conn, $q)) {
        $message = "<div class='alert alert-success'>Notifikasi berhasil dikirim kepada user terkait.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal mengirim notifikasi: " . mysqli_error($conn) . "</div>";
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-paper-plane"></i> Kirim Intervensi / Notifikasi</h1>

    <?= $message; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4 border-left-primary">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-edit"></i> Buat Notifikasi Baru</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Tipe Penerima</label>
                            <select name="penerima_tipe" id="penerima_tipe" class="form-control" required onchange="loadPenerima(this.value)">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="Guru">Guru</option>
                                <option value="Siswa">Siswa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pilih Penerima</label>
                            <select name="penerima_id" id="penerima_id" class="form-control" required>
                                <option value="">-- Silakan pilih tipe penerima dahulu --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pesan Intervensi / Notifikasi</label>
                            <textarea name="pesan" class="form-control" rows="4" placeholder="Tuliskan pesan teguran, motivasi, atau informasi di sini..." required></textarea>
                        </div>
                        <button type="submit" name="kirim_intervensi" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4 border-left-info">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-history"></i> Riwayat Intervensi Terakhir</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" width="100%">
                            <thead>
                                <tr class="bg-light">
                                    <th>Tanggal</th>
                                    <th>Penerima</th>
                                    <th>Pesan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $qHistory = mysqli_query($conn, "SELECT * FROM tbl_notifikasi ORDER BY created_at DESC LIMIT 10");
                                if (mysqli_num_rows($qHistory) > 0) {
                                    while ($r = mysqli_fetch_assoc($qHistory)) {
                                        $statusBadge = $r['is_read'] == 1 ? '<span class="badge badge-success">Dibaca</span>' : '<span class="badge badge-secondary">Belum Dibaca</span>';
                                        
                                        // Cari nama penerima
                                        $nama = $r['penerima_id'];
                                        if ($r['penerima_tipe'] == 'Guru') {
                                            $qG = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='{$r['penerima_id']}'");
                                            if ($rowG = mysqli_fetch_assoc($qG)) $nama = $rowG['nama_guru'];
                                        } else if ($r['penerima_tipe'] == 'Siswa') {
                                            $qS = mysqli_query($conn, "SELECT nama_siswa FROM tbl_siswa WHERE no_induk='{$r['penerima_id']}'");
                                            if ($rowS = mysqli_fetch_assoc($qS)) $nama = $rowS['nama_siswa'];
                                        }

                                        echo "<tr>
                                                <td>".date('d M Y H:i', strtotime($r['created_at']))."</td>
                                                <td><b>{$nama}</b><br><small class='text-muted'>{$r['penerima_tipe']}</small></td>
                                                <td>".htmlspecialchars($r['pesan'])."</td>
                                                <td>{$statusBadge}</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted'>Belum ada riwayat intervensi.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadPenerima(tipe) {
    var select = document.getElementById('penerima_id');
    select.innerHTML = '<option value="">-- Loading... --</option>';
    
    if (!tipe) {
        select.innerHTML = '<option value="">-- Silakan pilih tipe penerima dahulu --</option>';
        return;
    }
    
    fetch('ajax_get_penerima.php?tipe=' + tipe)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Pilih Penerima --</option>';
            data.forEach(item => {
                select.innerHTML += '<option value="'+item.id+'">'+item.nama+'</option>';
            });
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error memuat data</option>';
        });
}
</script>
