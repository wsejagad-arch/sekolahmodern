<?php
$tabs = [
    'identitas' => [
        'icon' => 'fa-user',
        'title' => 'Identitas Pribadi',
        'fields' => ['no_induk', 'nisn', 'nipd', 'nama_siswa', 'jk', 'tempat_lahir', 'tanggal_lahir', 'nik', 'agama', 'anak_ke', 'jml_saudara', 'berat_badan', 'tinggi_badan', 'lingkar_kepala']
    ],
    'sekolah' => [
        'icon' => 'fa-graduation-cap',
        'title' => 'Sekolah & Akademik',
        'fields' => ['kelas', 'rombel', 'jabatan', 'status', 'sekolah_asal', 'no_peserta_ujian', 'no_seri_ijazah', 'skhun']
    ],
    'domisili' => [
        'icon' => 'fa-map-marker-alt',
        'title' => 'Domisili & Kontak',
        'fields' => ['alamat', 'rt', 'rw', 'dusun', 'kelurahan', 'kecamatan', 'kode_pos', 'jenis_tinggal', 'alat_transportasi', 'jarak_rumah', 'telepon', 'hp', 'email', 'no_wa', 'lat', 'lng', 'lintang', 'bujur']
    ],
    'keluarga' => [
        'icon' => 'fa-users',
        'title' => 'Data Keluarga',
        'fields' => [
            'nama_darurat', 'no_darurat',
            'ayah_nama', 'ayah_tahun_lahir', 'ayah_nik', 'ayah_pendidikan', 'ayah_pekerjaan', 'ayah_penghasilan',
            'ibu_nama', 'ibu_tahun_lahir', 'ibu_nik', 'ibu_pendidikan', 'ibu_pekerjaan', 'ibu_penghasilan',
            'wali_nama', 'wali_tahun_lahir', 'wali_pendidikan', 'wali_pekerjaan', 'wali_penghasilan'
        ]
    ],
    'administrasi' => [
        'icon' => 'fa-hand-holding-heart',
        'title' => 'Bantuan & Admin',
        'fields' => ['penerima_kps', 'no_kps', 'penerima_kip', 'nomor_kip', 'nama_kip', 'nomor_kks', 'layak_pip', 'alasan_layak_pip', 'kebutuhan_khusus', 'bank', 'no_rek']
    ],
    'tujuan' => [
        'icon' => 'fa-bullseye',
        'title' => 'Tujuan Mendatang',
        'fields' => ['rencana_setelah_lulus', 'rencana_detail', 'minat_jurusan', 'bakat_minat', 'dukungan_dibutuhkan'],
        'highlight' => true
    ]
];
?>
    <form method="POST" action="" id="formProfil">
      <input type="hidden" name="_simpan_profil" value="1">

      <div class="alert <?= $izinEdit ? 'alert-info' : 'alert-warning' ?> border-0 shadow-sm mb-4 edit-status-banner">
        <div class="d-flex align-items-center">
          <i class="fas <?= $izinEdit ? 'fa-unlock text-primary' : 'fa-lock text-warning' ?> fa-2x me-3"></i>
          <div>
            <h6 class="mb-1 fw-bold"><?= $izinEdit ? 'Mode Edit Terbuka' : 'Mode Edit Terkunci' ?></h6>
            <p class="mb-0 small"><?= $izinEdit ? 'Anda dapat mengubah data profil Anda sekarang.' : 'Fitur edit profil saat ini sedang dikunci oleh admin.' ?></p>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <ul class="nav nav-pills mb-4 pb-2" id="profilTabs" role="tablist" style="overflow-x: auto; flex-wrap: nowrap; white-space: nowrap; -webkit-overflow-scrolling: touch;">
        <?php $isFirst = true; ?>
        <?php foreach ($tabs as $id => $tab): ?>
          <li class="nav-item me-2" role="presentation">
            <button class="nav-link <?= $isFirst ? 'active' : '' ?> rounded-pill <?= isset($tab['highlight']) ? 'fw-bold border border-primary text-primary' : '' ?>" 
                    id="tab-<?= $id ?>" data-bs-toggle="pill" data-bs-target="#content-<?= $id ?>" type="button" role="tab">
              <i class="fas <?= $tab['icon'] ?> me-2"></i><?= $tab['title'] ?>
            </button>
          </li>
          <?php $isFirst = false; ?>
        <?php endforeach; ?>
      </ul>

      <!-- Tabs Content -->
      <div class="tab-content bg-white p-4 rounded-4 shadow-sm mb-4 border" id="profilTabsContent">
        <?php $isFirstContent = true; ?>
        <?php foreach ($tabs as $id => $tab): ?>
          <div class="tab-pane fade <?= $isFirstContent ? 'show active' : '' ?>" id="content-<?= $id ?>" role="tabpanel">
            
            <?php if (isset($tab['highlight'])): ?>
              <div class="alert alert-primary border-0 bg-primary bg-opacity-10 mb-4 rounded-4">
                <h6 class="fw-bold text-primary mb-1"><i class="fas fa-rocket me-2"></i>Pemetaan Masa Depan</h6>
                <p class="small mb-0 text-primary">Data ini sangat penting bagi sekolah untuk memetakan dan mengarahkan rencana masa depan Anda setelah lulus. Pastikan diisi dengan sungguh-sungguh.</p>
              </div>
            <?php else: ?>
              <h6 class="fw-bold mb-4 text-primary border-bottom pb-2"><i class="fas <?= $tab['icon'] ?> me-2"></i><?= $tab['title'] ?></h6>
            <?php endif; ?>

            <div class="row g-3">
              <?php foreach ($tab['fields'] as $column): ?>
                <?php if ($hasColumn($column)): ?>
                  
                  <?php if ($column === 'alamat'): ?>
                    <div class="col-12">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <textarea name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" rows="3" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Masukkan alamat lengkap Anda"><?= htmlspecialchars((string)($siswa[$column] ?? '')) ?></textarea>
                    </div>
                  <?php elseif (in_array($column, ['rencana_detail', 'bakat_minat', 'dukungan_dibutuhkan', 'minat_jurusan'], true)): ?>
                    <div class="col-12">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <textarea name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" rows="3" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Tuliskan dengan detail..."><?= htmlspecialchars((string)($siswa[$column] ?? '')) ?></textarea>
                    </div>
                  <?php elseif ($column === 'tanggal_lahir'): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <input type="date" name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" value="<?= htmlspecialchars((string)($siswa[$column] ?? '')) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
                    </div>
                  <?php elseif ($column === 'jk'): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom">Jenis Kelamin</label>
                      <select name="jk" class="form-select form-control-app" <?= $izinEdit ? '' : 'disabled' ?>>
                        <option value="">Pilih</option>
                        <option value="L" <?= ($siswa['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($siswa['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                      </select>
                      <?php if (!$izinEdit): ?>
                        <input type="hidden" name="jk" value="<?= htmlspecialchars($siswa['jk'] ?? '') ?>">
                      <?php endif; ?>
                    </div>
                  <?php elseif ($column === 'kelas'): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom">Kelas</label>
                      <?php if ($izinEdit): ?>
                        <?php
                        $tenantKelas = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_kelas', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
                        $qKelas = @mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE {$tenantKelas} ORDER BY kelas ASC");
                        ?>
                        <select name="kelas" class="form-select form-control-app">
                          <option value="">Pilih Kelas</option>
                          <?php if ($qKelas) {
                            while ($rowKelas = mysqli_fetch_assoc($qKelas)) { ?>
                            <option value="<?= htmlspecialchars($rowKelas['kelas']) ?>" <?= ($siswa['kelas'] ?? '') === $rowKelas['kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($rowKelas['kelas']) ?></option>
                          <?php } } ?>
                        </select>
                      <?php else: ?>
                        <input type="text" name="kelas" class="form-control form-control-app" value="<?= htmlspecialchars($siswa['kelas'] ?? '') ?>" readonly placeholder="Contoh: X IPA 1">
                      <?php endif; ?>
                    </div>
                  <?php elseif ($column === 'rencana_setelah_lulus'): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <?php
                      $currentPlan = (string)($siswa[$column] ?? '');
                      $planOptions = ['Kuliah', 'Kerja', 'Wirausaha', 'Kursus/Sertifikasi', 'Kedinasan/TNI/Polri', 'Belum Menentukan', 'Lainnya'];
                      ?>
                      <select name="<?= htmlspecialchars($column) ?>" class="form-select form-control-app" <?= $izinEdit ? '' : 'disabled' ?>>
                        <option value="">Pilih rencana</option>
                        <?php foreach ($planOptions as $planOption): ?>
                          <option value="<?= htmlspecialchars($planOption) ?>" <?= $currentPlan === $planOption ? 'selected' : '' ?>><?= htmlspecialchars($planOption) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <?php if (!$izinEdit): ?>
                        <input type="hidden" name="<?= htmlspecialchars($column) ?>" value="<?= htmlspecialchars($currentPlan) ?>">
                      <?php endif; ?>
                    </div>
                  <?php elseif (in_array($column, ['penerima_kps', 'penerima_kip', 'layak_pip'], true)): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <?php $boolValue = (string)($siswa[$column] ?? '0'); ?>
                      <select name="<?= htmlspecialchars($column) ?>" class="form-select form-control-app" <?= $izinEdit ? '' : 'disabled' ?>>
                        <option value="0" <?= $boolValue === '0' ? 'selected' : '' ?>>Tidak</option>
                        <option value="1" <?= $boolValue === '1' ? 'selected' : '' ?>>Ya</option>
                      </select>
                      <?php if (!$izinEdit): ?>
                        <input type="hidden" name="<?= htmlspecialchars($column) ?>" value="<?= htmlspecialchars($boolValue) ?>">
                      <?php endif; ?>
                    </div>
                  <?php elseif ($column === 'lat' || $column === 'lng'): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <input type="text" name="<?= htmlspecialchars($column) ?>" id="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" value="<?= htmlspecialchars((string)($siswa[$column] ?? '')) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
                    </div>
                  <?php elseif ($column === 'no_wa' || $column === 'no_darurat' || $column === 'telepon' || $column === 'hp'): ?>
                    <div class="col-md-6">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <div class="input-group">
                        <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;">+62</span>
                        <input type="tel" name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app border-start-0" value="<?= htmlspecialchars((string)($siswa[$column] ?? '')) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="col-md-6">
                      <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                      <input type="text" name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" value="<?= htmlspecialchars((string)($siswa[$column] ?? '')) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
                    </div>
                  <?php endif; ?>
                  
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
            
            <?php if ($id === 'domisili'): ?>
              <!-- Special Map Actions for Domisili -->
              <div class="mt-4 p-3 bg-light rounded-3 border">
                <h6 class="fw-bold mb-3"><i class="fas fa-map-marked-alt text-primary me-2"></i>Pengaturan Koordinat Peta</h6>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-primary" id="btnGps" <?= $izinEdit ? '' : 'disabled' ?>>
                    <i class="fas fa-crosshairs me-2"></i><span id="gpsLabel">Gunakan GPS Saat Ini</span>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" onclick="toggleMapPanel()">
                    <i class="fas fa-map me-2"></i><span id="mapBtnLabel">Buka Panel Peta</span>
                  </button>
                </div>
                <div id="mapPanel" class="mt-3 d-none">
                  <div class="input-group mb-2">
                    <input type="text" id="searchAlamat" class="form-control" placeholder="Cari nama jalan / daerah..." <?= $izinEdit ? '' : 'disabled' ?>>
                    <button class="btn btn-primary" type="button" id="btnSearchAlamat" <?= $izinEdit ? '' : 'disabled' ?>><i class="fas fa-search"></i> Cari</button>
                  </div>
                  <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm border">
                    <iframe id="mapIframe" src="about:blank" width="100%" height="100%" style="border:0;" loading="lazy"></iframe>
                  </div>
                  <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i>Peta dari OpenStreetMap. Gunakan fitur cari untuk mendapatkan koordinat kasar, lalu sesuaikan angka Latitude/Longitude jika perlu.</small>
                </div>
              </div>
            <?php endif; ?>

          </div>
          <?php $isFirstContent = false; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($izinEdit): ?>
        <button type="submit" class="fab-save">
          <i class="fas fa-save me-2"></i>
          <span>Simpan Profil</span>
        </button>
      <?php endif; ?>
    </form>
