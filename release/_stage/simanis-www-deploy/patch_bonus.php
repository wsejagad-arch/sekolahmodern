<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

// Add the query for task timeliness
$logic = <<<'PHP'
$totalJurnalBulanIni = (int)($monthStat['total_jurnal'] ?? 0);
$avgScoreBulanIni = (float)($monthStat['avg_score_month'] ?? 0);

// --- Hitung Bonus Apresiasi Ketepatan Waktu Tugas ---
$bonusTugas = 0;
$onTimeCount = 0;
$lateCount = 0;

$qTugasProg = @mysqli_query($conn, "
    SELECT t.batas_waktu, ts.waktu_submit 
    FROM tbl_tugas t 
    JOIN tbl_tugas_siswa ts ON t.id = ts.id_tugas 
    WHERE ts.no_induk_siswa = '$nisEsc' AND ts.status = 'Selesai' AND DATE_FORMAT(ts.waktu_submit, '%Y-%m') = '$bulanNow'
");
if ($qTugasProg) {
    while($row = mysqli_fetch_assoc($qTugasProg)) {
        if ($row['batas_waktu'] && strtotime($row['waktu_submit']) > strtotime($row['batas_waktu'])) {
            $lateCount++;
        } else {
            $onTimeCount++;
        }
    }
}

$qLitProg = @mysqli_query($conn, "
    SELECT t.batas_waktu, p.waktu_selesai 
    FROM tbl_literasi_tugas t 
    JOIN tbl_literasi_progress p ON t.id = p.id_tugas 
    WHERE p.no_induk_siswa = '$nisEsc' AND p.status = 'Selesai' AND DATE_FORMAT(p.waktu_selesai, '%Y-%m') = '$bulanNow'
");
if ($qLitProg) {
    while($row = mysqli_fetch_assoc($qLitProg)) {
        if ($row['batas_waktu'] && strtotime($row['waktu_selesai']) > strtotime($row['batas_waktu'])) {
            $lateCount++;
        } else {
            $onTimeCount++;
        }
    }
}

// Tambahkan 5 poin untuk setiap tugas tepat waktu, kurangi 2 poin untuk terlambat.
$bonusTugas = ($onTimeCount * 5) - ($lateCount * 2);
$avgScoreBulanIni += $bonusTugas;
if ($avgScoreBulanIni < 0) $avgScoreBulanIni = 0;
PHP;

$content = str_replace(
    "\$totalJurnalBulanIni = (int)(\$monthStat['total_jurnal'] ?? 0);\n\$avgScoreBulanIni = (float)(\$monthStat['avg_score_month'] ?? 0);",
    $logic,
    $content
);

// Display the bonus in the UI
$old_ui = '<div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-blue-600 font-bold uppercase">Rata-rata Skor</div>
                        <div class="text-lg font-black text-blue-900"><?= number_format($avgScoreBulanIni, 1) ?></div>
                    </div>
                    <i class="fa-solid fa-star text-2xl text-blue-200"></i>
                </div>';

$new_ui = '<div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-blue-600 font-bold uppercase">Skor Akhir + Apresiasi</div>
                        <div class="text-lg font-black text-blue-900"><?= number_format($avgScoreBulanIni, 1) ?></div>
                        <div class="text-[9px] text-blue-500 mt-1">Termasuk Bonus Tugas: <?= $bonusTugas > 0 ? "+".$bonusTugas : $bonusTugas ?> Poin (<?= $onTimeCount ?> Tepat, <?= $lateCount ?> Telat)</div>
                    </div>
                    <i class="fa-solid fa-star text-2xl text-blue-200"></i>
                </div>';
                
$content = str_replace($old_ui, $new_ui, $content);
file_put_contents($file, $content);
echo "Updated jurnal-7kih.php with bonus logic\n";
?>
