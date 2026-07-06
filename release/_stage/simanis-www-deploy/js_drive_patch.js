// Perangkat Drive Functions
window.loadPerangkatDrive = function() {
    $.getJSON('?ajax=perangkat_load_drive', function(res) {
        if(res.status === 'success') {
            let html = '';
            if(res.folders.length === 0) {
                html = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-folder-x fs-1"></i><p class="mt-2">Belum ada folder perangkat ajar. Klik Tambah Folder atau Generate Baru (AI).</p></div>';
            } else {
                res.folders.forEach(f => {
                    html += `
                        <div class="col-12 mb-2">
                            <div class="card border rounded-3 p-3" style="background:#f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-folder-fill text-warning me-2 fs-5"></i>${f.nama}</h6>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="sharePerangkatFolder(${f.id}, '${f.nama}')"><i class="bi bi-share"></i> Share Folder</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deletePerangkatFolder(${f.id}, '${f.nama}')"><i class="bi bi-trash"></i> Hapus Folder</button>
                                    </div>
                                </div>
                                <div class="row g-2">
                    `;
                    if(f.files.length === 0) {
                        html += `<div class="col-12 text-muted small ms-4">Belum ada file.</div>`;
                    } else {
                        f.files.forEach(file => {
                            html += `
                                <div class="col-md-4">
                                    <div class="card p-2 border rounded" style="background:#fff;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi ${file.tipe_dokumen==='modul'?'bi-file-earmark-text text-primary':'bi-diagram-3 text-success'} fs-4"></i>
                                            <div style="min-width:0; flex:1;">
                                                <h6 class="mb-0 text-truncate" style="font-size:13px;" title="${file.nama_file}">${file.nama_file}</h6>
                                                <small class="text-muted" style="font-size:11px;">${new Date(file.created_at).toLocaleDateString()}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 mt-2 justify-content-end">
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:12px;" onclick="sharePerangkatFile(${file.id}, '${file.tipe_dokumen}', '${file.nama_file}')"><i class="bi bi-share"></i> Share</button>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;" onclick="deletePerangkatFile(${file.id})"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    html += `</div></div></div>`;
                });
            }
            $('#perangkat-drive-container').html(html);
        }
    });
};

window.addPerangkatFolder = function() {
    const nama = prompt('Masukkan nama folder baru (misal: Kelas XA):');
    if(nama) {
        $.post('?ajax=perangkat_create_folder', {nama_folder: nama}, function(res) {
            if(res.status === 'success') {
                loadPerangkatDrive();
            } else {
                alert('Gagal: ' + res.message);
            }
        }, 'json');
    }
};

window.deletePerangkatFolder = function(id, nama) {
    if(confirm(`Yakin ingin menghapus folder "${nama}" beserta SEMUA file di dalamnya?`)) {
        $.post('?ajax=perangkat_delete_folder', {id: id}, function(res) {
            if(res.status === 'success') {
                loadPerangkatDrive();
            }
        }, 'json');
    }
};

window.deletePerangkatFile = function(id) {
    if(confirm(`Hapus file ini?`)) {
        $.post('?ajax=perangkat_delete_file', {id: id}, function(res) {
            if(res.status === 'success') {
                loadPerangkatDrive();
            }
        }, 'json');
    }
};

window.sharePerangkatFile = function(id, tipe, nama) {
    // Generate token if not exist
    $.post('?ajax=create_share', {
        tipe: 'perangkat_file',
        sumber_id: id,
        label: nama,
        data_json: '' // data is already in tbl_ekinerja_dokumen, but let's just use create_share which will just create link
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link share publik perangkat berhasil disalin ke clipboard!\n\n' + path);
        } else {
            alert('Gagal membuat link share');
        }
    }, 'json');
};

$(document).ready(function() {
    loadPerangkatDrive();
});

window.sharePerangkatFolder = function(id, nama) {
    $.post('?ajax=create_share', {
        tipe: 'perangkat_folder',
        sumber_id: id,
        label: 'Folder ' + nama,
        data_json: ''
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link share publik untuk folder berhasil disalin ke clipboard!\n\n' + path);
        } else {
            alert('Gagal membuat link share');
        }
    }, 'json');
};
