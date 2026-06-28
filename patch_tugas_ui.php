<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\tugas.php';
$content = file_get_contents($file);

// Replace query to include batas_waktu
$content = str_replace("SELECT t.*, ts.status AS status_siswa", "SELECT t.*, t.batas_waktu, ts.status AS status_siswa, ts.waktu_submit", $content);

// In the HTML loop, we need to show batas_waktu and terlambat status.
// I'll search for the task rendering section.
$old_html = '<p class="text-xs text-gray-500 mb-3"><i class="fas fa-clock mr-1"></i>Diberikan: <?= date(\'d M Y\', strtotime($task[\'created_at\'])) ?></p>';
$new_html = '<p class="text-xs text-gray-500 mb-1"><i class="fas fa-clock mr-1"></i>Diberikan: <?= date(\'d M Y\', strtotime($task[\'created_at\'])) ?></p>
                            <?php 
                            $badgeLate = "";
                            if ($task[\'batas_waktu\']) {
                                $tenggatTime = strtotime($task[\'batas_waktu\']);
                                $isLate = ($task[\'status_siswa\'] != \'Selesai\' && time() > $tenggatTime) || ($task[\'status_siswa\'] == \'Selesai\' && strtotime($task[\'waktu_submit\']) > $tenggatTime);
                                if ($isLate) $badgeLate = \'<span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded ml-2">Terlambat</span>\';
                                elseif ($task[\'status_siswa\'] == \'Selesai\') $badgeLate = \'<span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded ml-2">Tepat Waktu</span>\';
                            }
                            ?>
                            <?php if ($task[\'batas_waktu\']): ?>
                            <p class="text-xs text-red-500 font-medium mb-3"><i class="fas fa-exclamation-circle mr-1"></i>Tenggat: <?= date(\'d M Y H:i\', strtotime($task[\'batas_waktu\'])) ?> <?= $badgeLate ?></p>
                            <?php else: ?>
                            <p class="text-xs text-gray-500 mb-3"><i class="fas fa-calendar mr-1"></i>Tidak ada tenggat waktu</p>
                            <?php endif; ?>';
$content = str_replace($old_html, $new_html, $content);
file_put_contents($file, $content);
echo "Updated tugas.php";
?>
