<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\lihat_berkas.php');

$insertStr = <<<EOT
    <?php elseif (\$tipe === 'perangkat_file'): ?>
        <?php
        \$idFile = (int)\$sumber_id;
        \$qFile = mysqli_query(\$conn, "SELECT data_json FROM tbl_ekinerja_dokumen WHERE id=\$idFile LIMIT 1");
        if (\$qFile && mysqli_num_rows(\$qFile) > 0) {
            \$rowFile = mysqli_fetch_assoc(\$qFile);
            \$fileData = json_decode(\$rowFile['data_json'], true);
            echo "<div class='document-content'>" . \$fileData['htmlContent'] . "</div>";
        } else {
            echo "<p class='text-danger'>File tidak ditemukan.</p>";
        }
        ?>

    <?php elseif (\$tipe === 'perangkat_folder'): ?>
        <?php
        \$folderId = (int)\$sumber_id;
        \$fileId = isset(\$_GET['file_id']) ? (int)\$_GET['file_id'] : 0;
        
        if (\$fileId > 0) {
            // View specific file in folder
            \$qFile = mysqli_query(\$conn, "SELECT nama_file, data_json FROM tbl_ekinerja_dokumen WHERE id=\$fileId AND sumber_id='\$folderId' LIMIT 1");
            if (\$qFile && mysqli_num_rows(\$qFile) > 0) {
                \$rowFile = mysqli_fetch_assoc(\$qFile);
                \$fileData = json_decode(\$rowFile['data_json'], true);
                echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                echo "<h4><i class='bi bi-file-earmark-text text-primary'></i> " . htmlspecialchars(\$rowFile['nama_file']) . "</h4>";
                echo "<a href='?token=" . urlencode(\$token) . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali ke Folder</a>";
                echo "</div><hr class='no-print'>";
                echo "<div class='document-content'>" . \$fileData['htmlContent'] . "</div>";
            } else {
                echo "<p class='text-danger'>File tidak ditemukan dalam folder ini.</p>";
            }
        } else {
            // List files
            echo "<h4><i class='bi bi-folder-fill text-warning'></i> " . htmlspecialchars(\$label) . "</h4>";
            echo "<p class='text-muted'>Daftar perangkat ajar dalam folder ini:</p>";
            echo "<div class='list-group mt-3'>";
            
            \$qFiles = mysqli_query(\$conn, "SELECT id, nama_file, tipe_dokumen, created_at FROM tbl_ekinerja_dokumen WHERE sumber_id='\$folderId' AND tipe_dokumen IN ('modul', 'atp') ORDER BY created_at DESC");
            if (\$qFiles && mysqli_num_rows(\$qFiles) > 0) {
                while (\$f = mysqli_fetch_assoc(\$qFiles)) {
                    \$icon = \$f['tipe_dokumen'] === 'modul' ? 'bi-file-earmark-text text-primary' : 'bi-diagram-3 text-success';
                    echo "<a href='?token=" . urlencode(\$token) . "&file_id=" . \$f['id'] . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                    echo "<span><i class='bi \$icon me-2'></i> " . htmlspecialchars(\$f['nama_file']) . "</span>";
                    echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                    echo "</a>";
                }
            } else {
                echo "<div class='text-center text-muted py-4'>Folder ini kosong.</div>";
            }
            echo "</div>";
        }
        ?>
EOT;

$marker = "    <?php elseif (!empty(\$data['htmlContent'])): ?>";
$content = str_replace($marker, $insertStr . "\n\n" . $marker, $content);
file_put_contents('c:\xampp\htdocs\jurnal\lihat_berkas.php', $content);
echo "Patch applied to lihat_berkas.php";
?>
