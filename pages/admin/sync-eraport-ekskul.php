<?php
if (!is_admin()) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Akses ditolak. Halaman ini hanya untuk admin.</div></div>';
    return;
}

require_once __DIR__ . '/../../eraport_helper.php';
$cfgSync = eraport_get_config();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sync e-Raport Ekskul</h1>
        <a href="home.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Sinkron Data Master Ekstrakurikuler</h6>
            <span class="badge badge-info">Admin Only</span>
        </div>
        <div class="card-body">
            <p class="mb-3 text-muted">
                Tombol di bawah akan login ke e-Raport, ambil data dari halaman <strong>data_ekskul</strong>, lalu simpan/upsert ke tabel lokal <strong>tbl_ekskul_eraport</strong>.
            </p>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="small text-muted">Base URL e-Raport</div>
                    <div class="font-weight-bold"><?php echo htmlspecialchars((string)$cfgSync['base_url']); ?></div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">Kredensial login sesi</div>
                    <div class="font-weight-bold"><?php echo !empty($cfgSync['admin_username']) ? 'Tersedia' : 'Belum diisi (cek config.local.php)'; ?></div>
                </div>
            </div>

            <button type="button" id="btnSyncEraportEkskul" class="btn btn-primary">
                <i class="fas fa-sync-alt mr-1"></i>Jalankan Sinkron Sekarang
            </button>
            <span id="syncLoading" class="ml-2 text-muted" style="display:none;">Memproses...</span>

            <hr>

            <div id="syncResult" class="d-none"></div>

            <div class="table-responsive mt-3 d-none" id="syncPreviewWrap">
                <table class="table table-bordered table-sm" id="syncPreviewTable">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Kelas Ekskul</th>
                            <th>Jenis Ekskul</th>
                            <th>Nama Ekskul</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-info">Sinkron Full Daftar Siswa e-Raport</h6>
            <span class="badge badge-primary">Opsional</span>
        </div>
        <div class="card-body">
            <p class="mb-3 text-muted">
                Tombol ini mengambil seluruh daftar siswa aktif dari halaman <strong>data_siswa</strong>, lalu menyimpan/upsert ke tabel lokal <strong>tbl_siswa_eraport</strong>.
            </p>

            <button type="button" id="btnSyncEraportSiswa" class="btn btn-info text-white">
                <i class="fas fa-users mr-1"></i>Sync Daftar Siswa Sekarang
            </button>
            <span id="syncSiswaListLoading" class="ml-2 text-muted" style="display:none;">Memproses daftar siswa...</span>

            <hr>

            <div id="syncSiswaListResult" class="d-none"></div>

            <div class="table-responsive mt-3 d-none" id="syncSiswaListPreviewWrap">
                <table class="table table-bordered table-sm" id="syncSiswaListPreviewTable">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>NIS</th>
                            <th>NISN</th>
                            <th>Kelas</th>
                            <th>Peserta Didik ID</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <hr>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap:8px;">
                <h6 class="mb-0 font-weight-bold text-secondary">Data Lokal Siswa (Search + Filter)</h6>
                <div>
                    <button type="button" id="btnExportSiswaCsv" class="btn btn-outline-success btn-sm mr-1">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button type="button" id="btnReloadSiswaLocal" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-sync-alt mr-1"></i>Reload Data
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Cari nama / NIS / NISN / ID</label>
                    <input type="text" id="filterSiswaQ" class="form-control form-control-sm" placeholder="Ketik kata kunci...">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Filter Kelas</label>
                    <select id="filterSiswaKelas" class="form-control form-control-sm">
                        <option value="">Semua kelas</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Filter JK</label>
                    <select id="filterSiswaJk" class="form-control form-control-sm">
                        <option value="">Semua</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Per Halaman</label>
                    <select id="filterSiswaPerPage" class="form-control form-control-sm">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" id="btnApplySiswaFilter" class="btn btn-info btn-sm text-white w-100">Terapkan</button>
                </div>
            </div>

            <div id="siswaLocalInfo" class="small text-muted mt-2">Memuat data siswa lokal...</div>

            <div class="table-responsive mt-2" id="siswaLocalWrap">
                <table class="table table-bordered table-sm" id="siswaLocalTable">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>NIS</th>
                            <th>NISN</th>
                            <th>JK</th>
                            <th>Kelas</th>
                            <th>Peserta Didik ID</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                <button type="button" id="btnSiswaPrev" class="btn btn-outline-secondary btn-sm">Sebelumnya</button>
                <div id="siswaLocalPageInfo" class="small text-muted">Halaman -</div>
                <button type="button" id="btnSiswaNext" class="btn btn-outline-secondary btn-sm">Berikutnya</button>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-secondary">Discovery + Sinkron Relasi Ekskul per Siswa</h6>
            <span class="badge badge-warning">Eksperimen Endpoint</span>
        </div>
        <div class="card-body">
            <p class="mb-3 text-muted">
                Tombol di bawah akan mencoba beberapa endpoint kandidat e-Raport (berbasis sesi login), mengekstrak kemungkinan relasi <strong>siswa -> ekskul</strong>, lalu menyimpan ke tabel <strong>tbl_ekskul_siswa_eraport</strong>.
            </p>
            <p class="mb-2 small text-muted">
                Mode yang dipakai: <strong>Deep Probe</strong> (lebih banyak endpoint + sampel siswa lebih besar) dan hasil kandidat disimpan ke log lokal.
            </p>

            <button type="button" id="btnSyncEraportEkskulSiswa" class="btn btn-warning text-dark">
                <i class="fas fa-search mr-1"></i>Discovery & Sinkron Relasi Siswa-Ekskul
            </button>
            <span id="syncSiswaLoading" class="ml-2 text-muted" style="display:none;">Memproses discovery...</span>

            <hr>

            <div id="syncSiswaResult" class="d-none"></div>

            <div class="table-responsive mt-3 d-none" id="syncSiswaPreviewWrap">
                <table class="table table-bordered table-sm" id="syncSiswaPreviewTable">
                    <thead class="thead-light">
                        <tr>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Nama Ekskul</th>
                            <th>Sumber Endpoint</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="table-responsive mt-3 d-none" id="syncSiswaCandidateWrap">
                <div class="small text-muted mb-2">Endpoint kandidat yang terdeteksi:</div>
                <table class="table table-bordered table-sm" id="syncSiswaCandidateTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Keyword</th>
                            <th>Relations</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <hr>

            <div class="row align-items-end" style="gap:0;">
                <div class="col-md-5 mb-2">
                    <label class="small text-muted mb-1">Run ID Log Discovery</label>
                    <input type="text" id="discoveryRunId" class="form-control form-control-sm" placeholder="Kosongkan untuk log terbaru">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Limit</label>
                    <input type="number" id="discoveryLimit" class="form-control form-control-sm" value="150" min="1" max="500">
                </div>
                <div class="col-md-4 mb-2">
                    <button type="button" id="btnLoadDiscoveryLog" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list mr-1"></i>Muat Log Discovery
                    </button>
                </div>
            </div>

            <div id="discoveryLogInfo" class="small text-muted mt-2"></div>

            <div class="table-responsive mt-2 d-none" id="discoveryLogWrap">
                <table class="table table-bordered table-sm" id="discoveryLogTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Keyword</th>
                            <th>Relations</th>
                            <th>Preview</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <hr>

            <h6 class="mb-2 font-weight-bold text-success">Data Siswa yang Mengikuti Setiap Ekstrakurikuler</h6>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Cari nama / NIS / nama ekskul</label>
                    <input type="text" id="filterEkskulSiswaQ" class="form-control form-control-sm" placeholder="Ketik kata kunci...">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Filter Ekstrakurikuler</label>
                    <select id="filterEkskulSiswaEkskul" class="form-control form-control-sm">
                        <option value="">Semua ekstrakurikuler</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Filter Kelas</label>
                    <select id="filterEkskulSiswaKelas" class="form-control form-control-sm">
                        <option value="">Semua kelas</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Per Halaman</label>
                    <select id="filterEkskulSiswaPerPage" class="form-control form-control-sm">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" id="btnApplyEkskulSiswaFilter" class="btn btn-success btn-sm w-100">Terapkan</button>
                </div>
            </div>

            <div id="ekskulSiswaInfo" class="small text-muted mt-2">Memuat data siswa-ekskul...</div>

            <div class="table-responsive mt-2" id="ekskulSiswaWrap">
                <table class="table table-bordered table-sm" id="ekskulSiswaTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama Ekskul</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Sumber Endpoint</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                <button type="button" id="btnEkskulSiswaPrev" class="btn btn-outline-secondary btn-sm">Sebelumnya</button>
                <div id="ekskulSiswaPageInfo" class="small text-muted">Halaman -</div>
                <button type="button" id="btnEkskulSiswaNext" class="btn btn-outline-secondary btn-sm">Berikutnya</button>
            </div>

            <div class="table-responsive mt-3 d-none" id="ekskulGroupWrap">
                <div class="small text-muted mb-2">Ringkasan jumlah siswa per ekstrakurikuler:</div>
                <table class="table table-bordered table-sm" id="ekskulGroupTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Ekstrakurikuler</th>
                            <th>Total Siswa</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <hr>

            <h6 class="mb-2 font-weight-bold text-primary">Generator Relasi Manual (Ekskul -> Kelas)</h6>
            <p class="small text-muted mb-2">
                Gunakan ini jika discovery e-Raport tidak menghasilkan relasi siswa-ekskul.
                Buat pasangan <strong>ekskul</strong> dan <strong>kelas</strong>, lalu klik generate.
            </p>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Ekskul</label>
                    <select id="manualEkskulSelect" class="form-control form-control-sm">
                        <option value="">Pilih ekskul</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Kelas</label>
                    <select id="manualKelasSelect" class="form-control form-control-sm">
                        <option value="">Pilih kelas</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end" style="gap:8px;">
                    <button type="button" id="btnLoadManualOptions" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-sync-alt mr-1"></i>Muat Opsi
                    </button>
                    <button type="button" id="btnAddManualMapping" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i>Tambah Mapping
                    </button>
                </div>
            </div>

            <div id="manualMappingInfo" class="small text-muted mb-2">Belum ada mapping manual.</div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="manualMappingTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Ekskul</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="mt-2" style="gap:8px; display:flex; flex-wrap:wrap;">
                <button type="button" id="btnRunManualMapping" class="btn btn-success btn-sm">
                    <i class="fas fa-play mr-1"></i>Generate Relasi Dari Mapping
                </button>
                <button type="button" id="btnClearManualMapping" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-trash mr-1"></i>Hapus Semua Mapping
                </button>
            </div>

            <div id="manualMappingResult" class="mt-3 d-none"></div>
        </div>
    </div>
</div>

<script>
    (function() {
        var btn = document.getElementById('btnSyncEraportEkskul');
        var loading = document.getElementById('syncLoading');
        var resultEl = document.getElementById('syncResult');
        var previewWrap = document.getElementById('syncPreviewWrap');
        var previewBody = document.querySelector('#syncPreviewTable tbody');
        var btnSiswaList = document.getElementById('btnSyncEraportSiswa');
        var loadingSiswaList = document.getElementById('syncSiswaListLoading');
        var resultSiswaListEl = document.getElementById('syncSiswaListResult');
        var previewSiswaListWrap = document.getElementById('syncSiswaListPreviewWrap');
        var previewSiswaListBody = document.querySelector('#syncSiswaListPreviewTable tbody');
        var btnExportSiswaCsv = document.getElementById('btnExportSiswaCsv');
        var btnReloadSiswaLocal = document.getElementById('btnReloadSiswaLocal');
        var filterSiswaQ = document.getElementById('filterSiswaQ');
        var filterSiswaKelas = document.getElementById('filterSiswaKelas');
        var filterSiswaJk = document.getElementById('filterSiswaJk');
        var filterSiswaPerPage = document.getElementById('filterSiswaPerPage');
        var btnApplySiswaFilter = document.getElementById('btnApplySiswaFilter');
        var siswaLocalInfo = document.getElementById('siswaLocalInfo');
        var siswaLocalBody = document.querySelector('#siswaLocalTable tbody');
        var btnSiswaPrev = document.getElementById('btnSiswaPrev');
        var btnSiswaNext = document.getElementById('btnSiswaNext');
        var siswaLocalPageInfo = document.getElementById('siswaLocalPageInfo');
        var btnSiswa = document.getElementById('btnSyncEraportEkskulSiswa');
        var loadingSiswa = document.getElementById('syncSiswaLoading');
        var resultSiswaEl = document.getElementById('syncSiswaResult');
        var previewSiswaWrap = document.getElementById('syncSiswaPreviewWrap');
        var previewSiswaBody = document.querySelector('#syncSiswaPreviewTable tbody');
        var candidateWrap = document.getElementById('syncSiswaCandidateWrap');
        var candidateBody = document.querySelector('#syncSiswaCandidateTable tbody');
        var btnLoadDiscoveryLog = document.getElementById('btnLoadDiscoveryLog');
        var discoveryRunIdInput = document.getElementById('discoveryRunId');
        var discoveryLimitInput = document.getElementById('discoveryLimit');
        var discoveryLogInfo = document.getElementById('discoveryLogInfo');
        var discoveryLogWrap = document.getElementById('discoveryLogWrap');
        var discoveryLogBody = document.querySelector('#discoveryLogTable tbody');
        var filterEkskulSiswaQ = document.getElementById('filterEkskulSiswaQ');
        var filterEkskulSiswaEkskul = document.getElementById('filterEkskulSiswaEkskul');
        var filterEkskulSiswaKelas = document.getElementById('filterEkskulSiswaKelas');
        var filterEkskulSiswaPerPage = document.getElementById('filterEkskulSiswaPerPage');
        var btnApplyEkskulSiswaFilter = document.getElementById('btnApplyEkskulSiswaFilter');
        var ekskulSiswaInfo = document.getElementById('ekskulSiswaInfo');
        var ekskulSiswaBody = document.querySelector('#ekskulSiswaTable tbody');
        var btnEkskulSiswaPrev = document.getElementById('btnEkskulSiswaPrev');
        var btnEkskulSiswaNext = document.getElementById('btnEkskulSiswaNext');
        var ekskulSiswaPageInfo = document.getElementById('ekskulSiswaPageInfo');
        var ekskulGroupWrap = document.getElementById('ekskulGroupWrap');
        var ekskulGroupBody = document.querySelector('#ekskulGroupTable tbody');
        var manualEkskulSelect = document.getElementById('manualEkskulSelect');
        var manualKelasSelect = document.getElementById('manualKelasSelect');
        var btnLoadManualOptions = document.getElementById('btnLoadManualOptions');
        var btnAddManualMapping = document.getElementById('btnAddManualMapping');
        var btnRunManualMapping = document.getElementById('btnRunManualMapping');
        var btnClearManualMapping = document.getElementById('btnClearManualMapping');
        var manualMappingInfo = document.getElementById('manualMappingInfo');
        var manualMappingBody = document.querySelector('#manualMappingTable tbody');
        var manualMappingResult = document.getElementById('manualMappingResult');
        var siswaLocalState = {
            page: 1,
            perPage: 25,
            totalPages: 1
        };
        var ekskulSiswaState = {
            page: 1,
            perPage: 25,
            totalPages: 1
        };
        var manualMappings = [];

        function escapeHtml(v) {
            return String(v === null || v === undefined ? '' : v)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function setResult(type, html) {
            resultEl.className = 'alert alert-' + type;
            resultEl.innerHTML = html;
            resultEl.classList.remove('d-none');
        }

        function setResultSiswaList(type, html) {
            resultSiswaListEl.className = 'alert alert-' + type;
            resultSiswaListEl.innerHTML = html;
            resultSiswaListEl.classList.remove('d-none');
        }

        function setResultSiswa(type, html) {
            resultSiswaEl.className = 'alert alert-' + type;
            resultSiswaEl.innerHTML = html;
            resultSiswaEl.classList.remove('d-none');
        }

        function fillSelectOptions(selectEl, options, selectedValue, defaultLabel) {
            var html = '<option value="">' + escapeHtml(defaultLabel) + '</option>';
            (Array.isArray(options) ? options : []).forEach(function(opt) {
                var selected = String(opt) === String(selectedValue) ? ' selected' : '';
                html += '<option value="' + escapeHtml(opt) + '"' + selected + '>' + escapeHtml(opt) + '</option>';
            });
            selectEl.innerHTML = html;
        }

        function setManualMappingResult(type, html) {
            manualMappingResult.className = 'alert alert-' + type + ' mt-3';
            manualMappingResult.innerHTML = html;
            manualMappingResult.classList.remove('d-none');
        }

        function renderManualMappings() {
            manualMappingBody.innerHTML = '';
            if (!manualMappings.length) {
                manualMappingInfo.textContent = 'Belum ada mapping manual.';
                return;
            }

            manualMappingInfo.textContent = 'Total mapping: ' + manualMappings.length;
            manualMappings.forEach(function(m, idx) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + escapeHtml(m.nama_ekskul || '') + '</td>' +
                    '<td>' + escapeHtml(m.kelas || '') + '</td>' +
                    '<td><button type="button" class="btn btn-outline-danger btn-sm" data-remove-manual="' + idx + '">Hapus</button></td>';
                manualMappingBody.appendChild(tr);
            });
        }

        function loadManualOptions() {
            btnLoadManualOptions.disabled = true;
            fetch('api/eraport-ekskul-kelas-options.php', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        var msg = data && data.message ? data.message : 'Gagal memuat opsi mapping manual.';
                        setManualMappingResult('danger', '<strong>Gagal:</strong> ' + escapeHtml(msg));
                        return;
                    }

                    var opt = data.options || {};
                    fillSelectOptions(manualEkskulSelect, opt.ekskul || [], '', 'Pilih ekskul');
                    fillSelectOptions(manualKelasSelect, opt.kelas || [], '', 'Pilih kelas');
                    setManualMappingResult('info', 'Opsi berhasil dimuat. Pilih ekskul + kelas lalu tambah mapping.');
                })
                .catch(function(err) {
                    setManualMappingResult('danger', '<strong>Error:</strong> ' + escapeHtml(err && err.message ? err.message : err));
                })
                .finally(function() {
                    btnLoadManualOptions.disabled = false;
                });
        }

        function loadEkskulSiswa(page) {
            if (!page || page < 1) {
                page = 1;
            }

            ekskulSiswaInfo.textContent = 'Memuat data siswa-ekskul...';
            ekskulSiswaBody.innerHTML = '';
            ekskulGroupBody.innerHTML = '';
            ekskulGroupWrap.classList.add('d-none');
            btnEkskulSiswaPrev.disabled = true;
            btnEkskulSiswaNext.disabled = true;

            var q = (filterEkskulSiswaQ.value || '').trim();
            var ekskul = (filterEkskulSiswaEkskul.value || '').trim();
            var kelas = (filterEkskulSiswaKelas.value || '').trim();
            var perPage = parseInt(filterEkskulSiswaPerPage.value || '25', 10);
            if (!perPage || perPage < 1) {
                perPage = 25;
            }

            var qs = '?page=' + encodeURIComponent(String(page)) +
                '&per_page=' + encodeURIComponent(String(perPage)) +
                '&q=' + encodeURIComponent(q) +
                '&ekskul=' + encodeURIComponent(ekskul) +
                '&kelas=' + encodeURIComponent(kelas);

            fetch('api/eraport-list-ekskul-siswa.php' + qs, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        var msg = data && data.message ? data.message : 'Gagal memuat data siswa-ekskul.';
                        ekskulSiswaInfo.textContent = msg;
                        ekskulSiswaPageInfo.textContent = 'Halaman -';
                        return;
                    }

                    var pag = data.pagination || {};
                    var rows = Array.isArray(data.rows) ? data.rows : [];
                    var opt = data.options || {};
                    var groups = Array.isArray(data.groups) ? data.groups : [];

                    ekskulSiswaState.page = parseInt(pag.page || 1, 10);
                    ekskulSiswaState.perPage = parseInt(pag.per_page || perPage, 10);
                    ekskulSiswaState.totalPages = parseInt(pag.total_pages || 1, 10);

                    fillSelectOptions(filterEkskulSiswaEkskul, opt.ekskul || [], data.filters ? data.filters.ekskul : '', 'Semua ekstrakurikuler');
                    fillSelectOptions(filterEkskulSiswaKelas, opt.kelas || [], data.filters ? data.filters.kelas : '', 'Semua kelas');

                    if (rows.length === 0) {
                        var dg = data.diagnostic || {};
                        ekskulSiswaInfo.textContent =
                            'Data siswa-ekskul belum tersedia. ' +
                            'Relasi: ' + escapeHtml(dg.relasi_total || 0) +
                            ', Siswa lokal: ' + escapeHtml(dg.siswa_total || 0) +
                            ', Master ekskul: ' + escapeHtml(dg.ekskul_master_total || 0) +
                            ', Log discovery: ' + escapeHtml(dg.log_discovery_total || 0) +
                            '. Jalankan tombol "Discovery & Sinkron Relasi Siswa-Ekskul".';
                    } else {
                        ekskulSiswaInfo.textContent =
                            'Total relasi: ' + escapeHtml(pag.total || 0) +
                            ' | Menampilkan: ' + escapeHtml(rows.length) +
                            ' baris';
                    }

                    rows.forEach(function(row) {
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + escapeHtml(row.nama_ekskul || '') + '</td>' +
                            '<td>' + escapeHtml(row.nis || '') + '</td>' +
                            '<td>' + escapeHtml(row.nama_siswa || '') + '</td>' +
                            '<td>' + escapeHtml(row.kelas_siswa || '') + '</td>' +
                            '<td>' + escapeHtml(row.sumber_endpoint || '') + '</td>' +
                            '<td>' + escapeHtml(row.synced_at || '') + '</td>';
                        ekskulSiswaBody.appendChild(tr);
                    });

                    if (groups.length > 0) {
                        groups.forEach(function(g) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escapeHtml(g.nama_ekskul || '') + '</td>' +
                                '<td>' + escapeHtml(g.total_siswa || 0) + '</td>';
                            ekskulGroupBody.appendChild(tr);
                        });
                        ekskulGroupWrap.classList.remove('d-none');
                    }

                    ekskulSiswaPageInfo.textContent =
                        'Halaman ' + escapeHtml(pag.page || 1) +
                        ' / ' + escapeHtml(pag.total_pages || 1);

                    btnEkskulSiswaPrev.disabled = !(pag.has_prev);
                    btnEkskulSiswaNext.disabled = !(pag.has_next);
                })
                .catch(function(err) {
                    ekskulSiswaInfo.textContent = 'Error: ' + (err && err.message ? err.message : err);
                    ekskulSiswaPageInfo.textContent = 'Halaman -';
                });
        }

        function loadSiswaLocal(page) {
            if (!page || page < 1) {
                page = 1;
            }

            siswaLocalInfo.textContent = 'Memuat data siswa lokal...';
            siswaLocalBody.innerHTML = '';
            btnSiswaPrev.disabled = true;
            btnSiswaNext.disabled = true;

            var q = (filterSiswaQ.value || '').trim();
            var kelas = (filterSiswaKelas.value || '').trim();
            var jk = (filterSiswaJk.value || '').trim();
            var perPage = parseInt(filterSiswaPerPage.value || '25', 10);
            if (!perPage || perPage < 1) {
                perPage = 25;
            }

            var qs = '?page=' + encodeURIComponent(String(page)) +
                '&per_page=' + encodeURIComponent(String(perPage)) +
                '&q=' + encodeURIComponent(q) +
                '&kelas=' + encodeURIComponent(kelas) +
                '&jk=' + encodeURIComponent(jk);

            fetch('api/eraport-list-siswa.php' + qs, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        var msg = data && data.message ? data.message : 'Gagal memuat data siswa lokal.';
                        siswaLocalInfo.textContent = msg;
                        siswaLocalPageInfo.textContent = 'Halaman -';
                        return;
                    }

                    var pag = data.pagination || {};
                    var rows = Array.isArray(data.rows) ? data.rows : [];
                    var opt = data.options || {};

                    siswaLocalState.page = parseInt(pag.page || 1, 10);
                    siswaLocalState.perPage = parseInt(pag.per_page || perPage, 10);
                    siswaLocalState.totalPages = parseInt(pag.total_pages || 1, 10);

                    fillSelectOptions(filterSiswaKelas, opt.kelas || [], data.filters ? data.filters.kelas : '', 'Semua kelas');
                    fillSelectOptions(filterSiswaJk, opt.jk || [], data.filters ? data.filters.jk : '', 'Semua');

                    if (rows.length === 0) {
                        siswaLocalInfo.textContent = 'Data tidak ditemukan untuk filter saat ini.';
                    } else {
                        siswaLocalInfo.textContent =
                            'Total: ' + escapeHtml(pag.total || 0) +
                            ' siswa | Menampilkan: ' + escapeHtml(rows.length) +
                            ' baris';
                    }

                    rows.forEach(function(row) {
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + escapeHtml(row.source_no || '') + '</td>' +
                            '<td>' + escapeHtml(row.nama_siswa || '') + '</td>' +
                            '<td>' + escapeHtml(row.nis || '') + '</td>' +
                            '<td>' + escapeHtml(row.nisn || '') + '</td>' +
                            '<td>' + escapeHtml(row.jenis_kelamin || '') + '</td>' +
                            '<td>' + escapeHtml(row.kelas || '') + '</td>' +
                            '<td>' + escapeHtml(row.peserta_didik_id || '') + '</td>' +
                            '<td>' + escapeHtml(row.synced_at || '') + '</td>';
                        siswaLocalBody.appendChild(tr);
                    });

                    siswaLocalPageInfo.textContent =
                        'Halaman ' + escapeHtml(pag.page || 1) +
                        ' / ' + escapeHtml(pag.total_pages || 1);

                    btnSiswaPrev.disabled = !(pag.has_prev);
                    btnSiswaNext.disabled = !(pag.has_next);
                })
                .catch(function(err) {
                    siswaLocalInfo.textContent = 'Error: ' + (err && err.message ? err.message : err);
                    siswaLocalPageInfo.textContent = 'Halaman -';
                });
        }

        btn.addEventListener('click', function() {
            btn.disabled = true;
            loading.style.display = 'inline';
            resultEl.classList.add('d-none');
            previewWrap.classList.add('d-none');
            previewBody.innerHTML = '';

            fetch('api/eraport-sync-ekskul.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        var msg = data && data.message ? data.message : 'Sinkron gagal.';
                        setResult('danger', '<strong>Gagal:</strong> ' + escapeHtml(msg));
                        return;
                    }

                    var summary = data.summary || {};
                    setResult(
                        'success',
                        '<strong>Sinkron berhasil.</strong><br>' +
                        'Fetched: <strong>' + escapeHtml(summary.fetched || 0) + '</strong> | ' +
                        'Inserted: <strong>' + escapeHtml(summary.inserted || 0) + '</strong> | ' +
                        'Updated: <strong>' + escapeHtml(summary.updated || 0) + '</strong> | ' +
                        'Errors: <strong>' + escapeHtml(summary.errors || 0) + '</strong>'
                    );

                    var preview = Array.isArray(data.preview) ? data.preview : [];
                    if (preview.length > 0) {
                        preview.forEach(function(row) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escapeHtml(row.source_no || '') + '</td>' +
                                '<td>' + escapeHtml(row.nama_kelas_ekskul || '') + '</td>' +
                                '<td>' + escapeHtml(row.jenis_ekskul || '') + '</td>' +
                                '<td>' + escapeHtml(row.nama_ekskul || '') + '</td>' +
                                '<td>' + escapeHtml(row.synced_at || '') + '</td>';
                            previewBody.appendChild(tr);
                        });
                        previewWrap.classList.remove('d-none');
                    }
                })
                .catch(function(err) {
                    setResult('danger', '<strong>Error:</strong> ' + escapeHtml(err && err.message ? err.message : err));
                })
                .finally(function() {
                    btn.disabled = false;
                    loading.style.display = 'none';
                });
        });

        btnSiswaList.addEventListener('click', function() {
            btnSiswaList.disabled = true;
            loadingSiswaList.style.display = 'inline';
            resultSiswaListEl.classList.add('d-none');
            previewSiswaListWrap.classList.add('d-none');
            previewSiswaListBody.innerHTML = '';

            fetch('api/eraport-sync-siswa.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        var msg = data && data.message ? data.message : 'Sinkron daftar siswa gagal.';
                        setResultSiswaList('danger', '<strong>Gagal:</strong> ' + escapeHtml(msg));
                        return;
                    }

                    var summary = data.summary || {};
                    setResultSiswaList(
                        'success',
                        '<strong>Sinkron daftar siswa berhasil.</strong><br>' +
                        'Fetched: <strong>' + escapeHtml(summary.fetched || 0) + '</strong> | ' +
                        'Inserted: <strong>' + escapeHtml(summary.inserted || 0) + '</strong> | ' +
                        'Updated: <strong>' + escapeHtml(summary.updated || 0) + '</strong> | ' +
                        'Errors: <strong>' + escapeHtml(summary.errors || 0) + '</strong>'
                    );

                    var preview = Array.isArray(data.preview) ? data.preview : [];
                    if (preview.length > 0) {
                        preview.forEach(function(row) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escapeHtml(row.source_no || '') + '</td>' +
                                '<td>' + escapeHtml(row.nama_siswa || '') + '</td>' +
                                '<td>' + escapeHtml(row.nis || '') + '</td>' +
                                '<td>' + escapeHtml(row.nisn || '') + '</td>' +
                                '<td>' + escapeHtml(row.kelas || '') + '</td>' +
                                '<td>' + escapeHtml(row.peserta_didik_id || '') + '</td>' +
                                '<td>' + escapeHtml(row.synced_at || '') + '</td>';
                            previewSiswaListBody.appendChild(tr);
                        });
                        previewSiswaListWrap.classList.remove('d-none');
                    }
                })
                .catch(function(err) {
                    setResultSiswaList('danger', '<strong>Error:</strong> ' + escapeHtml(err && err.message ? err.message : err));
                })
                .finally(function() {
                    btnSiswaList.disabled = false;
                    loadingSiswaList.style.display = 'none';
                    loadSiswaLocal(1);
                });
        });

        btnApplySiswaFilter.addEventListener('click', function() {
            loadSiswaLocal(1);
        });

        filterSiswaQ.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                loadSiswaLocal(1);
            }
        });

        filterSiswaPerPage.addEventListener('change', function() {
            loadSiswaLocal(1);
        });

        btnReloadSiswaLocal.addEventListener('click', function() {
            loadSiswaLocal(siswaLocalState.page || 1);
        });

        btnExportSiswaCsv.addEventListener('click', function() {
            var q = (filterSiswaQ.value || '').trim();
            var kelas = (filterSiswaKelas.value || '').trim();
            var jk = (filterSiswaJk.value || '').trim();

            var qs = '?q=' + encodeURIComponent(q) +
                '&kelas=' + encodeURIComponent(kelas) +
                '&jk=' + encodeURIComponent(jk);

            window.open('api/eraport-export-siswa-csv.php' + qs, '_blank');
        });

        btnSiswaPrev.addEventListener('click', function() {
            var targetPage = (siswaLocalState.page || 1) - 1;
            if (targetPage < 1) {
                targetPage = 1;
            }
            loadSiswaLocal(targetPage);
        });

        btnSiswaNext.addEventListener('click', function() {
            var targetPage = (siswaLocalState.page || 1) + 1;
            if (targetPage > (siswaLocalState.totalPages || 1)) {
                targetPage = siswaLocalState.totalPages || 1;
            }
            loadSiswaLocal(targetPage);
        });

        btnSiswa.addEventListener('click', function() {
            btnSiswa.disabled = true;
            loadingSiswa.style.display = 'inline';
            resultSiswaEl.classList.add('d-none');
            previewSiswaWrap.classList.add('d-none');
            candidateWrap.classList.add('d-none');
            previewSiswaBody.innerHTML = '';
            candidateBody.innerHTML = '';

            fetch('api/eraport-sync-ekskul-siswa.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    var summary = data && data.summary ? data.summary : {};
                    var msg = data && data.message ? data.message : 'Discovery selesai.';
                    var alertType = data && data.success ? 'success' : 'warning';

                    setResultSiswa(
                        alertType,
                        '<strong>' + escapeHtml(msg) + '</strong><br>' +
                        'Endpoints checked: <strong>' + escapeHtml(summary.endpoint_checked || 0) + '</strong> | ' +
                        'Deep probe: <strong>' + escapeHtml(summary.deep_probe ? 'ya' : 'tidak') + '</strong> | ' +
                        'Relations found: <strong>' + escapeHtml(summary.relations_found || 0) + '</strong> | ' +
                        'Fallback kelas-generated: <strong>' + escapeHtml(summary.fallback_generated || 0) + '</strong> | ' +
                        'Inserted: <strong>' + escapeHtml(summary.inserted || 0) + '</strong> | ' +
                        'Updated: <strong>' + escapeHtml(summary.updated || 0) + '</strong><br>' +
                        'Candidate logs saved: <strong>' + escapeHtml(summary.candidate_logs_saved || 0) + '</strong> | ' +
                        'Run ID: <strong>' + escapeHtml(summary.candidate_run_id || '-') + '</strong>'
                    );

                    if (summary && summary.candidate_run_id) {
                        discoveryRunIdInput.value = summary.candidate_run_id;
                    }

                    var preview = Array.isArray(data.preview) ? data.preview : [];
                    if (preview.length > 0) {
                        preview.forEach(function(row) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escapeHtml(row.nis || '') + '</td>' +
                                '<td>' + escapeHtml(row.nama_siswa || '') + '</td>' +
                                '<td>' + escapeHtml(row.kelas_siswa || '') + '</td>' +
                                '<td>' + escapeHtml(row.nama_ekskul || '') + '</td>' +
                                '<td>' + escapeHtml(row.sumber_endpoint || '') + '</td>' +
                                '<td>' + escapeHtml(row.synced_at || '') + '</td>';
                            previewSiswaBody.appendChild(tr);
                        });
                        previewSiswaWrap.classList.remove('d-none');
                    }

                    var candidates = Array.isArray(data.candidates) ? data.candidates : [];
                    if (candidates.length > 0) {
                        candidates.forEach(function(item) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escapeHtml(item.endpoint || '') + '</td>' +
                                '<td>' + escapeHtml(item.method || 'GET') + '</td>' +
                                '<td>' + escapeHtml(item.status_code || '') + '</td>' +
                                '<td>' + escapeHtml(item.has_keyword ? 'ya' : 'tidak') + '</td>' +
                                '<td>' + escapeHtml(item.relations_found || 0) + '</td>';
                            candidateBody.appendChild(tr);
                        });
                        candidateWrap.classList.remove('d-none');
                    }
                })
                .catch(function(err) {
                    setResultSiswa('danger', '<strong>Error:</strong> ' + escapeHtml(err && err.message ? err.message : err));
                })
                .finally(function() {
                    btnSiswa.disabled = false;
                    loadingSiswa.style.display = 'none';
                    loadEkskulSiswa(1);
                });
        });

        btnLoadManualOptions.addEventListener('click', function() {
            loadManualOptions();
        });

        btnAddManualMapping.addEventListener('click', function() {
            var namaEkskul = (manualEkskulSelect.value || '').trim();
            var kelas = (manualKelasSelect.value || '').trim();
            if (!namaEkskul || !kelas) {
                setManualMappingResult('warning', 'Pilih ekskul dan kelas terlebih dahulu.');
                return;
            }

            var exists = manualMappings.some(function(m) {
                return String(m.nama_ekskul).toLowerCase() === namaEkskul.toLowerCase() &&
                    String(m.kelas).toLowerCase() === kelas.toLowerCase();
            });
            if (exists) {
                setManualMappingResult('warning', 'Mapping tersebut sudah ada.');
                return;
            }

            manualMappings.push({
                nama_ekskul: namaEkskul,
                kelas: kelas
            });
            renderManualMappings();
        });

        manualMappingBody.addEventListener('click', function(e) {
            var target = e.target;
            if (!target || !target.getAttribute) {
                return;
            }
            var idxRaw = target.getAttribute('data-remove-manual');
            if (idxRaw === null) {
                return;
            }
            var idx = parseInt(idxRaw, 10);
            if (!Number.isInteger(idx) || idx < 0 || idx >= manualMappings.length) {
                return;
            }
            manualMappings.splice(idx, 1);
            renderManualMappings();
        });

        btnClearManualMapping.addEventListener('click', function() {
            manualMappings = [];
            renderManualMappings();
            manualMappingResult.classList.add('d-none');
        });

        btnRunManualMapping.addEventListener('click', function() {
            if (!manualMappings.length) {
                setManualMappingResult('warning', 'Belum ada mapping. Tambahkan dulu minimal satu mapping.');
                return;
            }

            btnRunManualMapping.disabled = true;
            setManualMappingResult('info', 'Memproses generate relasi manual...');

            var formData = new FormData();
            formData.append('mappings', JSON.stringify(manualMappings));

            fetch('api/eraport-seed-ekskul-siswa-manual.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        var msg = data && data.message ? data.message : 'Generate relasi manual gagal.';
                        setManualMappingResult('danger', '<strong>Gagal:</strong> ' + escapeHtml(msg));
                        return;
                    }

                    var summary = data.summary || {};
                    setManualMappingResult(
                        'success',
                        '<strong>Generate relasi manual berhasil.</strong><br>' +
                        'Mapping: <strong>' + escapeHtml(summary.mapping_count || 0) + '</strong> | ' +
                        'Affected siswa rows: <strong>' + escapeHtml(summary.affected_siswa_rows || 0) + '</strong> | ' +
                        'Inserted: <strong>' + escapeHtml(summary.inserted || 0) + '</strong> | ' +
                        'Updated: <strong>' + escapeHtml(summary.updated || 0) + '</strong> | ' +
                        'Errors: <strong>' + escapeHtml(summary.errors || 0) + '</strong>'
                    );

                    loadEkskulSiswa(1);
                })
                .catch(function(err) {
                    setManualMappingResult('danger', '<strong>Error:</strong> ' + escapeHtml(err && err.message ? err.message : err));
                })
                .finally(function() {
                    btnRunManualMapping.disabled = false;
                });
        });

        btnApplyEkskulSiswaFilter.addEventListener('click', function() {
            loadEkskulSiswa(1);
        });

        filterEkskulSiswaQ.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                loadEkskulSiswa(1);
            }
        });

        filterEkskulSiswaPerPage.addEventListener('change', function() {
            loadEkskulSiswa(1);
        });

        btnEkskulSiswaPrev.addEventListener('click', function() {
            var targetPage = (ekskulSiswaState.page || 1) - 1;
            if (targetPage < 1) {
                targetPage = 1;
            }
            loadEkskulSiswa(targetPage);
        });

        btnEkskulSiswaNext.addEventListener('click', function() {
            var targetPage = (ekskulSiswaState.page || 1) + 1;
            if (targetPage > (ekskulSiswaState.totalPages || 1)) {
                targetPage = ekskulSiswaState.totalPages || 1;
            }
            loadEkskulSiswa(targetPage);
        });

        btnLoadDiscoveryLog.addEventListener('click', function() {
            btnLoadDiscoveryLog.disabled = true;
            discoveryLogWrap.classList.add('d-none');
            discoveryLogBody.innerHTML = '';
            discoveryLogInfo.textContent = 'Memuat log...';

            var runId = (discoveryRunIdInput.value || '').trim();
            var limit = parseInt(discoveryLimitInput.value || '150', 10);
            if (!limit || limit < 1) {
                limit = 150;
            }
            if (limit > 500) {
                limit = 500;
            }

            var qs = '?limit=' + encodeURIComponent(String(limit));
            if (runId) {
                qs += '&run_id=' + encodeURIComponent(runId);
            }

            fetch('api/eraport-discovery-log.php' + qs, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data || !data.success) {
                        discoveryLogInfo.textContent = 'Gagal memuat log discovery.';
                        return;
                    }

                    var selectedRun = data.selected_run_id || '-';
                    var rows = Array.isArray(data.rows) ? data.rows : [];
                    var runs = Array.isArray(data.runs) ? data.runs : [];

                    discoveryLogInfo.textContent = 'Run aktif: ' + selectedRun + ' | Rows: ' + rows.length + ' | Daftar run: ' + runs.length;

                    if (rows.length > 0) {
                        rows.forEach(function(row) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escapeHtml(row.endpoint || '') + '</td>' +
                                '<td>' + escapeHtml(row.method || '') + '</td>' +
                                '<td>' + escapeHtml(row.status_code || '') + '</td>' +
                                '<td>' + escapeHtml(String(row.has_keyword || '0') === '1' ? 'ya' : 'tidak') + '</td>' +
                                '<td>' + escapeHtml(row.relations_found || 0) + '</td>' +
                                '<td>' + escapeHtml(row.preview_text || '') + '</td>';
                            discoveryLogBody.appendChild(tr);
                        });
                        discoveryLogWrap.classList.remove('d-none');
                    }
                })
                .catch(function(err) {
                    discoveryLogInfo.textContent = 'Error: ' + (err && err.message ? err.message : err);
                })
                .finally(function() {
                    btnLoadDiscoveryLog.disabled = false;
                });
        });

        // Load the most recent discovery log on first render.
        btnLoadDiscoveryLog.click();
        loadSiswaLocal(1);
        loadEkskulSiswa(1);
        renderManualMappings();
        loadManualOptions();
    })();
</script>