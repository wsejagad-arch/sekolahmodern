<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"]) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    header("location: ../../index.php?haruslogin");
    exit;
}

require_once '../../koneksi.php';
require_once '../../functions.php';
date_default_timezone_set('Asia/Jakarta');

$nis = $_SESSION['no_induk'];
$kelas = $_SESSION['kelas'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$kelasEsc = mysqli_real_escape_string($conn, $kelas);
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantTugas = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_tugas', 'id_sekolah') ? " AND t.id_sekolah={$tenantId} " : "";

// Ambil daftar tugas aktif untuk kelas siswa
$query = "
    SELECT t.*, t.batas_waktu, ts.status AS status_siswa, ts.waktu_submit
    FROM tbl_tugas t
    LEFT JOIN tbl_tugas_siswa ts ON t.id = ts.id_tugas AND ts.no_induk_siswa = '$nisEsc'
    WHERE t.kelas = '$kelasEsc' AND t.status = 'aktif' {$tenantTugas}
    ORDER BY t.created_at DESC
";
$result = mysqli_query($conn, $query);
$tasks = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tasks[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas - SIMANIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="text-slate-800 antialiased pb-20">
    <div class="w-full max-w-2xl mx-auto p-4 sm:p-6">
        
        <!-- Header -->
        <header class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="relative bg-red-500 rounded-[20px] w-14 h-14 flex items-center justify-center text-white shadow-lg shadow-red-500/20">
                    <i class="fas fa-book-open text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Tugas Kelas</h1>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Daftar tugas dari guru pengampu</p>
                </div>
            </div>
            <a href="siswa.php" class="text-blue-600 hover:text-blue-700 text-sm font-semibold flex items-center gap-1.5 transition-colors">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </header>

        <!-- Daftar Tugas -->
        <div class="space-y-4">
            <?php if (empty($tasks)): ?>
                <div class="bg-white rounded-[24px] p-8 shadow-sm border border-slate-100 text-center mt-4">
                    <div class="w-20 h-20 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-4xl mx-auto mb-4">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h2 class="text-lg font-bold text-slate-700 mb-1">Belum ada tugas</h2>
                    <p class="text-sm text-slate-500">Saat ini tidak ada tugas aktif untuk kelas Anda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $t): 
                    $isDone = ($t['status_siswa'] === 'Selesai');
                    $isWaiting = ($t['status_siswa'] === 'Menunggu Konfirmasi');
                    $isPending = (!$isDone && !$isWaiting);
                ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <span class="inline-block px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-md mb-2 uppercase tracking-wide">
                                <?= htmlspecialchars($t['mapel']) ?>
                            </span>
                            <h3 class="font-black text-slate-800 text-lg leading-tight mb-1"><?= htmlspecialchars($t['judul_tugas']) ?></h3>
                            <div class="text-xs text-slate-500 font-medium"><i class="far fa-calendar-alt mr-1"></i> Tenggat: <?= !empty($t['tanggal_pengumpulan']) ? date('d M Y', strtotime($t['tanggal_pengumpulan'])) : '-' ?></div>
                        </div>
                        <?php if ($isDone): ?>
                            <span class="bg-emerald-100 text-emerald-700 p-2 rounded-xl" title="Selesai"><i class="fas fa-check-circle text-xl"></i></span>
                        <?php elseif ($isWaiting): ?>
                            <span class="bg-orange-100 text-orange-600 p-2 rounded-xl" title="Menunggu Konfirmasi"><i class="fas fa-clock text-xl"></i></span>
                        <?php else: ?>
                            <span class="bg-slate-100 text-slate-500 p-2 rounded-xl" title="Belum Dikerjakan"><i class="fas fa-tasks text-xl"></i></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-sm text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <?= nl2br(htmlspecialchars($t['deskripsi'])) ?>
                    </div>

                    <div class="flex gap-2 items-center flex-wrap">
                        <?php if (!empty($t['link_tugas'])): ?>
                            <a href="<?= htmlspecialchars($t['link_tugas']) ?>" target="_blank" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg font-semibold hover:bg-indigo-100 transition-colors">
                                <i class="fas fa-link mr-1"></i> Buka Link
                            </a>
                        <?php endif; ?>
                        
                        <div class="flex-1 text-right">
                            <?php if ($isDone): ?>
                                <button class="bg-emerald-50 text-emerald-600 font-bold py-2 px-4 rounded-xl text-sm w-full sm:w-auto opacity-80 cursor-not-allowed">
                                    <i class="fas fa-check mr-1"></i> Selesai
                                </button>
                            <?php elseif ($isWaiting): ?>
                                <button class="bg-orange-50 text-orange-600 font-bold py-2 px-4 rounded-xl text-sm w-full sm:w-auto opacity-80 cursor-not-allowed">
                                    <i class="fas fa-clock mr-1"></i> Menunggu Konfirmasi Guru
                                </button>
                            <?php else: ?>
                                <button onclick="kerjakanTugas(<?= $t['id'] ?>)" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl text-sm shadow-md shadow-blue-500/20 transition-all w-full sm:w-auto">
                                    <i class="fas fa-pencil-alt mr-1"></i> Kerjakan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function kerjakanTugas(idTugas) {
        Swal.fire({
            title: 'Kerjakan Tugas?',
            text: "Anda akan menandai tugas ini telah dikerjakan. Guru Anda harus mengkonfirmasinya.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Tandai Selesai!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax_kerjakan_tugas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_tugas=' + idTugas
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', 'Tugas ditandai. Menunggu konfirmasi guru.', 'success')
                        .then(() => window.location.reload());
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Gagal terhubung ke server', 'error');
                });
            }
        });
    }
    </script>
<?php include 'siswa_footer.php'; ?>

</body>
</html>
