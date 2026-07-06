<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\siswa.php';
$content = file_get_contents($file);

$notif_logic = <<<'PHP'
// 6.5. Tugas Reguler & Literasi Belum Selesai (Batas Waktu)
$__tblTugas = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_tugas'");
$__tblTugasSiswa = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_tugas_siswa'");
if ($__tblTugas && mysqli_num_rows($__tblTugas) > 0 && $__tblTugasSiswa && mysqli_num_rows($__tblTugasSiswa) > 0) {
    $qTugas = @mysqli_query($conn, "
        SELECT t.id, t.judul_tugas, t.batas_waktu 
        FROM tbl_tugas t 
        LEFT JOIN tbl_tugas_siswa ts ON t.id = ts.id_tugas AND ts.no_induk_siswa = '$nisEsc' 
        WHERE t.kelas = '$klsEsc' AND t.status = 'aktif' AND (ts.id IS NULL OR ts.status != 'Selesai') 
        ORDER BY t.batas_waktu ASC LIMIT 5");
    if ($qTugas) {
        while($tg = mysqli_fetch_assoc($qTugas)) {
            $tenggat = $tg['batas_waktu'] ? date('d/m/Y H:i', strtotime($tg['batas_waktu'])) : 'Tidak ada tenggat';
            $is_late = $tg['batas_waktu'] && strtotime($tg['batas_waktu']) < time() ? true : false;
            
            $all_notifications[] = [
                'type' => 'tugas',
                'icon' => 'fas fa-book-open',
                'color' => $is_late ? '#ef4444' : '#f97316',
                'title' => 'Tugas ' . ($is_late ? 'Terlambat!' : 'Belum Selesai'),
                'text' => htmlspecialchars($tg['judul_tugas']) . ' (Tenggat: ' . $tenggat . ')',
                'link' => 'tugas.php'
            ];
        }
    }
}

$__tblLiterasi = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_literasi_tugas'");
$__tblLiterasiProg = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_literasi_progress'");
if ($__tblLiterasi && mysqli_num_rows($__tblLiterasi) > 0 && $__tblLiterasiProg && mysqli_num_rows($__tblLiterasiProg) > 0) {
    $qLit = @mysqli_query($conn, "
        SELECT t.id, t.judul, t.batas_waktu 
        FROM tbl_literasi_tugas t 
        LEFT JOIN tbl_literasi_progress p ON t.id = p.id_tugas AND p.no_induk_siswa = '$nisEsc' 
        WHERE t.kelas = '$klsEsc' AND (p.id IS NULL OR p.status != 'Selesai') 
        ORDER BY t.batas_waktu ASC LIMIT 5");
    if ($qLit) {
        while($lit = mysqli_fetch_assoc($qLit)) {
            $tenggat = $lit['batas_waktu'] ? date('d/m/Y H:i', strtotime($lit['batas_waktu'])) : 'Tidak ada tenggat';
            $is_late = $lit['batas_waktu'] && strtotime($lit['batas_waktu']) < time() ? true : false;
            
            $all_notifications[] = [
                'type' => 'literasi',
                'icon' => 'fas fa-rocket',
                'color' => $is_late ? '#ef4444' : '#3b82f6',
                'title' => 'Literasi ' . ($is_late ? 'Terlambat!' : 'Belum Selesai'),
                'text' => htmlspecialchars($lit['judul']) . ' (Tenggat: ' . $tenggat . ')',
                'link' => 'literasi.php'
            ];
        }
    }
}

// 7. Leaderboard 7 KIH
PHP;

if (strpos($content, '// 7. Leaderboard 7 KIH') !== false) {
    $content = str_replace('// 7. Leaderboard 7 KIH', $notif_logic, $content);
    file_put_contents($file, $content);
    echo "Updated siswa.php notifications";
} else {
    echo "Anchor not found";
}
?>
