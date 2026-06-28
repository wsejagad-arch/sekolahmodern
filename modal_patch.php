<!-- Modal Perangkat Ajar AI -->
<div class="modal fade" id="aiPerangkatModal" tabindex="-1" aria-labelledby="aiPerangkatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="aiPerangkatModalLabel"><i class="bi bi-stars text-primary"></i> Generate Perangkat Ajar (AI)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formModulAjar" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Mata Pelajaran</label>
                <input type="text" class="form-control" id="modul_mapel" placeholder="Contoh: Matematika" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Kelas</label>
                <select class="form-select" id="modul_kelas" required>
                    <?php foreach ($kelasAmpu as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Tahun Ajaran</label>
                <input type="text" class="form-control" id="modul_ta" placeholder="Contoh: 2025/2026" required value="2025/2026">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold">Materi Pokok / Bahasan</label>
                <input type="text" class="form-control" id="modul_materi" placeholder="Contoh: Sistem Persamaan Linear Dua Variabel" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Alokasi Waktu (JP)</label>
                <input type="text" class="form-control" id="modul_jp" placeholder="Contoh: 2 x 45 Menit" required value="2 x 45 Menit">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end mt-4">
                <button type="button" class="btn btn-outline-primary" onclick="generatePerangkatAI('atp')"><i class="bi bi-diagram-3"></i> Generate ATP</button>
                <button type="button" class="btn btn-primary" onclick="generatePerangkatAI('modul')"><i class="bi bi-file-earmark-text"></i> Generate Modul Ajar</button>
            </div>
        </form>
        
        <div id="ai-perangkat-loading" class="mt-4 text-center d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted" id="ai-perangkat-status-text">AI sedang menyusun perangkat ajar... (Mungkin butuh 10-20 detik)</p>
        </div>
        
        <div id="ai-perangkat-result" class="mt-4 d-none">
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> Berhasil disimpan ke Repository Perangkat Ajar!</div>
            <div id="perangkat-preview" class="border p-4 bg-white shadow-sm rounded-3 print-page" style="max-height: 400px; overflow-y: auto;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
