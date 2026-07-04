<?php
$file = 'c:/xampp/htdocs/jurnal/pages/siswa/jurnal-7kih.php';
$content = file_get_contents($file);

// 1. ADD MONTHLY STATS QUERY
$monthlyStatsQuery = <<<'PHP'
$qMonthStat = @mysqli_query($conn, "
    SELECT COUNT(*) AS total_jurnal, AVG(score) AS avg_score_month, COUNT(DISTINCT tanggal) AS total_hari
    FROM tbl_7kih_jurnal
    WHERE no_induk='$nisEsc' AND DATE_FORMAT(tanggal, '%Y-%m')='$monthEsc'
");
$monthStat = $qMonthStat ? mysqli_fetch_assoc($qMonthStat) : ['total_jurnal'=>0, 'avg_score_month'=>0, 'total_hari'=>0];
$totalJurnalBulanIni = (int)($monthStat['total_jurnal'] ?? 0);
$avgScoreBulanIni = (float)($monthStat['avg_score_month'] ?? 0);
$hariAktifBulanIni = (int)($monthStat['total_hari'] ?? 0);
$daysInMonth = (int)date('t', strtotime($today));
$expectedMonth = $daysInMonth * $expectedToday;
$pctMonth = $expectedMonth > 0 ? min(100, round(($totalJurnalBulanIni / $expectedMonth) * 100)) : 0;
PHP;

$content = str_replace(
    'while ($qHist && ($row = mysqli_fetch_assoc($qHist))) {
    $history[] = $row;
}',
    'while ($qHist && ($row = mysqli_fetch_assoc($qHist))) {
    $history[] = $row;
}
' . $monthlyStatsQuery,
    $content
);

// 2. ADD REKAP UI SECTION
// Insert after the Pilih Jurnal section (before Riwayat Bulan Ini)
// Let's find: `    <section class="card p-4 mt-4">` that contains Riwayat Bulan Ini
// Or just inject right before `<section class="card p-4 mt-4">\n        <h2 class="font-black text-slate-900 mb-2">Riwayat Bulan Ini</h2>`

$rekapUI = <<<'HTML'
    <section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-3"><i class="fa-solid fa-chart-pie text-emerald-600 mr-1"></i> Rekapitulasi</h2>
        
        <!-- Rekap Harian -->
        <div class="mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Hari Ini</h3>
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <?php 
                    foreach ($habits as $key => $habit) {
                        if ($key === 'beribadah' && $isIslam) {
                            foreach ($prayers as $pKey => $prayer) {
                                $isDone = isset($done[$key . '|' . $pKey]);
                                $color = $isDone ? 'text-emerald-600' : 'text-slate-400';
                                $icon = $isDone ? 'fa-circle-check' : 'fa-clock';
                                $label = $prayer['label'];
                                echo "<div class='flex items-center gap-1.5 $color truncate' title='Sholat $label'><i class='fa-solid $icon'></i> <span class='truncate'>Sholat $label</span></div>";
                            }
                        } else {
                            $isDone = isset($done[$key . '|']);
                            $color = $isDone ? 'text-emerald-600' : 'text-slate-400';
                            $icon = $isDone ? 'fa-circle-check' : 'fa-clock';
                            $label = $habit['label'];
                            echo "<div class='flex items-center gap-1.5 $color truncate' title='$label'><i class='fa-solid $icon'></i> <span class='truncate'>$label</span></div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Rekap Bulanan -->
        <div>
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Bulan Ini (<?= date('M Y') ?>)</h3>
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-emerald-600 font-bold uppercase">Total Terkirim</div>
                        <div class="text-lg font-black text-emerald-900"><?= $totalJurnalBulanIni ?> <span class="text-xs font-normal text-emerald-700">/ <?= $expectedMonth ?></span></div>
                    </div>
                    <i class="fa-solid fa-list-check text-2xl text-emerald-200"></i>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-blue-600 font-bold uppercase">Rata-rata Skor</div>
                        <div class="text-lg font-black text-blue-900"><?= number_format($avgScoreBulanIni, 1) ?></div>
                    </div>
                    <i class="fa-solid fa-star text-2xl text-blue-200"></i>
                </div>
                <div class="col-span-2 bg-slate-50 border border-slate-100 rounded-xl p-3">
                    <div class="flex justify-between items-center mb-1">
                        <div class="text-[10px] text-slate-500 font-bold uppercase">Tingkat Penyelesaian</div>
                        <div class="text-xs font-black text-slate-700"><?= $pctMonth ?>%</div>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5">
                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?= $pctMonth ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

HTML;

$content = str_replace(
    '<section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-2">Riwayat Bulan Ini</h2>',
    $rekapUI . '
    <section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-2">Riwayat Bulan Ini</h2>',
    $content
);

file_put_contents($file, $content);
echo "Successfully updated jurnal-7kih.php\n";
?>
