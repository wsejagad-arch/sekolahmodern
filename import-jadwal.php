<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Import Jadwal Guru</h1>
    
    <div class="alert alert-info">
        <strong>Petunjuk:</strong> 
        Anda dapat mengimpor jadwal mengajar guru menggunakan template Excel (<code>.xlsx</code>). <br>
        Pastikan urutan kolom sesuai dengan template: 
        <code>no_induk</code>, <code>nama_guru</code>, <code>nama_mapel</code>, <code>kelas</code>, <code>hari</code>, <code>jam_mulai</code>, <code>jam_selesai</code>, <code>ruang</code>. <br>
        <em>Tips: Template sudah berisi <strong>Daftar Guru</strong> (Nomor Induk + Nama) dan <strong>Daftar Kelas</strong> dari database, sehingga data selalu sinkron.</em>
    </div>
    
    <a href="/home/download-template-jadwal" class="btn btn-info mb-4">
        <i class="fas fa-download"></i> Download Template Excel (Sinkron Database)
    </a>
    
    <?php if (file_exists("jadwal_extracted.xlsx")): ?>
    <a href="/jadwal_extracted.xlsx" class="btn btn-success mb-4 ml-2">
        <i class="fas fa-file-excel"></i> Download Excel Hasil Ekstrak PDF
    </a>
    <?php endif; ?>

    <div class="row">
        <!-- Langkah 1: Fix Format Excel -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Langkah 1: Sesuaikan NIP & Format Kelas</h6>
                </div>
                <div class="card-body">
                    <p class="small">Upload file Excel hasil ekstrak PDF di sini. Sistem akan otomatis mengisi <strong>Nomor Induk</strong> guru dan merapikan <strong>Format Kelas</strong> sesuai database.</p>
                    <form action="/home/import-jadwal" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Pilih File Excel Raw (.xlsx)</label>
                            <input type="file" name="file" class="form-control-file" accept=".xlsx" required>
                        </div>
                        <button type="submit" name="fix_excel" class="btn btn-warning">
                            <i class="fas fa-magic"></i> Proses & Download Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Langkah 2: Import -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Langkah 2: Upload File Jadwal ke Database</h6>
                </div>
                <div class="card-body">
                    <p class="small">Setelah Excel disempurnakan (atau jika format sudah 100% benar), upload file tersebut di sini untuk menyimpan jadwal ke sistem.</p>
                    <form action="/proses-import-jadwal.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Pilih File Excel Matang (.xlsx)</label>
                            <input type="file" name="file" class="form-control-file" accept=".xlsx" required>
                        </div>
                        <button type="submit" name="import" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Data ke Sistem
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tampilkan Data Jadwal yang Ada di Database -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-success">Jadwal Guru yang Berhasil Diimpor</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableJadwalImport" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIP / No Induk</th>
                            <th>Nama Guru</th>
                            <th>Mapel</th>
                            <th>Kelas</th>
                            <th>Hari</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Ruang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qJadwal = mysqli_query($conn, "SELECT j.*, g.nama AS nama_guru FROM tbl_mapel_ampu j LEFT JOIN tbl_guru g ON j.no_induk = g.no_induk ORDER BY j.hari ASC, j.jam_mulai ASC");
                        $no = 1;
                        if ($qJadwal && mysqli_num_rows($qJadwal) > 0) {
                            while ($r = mysqli_fetch_assoc($qJadwal)) {
                                echo "<tr>";
                                echo "<td>".$no++."</td>";
                                echo "<td>".htmlspecialchars($r['no_induk'])."</td>";
                                echo "<td>".htmlspecialchars($r['nama_guru'] ?? '-')."</td>";
                                echo "<td>".htmlspecialchars($r['nama_mapel'])."</td>";
                                echo "<td>".htmlspecialchars($r['kelas'])."</td>";
                                echo "<td>".htmlspecialchars($r['hari'])."</td>";
                                echo "<td>".htmlspecialchars($r['jam_mulai'])."</td>";
                                echo "<td>".htmlspecialchars($r['jam_selesai'])."</td>";
                                echo "<td>".htmlspecialchars($r['ruang'])."</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>Belum ada data jadwal di sistem. Silakan import melalui form di atas.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if ($.fn.DataTable) {
        $('#dataTableJadwalImport').DataTable();
    }
});
</script>
