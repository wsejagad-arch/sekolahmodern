<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<?php
// Tampilkan notifikasi hasil import
if (isset($_SESSION['import_success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 15px; border-left: 4px solid #28a745;">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Berhasil!</strong> ' . $_SESSION['import_success'] . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
    unset($_SESSION['import_success']);
}

if (isset($_SESSION['import_error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 15px; border-left: 4px solid #dc3545;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error!</strong> ' . $_SESSION['import_error'] . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
    unset($_SESSION['import_error']);
}

if (isset($_SESSION['import_warnings'])) {
    echo '<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="border-radius: 15px; border-left: 4px solid #ffc107;">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Perhatian!</strong> Beberapa data tidak dapat diproses:
            <ul class="mb-0 mt-2">';
    foreach ($_SESSION['import_warnings'] as $warning) {
        echo '<li>' . $warning . '</li>';
    }
    echo '</ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
    unset($_SESSION['import_warnings']);
}
?>

<!-- Modern Page Header -->
<div class="mb-4">
  <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; overflow: hidden;">
    <div class="card-body p-4 text-white">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
          <h1 class="h4 mb-2 font-weight-bold">
            <i class="fas fa-file-excel me-3"></i>
            Import Data Guru
          </h1>
          <p class="mb-0 opacity-75">Import data guru dari file Excel (.xlsx/.xls)</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="?page=data-guru" class="btn btn-outline-light btn-sm px-4" style="border-radius: 25px; font-weight: 600;">
            <i class="fas fa-arrow-left me-2"></i>Kembali
          </a>
          <a href="template/template_guru.csv" class="btn btn-light btn-sm px-4 shadow-sm" style="border-radius: 25px; font-weight: 600;" download>
            <i class="fas fa-download me-2"></i>Download Template CSV
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Instructions Card -->
<div class="row mb-4">
  <div class="col-lg-8 mb-4">
    <!-- Import Form -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
      <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <h5 class="m-0 font-weight-bold text-white">
          <i class="fas fa-upload me-3"></i>Upload File Excel
        </h5>
      </div>
      <div class="card-body p-4">
        <form action="proses-import-guru.php" method="post" enctype="multipart/form-data" id="importForm">
          <div class="mb-4">
            <label class="form-label font-weight-bold text-dark mb-3">Pilih File Excel:</label>
            <div class="upload-area border-2 border-dashed border-secondary rounded p-4 text-center" style="border-radius: 15px; background: #f8f9fa; transition: all 0.3s ease;" 
                 ondrop="dropHandler(event);" ondragover="dragOverHandler(event);" ondragenter="dragEnterHandler(event);" ondragleave="dragLeaveHandler(event);">
              <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-3"></i>
              <h6 class="text-secondary mb-3">Drag & drop file Excel di sini atau</h6>
              <input type="file" class="form-control-file" id="fileGuru" name="file_guru" accept=".xlsx,.xls,.csv" style="display: none;" onchange="handleFileSelect(this)">
              <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileGuru').click()" style="border-radius: 25px;">
                <i class="fas fa-folder-open me-2"></i>Pilih File
              </button>
              <div id="fileInfo" class="mt-3" style="display: none;">
                <div class="alert alert-info" style="border-radius: 15px;">
                  <i class="fas fa-file-excel text-success me-2"></i>
                  <span id="fileName"></span>
                  <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearFile()" style="border-radius: 15px;">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
            <small class="form-text text-muted mt-2">
              <i class="fas fa-info-circle me-1"></i>
              Format file yang didukung: .xlsx, .xls, .csv (Maksimal 5MB)
            </small>
          </div>
          
          <div class="mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="replaceData">
              <label class="form-check-label" for="replaceData">
                <strong>Replace data yang sudah ada</strong><br>
                <small class="text-muted">Centang jika ingin mengganti data guru yang sudah ada dengan data baru dari Excel</small>
              </label>
            </div>
          </div>

          <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary px-4 py-2 shadow" style="border-radius: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
              <i class="fas fa-upload me-2"></i>Import Data
            </button>
            <button type="reset" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 25px;" onclick="clearFile()">
              <i class="fas fa-undo me-2"></i>Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4">
    <!-- Instructions -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
      <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 20px 20px 0 0;">
        <h6 class="m-0 font-weight-bold text-white">
          <i class="fas fa-lightbulb me-2"></i>Petunjuk Import
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="instruction-list">
          <div class="instruction-item mb-3">
            <div class="d-flex align-items-start">
              <span class="badge badge-primary rounded-circle me-3 mt-1" style="width: 25px; height: 25px; display: flex; align-items: center; justify-content: center;">1</span>
              <div class="flex-grow-1">
                <strong>Download Template</strong>
                <p class="mb-0 small text-muted">Gunakan template Excel yang sudah disediakan</p>
              </div>
            </div>
          </div>
          
          <div class="instruction-item mb-3">
            <div class="d-flex align-items-start">
              <span class="badge badge-primary rounded-circle me-3 mt-1" style="width: 25px; height: 25px; display: flex; align-items: center; justify-content: center;">2</span>
              <div class="flex-grow-1">
                <strong>Isi Data</strong>
                <p class="mb-0 small text-muted">Lengkapi data guru sesuai kolom yang tersedia</p>
              </div>
            </div>
          </div>
          
          <div class="instruction-item mb-3">
            <div class="d-flex align-items-start">
              <span class="badge badge-primary rounded-circle me-3 mt-1" style="width: 25px; height: 25px; display: flex; align-items: center; justify-content: center;">3</span>
              <div class="flex-grow-1">
                <strong>Upload File</strong>
                <p class="mb-0 small text-muted">Pilih file Excel yang sudah diisi</p>
              </div>
            </div>
          </div>
          
          <div class="instruction-item">
            <div class="d-flex align-items-start">
              <span class="badge badge-primary rounded-circle me-3 mt-1" style="width: 25px; height: 25px; display: flex; align-items: center; justify-content: center;">4</span>
              <div class="flex-grow-1">
                <strong>Proses Import</strong>
                <p class="mb-0 small text-muted">Klik tombol Import untuk memproses data</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Format Info -->
    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
      <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 20px 20px 0 0;">
        <h6 class="m-0 font-weight-bold text-white">
          <i class="fas fa-table me-2"></i>Format Data
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="table-responsive">
          <table class="table table-sm table-borderless">
            <tbody>
              <tr>
                <td><strong>NIP/NUPTK</strong></td>
                <td class="text-muted">Text</td>
              </tr>
              <tr>
                <td><strong>Nama Guru</strong></td>
                <td class="text-muted">Text</td>
              </tr>
              <tr>
                <td><strong>Status Kepegawaian</strong></td>
                <td class="text-muted">PNS/CPNS/GTT/PTT/Honorer</td>
              </tr>
              <tr>
                <td><strong>Status Keaktifan</strong></td>
                <td class="text-muted">Aktif/Tidak Aktif</td>
              </tr>
              <tr>
                <td><strong>Email</strong></td>
                <td class="text-muted">email@domain.com</td>
              </tr>
              <tr>
                <td><strong>No. Telepon</strong></td>
                <td class="text-muted">08xxxxxxxxxx</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

</div>

<!-- Custom Styles and JavaScript -->
<style>
.upload-area {
  transition: all 0.3s ease;
  cursor: pointer;
}

.upload-area:hover {
  border-color: #667eea !important;
  background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%) !important;
}

.upload-area.dragover {
  border-color: #667eea !important;
  background: linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%) !important;
  transform: scale(1.02);
}

.instruction-item {
  position: relative;
}

.instruction-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 12px;
  top: 30px;
  width: 1px;
  height: 20px;
  background: #dee2e6;
}

.badge-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}
</style>

<script>
// File upload handling
function handleFileSelect(input) {
  const file = input.files[0];
  if (file) {
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    
    fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
    fileInfo.style.display = 'block';
    
    // Validate file type
    const allowedTypes = ['.xlsx', '.xls', '.csv'];
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(fileExtension)) {
      alert('Format file tidak didukung! Gunakan file .xlsx, .xls, atau .csv');
      clearFile();
      return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran file terlalu besar! Maksimal 5MB');
      clearFile();
      return;
    }
  }
}

function clearFile() {
  document.getElementById('fileGuru').value = '';
  document.getElementById('fileInfo').style.display = 'none';
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Drag and drop handlers
function dragOverHandler(ev) {
  ev.preventDefault();
}

function dragEnterHandler(ev) {
  ev.preventDefault();
  ev.currentTarget.classList.add('dragover');
}

function dragLeaveHandler(ev) {
  ev.preventDefault();
  ev.currentTarget.classList.remove('dragover');
}

function dropHandler(ev) {
  ev.preventDefault();
  ev.currentTarget.classList.remove('dragover');
  
  const files = ev.dataTransfer.files;
  if (files.length > 0) {
    document.getElementById('fileGuru').files = files;
    handleFileSelect(document.getElementById('fileGuru'));
  }
}

// Form validation
document.getElementById('importForm').addEventListener('submit', function(e) {
  const fileInput = document.getElementById('fileGuru');
  if (!fileInput.files.length) {
    e.preventDefault();
    alert('Silakan pilih file Excel terlebih dahulu!');
    return false;
  }
});
</script>