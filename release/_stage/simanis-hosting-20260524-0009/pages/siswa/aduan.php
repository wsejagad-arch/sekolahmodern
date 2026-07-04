<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    header('Location: ../../index.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

function ad_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ad_create_tables(mysqli $conn): void
{
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_aduan_siswa (
            id_aduan INT UNSIGNED NOT NULL AUTO_INCREMENT,
            kode_aduan VARCHAR(30) NOT NULL,
            no_induk_pelapor VARCHAR(50) NOT NULL,
            nama_pelapor VARCHAR(150) NOT NULL DEFAULT '',
            kelas_pelapor VARCHAR(80) NOT NULL DEFAULT '',
            kategori VARCHAR(80) NOT NULL,
            judul VARCHAR(180) NOT NULL,
            isi_laporan TEXT NOT NULL,
            lokasi VARCHAR(180) DEFAULT NULL,
            tanggal_kejadian DATE DEFAULT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'baru',
            tahap_aktif VARCHAR(40) NOT NULL DEFAULT 'stpks',
            prioritas VARCHAR(20) NOT NULL DEFAULT 'normal',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id_aduan),
            UNIQUE KEY uniq_kode_aduan (kode_aduan),
            KEY idx_status_tahap (status, tahap_aktif),
            KEY idx_pelapor (no_induk_pelapor),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_aduan_tindak_lanjut (
            id_tindak INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_aduan INT UNSIGNED NOT NULL,
            tahap VARCHAR(40) NOT NULL,
            aksi VARCHAR(60) NOT NULL,
            catatan TEXT DEFAULT NULL,
            handled_by VARCHAR(50) NOT NULL DEFAULT '',
            handled_name VARCHAR(150) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_tindak),
            KEY idx_aduan (id_aduan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

ad_create_tables($conn);

$nis = (string)$_SESSION['no_induk'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$qSiswa = @mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE no_induk='$nisEsc' LIMIT 1");
$siswa = $qSiswa ? mysqli_fetch_assoc($qSiswa) : [];
$nama = (string)($siswa['nama_siswa'] ?? ($_SESSION['nama_siswa'] ?? ''));
$kelas = (string)($siswa['kelas'] ?? ($_SESSION['kelas'] ?? ''));

$flash = '';
$flashType = 'success';
$kategoriList = ['Kekerasan Fisik', 'Kekerasan Verbal', 'Perundungan/Bullying', 'Pelecehan', 'Pemalakan/Intimidasi', 'Keamanan Sekolah', 'Lainnya'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori = trim((string)($_POST['kategori'] ?? ''));
    $judul = trim((string)($_POST['judul'] ?? ''));
    $isi = trim((string)($_POST['isi_laporan'] ?? ''));
    $lokasi = trim((string)($_POST['lokasi'] ?? ''));
    $tanggal = trim((string)($_POST['tanggal_kejadian'] ?? ''));
    $prioritas = trim((string)($_POST['prioritas'] ?? 'normal'));

    if (!in_array($kategori, $kategoriList, true)) {
        $flash = 'Pilih kategori laporan yang valid.';
        $flashType = 'danger';
    } elseif ($judul === '' || $isi === '') {
        $flash = 'Judul dan isi laporan wajib diisi.';
        $flashType = 'danger';
    } else {
        $kode = 'ADN-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $tglSql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) ? "'" . mysqli_real_escape_string($conn, $tanggal) . "'" : 'NULL';
        $kodeEsc = mysqli_real_escape_string($conn, $kode);
        $kategoriEsc = mysqli_real_escape_string($conn, $kategori);
        $judulEsc = mysqli_real_escape_string($conn, $judul);
        $isiEsc = mysqli_real_escape_string($conn, $isi);
        $lokasiEsc = mysqli_real_escape_string($conn, $lokasi);
        $namaEsc = mysqli_real_escape_string($conn, $nama);
        $kelasEsc = mysqli_real_escape_string($conn, $kelas);
        $prioritas = in_array($prioritas, ['normal', 'tinggi', 'darurat'], true) ? $prioritas : 'normal';
        $prioritasEsc = mysqli_real_escape_string($conn, $prioritas);
        $ok = @mysqli_query($conn, "
            INSERT INTO tbl_aduan_siswa
                (kode_aduan, no_induk_pelapor, nama_pelapor, kelas_pelapor, kategori, judul, isi_laporan, lokasi, tanggal_kejadian, prioritas)
            VALUES
                ('$kodeEsc', '$nisEsc', '$namaEsc', '$kelasEsc', '$kategoriEsc', '$judulEsc', '$isiEsc', '$lokasiEsc', $tglSql, '$prioritasEsc')
        ");
        if ($ok) {
            $id = (int)mysqli_insert_id($conn);
            @mysqli_query($conn, "INSERT INTO tbl_aduan_tindak_lanjut (id_aduan, tahap, aksi, catatan, handled_by, handled_name) VALUES ($id, 'siswa', 'laporan_dibuat', 'Laporan dibuat oleh siswa. Identitas disamarkan untuk petugas.', '$nisEsc', 'Pelapor Anonim')");
            $flash = 'Laporan berhasil dikirim. Kode aduan Anda: ' . $kode;
            $flashType = 'success';
        } else {
            $flash = 'Gagal mengirim laporan: ' . mysqli_error($conn);
            $flashType = 'danger';
        }
    }
}

$riwayat = [];
$qRiwayat = @mysqli_query($conn, "SELECT kode_aduan, kategori, judul, status, tahap_aktif, created_at, updated_at FROM tbl_aduan_siswa WHERE no_induk_pelapor='$nisEsc' ORDER BY id_aduan DESC LIMIT 10");
while ($qRiwayat && ($row = mysqli_fetch_assoc($qRiwayat))) {
    $riwayat[] = $row;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aduan Siswa - SIMANIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg,#fee2e2,#f8fafc 45%,#dbeafe); min-height:100vh; padding-bottom:76px; }
        .wrap { max-width:520px; margin:0 auto; padding:16px; }
        .card { background:rgba(255,255,255,.96); border:1px solid #e2e8f0; border-radius:20px; box-shadow:0 12px 30px rgba(15,23,42,.08); }
        .bottom-nav { position:fixed; left:0; right:0; bottom:0; background:rgba(255,255,255,.96); border-top:1px solid #e2e8f0; display:flex; justify-content:center; gap:26px; padding:10px 12px; z-index:30; }
        .bottom-nav a { color:#64748b; text-decoration:none; font-size:11px; font-weight:800; display:flex; flex-direction:column; align-items:center; gap:2px; }
        .bottom-nav i { font-size:19px; }
    </style>
</head>
<body>
<main class="wrap">
    <section class="rounded-[24px] bg-gradient-to-br from-rose-700 to-slate-900 text-white p-5 shadow-xl">
        <a href="siswa.php" class="text-white/80 text-sm font-bold"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <h1 class="text-2xl font-black mt-3">Aduan Siswa</h1>
        <p class="text-white/75 text-sm mt-1">Laporkan tindakan kekerasan, perundungan, ancaman, atau kejadian yang membuat Anda tidak aman. Nama pelapor disamarkan untuk petugas penanganan.</p>
    </section>

    <?php if ($flash !== ''): ?>
        <div class="mt-4 rounded-2xl p-3 text-sm font-bold <?= $flashType === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>"><?= ad_h($flash); ?></div>
    <?php endif; ?>

    <section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-1">Buat Laporan</h2>
        <p class="text-xs text-slate-500 mb-4">Isi dengan jujur dan jelas. Jika keadaan darurat, segera hubungi guru terdekat.</p>
        <form method="post" class="space-y-3">
            <div>
                <label class="text-xs font-black text-slate-700">Kategori</label>
                <select name="kategori" class="w-full rounded-xl border border-slate-200 p-3 text-sm" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= ad_h($kat); ?>"><?= ad_h($kat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-black text-slate-700">Prioritas</label>
                <select name="prioritas" class="w-full rounded-xl border border-slate-200 p-3 text-sm">
                    <option value="normal">Normal</option>
                    <option value="tinggi">Tinggi</option>
                    <option value="darurat">Darurat</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-black text-slate-700">Judul Singkat</label>
                <input name="judul" class="w-full rounded-xl border border-slate-200 p-3 text-sm" maxlength="180" required placeholder="Contoh: Saya melihat perundungan di kantin">
            </div>
            <div>
                <label class="text-xs font-black text-slate-700">Isi Laporan</label>
                <textarea name="isi_laporan" rows="5" class="w-full rounded-xl border border-slate-200 p-3 text-sm" required placeholder="Ceritakan apa yang terjadi, siapa yang terlibat jika diketahui, dan dampaknya."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-black text-slate-700">Lokasi</label>
                    <input name="lokasi" class="w-full rounded-xl border border-slate-200 p-3 text-sm" placeholder="Opsional">
                </div>
                <div>
                    <label class="text-xs font-black text-slate-700">Tanggal Kejadian</label>
                    <input type="date" name="tanggal_kejadian" class="w-full rounded-xl border border-slate-200 p-3 text-sm">
                </div>
            </div>
            <button class="w-full rounded-xl bg-rose-700 text-white font-black py-3" type="submit"><i class="fa-solid fa-shield-heart"></i> Kirim Aduan</button>
        </form>
    </section>

    <section class="card p-4 mt-4">
        <h2 class="font-black text-slate-900 mb-3">Riwayat Aduan Saya</h2>
        <?php if (empty($riwayat)): ?>
            <p class="text-sm text-slate-500">Belum ada aduan.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($riwayat as $r): ?>
                    <div class="rounded-2xl border border-slate-100 p-3">
                        <div class="flex justify-between gap-2">
                            <div class="font-black text-sm"><?= ad_h($r['kode_aduan']); ?></div>
                            <span class="text-[11px] rounded-full bg-slate-100 px-2 py-1 font-bold"><?= ad_h($r['status']); ?></span>
                        </div>
                        <div class="text-sm font-bold mt-1"><?= ad_h($r['judul']); ?></div>
                        <div class="text-xs text-slate-500 mt-1"><?= ad_h($r['kategori']); ?> - tahap <?= ad_h(strtoupper($r['tahap_aktif'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<nav class="bottom-nav">
    <a href="siswa.php"><i class="fas fa-home"></i><span>Beranda</span></a>
    <a href="presensi.php"><i class="fas fa-fingerprint"></i><span>Presensi</span></a>
    <a href="aduan.php" style="color:#be123c;"><i class="fas fa-shield-heart"></i><span>Aduan</span></a>
    <a href="profil.php"><i class="fas fa-user-circle"></i><span>Profil</span></a>
</nav>
</body>
</html>
