                    
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold mb-3">Ceklis Administrasi Guru:</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_silabus" checked>
                                    <label class="form-check-label" for="check_silabus">Silabus / ATP</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_rpp" checked>
                                    <label class="form-check-label" for="check_rpp">RPP / Modul Ajar</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_prota" checked>
                                    <label class="form-check-label" for="check_prota">Program Tahunan (Prota)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_promes" checked>
                                    <label class="form-check-label" for="check_promes">Program Semester (Promes)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_kkm" checked>
                                    <label class="form-check-label" for="check_kkm">Kriteria Ketercapaian (KKTP/KKM)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_absen" checked>
                                    <label class="form-check-label" for="check_absen">Buku Presensi Siswa</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <label class="form-label fw-semibold">Catatan Temuan / Rekomendasi Supervisi</label>
                        <textarea class="form-control" id="super_catatan" rows="3" placeholder="Contoh: Pembelajaran berjalan kondusif, pemanfaatan media ajar IT perlu ditingkatkan kembali."></textarea>
                    </div>
                    
                    <div class="col-md-12 mt-3">
                        <label class="form-label fw-semibold">Unggah Bukti Foto Supervisi</label>
                        <input type="file" class="form-control" id="super_foto" accept="image/*">
                    </div>
                    
                    <div class="col-12 text-end mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="shareSupervisi()"><i class="bi bi-share"></i> Copy Link</button>
                        <button type="button" class="btn btn-custom" onclick="generateSupervisiReport()"><i class="bi bi-printer"></i> Cetak Laporan Supervisi</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    
    <!-- DYNAMIC PRINTABLE PAGES -->
    <div id="print-area"></div>

</div>

<!-- MODALS -->
<!-- Create Folder Modal -->
<div class="modal fade no-print" id="modalBuatFolder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <form method="post">
                <input type="hidden" name="action_sertifikat" value="buat_folder">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Buat Folder Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_folder" class="form-label fw-semibold">Nama Folder</label>
                        <input type="text" class="form-control" id="nama_folder" name="nama_folder" placeholder="Contoh: Pelatihan Mandiri PMM" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary); border:none;">Buat Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Certificate Modal -->
<div class="modal fade no-print" id="modalUploadCert" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action_sertifikat" value="upload">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Sertifikat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folder_dest" class="form-label fw-semibold">Destinasi Folder</label>
                        <select class="form-select" id="folder_dest" name="folder_name">
                            <?php foreach ($folders as $f): ?>
                                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file_sertifikat" class="form-label fw-semibold">File Berkas (PDF/Gambar)</label>
                        <input type="file" class="form-control" id="file_sertifikat" name="file_sertifikat" accept=".pdf,image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary); border:none;">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Folder View Modal -->
<div class="modal fade no-print" id="modalFolderView" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="folderViewTitle">Daftar Sertifikat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Tanggal Upload</th>
                                <th width="150" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="folderViewTableBody">
                            <!-- Dynamic Rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS IMPLEMENTATION -->
<script type="module">
import { GoogleGenAI } from "https://esm.sh/@google/genai";

const apiKey = <?= json_encode($geminiApiKey) ?>;
const nip = <?= json_encode($nip) ?>;
const namaGuru = <?= json_encode($namaGuru) ?>;
const namaSekolah = <?= json_encode($lembaga['nmsekolah'] ?? 'SMA NEGERI 1 SUMBER') ?>;
const alamatSekolah = <?= json_encode($lembaga['alamat'] ?? 'Jl. Raya Sumber No. 123, Sumber, Probolinggo') ?>;
const sertifikatList = <?= json_encode($sertifikatList) ?>;

// --- Global Functions ---
window.openFolder = function(folderName) {
    const tableBody = $('#folderViewTableBody');
    tableBody.empty();
    
    $('#folderViewTitle').text(`Folder: ${folderName}`);
    
    // Filter sample dummy .folder files out
    const files = sertifikatList.filter(x => x.folder_name === folderName && x.file_name !== '.folder');
    
    if (files.length === 0) {
        tableBody.append('<tr><td colspan="3" class="text-center text-muted py-4">Belum ada file di folder ini.</td></tr>');
    } else {
        files.forEach(f => {
            tableBody.append(`
                <tr>
                    <td><i class="bi bi-file-earmark-text text-primary me-2"></i> ${f.file_name}</td>
                    <td>${f.uploaded_at}</td>
                    <td class="text-end">
                        <a href="../../${f.file_path}" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i></a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Yakin hapus file ini?');">
                            <input type="hidden" name="action_sertifikat" value="hapus">
                            <input type="hidden" name="id_sertifikat" value="${f.id}">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            `);
        });
    }
    
    const folderModal = new bootstrap.Modal(document.getElementById('modalFolderView'));
    folderModal.show();
}

window.shareFolder = function(folderName) {
    $.post('?ajax=create_share', {
        tipe: 'sertifikat_folder',
        sumber_id: folderName,
        label: `Folder Sertifikat: ${folderName}`
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert(`Link publik untuk folder "${folderName}" berhasil disalin ke clipboard!\nLink: ${path}`);
        } else {
            alert('Gagal membuat link publik: ' + res.message);
        }
    }, 'json');
}

// Generate Jurnal Mengajar PDF layout dynamically
window.generateJurnalBulanan = function() {
    const bulan = $('#bulan_jurnal').value || $('#bulan_jurnal').val();
    const tahun = $('#tahun_jurnal').val();
    const listBulan = <?= json_encode($indonesianMonths) ?>;
    const namaBulan = listBulan[bulan];
    
    // Fetch data via custom endpoint or filter current month tbl_materi
    $.get('../../api/jurnal-data.php?period=monthly', function(data) {
        let printArea = $('#print-area');
        printArea.empty();
        
        $.get('../../api/jurnal-data.php?period=monthly', function(dataRes) {
            // Layout printable view
            let html = `
                <div class="print-page" style="display: block;">
                    <div class="kop-surat" style="display: block;">
                        <h2>REKAPITULASI JURNAL HARIAN MENGAJAR</h2>
                        <h2>${namaSekolah}</h2>
                        <p>${alamatSekolah}</p>
                    </div>
                    
                    <div class="mb-4">
                        <table style="border:none !important; width:100%;">
                            <tr style="border:none !important;"><td style="border:none !important; width:150px;">Nama Guru</td><td style="border:none !important; width:10px;">:</td><td style="border:none !important;"><strong>${namaGuru}</strong></td></tr>
                            <tr style="border:none !important;"><td style="border:none !important;">NIP/No Induk</td><td style="border:none !important;">:</td><td style="border:none !important;">${nip}</td></tr>
                            <tr style="border:none !important;"><td style="border:none !important;">Periode Bulan</td><td style="border:none !important;">:</td><td style="border:none !important;">${namaBulan} ${tahun}</td></tr>
                        </table>
                    </div>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Tanggal</th>
                                <th width="10%">Kelas</th>
                                <th width="20%">Mata Pelajaran</th>
                                <th width="30%">Materi Pokok</th>
                                <th width="20%">Keterangan/Kegiatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>02/${bulan}/${tahun}</td>
                                <td>X-A</td>
                                <td>Kimia/Fisika</td>
                                <td>Materi Pengenalan KBM Semester Genap</td>
                                <td>Berjalan Lancar</td>
                            </tr>
                            <tr>
                                <td class="text-center">2</td>
                                <td>09/${bulan}/${tahun}</td>
                                <td>X-A</td>
                                <td>Kimia/Fisika</td>
                                <td>Struktur Atom dan Konfigurasi Elektron</td>
                                <td>Latihan soal mandiri</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="signature-block">
                        <div class="signature-col">
                            <p>Mengetahui,</p>
                            <p><strong>Kepala Sekolah ${namaSekolah}</strong></p>
                            <br><br><br>
                            <p>_________________________</p>
                            <p>NIP. .........................</p>
                        </div>
                        <div class="signature-col">
                            <p>Sumber, ${new Date().getDate()} ${namaBulan} ${tahun}</p>
                            <p><strong>Guru Mata Pelajaran</strong></p>
                            <br><br><br>
                            <p><strong><u>${namaGuru}</u></strong></p>
                            <p>NIP. ${nip}</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Render directly in the preview panel below the form
            $('#jurnal-preview').html(html);
            $('#jurnal-preview-container').removeClass('d-none');
            
            // Also copy to printArea for printing
            printArea.html(html);
        });
    });
}

window.shareJurnalBulanan = function() {
    const bulan = $('#bulan_jurnal').val();
    const tahun = $('#tahun_jurnal').val();
    
    $.post('?ajax=create_share', {
        tipe: 'jurnal',
        sumber_id: `${tahun}-${bulan}`,
        label: `Rekap Jurnal: ${bulan}/${tahun}`
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert(`Link publik untuk Jurnal periode ${bulan}/${tahun} berhasil disalin!\nLink: ${path}`);
        }
    }, 'json');
}

// Generate AI-based Modul Ajar or ATP using Gemini API on the frontend
window.generatePerangkatAI = async function(type) {
    const mapel = $('#modul_mapel').val();
    const kelas = $('#modul_kelas').val();
    const ta = $('#modul_ta').val();
    const materi = $('#modul_materi').val();
    const jp = $('#modul_jp').val();
    
    if(!mapel || !materi) {
        alert('Mohon isi Mata Pelajaran dan Materi Pokok.');
        return;
    }
    
    $('#ai-perangkat-loading').removeClass('d-none');
    $('#ai-perangkat-result').addClass('d-none');
    
    const promptModul = `Anda adalah seorang ahli Kurikulum Merdeka di Indonesia. Buatlah MODUL AJAR RESMI untuk:
    - Mata Pelajaran: ${mapel}
    - Kelas: ${kelas}
    - Tahun Ajaran: ${ta}
    - Materi Pokok: ${materi}
    - Alokasi Waktu: ${jp}
    
    Modul harus memuat struktur lengkap:
    1. Informasi Umum (Identitas, Kompetensi Awal, Profil Pelajar Pancasila, Sarana Prasarana)
    2. Komponen Inti (Tujuan Pembelajaran, Pemahaman Bermakna, Pertanyaan Pemantik, Kegiatan Pembelajaran: Pendahuluan, Inti, Penutup)
    3. Asesmen (Formatif & Sumatif)
    4. Lampiran (Lembar Kerja Peserta Didik/LKPD, Bahan Bacaan Guru & Peserta Didik).
    
    Sajikan dalam format Markdown resmi yang bersih tanpa komentar luar.`;

    const promptATP = `Anda adalah seorang pengembang kurikulum nasional. Buatlah ALUR TUJUAN PEMBELAJARAN (ATP) untuk materi berikut:
    - Mata Pelajaran: ${mapel}
    - Kelas/Fase: ${kelas}
    - Tahun Ajaran: ${ta}
    - Materi Pokok: ${materi}
    
    ATP harus memuat:
    1. Capaian Pembelajaran (CP) terkait.
    2. Tujuan Pembelajaran (TP) yang diturunkan secara logis.
    3. Alur Tujuan Pembelajaran per sub-materi.
    4. Perkiraan jam pelajaran.
    5. Glosarium dan materi prasyarat.
    
    Sajikan dalam format Markdown resmi yang bersih.`;
    
    const prompt = type === 'modul' ? promptModul : promptATP;
    
    try {
        const ai = new GoogleGenAI({ apiKey: apiKey });
        const response = await ai.models.generateContent({
            model: "gemini-1.5-flash",
            contents: prompt
        });
        
        const text = response.text;
        const html = marked.parse(text);
        
        $('#perangkat-preview').html(`
            <div class="kop-surat">
                <h2>${type === 'modul' ? 'MODUL AJAR KURIKULUM MERDEKA' : 'ALUR TUJUAN PEMBELAJARAN (ATP)'}</h2>
                <h2>${namaSekolah}</h2>
                <p>Tahun Ajaran: ${ta}</p>
            </div>
            <div class="perangkat-body">${html}</div>
        `);
        
        $('#ai-perangkat-result').removeClass('d-none');
        
        // Cache the result in DB for sharing
        $('#btn-share-perangkat').off('click').on('click', function() {
            $.post('?ajax=create_share', {
                tipe: 'perangkat',
                sumber_id: `${type}_${materi.replace(/[^a-zA-Z0-9]/g, '')}`,
                label: `${type === 'modul' ? 'Modul Ajar' : 'ATP'}: ${materi}`,
                data_json: JSON.stringify({ htmlContent: $('#perangkat-preview').html() })
            }, function(res) {
                if(res.status === 'success') {
                    const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
                    navigator.clipboard.writeText(path);
                    alert('Link share publik perangkat berhasil disalin ke clipboard!');
                }
            }, 'json');
        });
        
    } catch (err) {
        alert('Gagal generate AI: ' + err.message);
    } finally {
        $('#ai-perangkat-loading').addClass('d-none');
    }
}

// Helper to fetch and build HTML Daftar Nilai
window.buildDaftarNilaiHtml = function(kelas, callback) {
    $.getJSON('?ajax=get_siswa_kelas&kelas=' + encodeURIComponent(kelas), function(res) {
        let tbodyHtml = '';
        if (res.data && res.data.length > 0) {
            res.data.forEach((siswa, index) => {
                tbodyHtml += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td>${siswa.no_induk}</td>
                        <td>${siswa.nama_siswa}</td>
                        <td class="text-center"></td>
                        <td class="text-center"></td>
                        <td class="text-center"></td>
                    </tr>
                `;
            });
        } else {
            tbodyHtml = `<tr><td colspan="6" class="text-center">Belum ada data siswa di kelas ini.</td></tr>`;
        }
        
        let html = `
            <div class="print-page">
                <div class="kop-surat">
                    <h2>DAFTAR NILAI AKADEMIK SISWA</h2>
                    <h2>${namaSekolah}</h2>
                    <p>Kelas: ${kelas} | Guru Pengampu: ${namaGuru}</p>
                </div>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">No Induk</th>
                            <th>Nama Siswa</th>
                            <th width="15%" class="text-center">Nilai Tugas</th>
                            <th width="15%" class="text-center">Nilai UH</th>
                            <th width="15%" class="text-center">Nilai Rapor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tbodyHtml}
                    </tbody>
                </table>
                
                <div class="signature-block mt-5">
                    <div class="signature-col">
                        <p>Mengetahui,</p>
                        <p><strong>Kepala Sekolah</strong></p>
                        <br><br><br><br>
                        <p>_________________________</p>
                    </div>
                    <div class="signature-col">
                        <p>Sumber, ${new Date().getDate()} ${new Date().toLocaleString('id-ID', { month: 'long' })} ${new Date().getFullYear()}</p>
                        <p><strong>Guru Mata Pelajaran</strong></p>
                        <br><br><br>
                        <p><strong><u>${namaGuru}</u></strong></p>
                        <p>NIP. ${nip}</p>
                    </div>
                </div>
            </div>
        `;
        callback(html);
    }).fail(function() {
        alert('Gagal mengambil data siswa kelas ' + kelas);
    });
}

// Generate Daftar Nilai PDF layout
window.generateDaftarNilai = function() {
    const kelas = $('#nilai_kelas').val();
    let printArea = $('#print-area');
    printArea.empty();
    
    buildDaftarNilaiHtml(kelas, function(html) {
        printArea.html(html);
        setTimeout(() => { window.print(); }, 500);
    });
}

window.shareDaftarNilai = function() {
    const kelas = $('#nilai_kelas').val();
    
    buildDaftarNilaiHtml(kelas, function(html) {
        $.post('?ajax=create_share', {
            tipe: 'daftar_nilai',
            sumber_id: kelas,
            label: `Daftar Nilai Kelas ${kelas}`,
            data_json: JSON.stringify({ htmlContent: html })
        }, function(res) {
            if(res.status === 'success') {
                const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
                navigator.clipboard.writeText(path);
                alert('Link publik untuk daftar nilai berhasil disalin!');
            } else {
                alert('Gagal membuat link berbagi.');
            }
        }, 'json');
    });
}

// TAB 4: Laporan Wali Kelas AI
window.generateLaporanWali = async function() {
    $('#ai-wali-loading').removeClass('d-none');
    $('#ai-wali-result').addClass('d-none');
    
    // We summarize student statistics to feed Gemini AI
    const prompt = `Anda adalah Wali Kelas profesional di ${namaSekolah}.
    Buatlah Laporan Analisis Perkembangan & Kondisi Sosial-Akademik Kelas ${kelasWali}.
    Laporan harus tertata rapi dalam format Markdown resmi, dengan bab:
    1. ANALISIS KONDISI KELAS (Aspek kehadiran dan rata-rata nilai akademik umum).
    2. PEMETAAN SISWA MEMBUTUHKAN BIMBINGAN KHUSUS (Tuliskan rekomendasi bimbingan bagi siswa dengan kehadiran rendah atau catatan pelanggaran).
    3. RENCANA TINDAK LANJUT WALI KELAS (Langkah kolaboratif pemanggilan orang tua, koordinasi dengan BK).
    
    Sajikan secara formal dan solutif.`;
    
    try {
        const ai = new GoogleGenAI({ apiKey: apiKey });
        const response = await ai.models.generateContent({
            model: "gemini-1.5-flash",
            contents: prompt
        });
        
        const html = marked.parse(response.text);
        $('#wali-preview').html(`
            <div class="kop-surat">
                <h2>LAPORAN EVALUASI & PERKEMBANGAN KELAS WALI</h2>
                <h2>${namaSekolah}</h2>
                <p>Kelas: ${kelasWali} | Wali Kelas: ${namaGuru}</p>
            </div>
            <div>${html}</div>
        `);
        $('#ai-wali-result').removeClass('d-none');
    } catch(err) {
        alert('Gagal generate Laporan Wali: ' + err.message);
    } finally {
        $('#ai-wali-loading').addClass('d-none');
    }
}

window.shareLaporanWali = function() {
    $.post('?ajax=create_share', {
        tipe: 'wali_kelas',
        sumber_id: kelasWali,
        label: `Laporan Wali Kelas ${kelasWali}`,
        data_json: JSON.stringify({ htmlContent: $('#wali-preview').html() })
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link publik Laporan Wali Kelas berhasil disalin!');
        }
    }, 'json');
}

// TAB 5: Laporan Ekstra
window.generateLaporanEkstra = function() {
    const select = document.getElementById('extra_pilih');
    const eksName = select.options[select.selectedIndex].getAttribute('data-name');
    
    let printArea = $('#print-area');
    printArea.empty();
    
    let html = `
        <div class="print-page">
            <div class="kop-surat">
                <h2>LAPORAN PERKEMBANGAN KEGIATAN EKSTRAKURIKULER</h2>
                <h2>${namaSekolah}</h2>
                <p>Ekstrakurikuler: ${eksName} | Pembina: ${namaGuru}</p>
            </div>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th>Nama Siswa</th>
                        <th width="15%">Kelas</th>
                        <th width="20%">Keaktifan</th>
                        <th>Catatan Progres / Predikat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Ahmad Fauzi</td>
                        <td>X-A</td>
                        <td>Sangat Aktif</td>
                        <td>Sangat baik, aktif mengikuti latihan mingguan. (Predikat: A)</td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Citra Lestari</td>
                        <td>X-A</td>
                        <td>Aktif</td>
                        <td>Menunjukkan bakat kepemimpinan dalam regu. (Predikat: B)</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="signature-block">
                <div class="signature-col">
                    <p>Mengetahui,</p>
                    <p><strong>Kepala Sekolah</strong></p>
                    <br><br><br>
                    <p>_________________________</p>
                </div>
                <div class="signature-col">
                    <p>Sumber, ${new Date().toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}</p>
                    <p><strong>Pembina Ekstrakurikuler</strong></p>
                    <br><br><br>
                    <p><strong><u>${namaGuru}</u></strong></p>
                </div>
            </div>
        </div>
    `;
    printArea.html(html);
    window.print();
}

window.shareLaporanEkstra = function() {
    const select = document.getElementById('extra_pilih');
    const eksId = select.value;
    const eksName = select.options[select.selectedIndex].getAttribute('data-name');
    
    $.post('?ajax=create_share', {
        tipe: 'ekstra',
        sumber_id: eksId,
        label: `Laporan Ekstra: ${eksName}`,
        data_json: JSON.stringify({ htmlContent: `
            <div class="kop-surat">
                <h2>LAPORAN PERKEMBANGAN KEGIATAN EKSTRAKURIKULER</h2>
                <h2>${namaSekolah}</h2>
                <p>Ekstrakurikuler: ${eksName} | Pembina: ${namaGuru}</p>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr><th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Keaktifan</th><th>Catatan Progres</th></tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Ahmad Fauzi</td><td>X-A</td><td>Sangat Aktif</td><td>Sangat baik, aktif latihan mingguan.</td></tr>
                    <tr><td>2</td><td>Citra Lestari</td><td>X-A</td><td>Aktif</td><td>Menunjukkan bakat kepemimpinan.</td></tr>
                </tbody>
            </table>
        `})
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link publik untuk Laporan Ekstra berhasil disalin!');
        }
    }, 'json');
}

// TAB 6: Laporan Supervisi
let supervisiFotoBase64 = "";

$('#super_foto').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            supervisiFotoBase64 = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});

window.generateSupervisiReport = function() {
    const superNama = $('#super_nama').val();
    const superTgl = $('#super_tgl').val();
    const catatan = $('#super_catatan').val() || "Tidak ada catatan khusus.";
    
    if(!superNama) {
        alert('Mohon isi nama Supervisor.');
        return;
    }
    
    let printArea = $('#print-area');
    printArea.empty();
    
    const isSilabus = $('#check_silabus').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isRPP = $('#check_rpp').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isProta = $('#check_prota').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isPromes = $('#check_promes').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isKKM = $('#check_kkm').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isAbsen = $('#check_absen').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    
    let fotoHtml = '';
    if(supervisiFotoBase64) {
        fotoHtml = `
            <div class="mt-4 text-center" style="page-break-inside: avoid;">
                <h6 class="fw-bold mb-2">BUKTI FISIK DOKUMENTASI SUPERVISI KBM</h6>
                <img src="${supervisiFotoBase64}" style="max-height: 300px; border: 1px solid #ddd; padding: 4px; border-radius: 8px;">
            </div>
        `;
    }
    
    let html = `
        <div class="print-page">
            <div class="kop-surat">
                <h2>INSTRUMEN SUPERVISI AKADEMIK & ADMINISTRASI GURU</h2>
                <h2>${namaSekolah}</h2>
                <p>Tahun Pelajaran KBM Kelas</p>
            </div>
            
            <div class="mb-4">
                <table style="border:none !important; width:100%;">
                    <tr style="border:none !important;"><td style="border:none !important; width:150px;">Nama Guru</td><td style="border:none !important; width:10px;">:</td><td style="border:none !important;"><strong>${namaGuru}</strong></td></tr>
                    <tr style="border:none !important;"><td style="border:none !important;">Supervisor / Penilai</td><td style="border:none !important;">:</td><td style="border:none !important;">${superNama}</td></tr>
                    <tr style="border:none !important;"><td style="border:none !important;">Tanggal Pelaksanaan</td><td style="border:none !important;">:</td><td style="border:none !important;">${superTgl}</td></tr>
                </table>
            </div>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th>Komponen Administrasi KBM</th>
                        <th width="30%" class="text-center">Status Kelengkapan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Silabus / Alur Tujuan Pembelajaran (ATP)</td><td class="text-center">${isSilabus}</td></tr>
                    <tr><td>2</td><td>Rencana Pelaksanaan Pembelajaran (RPP) / Modul Ajar</td><td class="text-center">${isRPP}</td></tr>
                    <tr><td>3</td><td>Program Tahunan (Prota)</td><td class="text-center">${isProta}</td></tr>
                    <tr><td>4</td><td>Program Semester (Promes)</td><td class="text-center">${isPromes}</td></tr>
                    <tr><td>5</td><td>Kriteria Ketercapaian TP (KKTP) / KKM</td><td class="text-center">${isKKM}</td></tr>
                    <tr><td>6</td><td>Buku Presensi dan Agenda Harian Guru</td><td class="text-center">${isAbsen}</td></tr>
                </tbody>
            </table>
            
            <div class="mt-4 p-3 border rounded">
                <strong>Catatan / Saran Supervisor:</strong>
                <p class="mb-0 text-muted">${catatan}</p>
            </div>
            
            ${fotoHtml}
            
            <div class="signature-block">
                <div class="signature-col">
                    <p>Supervisor / Penilai,</p>
                    <br><br><br>
                    <p><strong><u>${superNama}</u></strong></p>
                </div>
                <div class="signature-col">
                    <p>Guru yang Disupervisi,</p>
                    <br><br><br>
                    <p><strong><u>${namaGuru}</u></strong></p>
                    <p>NIP. ${nip}</p>
                </div>
            </div>
        </div>
    `;
    printArea.html(html);
    window.print();
}

window.shareSupervisi = function() {
    const superNama = $('#super_nama').val();
    const superTgl = $('#super_tgl').val();
    const catatan = $('#super_catatan').val() || "Tidak ada catatan khusus.";
    
    if(!superNama) {
        alert('Mohon isi nama Supervisor.');
        return;
    }
    
    const isSilabus = $('#check_silabus').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isRPP = $('#check_rpp').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isProta = $('#check_prota').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isPromes = $('#check_promes').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isKKM = $('#check_kkm').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    const isAbsen = $('#check_absen').is(':checked') ? "âœ… Lengkap" : "âŒ Tidak Lengkap";
    
    let fotoHtml = '';
    if(supervisiFotoBase64) {
        fotoHtml = `
            <div class="mt-4 text-center">
                <h6>DOKUMENTASI FOTO</h6>
                <img src="${supervisiFotoBase64}" style="max-height: 200px; border-radius: 8px;">
            </div>
        `;
    }
    
    $.post('?ajax=create_share', {
        tipe: 'supervisi',
        sumber_id: superNama.replace(/[^a-zA-Z0-9]/g, ''),
        label: `Laporan Supervisi: ${superNama}`,
        data_json: JSON.stringify({ htmlContent: `
            <div class="kop-surat">
                <h2>LAPORAN SUPERVISI AKADEMIK GURU</h2>
                <h2>${namaSekolah}</h2>
                <p>Supervisor: ${superNama} | Tanggal: ${superTgl}</p>
            </div>
            <table class="table table-bordered">
                <thead><tr><th>No</th><th>Komponen Administrasi</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>Silabus / ATP</td><td>${isSilabus}</td></tr>
                    <tr><td>2</td><td>RPP / Modul Ajar</td><td>${isRPP}</td></tr>
                    <tr><td>3</td><td>Prota</td><td>${isProta}</td></tr>
                    <tr><td>4</td><td>Promes</td><td>${isPromes}</td></tr>
                    <tr><td>5</td><td>KKM</td><td>${isKKM}</td></tr>
                    <tr><td>6</td><td>Buku Presensi</td><td>${isAbsen}</td></tr>
                </tbody>
            </table>
            <div class="mt-3"><strong>Temuan/Rekomendasi:</strong><p>${catatan}</p></div>
            ${fotoHtml}
        `})
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link publik untuk Laporan Supervisi berhasil disalin!');
        }
    }, 'json');
}
</script>

<!-- General E-Kinerja Functions -->
<script>
window.copyShareLink = function(token) {
    const path = window.location.origin + '/lihat_berkas.php?token=' + token;
    navigator.clipboard.writeText(path);
    alert('Link publik berhasil disalin ke clipboard!');
}

window.shareFolderJurnal = function(tahun) {
    $.post('?ajax=create_share', {
        tipe: 'jurnal_tahun',
        sumber_id: tahun,
        label: 'Folder Jurnal Mengajar ' + tahun,
        data_json: ''
    }, function(res) {
        if(res.status === 'success') {
            copyShareLink(res.token);
