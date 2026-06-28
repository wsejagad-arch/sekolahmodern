<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

// 1. Update kihs_prayers
$old_prayers = "'subuh' => ['label' => 'Subuh', 'start' => '04:00', 'end' => '06:00'],
            'dzuhur' => ['label' => 'Dzuhur', 'start' => '11:30', 'end' => '13:30'],
            'ashar' => ['label' => 'Ashar', 'start' => '15:00', 'end' => '16:30'],
            'maghrib' => ['label' => 'Maghrib', 'start' => '17:30', 'end' => '18:30'],
            'isya' => ['label' => 'Isya', 'start' => '19:00', 'end' => '20:30'],";

$new_prayers = "'subuh' => ['label' => 'Subuh', 'start' => '04:00', 'end' => '05:59'],
            'dzuhur' => ['label' => 'Dzuhur', 'start' => '11:30', 'end' => '13:30'],
            'ashar' => ['label' => 'Ashar', 'start' => '14:30', 'end' => '17:30'],
            'maghrib' => ['label' => 'Maghrib', 'start' => '17:30', 'end' => '19:00'],
            'isya' => ['label' => 'Isya', 'start' => '19:00', 'end' => '03:59'],";

$content = str_replace($old_prayers, $new_prayers, $content);

// 2. Update Beribadah main button color
// Look for <button type="button" class="w-full text-left flex gap-3 items-start mb-2" onclick="document.getElementById('prayers-grid').classList.toggle('hidden')">
// We need to inject the parent checked logic.

$parent_logic = "<?php if (\$key === 'beribadah'): ?>
                    <div class=\"border border-slate-200 rounded-2xl p-3\">";
                    
$parent_logic_new = "<?php if (\$key === 'beribadah'): ?>
                    <?php 
                        \$isAllPrayersDone = false;
                        if (!empty(\$prayers)) {
                            \$c = 0;
                            foreach (\$prayers as \$pk => \$pv) {
                                if (isset(\$done['beribadah|' . \$pk])) \$c++;
                            }
                            \$isAllPrayersDone = (\$c === count(\$prayers));
                        }
                    ?>
                    <div class=\"border border-slate-200 rounded-2xl p-3 <?= \$isAllPrayersDone ? 'bg-emerald-50/50' : '' ?>\">";

$content = str_replace($parent_logic, $parent_logic_new, $content);

$button_logic = "<button type=\"button\" class=\"w-full text-left flex gap-3 items-start mb-2\" onclick=\"document.getElementById('prayers-grid').classList.toggle('hidden')\">
                            <div class=\"w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 grid place-items-center\"><i class=\"fa-solid <?= kihs_h(\$habit['icon']); ?>\"></i></div>
                            <div class=\"flex-1\">
                                <div class=\"font-black text-slate-900\"><?= kihs_h(\$habit['label']); ?></div>
                                <div class=\"text-xs text-slate-500\">Isi daftar ibadah sesuai keyakinan (Klik)</div>
                            </div>
                            <i class=\"fa-solid fa-chevron-down text-slate-400 mt-2\"></i>
                        </button>";

$button_logic_new = "<button type=\"button\" class=\"w-full text-left flex gap-3 items-start mb-2 <?= \$isAllPrayersDone ? 'done' : '' ?>\" onclick=\"document.getElementById('prayers-grid').classList.toggle('hidden')\" data-title=\"Semua Ibadah\">
                            <div class=\"w-11 h-11 rounded-2xl <?= \$isAllPrayersDone ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700' ?> grid place-items-center transition-colors\">
                                <i class=\"fa-solid <?= \$isAllPrayersDone ? 'fa-check' : kihs_h(\$habit['icon']); ?>\"></i>
                            </div>
                            <div class=\"flex-1\">
                                <div class=\"font-black <?= \$isAllPrayersDone ? 'text-emerald-700' : 'text-slate-900' ?>\"><?= kihs_h(\$habit['label']); ?> <?= \$isAllPrayersDone ? '<span class=\"text-[10px] bg-emerald-100 px-2 py-0.5 rounded-full ml-1\">Selesai Semua</span>' : '' ?></div>
                                <div class=\"text-xs <?= \$isAllPrayersDone ? 'text-emerald-600/70' : 'text-slate-500' ?>\"><?= \$isAllPrayersDone ? 'Alhamdulillah, semua kewajiban hari ini tertunaikan.' : 'Isi daftar ibadah sesuai keyakinan (Klik)' ?></div>
                            </div>
                            <i class=\"fa-solid fa-chevron-down <?= \$isAllPrayersDone ? 'text-emerald-400' : 'text-slate-400' ?> mt-2 transition-transform\"></i>
                        </button>";

$content = str_replace($button_logic, $button_logic_new, $content);

file_put_contents($file, $content);
echo "Updated jurnal-7kih.php successfully.";
?>
