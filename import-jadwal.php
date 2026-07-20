<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Import Jadwal Guru</h1>
    
    <div class="alert alert-info">
        <strong>Petunjuk:</strong> 
        Anda dapat mengimpor jadwal mengajar guru menggunakan template Excel (<code>.xlsx</code>). <br>
        Pastikan urutan kolom sesuai dengan template: 
        <code>no_induk</code>, <code>nama_guru</code>, <code>nama_mapel</code>, <code>kelas</code>, <code>hari</code>, <code>jam_mulai</code>, <code>jam_selesai</code>, <code>ruang</code>. <br>
        <em>Tips: Anda dapat menyalin data dari hasil export Excel aSc Timetables ke dalam template ini.</em>
    </div>
    
    <a href="/template_jadwal.xlsx" class="btn btn-info mb-4">
        <i class="fas fa-download"></i> Download Template Excel
    </a>
    
    <?php if (file_exists("jadwal_extracted.xlsx")): ?>
    <a href="/jadwal_extracted.xlsx" class="btn btn-success mb-4 ml-2">
        <i class="fas fa-file-excel"></i> Download Excel Hasil Ekstrak PDF
    </a>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Upload File Jadwal</h6>
        </div>
        <div class="card-body">
            <form action="/proses-import-jadwal.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Pilih File Excel (.xlsx)</label>
                    <input type="file" name="file" class="form-control-file" accept=".xlsx" required>
                </div>
                <button type="submit" name="import" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Import Data
                </button>
            </form>
        </div>
    </div>
</div>
