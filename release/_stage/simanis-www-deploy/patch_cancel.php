<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\status-izin.php';
$content = file_get_contents($file);

$cancelLogic = <<<PHP
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action']) && \$_POST['action'] === 'batalkan_izin') {
    \$id_izin = (int)\$_POST['id_izin'];
    // Validasi bahwa izin ini milik user login dan masih bisa dibatalkan
    \$qCek = mysqli_query(\$conn, "SELECT status_izin FROM tbl_izin_siswa WHERE id_izin = \$id_izin AND no_induk_siswa = '\$nis' AND status_izin IN ('Menunggu Validasi', 'Menunggu')");
    if (\$qCek && mysqli_num_rows(\$qCek) > 0) {
        mysqli_query(\$conn, "UPDATE tbl_izin_siswa SET status_izin = 'Dibatalkan', validasi_wali_kelas = 'Dibatalkan', validasi_satpam = 'Dibatalkan' WHERE id_izin = \$id_izin");
        \$pesan = 'Pengajuan izin berhasil dibatalkan.';
    } else {
        \$error = 'Gagal membatalkan izin. Izin mungkin sudah diproses.';
    }
}
PHP;

// Find a place to insert the logic, before the DOCTYPE
$content = preg_replace('/(\$q_izin\s*=\s*mysqli_query\(.*?\);)/is', $cancelLogic . "\n\n$1", $content);

// Add the cancel button in the UI
// Look for where the status is displayed, e.g. <div class="p-6"> or similar card layout
$cancelBtn = <<<HTML
                                <?php if (in_array(\$izin['status_izin'], ['Menunggu Validasi', 'Menunggu'])): ?>
                                <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan izin ini?');">
                                        <input type="hidden" name="action" value="batalkan_izin">
                                        <input type="hidden" name="id_izin" value="<?= \$izin['id_izin'] ?>">
                                        <button type="submit" class="px-4 py-2 text-sm text-red-600 bg-red-50 hover:bg-red-100 rounded-lg font-medium transition">
                                            <i class="fas fa-times-circle mr-1"></i> Batalkan Pengajuan
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
HTML;

// Append to the bottom of the card content. Usually ends with </div> </div> <?php endforeach;
// We'll replace `</div> </div> <?php endwhile;`
$content = str_replace('                    </div>
                </div>
            <?php endwhile;', $cancelBtn . '                    </div>
                </div>
            <?php endwhile;', $content);

file_put_contents($file, $content);
echo "Status-izin updated with cancel feature\n";
?>
