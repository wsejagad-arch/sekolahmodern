<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\lihat_berkas.php');

$insertStr = <<<EOT
    <?php elseif (str_ends_with(\$tipe, '_umum')): ?>
        <?php
        \$tipeAnak = str_replace('_umum', '', \$tipe);
        
        // Handle nested view for Perangkat Umum (folders -> files)
        if (\$tipeAnak === 'perangkat') {
            \$folderId = isset(\$_GET['folder_id']) ? (int)\$_GET['folder_id'] : 0;
            \$fileId = isset(\$_GET['file_id']) ? (int)\$_GET['file_id'] : 0;
            
            if (\$fileId > 0) {
                // View specific file
                \$qFile = mysqli_query(\$conn, "SELECT nama_file, data_json FROM tbl_ekinerja_dokumen WHERE id=\$fileId AND no_induk_guru='\$nipGuruEsc' LIMIT 1");
                if (\$qFile && mysqli_num_rows(\$qFile) > 0) {
                    \$rowFile = mysqli_fetch_assoc(\$qFile);
                    \$fileData = json_decode(\$rowFile['data_json'], true);
                    echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                    echo "<h4><i class='bi bi-file-earmark-text text-primary'></i> " . htmlspecialchars(\$rowFile['nama_file']) . "</h4>";
                    echo "<a href='?token=" . urlencode(\$token) . "&folder_id=" . \$folderId . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali ke Folder</a>";
                    echo "</div><hr class='no-print'>";
                    echo "<div class='document-content'>" . \$fileData['htmlContent'] . "</div>";
                } else {
                    echo "<p class='text-danger'>File tidak ditemukan.</p>";
                }
            } elseif (\$folderId > 0) {
                // View specific folder contents
                \$qF = mysqli_query(\$conn, "SELECT nama_file FROM tbl_ekinerja_dokumen WHERE id=\$folderId AND no_induk_guru='\$nipGuruEsc' LIMIT 1");
                \$namaFolder = (\$qF && mysqli_num_rows(\$qF) > 0) ? mysqli_fetch_assoc(\$qF)['nama_file'] : 'Folder';
                
                echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                echo "<h4><i class='bi bi-folder-fill text-warning'></i> " . htmlspecialchars(\$namaFolder) . "</h4>";
                echo "<a href='?token=" . urlencode(\$token) . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali</a>";
                echo "</div><hr class='no-print'>";
                echo "<p class='text-muted'>Daftar perangkat ajar dalam folder ini:</p>";
                echo "<div class='list-group mt-3'>";
                
                \$qFiles = mysqli_query(\$conn, "SELECT id, nama_file, tipe_dokumen FROM tbl_ekinerja_dokumen WHERE sumber_id='\$folderId' AND tipe_dokumen IN ('modul', 'atp') ORDER BY created_at DESC");
                if (\$qFiles && mysqli_num_rows(\$qFiles) > 0) {
                    while (\$f = mysqli_fetch_assoc(\$qFiles)) {
                        \$icon = \$f['tipe_dokumen'] === 'modul' ? 'bi-file-earmark-text text-primary' : 'bi-diagram-3 text-success';
                        echo "<a href='?token=" . urlencode(\$token) . "&folder_id=\$folderId&file_id=" . \$f['id'] . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                        echo "<span><i class='bi \$icon me-2'></i> " . htmlspecialchars(\$f['nama_file']) . "</span>";
                        echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Folder ini kosong.</div>";
                }
                echo "</div>";
            } else {
                // List all folders
                echo "<h4><i class='bi bi-folder-fill text-primary'></i> " . htmlspecialchars(\$label) . "</h4>";
                echo "<p class='text-muted'>Daftar folder perangkat pembelajaran yang telah dibuat oleh <strong>" . htmlspecialchars(\$namaGuru) . "</strong>:</p>";
                echo "<div class='list-group mt-3'>";
                
                \$qItems = mysqli_query(\$conn, "SELECT id, nama_file FROM tbl_ekinerja_dokumen WHERE no_induk_guru='\$nipGuruEsc' AND tipe_dokumen='perangkat_folder' ORDER BY nama_file ASC");
                if (\$qItems && mysqli_num_rows(\$qItems) > 0) {
                    while (\$f = mysqli_fetch_assoc(\$qItems)) {
                        echo "<a href='?token=" . urlencode(\$token) . "&folder_id=" . \$f['id'] . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                        echo "<span><i class='bi bi-folder text-warning me-2'></i> " . htmlspecialchars(\$f['nama_file']) . "</span>";
                        echo "<span class='text-muted small'>Buka Folder <i class='bi bi-arrow-right'></i></span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Belum ada folder.</div>";
                }
                echo "</div>";
            }
        } else {
            // General handler for Sertifikat, Wali Kelas, Ekstra, Supervisi
            echo "<h4><i class='bi bi-folder-fill text-primary'></i> " . htmlspecialchars(\$label) . "</h4>";
            echo "<p class='text-muted'>Daftar berkas yang telah dibuat oleh <strong>" . htmlspecialchars(\$namaGuru) . "</strong>:</p>";
            echo "<div class='list-group mt-3'>";
            
            if (\$tipeAnak === 'sertifikat') {
                \$qItems = mysqli_query(\$conn, "SELECT DISTINCT folder_name FROM tbl_sertifikat WHERE no_induk_guru='\$nipGuruEsc'");
                if (\$qItems && mysqli_num_rows(\$qItems) > 0) {
                    while (\$f = mysqli_fetch_assoc(\$qItems)) {
                        \$fname = mysqli_real_escape_string(\$conn, \$f['folder_name']);
                        \$qTk = mysqli_query(\$conn, "SELECT token FROM tbl_share_links WHERE no_induk_guru='\$nipGuruEsc' AND tipe_sumber='sertifikat_folder' AND sumber_id='\$fname' LIMIT 1");
                        if(\$qTk && mysqli_num_rows(\$qTk) > 0) {
                            \$tk = mysqli_fetch_assoc(\$qTk)['token'];
                            echo "<a href='?token=" . urlencode(\$tk) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                            echo "<span><i class='bi bi-patch-check text-success me-2'></i> " . htmlspecialchars(\$f['folder_name']) . "</span>";
                            echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                            echo "</a>";
                        }
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Belum ada sertifikat.</div>";
                }
            } else {
                \$qItems = mysqli_query(\$conn, "SELECT * FROM tbl_share_links WHERE no_induk_guru='\$nipGuruEsc' AND tipe_sumber='\$tipeAnak' ORDER BY id DESC");
                if (\$qItems && mysqli_num_rows(\$qItems) > 0) {
                    while (\$f = mysqli_fetch_assoc(\$qItems)) {
                        echo "<a href='?token=" . urlencode(\$f['token']) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                        echo "<span><i class='bi bi-file-earmark-pdf-fill text-danger me-2'></i> " . htmlspecialchars(\$f['sumber_label']) . "</span>";
                        echo "<span class='text-muted small'>Lihat Dokumen <i class='bi bi-box-arrow-up-right'></i></span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Belum ada dokumen.</div>";
                }
            }
            echo "</div>";
        }
        ?>
EOT;

$marker = "    <?php elseif (\$tipe === 'jurnal_tahun'): ?>";
$content = str_replace($marker, $insertStr . "\n\n" . $marker, $content);
file_put_contents('c:\xampp\htdocs\jurnal\lihat_berkas.php', $content);
echo "Patch 2 applied to lihat_berkas.php";
?>
