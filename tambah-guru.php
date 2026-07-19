<?php
if (!isset($_SESSION["username"])) {
  header("location: index.php?haruslogin");
  exit;
} else if ($hakakses != 1) { ?>
  <script>
    window.location = '404.html';
  </script>
  <?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

// Auto-migrate: tambah kolom no_wa jika belum ada
$_chkWa = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
if ($_chkWa && mysqli_num_rows($_chkWa) === 0) {
  @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru");
}
$_chkBK = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_guru_bk'");
if ($_chkBK && mysqli_num_rows($_chkBK) === 0) {
  @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_guru_bk TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}
$_chkLit = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_pendamping_literasi'");
if ($_chkLit && mysqli_num_rows($_chkLit) === 0) {
  @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_pendamping_literasi TINYINT(1) NOT NULL DEFAULT 0 AFTER is_guru_bk");
}
$_chkAduan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_tim_aduan'");
if ($_chkAduan && mysqli_num_rows($_chkAduan) === 0) {
  @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_tim_aduan TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pendamping_literasi");
}
$_chkJabatan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'jabatan'");
if ($_chkJabatan && mysqli_num_rows($_chkJabatan) === 0) {
  @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian");
}

// Pemrosesan form
if (isset($_POST['submit'])) {
  $nip              = trim(mysqli_real_escape_string($conn, isset($_POST['nip']) ? $_POST['nip'] : ''));
  $defaultPassword  = '12345';
  $hashnip          = md5($defaultPassword);
  $nami             = mysqli_real_escape_string($conn, isset($_POST['nama']) ? $_POST['nama'] : '');
  $no_wa            = mysqli_real_escape_string($conn, trim(isset($_POST['no_wa']) ? $_POST['no_wa'] : ''));
  $status_kepegawaian = mysqli_real_escape_string($conn, isset($_POST['status_kepegawaian']) ? $_POST['status_kepegawaian'] : '');
  $jabatan          = mysqli_real_escape_string($conn, trim(isset($_POST['jabatan']) ? $_POST['jabatan'] : ''));
  $is_guru_bk       = isset($_POST['is_guru_bk']) ? 1 : 0;
  $is_pendamping_literasi = isset($_POST['is_pendamping_literasi']) ? 1 : 0;
  $is_tim_aduan       = isset($_POST['is_tim_aduan']) ? 1 : 0;
  $id_kelas_wali    = isset($_POST['wali_kelas']) ? (int)$_POST['wali_kelas'] : 0;
  $walas_status     = ($id_kelas_wali > 0) ? 'Ya' : 'Tidak';
  $status           = mysqli_real_escape_string($conn, isset($_POST['status_keaktifan']) ? $_POST['status_keaktifan'] : 'Aktif');
  $akses            = isset($_POST['is_admin']) ? '1' : '2';
  $tglskr           = date('Y-m-d H:i:s');
  
  $namafile         = isset($_FILES['file']['name']) ? $_FILES['file']['name'] : '';
  $ukuranFile       = isset($_FILES['file']['size']) ? $_FILES['file']['size'] : 0;
  $error            = isset($_FILES['file']['error']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
  $tmpName          = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
  $isilog           = "$nami menambahkan data guru dengan NIP/NUPTK $nip kedalam sistem";

  $cek = cek_guru($nip);
  if ($cek == True) {
      $success = false;
      $dbError = "";
      $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
      
      if ($error != UPLOAD_ERR_NO_FILE) {
          $cekfoto = cek_foto($namafile);
          $ins1 = mysqli_query($conn, "INSERT INTO tbl_guru(no_induk, nama_guru, no_wa, status_kepegawaian, jabatan, is_guru_bk, is_pendamping_literasi, is_tim_aduan, walas, foto, status) VALUES('$nip','$nami','$no_wa','$status_kepegawaian','$jabatan',$is_guru_bk,$is_pendamping_literasi,$is_tim_aduan,'$walas_status','$cekfoto','$status')");
          if (!$ins1) {
              $dbError = mysqli_error($conn);
          } else {
              $success = true;
              move_uploaded_file($tmpName, 'foto/' . $cekfoto);
          }
      } else {
          $ins2 = mysqli_query($conn, "INSERT INTO tbl_guru(no_induk, nama_guru, no_wa, status_kepegawaian, jabatan, is_guru_bk, is_pendamping_literasi, is_tim_aduan, walas, status) VALUES('$nip','$nami','$no_wa','$status_kepegawaian','$jabatan',$is_guru_bk,$is_pendamping_literasi,$is_tim_aduan,'$walas_status','$status')");
          if (!$ins2) {
              $dbError = mysqli_error($conn);
          } else {
              $success = true;
          }
      }
      
      if ($success) {
          mysqli_query($conn, "INSERT INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$nip','$hashnip','$akses')");
          
          // --- SINKRONISASI WALI KELAS ---
          if ($id_kelas_wali > 0) {
              mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
              $tgl_now = date('Y-m-d H:i:s');
              mysqli_query($conn, "INSERT INTO tbl_wali_kelas(id_kelas, nip_wali, nama_wali, id_sekolah, created_at, updated_at) VALUES($id_kelas_wali, '$nip', '$nami', $tenantId, '$tgl_now', '$tgl_now')");
              mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas='$nami', nip_wali='$nip' WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
          }
          // -------------------------------
          
          mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr','$isilog')");
          ?>
          <script>
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil menambah data guru!',
                showConfirmButton: false,
                timer: 1500
              })
              .then(function() {
                window.location.href = "?page=data-guru";
              });
          </script>
          <?php
      } else {
          ?>
          <script>
              Swal.fire('Gagal Menyimpan!', 'Database Error: <?= htmlspecialchars($dbError) ?>', 'error')
          </script>
          <?php
      }
  } else { 
      ?>
      <script>
        Swal.fire('Gagal', 'Guru dengan NIP ini sudah ada di dalam daftar!', 'error')
      </script>
      <?php 
  }
}

?>

<div class="container-fluid">

  <!-- Modern Page Header -->
  <div class="mb-4">
    <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; overflow: hidden;">
      <div class="card-body p-4 text-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div>
            <h1 class="h4 mb-2 font-weight-bold">
              <i class="fas fa-user-plus me-3"></i>
              Tambah Data Guru
            </h1>
            <p class="mb-0 opacity-75">Tambahkan data guru baru ke sistem</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="?page=data-guru" class="btn btn-outline-light btn-sm px-4" style="border-radius: 25px; font-weight: 600;">
              <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <a href="?page=import-guru" class="btn btn-light btn-sm px-4 shadow-sm" style="border-radius: 25px; font-weight: 600;">
              <i class="fas fa-file-excel me-2"></i>Import Excel
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Form Card -->
  <div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
      <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
          <h5 class="m-0 font-weight-bold text-white">
            <i class="fas fa-edit me-3"></i>Form Data Guru
          </h5>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate id="formGuru">

            <!-- Row 1: NIP dan Nama -->
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="nip" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-id-card text-primary me-2"></i>NIP/NUPTK
                  </label>
                  <input type="text" class="form-control" id="nip" name="nip"
                    style="border-radius: 10px; padding: 10px 16px; border: 2px solid #e9ecef;"
                    placeholder="Masukkan NIP/NUPTK" required>
                  <div class="valid-feedback">✓ Valid</div>
                  <div class="invalid-feedback">Harap diisi kolom ini</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="nama" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-user text-success me-2"></i>Nama Guru
                  </label>
                  <input type="text" class="form-control" id="nama" name="nama"
                    style="border-radius: 10px; padding: 10px 16px; border: 2px solid #e9ecef;"
                    placeholder="Masukkan nama lengkap guru" required>
                  <div class="valid-feedback">✓ Valid</div>
                  <div class="invalid-feedback">Harap diisi kolom ini</div>
                </div>
              </div>
            </div>

            <!-- Row 2: No. WA -->
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="no_wa" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fab fa-whatsapp text-success me-2"></i>No. WhatsApp Aktif
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                    </div>
                    <input type="text" class="form-control" id="no_wa" name="no_wa"
                      placeholder="Contoh: 08123456789"
                      inputmode="numeric" maxlength="16">
                  </div>
                  <small class="text-muted">Nomor diawali 08 atau 628. Kosongkan jika tidak ada.</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-user-shield text-info me-2"></i>Role Khusus
                  </label>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_guru_bk" value="1" id="is_guru_bk">
                    <label class="form-check-label" for="is_guru_bk">
                      <strong>Guru BK</strong>
                      <small class="text-muted d-block">Bimbingan Konseling — dapat memvalidasi izin siswa</small>
                    </label>
                  </div>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_pendamping_literasi" value="1" id="is_pendamping_literasi">
                    <label class="form-check-label" for="is_pendamping_literasi">
                      <strong>Pendamping Literasi</strong>
                      <small class="text-muted d-block">Dapat mengatur kelas & memberi tugas literasi</small>
                    </label>
                  </div>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_tim_aduan" value="1" id="is_tim_aduan">
                    <label class="form-check-label" for="is_tim_aduan">
                      <strong>Tim Aduan</strong>
                      <small class="text-muted d-block">Koordinasi aduan — menerima notifikasi aduan siswa</small>
                    </label>
                  </div>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin">
                    <label class="form-check-label" for="is_admin">
                      <strong>Admin</strong>
                      <small class="text-muted d-block">Jadikan Admin — memiliki akses penuh ke dashboard admin</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Row 3: Status Kepegawaian, Jabatan WKS & Status Keaktifan -->
            <div class="row mb-3">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="status_kepegawaian" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-briefcase text-info me-2"></i>Status Kepegawaian
                  </label>
                  <select class="form-control" name="status_kepegawaian" id="status_kepegawaian" required
                    style="border-radius: 10px; padding: 10px 16px; border: 2px solid #e9ecef;">
                    <option value="" disabled selected>-- Pilih Status Kepegawaian --</option>
                    <option value="ASN">ASN</option>
                    <option value="NON_ASN">Non ASN</option>
                  </select>
                  <div class="invalid-feedback">Harap pilih status kepegawaian</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="jabatan" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-user-tie text-primary me-2"></i>Jabatan WKS
                  </label>
                  <select class="form-control" name="jabatan" id="jabatan"
                    style="border-radius: 10px; padding: 10px 16px; border: 2px solid #e9ecef;">
                    <option value="">-- Guru Biasa --</option>
                    <option value="WKS Kurikulum">WKS Kurikulum</option>
                    <option value="Tim WKS Kurikulum">Tim WKS Kurikulum</option>
                    <option value="WKS Kesiswaan">WKS Kesiswaan</option>
                    <option value="Tim WKS Kesiswaan">Tim WKS Kesiswaan</option>
                    <option value="WKS Humas">WKS Humas</option>
                    <option value="Tim WKS Humas">Tim WKS Humas</option>
                    <option value="WKS Sarpras">WKS Sarpras</option>
                    <option value="Tim WKS Sarpras">Tim WKS Sarpras</option>
                    <option value="STPKS">STPKS</option>
                    <option value="Kepala Sekolah">Kepala Sekolah</option>
                  </select>
                  <small class="text-muted">WKS Kurikulum dan Tim WKS Kurikulum dapat mengelola microsite WKS.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="status_keaktifan" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-toggle-on text-success me-2"></i>Status Keaktifan
                  </label>
                  <select class="form-control" name="status_keaktifan" id="status_keaktifan" required
                    style="border-radius: 10px; padding: 10px 16px; border: 2px solid #e9ecef;">
                    <option value="Aktif" selected>Aktif</option>
                    <option value="Non-Aktif">Non-Aktif</option>
                  </select>
                  <div class="invalid-feedback">Harap pilih status keaktifan</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="wali_kelas" class="form-label font-weight-bold text-dark mb-2">
                    <i class="fas fa-chalkboard-teacher text-info me-2"></i>Wali Kelas
                  </label>
                  <select class="form-control" name="wali_kelas" id="wali_kelas"
                    style="border-radius: 10px; padding: 10px 16px; border: 2px solid #e9ecef;">
                    <option value="">-- Tidak Menjabat --</option>
                    <?php
                    $sqlKelas = "SELECT id_kelas, kelas FROM tbl_kelas ORDER BY kelas ASC";
                    $resultKelas = mysqli_query($conn, $sqlKelas);
                    while ($dataKelas = mysqli_fetch_array($resultKelas)) {
                    ?>
                        <option value="<?= $dataKelas['id_kelas']; ?>">
                            <?= htmlspecialchars($dataKelas['kelas']); ?>
                        </option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- Row 4: Upload Foto -->
            <div class="row mb-3">
              <div class="col-12">
                <div class="form-group">
                  <label class="form-label font-weight-bold text-dark mb-3">
                    <i class="fas fa-camera text-warning me-2"></i>Foto Guru
                  </label>
                  <div class="upload-area border-2 border-dashed border-secondary rounded p-4 text-center"
                    style="border-radius: 15px; background: #f8f9fa; transition: all 0.3s ease; cursor: pointer;"
                    ondrop="dropHandler(event);" ondragover="dragOverHandler(event);" ondragenter="dragEnterHandler(event);" ondragleave="dragLeaveHandler(event);">
                    <div id="uploadContent">
                      <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-3"></i>
                      <h6 class="text-secondary mb-3">Drag & drop foto di sini atau</h6>
                      <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('file').click()" style="border-radius: 25px;">
                        <i class="fas fa-folder-open me-2"></i>Pilih Foto
                      </button>
                    </div>
                    <div id="photoPreview" style="display: none;">
                      <img id="previewImage" src="" alt="Preview" class="img-thumbnail mb-3" style="max-height: 200px; border-radius: 15px;">
                      <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearPhoto()" style="border-radius: 15px;">
                          <i class="fas fa-times me-1"></i>Hapus Foto
                        </button>
                      </div>
                    </div>
                    <input type="file" class="form-control-file" id="file" name="file" accept=".jpg,.jpeg" style="display: none;" onchange="handlePhotoSelect(this)">
                  </div>
                  <small class="form-text text-muted mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Format file: JPG/JPEG, Maksimal 500KB
                  </small>
                </div>
              </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="hak_akses" value="2">

            <!-- Action Buttons -->
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                  <a href="?page=data-guru" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 25px;">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                  </a>
                  <div class="d-flex gap-3">
                    <button type="reset" class="btn btn-outline-warning px-4 py-2" style="border-radius: 25px;" onclick="resetForm()">
                      <i class="fas fa-undo me-2"></i>Reset
                    </button>
                    <button type="submit" name="submit" class="btn btn-primary px-4 py-2 shadow"
                      style="border-radius: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;"
                      onclick="return confirm('Apakah data sudah benar? Setelah disimpan, NIP/NUPTK tidak bisa dirubah!')">
                      <i class="fas fa-save me-2"></i>Simpan Data
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Custom Styles and JavaScript -->
<style>
  .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
  }

  .card {
    transition: all 0.3s ease;
  }
</style>

<script>
  // Photo upload handling
  function handlePhotoSelect(input) {
    const file = input.files[0];
    if (file) {
      // Validate file type
      if (!file.type.match('image/jpeg')) {
        alert('File yang diizinkan hanya bertipe JPG/JPEG!');
        clearPhoto();
        return;
      }

      // Validate file size (500KB)
      if (file.size > 500 * 1024) {
        alert('Ukuran file maksimal 500KB!');
        clearPhoto();
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('previewImage').src = e.target.result;
        document.getElementById('uploadContent').style.display = 'none';
        document.getElementById('photoPreview').style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  }

  // Clear photo
  function clearPhoto() {
    document.getElementById('file').value = '';
    document.getElementById('uploadContent').style.display = 'block';
    document.getElementById('photoPreview').style.display = 'none';
  }

  // Reset form
  function resetForm() {
    clearPhoto();
    document.getElementById('formGuru').reset();
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
      document.getElementById('file').files = files;
      handlePhotoSelect(document.getElementById('file'));
    }
  }

  // Form validation enhancement
  document.getElementById('formGuru').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    this.classList.add('was-validated');
  });

  // Real-time validation for inputs
  document.querySelectorAll('.form-control').forEach(function(input) {
    input.addEventListener('blur', function() {
      if (this.checkValidity()) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  });
</script>
