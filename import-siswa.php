<div class="container-fluid">
    <a href="/template_siswa.xlsx" download="template_siswa.xlsx" class="btn btn-info mb-3">
        <i class="fas fa-download"></i> Download Template
    </a>
    <div class="alert alert-warning">
        <strong>Info:</strong> Pastikan format template memiliki kolom: <b>nis, nama, kelas</b> secara berurutan. Anda dapat mengupload file berextension <b>.xlsx</b> atau <b>.csv</b>.
    </div>
    <h4 class="mb-4">Import Data Siswa dari Excel/CSV</h4>
    <form method="post" enctype="multipart/form-data" action="proses-import-siswa.php">
      <div class="form-group">
          <label for="file">Pilih File Excel/CSV</label>
          <input type="file" name="file" class="form-control" accept=".xls,.xlsx,.csv" required>
      </div>
      <button type="submit" name="import" class="btn btn-success">Upload & Import</button>
  </form>
</div>
