<?php
// Anti numpuk logic
$file1 = 'c:\xampp\htdocs\jurnal\pages\siswa\ajukan-izin.php';
$content1 = file_get_contents($file1);

$antiNumpukLogic = <<<PHP
    // --- ANTI NUMPUK CHECK ---
    // Cek apakah ada izin aktif hari ini
    \$today = date('Y-m-d');
    \$qCekAktif = mysqli_query(\$conn, "SELECT id_izin, kategori_pengajuan, status_izin FROM tbl_izin_siswa WHERE no_induk_siswa = '\$nis' AND tanggal_izin = '\$today' AND status_izin IN ('Menunggu Validasi', 'Menunggu', 'Disetujui', 'Disetujui Penuh') ORDER BY waktu_pengajuan DESC LIMIT 1");
    \$adaIzinAktif = (\$qCekAktif && mysqli_num_rows(\$qCekAktif) > 0);
    if (\$adaIzinAktif) {
        \$rowAktif = mysqli_fetch_assoc(\$qCekAktif);
        \$kategoriAktif = \$rowAktif['kategori_pengajuan'];
    }
PHP;

// Insert anti numpuk logic before `if ($_SERVER['REQUEST_METHOD'] === 'POST')`
$content1 = str_replace('if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {', $antiNumpukLogic . "\n\nif (\$_SERVER['REQUEST_METHOD'] === 'POST') {\n    if (\$adaIzinAktif) {\n        \$pesan = 'Anda masih memiliki pengajuan izin aktif hari ini. Harap tunggu hingga selesai atau batalkan pengajuan sebelumnya.';\n    } else {", $content1);

// Close the else block inside the POST handling
// We need to carefully add } before the end of POST handling
$content1 = str_replace('        $pesan = \'Mohon lengkapi semua data wajib beserta foto.\';
    }
}', '        $pesan = \'Mohon lengkapi semua data wajib beserta foto.\';
    }
    }
}', $content1);

// Hide form if adaIzinAktif
$formStart = '<form action="" method="POST" id="formIzin" class="mt-8 space-y-6">';
$hideFormLogic = <<<HTML
        <?php if (\$adaIzinAktif && empty(\$pesan)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 mb-6 text-yellow-800">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle mt-1 text-yellow-600"></i>
                <div>
                    <h3 class="font-bold mb-1">Pengajuan Izin Aktif</h3>
                    <p class="text-sm">Anda masih memiliki pengajuan izin (<strong><?= htmlspecialchars(\$kategoriAktif) ?></strong>) yang sedang diproses hari ini. Anda tidak dapat mengajukan izin baru sebelum izin tersebut selesai atau dibatalkan.</p>
                    <a href="status-izin.php" class="inline-block mt-3 px-4 py-2 bg-yellow-600 text-white text-sm font-semibold rounded-lg hover:bg-yellow-700 transition">Lihat Status Izin</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        $formStart
HTML;

$content1 = str_replace($formStart, $hideFormLogic, $content1);

// Close the else block at the end of the form
$formEnd = '</form>';
$content1 = str_replace($formEnd, $formEnd . "\n        <?php endif; ?>", $content1);

file_put_contents($file1, $content1);

echo "Anti numpuk done in ajukan-izin.php\n";
?>
