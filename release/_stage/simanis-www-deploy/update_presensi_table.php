<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\presensi.php';
$content = file_get_contents($file);

$start = strpos($content, '<div class="divide-y divide-gray-100 max-h-80 overflow-y-auto pr-2">');
$end = strpos($content, '</div>', strpos($content, '<?php endforeach; ?>')); // finding end of detail list div
if ($start !== false && $end !== false) {
    $new_content = substr($content, 0, $start) . '<div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-600 border-collapse">
                                <thead class="bg-gray-50 border-b text-[10px] uppercase text-gray-500 font-bold tracking-wider">
                                    <tr>
                                        <th class="py-2 px-3">No</th>
                                        <th class="py-2 px-3 whitespace-nowrap">Hari/Tgl</th>
                                        <th class="py-2 px-3">Mata Pelajaran</th>
                                        <th class="py-2 px-3 text-center">Status</th>
                                        <th class="py-2 px-3 text-center whitespace-nowrap">Ket Akhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php
                                    $rekapHarian = [];
                                    foreach ($detailList as $row) {
                                        $tgl = $row[\'tanggal\'];
                                        if (!isset($rekapHarian[$tgl])) {
                                            $rekapHarian[$tgl] = [
                                                \'mapel\' => [],
                                                \'status\' => [],
                                                \'last_status\' => \'\'
                                            ];
                                        }
                                        $st = strtolower(trim($row[\'status\']));
                                        $huruf = \'A\';
                                        if ($st == \'hadir\') $huruf = \'H\';
                                        elseif ($st == \'ijin\' || $st == \'izin\') $huruf = \'I\';
                                        elseif ($st == \'sakit\') $huruf = \'S\';
                                        elseif ($st == \'dispen\' || $st == \'dispensasi\') $huruf = \'D\';
                                        else $huruf = strtoupper(substr($st, 0, 1));
                                        
                                        $rekapHarian[$tgl][\'mapel\'][] = $row[\'nama_mapel\'];
                                        $rekapHarian[$tgl][\'status\'][] = $huruf;
                                        $rekapHarian[$tgl][\'last_status\'] = $huruf;
                                    }

                                    $no = 1;
                                    foreach ($rekapHarian as $tgl => $d):
                                        $counts = array_count_values($d[\'status\']);
                                        $hadir = $counts[\'H\'] ?? 0;
                                        $total = count($d[\'status\']);
                                        $nonHadir = $total - $hadir;
                                        
                                        $ket = \'A\';
                                        if ($hadir > $nonHadir) {
                                            $ket = \'H\';
                                        } elseif ($hadir == $nonHadir) {
                                            $ket = $d[\'last_status\'];
                                        } else {
                                            $maxC = 0; $maxS = \'A\';
                                            foreach ($counts as $s => $c) {
                                                if ($s != \'H\' && $c > $maxC) {
                                                    $maxC = $c; $maxS = $s;
                                                }
                                            }
                                            $ket = $maxS;
                                        }

                                        $hariTgl = function_exists(\'tgl_indo\') ? tgl_indo($tgl) : date(\'d M Y\', strtotime($tgl));
                                        $mapelStr = implode(\'<br>\', $d[\'mapel\']);
                                        $statusStr = implode(\'<br>\', $d[\'status\']);
                                        
                                        $badgeKet = \'bg-red-100 text-red-700\';
                                        if ($ket == \'H\') $badgeKet = \'bg-green-100 text-green-700\';
                                        elseif (in_array($ket, [\'I\',\'S\',\'D\'])) $badgeKet = \'bg-yellow-100 text-yellow-700\';
                                    ?>
                                    <tr>
                                        <td class="py-2 px-3 align-top font-bold text-gray-500"><?= $no++ ?></td>
                                        <td class="py-2 px-3 align-top whitespace-nowrap font-bold text-[11px] text-gray-800"><?= htmlspecialchars($hariTgl) ?></td>
                                        <td class="py-2 px-3 align-top text-[11px] leading-relaxed text-gray-600"><?= $mapelStr ?></td>
                                        <td class="py-2 px-3 align-top text-[11px] text-center font-black leading-relaxed"><?= $statusStr ?></td>
                                        <td class="py-2 px-3 align-middle text-center">
                                            <span class="inline-block px-3 py-1 rounded-full text-[11px] font-black <?= $badgeKet ?>">
                                                <?= $ket ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>' . substr($content, $end + 6); // Add 6 for </div>
    file_put_contents($file, $new_content);
    echo "Replaced details with table recap.";
} else {
    echo "Could not find insertion points.";
}
?>
