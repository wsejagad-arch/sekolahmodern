<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

$old_button = "                    <button type=\"button\" class=\"habit-btn <?= \$d ? 'done' : ''; ?> <?= \$disabled ?> w-full text-left border border-slate-200 rounded-2xl p-3 flex gap-3 items-start\" data-habit=\"<?= kihs_h(\$key); ?>\" data-prayer=\"\" data-title=\"<?= kihs_h(\$habit['label']); ?>\">
                        <div class=\"w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 grid place-items-center\"><i class=\"fa-solid <?= kihs_h(\$habit['icon']); ?>\"></i></div>
                        <div class=\"flex-1 min-w-0\">
                            <div class=\"flex justify-between gap-2\">
                                <div class=\"font-black text-slate-900\"><?= kihs_h(\$habit['label']); ?></div>
                                <span class=\"text-[11px] text-slate-500\"><?= kihs_h(\$habit['start']); ?>-<?= kihs_h(\$habit['end']); ?></span>
                            </div>
                            <div class=\"text-xs text-slate-500\"><?= kihs_h(\$habit['hint']); ?></div>
                            <div class=\"text-[11px] mt-1 <?= \$d ? 'text-emerald-700' : 'text-slate-400'; ?>\">";

$new_button = "                    <button type=\"button\" class=\"habit-btn <?= \$d ? 'done bg-emerald-50/50' : ''; ?> <?= \$disabled ?> w-full text-left border border-slate-200 rounded-2xl p-3 flex gap-3 items-start transition-colors\" data-habit=\"<?= kihs_h(\$key); ?>\" data-prayer=\"\" data-title=\"<?= kihs_h(\$habit['label']); ?>\">
                        <div class=\"w-11 h-11 rounded-2xl <?= \$d ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700' ?> grid place-items-center transition-colors\">
                            <i class=\"fa-solid <?= \$d ? 'fa-check' : kihs_h(\$habit['icon']); ?>\"></i>
                        </div>
                        <div class=\"flex-1 min-w-0\">
                            <div class=\"flex justify-between gap-2\">
                                <div class=\"font-black <?= \$d ? 'text-emerald-700' : 'text-slate-900' ?>\">
                                    <?= kihs_h(\$habit['label']); ?>
                                    <?= \$d ? '<span class=\"text-[10px] bg-emerald-100 px-2 py-0.5 rounded-full ml-1\">Selesai</span>' : '' ?>
                                </div>
                                <span class=\"text-[11px] <?= \$d ? 'text-emerald-600/70' : 'text-slate-500' ?>\"><?= kihs_h(\$habit['start']); ?>-<?= kihs_h(\$habit['end']); ?></span>
                            </div>
                            <div class=\"text-xs <?= \$d ? 'text-emerald-600/70' : 'text-slate-500' ?>\"><?= kihs_h(\$habit['hint']); ?></div>
                            <div class=\"text-[11px] mt-1 <?= \$d ? 'text-emerald-700' : 'text-slate-400'; ?>\">";

$content = str_replace($old_button, $new_button, $content);
file_put_contents($file, $content);
echo "Updated buttons successfully.";
?>
