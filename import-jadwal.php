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
</div>
