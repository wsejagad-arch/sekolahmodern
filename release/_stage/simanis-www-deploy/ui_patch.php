                    <!-- Modul Ajar (Drive Style) -->
                    <div class="tab-pane fade show active" id="sub-modul">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Repository Perangkat Ajar</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" onclick="addPerangkatFolder()"><i class="bi bi-folder-plus"></i> Tambah Folder</button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#aiPerangkatModal"><i class="bi bi-stars"></i> Generate Baru (AI)</button>
                            </div>
                        </div>
                        
                        <div id="perangkat-drive-container" class="row g-3">
                            <!-- Folders and files loaded here via AJAX -->
                            <div class="col-12 text-center text-muted py-5">
                                <div class="spinner-border text-secondary" role="status"></div>
                                <p class="mt-2">Memuat repositori...</p>
                            </div>
                        </div>
                    </div>
