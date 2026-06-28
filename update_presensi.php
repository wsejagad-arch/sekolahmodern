<?php
$file = 'c:/xampp/htdocs/jurnal/pages/siswa/presensi.php';
$lines = file($file);

$newHtml = <<<'HTML'
    <style>
        body {
            background-color: #f4f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 90px;
        }

        .header-bg {
            background: linear-gradient(135deg, #0052D4, #4364F7, #6FB1FC);
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            padding-bottom: 40px;
            margin-bottom: -30px;
        }

        .card-shadow {
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .bottom-nav {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 50px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            display: flex;
            padding: 10px 30px;
            gap: 40px;
            z-index: 50;
            width: max-content;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 11px;
            color: #9ca3af;
            text-decoration: none;
            gap: 4px;
            position: relative;
        }

        .nav-item.active {
            color: #3b82f6;
            font-weight: 600;
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -6px;
            width: 5px;
            height: 5px;
            background-color: #3b82f6;
            border-radius: 50%;
        }

        /* kamera modal */
        #cameraModal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, .7);
            align-items: center;
            justify-content: center;
        }

        #cameraModal.open {
            display: flex;
        }

        #videoWrap {
            position: relative;
            width: min(360px, 90vw);
            border-radius: 1rem;
            overflow: hidden;
        }

        #previewVideo {
            width: 100%;
            display: block;
            transform: scaleX(-1);
        }

        #faceCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            transform: scaleX(-1);
        }

        #ovalGuide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 62%;
            padding-bottom: 80%;
            border-radius: 50%;
            border: 3px dashed rgba(255, 255, 255, .6);
            pointer-events: none;
        }

        .face-ok {
            border-color: #22c55e !important;
        }

        .face-fail {
            border-color: #ef4444 !important;
        }

        .badge-siswa {
            background: #ede9fe;
            color: #7c3aed;
        }

        .badge-guru {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>
    <div class="header-bg pt-6 px-4 pb-12 sm:px-6">
        <div class="max-w-4xl mx-auto flex justify-between items-center relative z-10">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 rounded-full shadow-sm flex items-center justify-center" style="width:42px;height:42px;">
                    <i class="fas fa-fingerprint text-blue-500 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white leading-tight">Presensi Saya</h1>
                    <p class="text-[11px] text-blue-100 mt-0.5">
                        <?= htmlspecialchars(strtoupper($namaSiswa)) ?> &middot; KELAS <?= htmlspecialchars(strtoupper($kelas)) ?>
                    </p>
                </div>
            </div>
            <a href="siswa.php" class="bg-white text-blue-600 hover:bg-gray-50 text-xs font-semibold px-4 py-2 rounded-full shadow-sm flex items-center transition-colors">
                <i class="fas fa-arrow-left mr-1.5"></i>Kembali
            </a>
        </div>
    </div>

    <div class="w-full max-w-4xl mx-auto px-4 sm:px-6 relative z-20">
        
        <!-- PRESENSI MANDIRI CARD -->
        <div class="mb-5 bg-white border border-gray-100 rounded-xl p-4 shadow-sm card-shadow">
            <h2 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center">
                <i class="fas fa-calendar-check mr-2 text-blue-500 text-sm"></i> PRESENSI MANDIRI — <?= strtoupper(htmlspecialchars($hariIndo)) ?>, <?= strtoupper(tgl_indo($tglHariIni)) ?>
            </h2>

            <?php if (empty($jadwalHariIni)): ?>
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500"><i class="fas fa-bed mr-2 text-gray-300"></i>Tidak ada jadwal pelajaran hari ini.</p>
                </div>
            <?php else: ?>
                <?php
                $lastMapelId = null;
                $_maxSel = '';
                foreach ($jadwalHariIni as $_j) {
                    if (isset($_j['jam_selesai']) && strcmp($_j['jam_selesai'], $_maxSel) > 0) {
                        $_maxSel = $_j['jam_selesai'];
                        $lastMapelId = (int)$_j['id_mapel'];
                    }
                }
                ?>
                <div class="space-y-4" id="jadwalList">
                    <?php foreach ($jadwalHariIni as $idx => $jdw):
                        $jamMul = !empty($jdw['jam_mulai']) ? date('H:i', strtotime($jdw['jam_mulai'])) : '-';
                        $jamSel = !empty($jdw['jam_selesai']) ? date('H:i', strtotime($jdw['jam_selesai'])) : '';
                        $jam = ($jamSel && $jamSel !== '-') ? "$jamMul - $jamSel" : $jamMul;
                        $sudahAbsen = !empty($jdw['status_absen']);
                        $byGuru = ($jdw['sumber_absen'] ?? '') === 'guru';
                        $bySiswa = ($jdw['sumber_absen'] ?? '') === 'siswa';
                        $isLast = ((int)$jdw['id_mapel'] === (int)$lastMapelId);
                        
                        $nm = strtoupper($jdw['nama_mapel']);
                        if (strpos($nm, 'BAHASA') !== false) {
                            $iconBg = 'bg-cyan-400'; $iconClass = 'fa-comment-dots';
                        } elseif (strpos($nm, 'PKN') !== false || strpos($nm, 'PENDIDIKAN PANCASILA') !== false) {
                            $iconBg = 'bg-blue-400'; $iconClass = 'fa-book-open';
                        } else {
                            $iconBg = 'bg-blue-600'; $iconClass = 'fa-users';
                        }
                    ?>
                        <div class="flex items-center justify-between <?= $idx > 0 ? 'border-t border-gray-100 pt-4' : '' ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0 w-10 h-10 <?= $iconBg ?> text-white rounded-full flex items-center justify-center shadow-sm">
                                    <i class="fas <?= $iconClass ?> text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($nm) ?></p>
                                    <div class="flex items-center text-[11px] text-gray-500 mt-0.5 gap-3">
                                        <span><i class="fas fa-clock mr-1 text-gray-400"></i><?= $jam ?></span>
                                        <?php if (!empty($jdw['nama_guru'])): ?>
                                            <span class="truncate"><i class="fas fa-user mr-1 text-gray-400"></i><?= htmlspecialchars(strtoupper($jdw['nama_guru'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="shrink-0 flex flex-col items-end gap-1.5 pl-2">
                                <?php if ($sudahAbsen): ?>
                                    <span class="text-[11px] font-semibold px-3 py-1 rounded-full <?= $byGuru ? 'badge-guru' : ($bySiswa ? 'badge-siswa' : 'bg-green-100 text-green-700') ?>">
                                        <i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars(strtoupper($jdw['status_absen'])) ?>
                                    </span>
                                <?php else: ?>
                                    <button type="button"
                                        class="btn-absen-mandiri text-[12px] font-semibold px-4 py-1.5 rounded-lg text-white shadow-sm transition-all active:scale-95 flex items-center
                                        <?= $isLast ? 'bg-blue-800 hover:bg-blue-900' : 'bg-blue-500 hover:bg-blue-600' ?>"
                                        data-idmapel="<?= (int)$jdw['id_mapel'] ?>"
                                        data-mapel="<?= htmlspecialchars($jdw['nama_mapel'], ENT_QUOTES) ?>"
                                        data-jam-mulai="<?= htmlspecialchars($jdw['jam_mulai'] ?? '') ?>"
                                        data-jam-selesai="<?= htmlspecialchars($jdw['jam_selesai'] ?? '') ?>"
                                        data-is-last="<?= $isLast ? '1' : '0' ?>"
                                        onclick="openCameraForMapel(<?= (int)$jdw['id_mapel'] ?>, '<?= htmlspecialchars($jdw['nama_mapel'], ENT_QUOTES | ENT_HTML5) ?>', '<?= htmlspecialchars($jdw['jam_mulai'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($jdw['jam_selesai'] ?? '', ENT_QUOTES) ?>', <?= $isLast ? 'true' : 'false' ?>)">
                                        <i class="fas <?= $isLast ? 'fa-sign-out-alt' : 'fa-fingerprint' ?> mr-1.5"></i><?= $isLast ? 'Absen Pulang' : 'Absen' ?>
                                    </button>
                                <?php endif; ?>
                                <div id="time-ind-<?= (int)$jdw['id_mapel'] ?>" class="text-[10px] text-right font-medium"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── KONFIRMASI KEHADIRAN GURU (Ketua Kelas) ─────────────────────── -->
        <?php if ($isKetuaKelas): ?>
            <div class="mb-5 bg-white border border-amber-100 rounded-xl p-4 shadow-sm card-shadow">
                <h2 class="text-xs font-bold text-amber-600 uppercase tracking-wide mb-1 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i> Konfirmasi Kehadiran Guru
                </h2>
                <p class="text-[11px] text-amber-600/70 mb-4">Konfirmasikan kehadiran guru mata pelajaran pada hari ini.</p>

                <?php if (empty($jadwalHariIni)): ?>
                    <p class="text-sm text-gray-500 italic"><i class="fas fa-calendar-times mr-1"></i>Tidak ada jadwal hari ini.</p>
                <?php else: ?>
                    <div class="space-y-3" id="konfirmasiList">
                        <?php foreach ($jadwalHariIni as $idx => $jdw):
                            $idMapelK = (int)$jdw['id_mapel'];
                            $konf     = $konfirmasiHariIni[$idMapelK] ?? null;
                            $jamMulK  = !empty($jdw['jam_mulai'])   ? date('H:i', strtotime($jdw['jam_mulai']))   : '-';
                            $jamSelK  = !empty($jdw['jam_selesai']) ? date('H:i', strtotime($jdw['jam_selesai'])) : '';
                            $jamK     = ($jamSelK && $jamSelK !== '-') ? "$jamMulK \u2013 $jamSelK" : $jamMulK;
                        ?>
                            <div class="<?= $idx > 0 ? 'border-t border-gray-100 pt-3' : '' ?>" id="konfirm-card-<?= $idMapelK ?>">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($jdw['nama_mapel']) ?></p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            <i class="fas fa-chalkboard-teacher mr-1"></i><?= htmlspecialchars($jdw['nama_guru'] ?? '-') ?>
                                            &nbsp;&middot;&nbsp;<i class="fas fa-clock mr-1"></i><?= $jamK ?>
                                        </p>
                                    </div>
                                    <?php if ($konf): ?>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded <?= konfirmasiStatusBadge($konf['status']) ?> shrink-0" id="badge-<?= $idMapelK ?>">
                                            <?= htmlspecialchars($konf['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($konf): ?>
                                    <div class="flex justify-between items-center">
                                        <div class="text-[11px] text-gray-400"><i class="fas fa-check-circle text-green-400 mr-1"></i>Sudah dikonfirmasi
                                            <?php if (!empty($konf['catatan'])): ?> &middot; <em><?= htmlspecialchars($konf['catatan']) ?></em><?php endif; ?>
                                        </div>
                                        <button type="button" onclick="hapusKonfirmasi(<?= $idMapelK ?>)" class="text-[11px] text-red-400 hover:text-red-600 font-semibold">
                                            <i class="fas fa-undo mr-0.5"></i>Batal
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1.5" id="konfirm-buttons-<?= $idMapelK ?>">
                                        <?php foreach (['Hadir', 'Telat', 'Izin', 'Tidak Hadir Tanpa Tugas', 'Tidak Hadir Ada Tugas'] as $optK): ?>
                                            <button type="button" onclick="kirimKonfirmasi(<?= $idMapelK ?>, this)"
                                                data-status="<?= htmlspecialchars($optK, ENT_QUOTES) ?>"
                                                class="text-[11px] font-semibold px-3 py-1 rounded-full border <?= konfirmasiButtonColor($optK) ?> hover:opacity-80 transition-all active:scale-95">
                                                <?= htmlspecialchars($optK) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- FILTER BULAN & REKAP -->
        <div class="mb-24 flex flex-col gap-4">
            
            <form method="GET" action="presensi.php" class="bg-white rounded-xl p-3 shadow-sm border border-gray-100 flex items-center justify-between card-shadow">
                <label class="text-xs font-bold text-gray-600 flex items-center">
                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Pilih Bulan
                </label>
                <div class="flex gap-2 items-center">
                    <select name="bulan"
                        class="border-none bg-gray-50 rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-700
                               focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <?php foreach ($bulanList as $bl): ?>
                            <option value="<?= $bl ?>" <?= ($bl === $bulan) ? 'selected' : '' ?>>
                                <?= strtoupper(bulanIndo($bl)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all active:scale-95">
                        Tampilkan
                    </button>
                </div>
            </form>

            <?php if (!$tblExists): ?>
                <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-xl p-4 text-sm card-shadow">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Tabel absensi belum tersedia. Silakan hubungi administrator.
                </div>
            <?php else: ?>

                <!-- Rekap -->
                <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm card-shadow">
                    <h2 class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-blue-500 text-sm"></i> REKAP — <?= strtoupper(bulanIndo($bulan)) ?>
                    </h2>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="flex items-center gap-2 text-gray-600 font-medium"><div class="w-2.5 h-2.5 rounded-full bg-green-500"></div> Hadir</span>
                            <span class="font-bold text-gray-800"><?= $summary['Hadir'] ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="flex items-center gap-2 text-gray-600 font-medium"><div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div> Ijin</span>
                            <span class="font-bold text-gray-800"><?= $summary['Ijin'] ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="flex items-center gap-2 text-gray-600 font-medium"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div> Alpha</span>
                            <span class="font-bold text-gray-800"><?= $summary['Alpha'] ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="flex items-center gap-2 text-gray-600 font-medium"><div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div> Sakit/Dispen</span>
                            <span class="font-bold text-gray-800"><?= $summary['Sakit'] + $summary['Dispen'] ?></span>
                        </div>
                    </div>

                    <?php if ($totalPertemuan > 0): ?>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex justify-between text-xs font-bold text-gray-600 mb-2">
                                <span>Tingkat Kehadiran</span>
                                <span class="<?= ($pctHadir >= 75) ? 'text-green-500' : 'text-red-500' ?>"><?= $pctHadir ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="progress-bar <?= ($pctHadir >= 75) ? 'bg-green-500' : 'bg-red-400' ?> h-2 rounded-full"
                                    style="width: <?= $pctHadir ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Detail Empty State or List -->
                <?php if (empty($detailList)): ?>
                    <div class="bg-white border border-gray-100 rounded-xl p-8 shadow-sm flex flex-col items-center justify-center card-shadow">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-clipboard-list text-blue-300 text-3xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium text-sm text-center">Tidak ada data presensi<br>pada <?= bulanIndo($bulan) ?>.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm card-shadow mb-8">
                        <div class="mb-3 text-xs font-bold text-gray-600 uppercase tracking-wide flex items-center">
                            <i class="fas fa-list-ul mr-2 text-blue-500"></i>Detail Pertemuan
                        </div>
                        <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto pr-2">
                            <?php
                            $currentDate = null;
                            foreach ($detailList as $row):
                                $tgl    = $row['tanggal'];
                                $mapel  = $row['nama_mapel'] ?? '-';
                                $guru   = $row['nama_guru']  ?? '-';
                                $jamMul = !empty($row['jam_mulai'])   ? date('H:i', strtotime($row['jam_mulai']))   : '';
                                $jamSel = !empty($row['jam_selesai']) ? date('H:i', strtotime($row['jam_selesai'])) : '';
                                $jam    = ($jamMul && $jamSel) ? "$jamMul – $jamSel" : ($jamMul ?: '-');
                                $badge  = statusBadge($row['status']);

                                if ($tgl !== $currentDate):
                                    $currentDate = $tgl;
                                    $hariTgl = function_exists('tgl_indo') ? tgl_indo($tgl) : date('d M Y', strtotime($tgl));
                            ?>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pt-3 pb-1">
                                        <?= htmlspecialchars($hariTgl) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between py-2.5">
                                    <div class="flex-1 min-w-0 pr-3">
                                        <p class="text-sm font-bold text-gray-800 truncate">
                                            <?= htmlspecialchars($mapel) ?>
                                        </p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            <i class="fas fa-chalkboard-teacher mr-1"></i><?= htmlspecialchars($guru) ?>
                                            <?php if ($jam !== '-'): ?>
                                                &nbsp;&middot;&nbsp;<i class="fas fa-clock mr-1"></i><?= $jam ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-bold px-3 py-1 rounded-full <?= $badge ?>">
                                        <?= htmlspecialchars(strtoupper($row['status'])) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- BOTTOM NAV -->
    <div class="bottom-nav">
        <a href="siswa.php" class="nav-item active">
            <i class="fas fa-home text-lg mb-1"></i>
            Beranda
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-book text-lg mb-1"></i>
            Studi
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-bell text-lg mb-1"></i>
            Notifikasi
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-user text-lg mb-1"></i>
            Profil
        </a>
    </div>

    <!-- ── MODAL KAMERA & WAJAH ───────────────────────────────────────── -->
    <div id="cameraModal" role="dialog" aria-modal="true" aria-labelledby="cameraTitle">
        <div class="bg-white rounded-2xl shadow-2xl w-min(380px,95vw) p-5 mx-4 max-w-sm w-full relative">
            <h3 id="cameraTitle" class="text-base font-bold text-gray-800 mb-1 text-center">
                <i class="fas fa-camera text-blue-500 mr-1"></i>Verifikasi Wajah
            </h3>
            <p class="text-xs text-gray-500 text-center mb-4" id="cameraSubtitle">Posisikan wajah Anda di dalam oval</p>

            <div id="videoWrap" class="mx-auto border-4 border-gray-100">
                <video id="previewVideo" autoplay playsinline muted></video>
                <canvas id="faceCanvas"></canvas>
                <div id="ovalGuide"></div>
            </div>

            <!-- Status deteksi -->
            <div id="faceStatus" class="mt-4 text-center text-sm font-medium text-gray-500 bg-gray-50 rounded-lg py-2">
                <i class="fas fa-spinner fa-spin mr-1 text-blue-500"></i>Memuat model deteksi…
            </div>

            <!-- Petunjuk -->
            <ul class="mt-3 text-[11px] text-gray-500 list-none space-y-1 bg-blue-50/50 p-3 rounded-lg border border-blue-100">
                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i>Hadapkan wajah ke kamera (depan)</li>
                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i>Pastikan cahaya cukup</li>
                <li class="flex items-center gap-2"><i class="fas fa-times-circle text-red-500"></i>Wajah miring/samping tidak terdeteksi</li>
            </ul>

            <!-- Tombol -->
            <div class="mt-4 flex gap-2">
                <button id="btnAbsenKonfirm"
                    disabled
                    class="flex-1 bg-blue-500 text-white text-sm font-bold py-2.5 rounded-xl opacity-50 cursor-not-allowed transition-all shadow-sm">
                    <i class="fas fa-check mr-1"></i>Konfirmasi
                </button>
                <button onclick="closeCamera()"
                    class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
                    Batal
                </button>
            </div>
            <div id="modalMsg" class="mt-3 text-xs text-center font-medium"></div>
        </div>
    </div>

HTML;

$startLine = 333; // 0-indexed: line 334 is <style>
$endLine = 739; // 0-indexed: line 740 is </div> right before <script> (Wait, let's verify)
// Actually, it's safer to find the indices by string matching.

$startIdx = -1;
$endIdx = -1;

for ($i = 0; $i < count($lines); $i++) {
    if (trim($lines[$i]) === '<style>' && $startIdx === -1 && $i > 300) {
        $startIdx = $i;
    }
    if (trim($lines[$i]) === '<!-- ═══════════════════════════════════════════════════════════════════════════' && $i > 500) {
        $endIdx = $i - 1;
        break;
    }
}

if ($startIdx !== -1 && $endIdx !== -1) {
    $before = array_slice($lines, 0, $startIdx);
    $after = array_slice($lines, $endIdx + 1);
    
    $newFileContent = implode("", $before) . $newHtml . "\n" . implode("", $after);
    file_put_contents($file, $newFileContent);
    echo "Successfully replaced HTML!\n";
} else {
    echo "Failed to find boundaries. Start: $startIdx, End: $endIdx\n";
}
?>
