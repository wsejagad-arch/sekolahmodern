<?php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';
// $conn sudah didefinisikan secara global di bootstrap.php -> koneksi.php

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);
$sqlGuru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipEsc'");
$dataGuru = mysqli_fetch_array($sqlGuru);
$lembaga = data_lembaga();

// Date Setup
$tglskr = date('Y-m-d');
$hariini = ubah_nama_hari($tglskr);

// Total Kelas Ampu
$kelasAmpu = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND kelas <> ''");
while ($row = mysqli_fetch_assoc($qKelas)) { $kelasAmpu[] = $row['kelas']; }
$totalKelasAmpu = count($kelasAmpu);

// Total Siswa (Dihitung per NIK/No Induk unik dari kelas-kelas yang diampu)
$totalSiswa = 0;
if ($totalKelasAmpu > 0) {
    $kelasIn = "'" . implode("','", array_map(function($k) use ($conn) { return mysqli_real_escape_string($conn, (string)$k); }, $kelasAmpu)) . "'";
    $qS = mysqli_query($conn, "SELECT COUNT(DISTINCT no_induk) as total FROM tbl_siswa WHERE kelas IN ($kelasIn) AND (status='Aktif' OR status='' OR status IS NULL OR UPPER(status)='AKTIF')");
    $rowS = mysqli_fetch_assoc($qS);
    $totalSiswa = (int)($rowS['total'] ?? 0);
}

// Jadwal Hari Ini & Progres Jurnal
$jadwalHariIni = [];
$qJ = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND hari='$hariini' ORDER BY jam_mulai ASC");
while ($row = mysqli_fetch_assoc($qJ)) { $jadwalHariIni[] = $row; }
$totalJadwalHari = count($jadwalHariIni);

// Tentukan jadwal berlangsung atau berikutnya
$ongoingJadwal = null;
$nextJadwal = null;
$currentTime = date('H:i:s');
foreach ($jadwalHariIni as $j) {
    $mulai = date('H:i:s', strtotime($j['jam_mulai']));
    $selesai = date('H:i:s', strtotime($j['jam_selesai']));
    if ($currentTime >= $mulai && $currentTime <= $selesai) {
        $ongoingJadwal = $j;
        break;
    } elseif ($mulai > $currentTime) {
        if ($nextJadwal === null || $j['jam_mulai'] < $nextJadwal['jam_mulai']) {
            $nextJadwal = $j;
        }
    }
}

$mapelIds = array_values(array_filter(array_map('intval', array_column($jadwalHariIni, 'id_mapel'))));
$jurnalStatusByMapel = [];
$jurnalTerisi = 0;
if (!empty($mapelIds)) {
    $idsIn = implode(',', $mapelIds);
    $qStatus = mysqli_query($conn, "SELECT id_mapel, MAX(id_materi) AS id_materi FROM tbl_materi WHERE tanggal='$tglskr' AND no_induk='$nipEsc' AND id_mapel IN ($idsIn) GROUP BY id_mapel");
    if ($qStatus) {
        while ($rowStatus = mysqli_fetch_assoc($qStatus)) {
            $jurnalStatusByMapel[(int)$rowStatus['id_mapel']] = [
                'id_materi' => (int)($rowStatus['id_materi'] ?? 0)
            ];
        }
    }
    $qT = mysqli_query($conn, "SELECT COUNT(DISTINCT id_mapel) as total FROM tbl_materi WHERE tanggal='$tglskr' AND no_induk='$nipEsc' AND id_mapel IN ($idsIn)");
    $rowT = mysqli_fetch_assoc($qT);
    $jurnalTerisi = (int)$rowT['total'];
}
$jurnalTotal = max(1, $totalJadwalHari);
$jurnalProgress = round(($jurnalTerisi / $jurnalTotal) * 100);
$jurnalBelum = max(0, $totalJadwalHari - $jurnalTerisi);
$jurnalBelumPct = 100 - $jurnalProgress;

// Kehadiran Hari Ini
$hadirToday = 0;
$totalAbsen = 0;
if ($totalKelasAmpu > 0) {
    $qA = mysqli_query($conn, "SELECT SUM(CASE WHEN status='Hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total FROM tbl_absen WHERE tanggal='$tglskr' AND kelas IN ($kelasIn)");
    $rowA = mysqli_fetch_assoc($qA);
    $hadirToday = (int)($rowA['hadir'] ?? 0);
    $totalAbsen = (int)($rowA['total'] ?? 0);
}
$hadirPct = $totalAbsen > 0 ? round(($hadirToday / $totalAbsen) * 100) : 0;

// NOTIFIKASI SYSTEM DATA RETRIEVAL
$unfilledJadwal = [];
foreach ($jadwalHariIni as $j) {
    $idm = (int)$j['id_mapel'];
    if (!isset($jurnalStatusByMapel[$idm])) {
        $unfilledJadwal[] = $j;
    }
}
$unfilledJurnalCount = count($unfilledJadwal);

$waliKelasList = [];
$qWaliKelasMain = @mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE nip_wali='$nipEsc' AND kelas <> ''");
while ($qWaliKelasMain && ($rowWali = mysqli_fetch_assoc($qWaliKelasMain))) {
    $waliKelasList[(string)$rowWali['kelas']] = (string)$rowWali['kelas'];
}
$qWaliKelasRel = @mysqli_query($conn, "
    SELECT k.kelas
    FROM tbl_wali_kelas wk
    JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
    WHERE wk.nip_wali='$nipEsc' AND k.kelas <> ''
");
while ($qWaliKelasRel && ($rowWali = mysqli_fetch_assoc($qWaliKelasRel))) {
    $waliKelasList[(string)$rowWali['kelas']] = (string)$rowWali['kelas'];
}
$waliKelasList = array_values($waliKelasList);
$waliKelasIn = '';
if (!empty($waliKelasList)) {
    $waliKelasIn = "'" . implode("','", array_map(static function ($kelas) use ($conn) {
        return mysqli_real_escape_string($conn, (string)$kelas);
    }, $waliKelasList)) . "'";
}

$hasPelanggaranTable = false;
$checkPelTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran_siswa'");
if ($checkPelTable && mysqli_num_rows($checkPelTable) > 0) {
    $hasPelanggaranTable = true;
}

// Kumpulkan semua kelas yang relevan untuk guru ini:
// 1. Kelas yang menjadi wali kelas (sudah ada di $waliKelasList)
// 2. Kelas yang diampu sebagai guru mapel ($kelasAmpu)
$pendampinganKelasAll = array_unique(array_merge($waliKelasList, $kelasAmpu));
$pendampinganKelasAll = array_filter($pendampinganKelasAll, function($k) { return $k !== ''; });

$problematicStudents = [];
if (!empty($pendampinganKelasAll)) {
    $pendampKelasIn = "'" . implode("','", array_map(static function($k) use ($conn) {
        return mysqli_real_escape_string($conn, (string)$k);
    }, array_values($pendampinganKelasAll))) . "'";

    if ($hasPelanggaranTable) {
        $qProb = mysqli_query($conn, "
            SELECT s.no_induk, s.nama_siswa, s.kelas,
                   COALESCE(absen.alpha_count, 0) as alpha_count,
                   COALESCE(absen.telat_count, 0) as telat_count,
                   COALESCE(pelanggaran.pelanggaran_count, 0) as pelanggaran_count,
                   (COALESCE(absen.alpha_count, 0) * 5 + COALESCE(absen.telat_count, 0) * 1.5 + COALESCE(pelanggaran.pelanggaran_count, 0) * 5) as indeks_masalah
            FROM tbl_siswa s
            LEFT JOIN (
                SELECT no_induk,
                       SUM(CASE WHEN UPPER(status) IN ('ALPHA', 'ALPA', 'A') THEN 1 ELSE 0 END) as alpha_count,
                       SUM(CASE WHEN UPPER(status) IN ('TELAT', 'TERLAMBAT', 'T') THEN 1 ELSE 0 END) as telat_count
                FROM tbl_absen
                GROUP BY no_induk
            ) absen ON absen.no_induk = s.no_induk
            LEFT JOIN (
                SELECT no_induk, COUNT(*) as pelanggaran_count
                FROM tbl_pelanggaran_siswa
                GROUP BY no_induk
            ) pelanggaran ON pelanggaran.no_induk = s.no_induk
            WHERE s.kelas IN ($pendampKelasIn) AND (s.status='Aktif' OR s.status='' OR s.status IS NULL OR UPPER(s.status)='AKTIF')
            HAVING indeks_masalah > 0
            ORDER BY indeks_masalah DESC
            LIMIT 10
        ");
    } else {
        $qProb = mysqli_query($conn, "
            SELECT s.no_induk, s.nama_siswa, s.kelas,
                   COALESCE(absen.alpha_count, 0) as alpha_count,
                   COALESCE(absen.telat_count, 0) as telat_count,
                   0 as pelanggaran_count,
                   (COALESCE(absen.alpha_count, 0) * 5 + COALESCE(absen.telat_count, 0) * 1.5) as indeks_masalah
            FROM tbl_siswa s
            LEFT JOIN (
                SELECT no_induk,
                       SUM(CASE WHEN UPPER(status) IN ('ALPHA', 'ALPA', 'A') THEN 1 ELSE 0 END) as alpha_count,
                       SUM(CASE WHEN UPPER(status) IN ('TELAT', 'TERLAMBAT', 'T') THEN 1 ELSE 0 END) as telat_count
                FROM tbl_absen
                GROUP BY no_induk
            ) absen ON absen.no_induk = s.no_induk
            WHERE s.kelas IN ($pendampKelasIn) AND (s.status='Aktif' OR s.status='' OR s.status IS NULL OR UPPER(s.status)='AKTIF')
            HAVING indeks_masalah > 0
            ORDER BY indeks_masalah DESC
            LIMIT 10
        ");
    }
    if ($qProb) {
        while ($rowP = mysqli_fetch_assoc($qProb)) {
            $problematicStudents[] = $rowP;
        }
    }
}
$problematicCount = count($problematicStudents);
// Grouping siswa butuh pendampingan per kelas untuk menu split
$pendampinganByKelas = [];
foreach ($problematicStudents as $s) {
    $pendampinganByKelas[(string)$s['kelas']][] = $s;
}

$announcements = [];
$qAnn = mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE status='aktif' ORDER BY id DESC LIMIT 3");
while ($qAnn && ($row = mysqli_fetch_assoc($qAnn))) {
    $announcements[] = $row;
}
$announcementCount = count($announcements);

$aduanGuruRows = [];
$aduanGuruCount = 0;
$isTimAduan = false;

// Buat tabel aduan siswa jika belum ada
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_aduan_siswa (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tambahkan kolom is_tim_aduan ke tbl_guru jika belum ada
$chkColAduan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_tim_aduan'");
if ($chkColAduan && mysqli_num_rows($chkColAduan) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_tim_aduan TINYINT(1) NOT NULL DEFAULT 0");
}

// Cek apakah guru ini adalah tim aduan (ditugaskan admin)
$qTimAduan = @mysqli_query($conn, "SELECT is_tim_aduan FROM tbl_guru WHERE no_induk='{$nipEsc}' LIMIT 1");
if ($qTimAduan) {
    $rowTimAduan = mysqli_fetch_assoc($qTimAduan);
    $isTimAduan = !empty($rowTimAduan['is_tim_aduan']) && (int)$rowTimAduan['is_tim_aduan'] === 1;
}

// Hanya tampilkan notifikasi aduan jika guru ini adalah tim aduan
if ($isTimAduan) {
    $qAduanNotif = @mysqli_query($conn, "
        SELECT kode_aduan, kategori, judul, kelas_pelapor, tahap_aktif, prioritas, created_at
        FROM tbl_aduan_siswa
        WHERE status <> 'selesai'
        ORDER BY FIELD(prioritas,'darurat','tinggi','normal'), created_at DESC
        LIMIT 5
    ");
    while ($qAduanNotif && ($row = mysqli_fetch_assoc($qAduanNotif))) {
        // Anonim: hapus nama pelapor sebelum disimpan ke array notifikasi
        unset($row['nama_pelapor'], $row['no_induk_pelapor']);
        $aduanGuruRows[] = $row;
    }
    $qAduanCount = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_aduan_siswa WHERE status <> 'selesai'");
    $rowAduanCount = $qAduanCount ? mysqli_fetch_assoc($qAduanCount) : ['total' => 0];
    $aduanGuruCount = (int)($rowAduanCount['total'] ?? 0);
}

// Hitung total notif - hide item-item kosong tidak dihitung
$totalNotifCount = $problematicCount
    + ($totalJadwalHari > 0 && $unfilledJurnalCount > 0 ? $unfilledJurnalCount : 0)
    + ($nextJadwal !== null ? 1 : 0)
    + $announcementCount
    + $aduanGuruCount;
// Tugas Terbaru
$tugasTerbaru = [];
$qTugas = mysqli_query($conn, "SELECT * FROM tbl_tugas WHERE no_induk_guru='$nipEsc' ORDER BY id DESC LIMIT 3");
if ($qTugas) {
    while ($row = mysqli_fetch_assoc($qTugas)) {
        $tugasTerbaru[] = [
            'judul' => $row['judul_tugas'],
            'kelas' => $row['kelas'],
            'status' => 'Batas: ' . date('d M', strtotime($row['tanggal_pengumpulan'])),
            'color' => '#3B82F6',
            'icon' => 'bi-file-earmark-text'
        ];
    }
}

$kelasDetailUrl = 'data-siswa';
if ($totalKelasAmpu === 1) {
    $kelasDetailUrl .= '?kelas=' . rawurlencode((string) $kelasAmpu[0]);
}

// Cek Wali Kelas
$isWaliKelas = !empty($waliKelasList);

// Cek Guru BK
$isGuruBK = false;
$qBkCheck = mysqli_query($conn, "SELECT id_guru FROM tbl_guru WHERE no_induk = '$nipEsc' AND (jabatan LIKE '%BK%' OR is_guru_bk = 1) LIMIT 1");
if ($qBkCheck && mysqli_num_rows($qBkCheck) > 0) {
    $isGuruBK = true;
}

// Hitung izin pending untuk notif badge
$pendingIzinCount = 0;
$pendingIzinList  = [];
if ($isWaliKelas && !empty($waliKelasList)) {
    $wkIn = "'" . implode("','", array_map(static fn($k) => mysqli_real_escape_string($conn, $k), $waliKelasList)) . "'";
    $qPendingWK = mysqli_query($conn, "SELECT i.id_izin, i.kategori_pengajuan, i.tanggal_izin, s.nama_siswa, s.kelas
        FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk
        WHERE s.kelas IN ($wkIn) AND i.validasi_wali_kelas = 'Menunggu'
        ORDER BY i.waktu_pengajuan ASC");
    if ($qPendingWK) {
        while ($rp = mysqli_fetch_assoc($qPendingWK)) { $pendingIzinList[] = $rp; }
    }
}
if ($isGuruBK) {
    $qPendingBK = mysqli_query($conn, "SELECT i.id_izin, i.kategori_pengajuan, i.tanggal_izin, s.nama_siswa, s.kelas
        FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk
        WHERE i.validasi_guru_bk = 'Menunggu'
        ORDER BY i.waktu_pengajuan ASC");
    if ($qPendingBK) {
        while ($rp = mysqli_fetch_assoc($qPendingBK)) {
            // Hindari duplikat
            $exist = false;
            foreach ($pendingIzinList as $pil) { if ($pil['id_izin'] == $rp['id_izin']) { $exist = true; break; } }
            if (!$exist) $pendingIzinList[] = $rp;
        }
    }
}
$pendingIzinCount = count($pendingIzinList);

// Cek Pembina Literasi (hanya tampilkan menu Literasi jika guru terdaftar di tbl_literasi_ampuh atau is_pendamping_literasi = 1)
$idSekolahGuru = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$qPembinaLiterasi = @mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_literasi_ampuh WHERE no_induk_guru='$nipEsc' AND id_sekolah=$idSekolahGuru");
$isPembinaLiterasi = false;
if ($qPembinaLiterasi) {
    $rowPembina = mysqli_fetch_assoc($qPembinaLiterasi);
    $isPembinaLiterasi = (int)($rowPembina['total'] ?? 0) > 0;
}
$qPendamping = @mysqli_query($conn, "SELECT is_pendamping_literasi FROM tbl_guru WHERE no_induk='$nipEsc' LIMIT 1");
if ($qPendamping && $rowPendamping = mysqli_fetch_assoc($qPendamping)) {
    if ((int)($rowPendamping['is_pendamping_literasi'] ?? 0) === 1) {
        $isPembinaLiterasi = true;
    }
}

// Guru Wali: siswa binaan personal
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_guru_wali_binaan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    no_induk_guru VARCHAR(50) NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_guru_siswa (no_induk_guru, no_induk_siswa),
    KEY idx_guru (no_induk_guru),
    KEY idx_siswa (no_induk_siswa),
    KEY idx_kelas (kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_guru_wali_jurnal_pendampingan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    no_induk_guru VARCHAR(50) NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    catatan TEXT NOT NULL,
    tindak_lanjut TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Dipantau',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_guru_tanggal (no_induk_guru, tanggal),
    KEY idx_siswa (no_induk_siswa),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$guruWaliFlash = '';
$guruWaliFlashType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guru_wali_action'])) {
    $guruWaliAction = (string)($_POST['guru_wali_action'] ?? '');

    if ($guruWaliAction === 'add') {
        $kelasBinaan = trim((string)($_POST['kelas_binaan'] ?? ''));
        $siswaBinaan = trim((string)($_POST['siswa_binaan'] ?? ''));

        if ($kelasBinaan === '' || $siswaBinaan === '') {
            $guruWaliFlash = 'Pilih kelas dan siswa terlebih dahulu.';
            $guruWaliFlashType = 'danger';
        } else {
            $kelasBinaanEsc = mysqli_real_escape_string($conn, $kelasBinaan);
            $siswaBinaanEsc = mysqli_real_escape_string($conn, $siswaBinaan);
            $qValidSiswa = @mysqli_query($conn, "SELECT no_induk FROM tbl_siswa WHERE no_induk='{$siswaBinaanEsc}' AND kelas='{$kelasBinaanEsc}' LIMIT 1");
            if (!$qValidSiswa || mysqli_num_rows($qValidSiswa) === 0) {
                $guruWaliFlash = 'Siswa tidak ditemukan pada kelas yang dipilih.';
                $guruWaliFlashType = 'danger';
            } else {
                $okBinaan = @mysqli_query($conn, "INSERT IGNORE INTO tbl_guru_wali_binaan (no_induk_guru, no_induk_siswa, kelas) VALUES ('{$nipEsc}', '{$siswaBinaanEsc}', '{$kelasBinaanEsc}')");
                $guruWaliFlash = $okBinaan ? 'Siswa binaan berhasil ditambahkan.' : 'Gagal menambahkan siswa binaan: ' . mysqli_error($conn);
                $guruWaliFlashType = $okBinaan ? 'success' : 'danger';
            }
        }
    } elseif ($guruWaliAction === 'delete') {
        $idBinaan = (int)($_POST['id_binaan'] ?? 0);
        $okDelete = $idBinaan > 0
            ? @mysqli_query($conn, "DELETE FROM tbl_guru_wali_binaan WHERE id={$idBinaan} AND no_induk_guru='{$nipEsc}'")
            : false;
        $guruWaliFlash = $okDelete ? 'Siswa binaan berhasil dihapus.' : 'Gagal menghapus siswa binaan.';
        $guruWaliFlashType = $okDelete ? 'success' : 'danger';
    } elseif ($guruWaliAction === 'journal_add') {
        $siswaJurnal = trim((string)($_POST['jurnal_siswa_binaan'] ?? ''));
        $tanggalJurnal = trim((string)($_POST['jurnal_tanggal'] ?? date('Y-m-d')));
        $catatanJurnal = trim((string)($_POST['jurnal_catatan'] ?? ''));
        $tindakLanjutJurnal = trim((string)($_POST['jurnal_tindak_lanjut'] ?? ''));
        $statusJurnal = trim((string)($_POST['jurnal_status'] ?? 'Dipantau'));

        if ($siswaJurnal === '' || $catatanJurnal === '') {
            $guruWaliFlash = 'Pilih siswa binaan dan isi catatan pendampingan.';
            $guruWaliFlashType = 'danger';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalJurnal)) {
            $guruWaliFlash = 'Tanggal jurnal tidak valid.';
            $guruWaliFlashType = 'danger';
        } else {
            $siswaJurnalEsc = mysqli_real_escape_string($conn, $siswaJurnal);
            $tanggalJurnalEsc = mysqli_real_escape_string($conn, $tanggalJurnal);
            $catatanJurnalEsc = mysqli_real_escape_string($conn, $catatanJurnal);
            $tindakLanjutJurnalEsc = mysqli_real_escape_string($conn, $tindakLanjutJurnal);
            $statusJurnalEsc = mysqli_real_escape_string($conn, $statusJurnal);
            $qValidBinaan = @mysqli_query($conn, "SELECT kelas FROM tbl_guru_wali_binaan WHERE no_induk_guru='{$nipEsc}' AND no_induk_siswa='{$siswaJurnalEsc}' LIMIT 1");
            $validBinaan = $qValidBinaan ? mysqli_fetch_assoc($qValidBinaan) : null;

            if (!$validBinaan) {
                $guruWaliFlash = 'Siswa belum terdaftar sebagai binaan guru wali.';
                $guruWaliFlashType = 'danger';
            } else {
                $kelasJurnalEsc = mysqli_real_escape_string($conn, (string)($validBinaan['kelas'] ?? ''));
                $okJurnal = @mysqli_query($conn, "INSERT INTO tbl_guru_wali_jurnal_pendampingan
                    (no_induk_guru, no_induk_siswa, kelas, tanggal, catatan, tindak_lanjut, status)
                    VALUES ('{$nipEsc}', '{$siswaJurnalEsc}', '{$kelasJurnalEsc}', '{$tanggalJurnalEsc}', '{$catatanJurnalEsc}', '{$tindakLanjutJurnalEsc}', '{$statusJurnalEsc}')");
                $guruWaliFlash = $okJurnal ? 'Jurnal pendampingan berhasil disimpan.' : 'Gagal menyimpan jurnal pendampingan: ' . mysqli_error($conn);
                $guruWaliFlashType = $okJurnal ? 'success' : 'danger';
            }
        }
    }
}

$guruWaliKelasOptions = [];
$guruWaliStudentsByClass = [];
$qBinaanSiswa = @mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE kelas <> '' AND (status='Aktif' OR status='' OR status IS NULL OR UPPER(status)='AKTIF') ORDER BY kelas ASC, nama_siswa ASC");
while ($qBinaanSiswa && ($rowBinaanSiswa = mysqli_fetch_assoc($qBinaanSiswa))) {
    $kelasItem = (string)($rowBinaanSiswa['kelas'] ?? '');
    if ($kelasItem === '') {
        continue;
    }
    $guruWaliKelasOptions[$kelasItem] = $kelasItem;
    $guruWaliStudentsByClass[$kelasItem][] = [
        'no_induk' => (string)($rowBinaanSiswa['no_induk'] ?? ''),
        'nama_siswa' => (string)($rowBinaanSiswa['nama_siswa'] ?? ''),
    ];
}
ksort($guruWaliKelasOptions, SORT_NATURAL | SORT_FLAG_CASE);

$guruWaliBinaan = [];
$qBinaan = @mysqli_query($conn, "SELECT b.id, b.kelas, b.no_induk_siswa, s.nama_siswa
    FROM tbl_guru_wali_binaan b
    LEFT JOIN tbl_siswa s ON s.no_induk = b.no_induk_siswa
    WHERE b.no_induk_guru='{$nipEsc}'
    ORDER BY b.kelas ASC, s.nama_siswa ASC");
while ($qBinaan && ($rowBinaan = mysqli_fetch_assoc($qBinaan))) {
    $guruWaliBinaan[] = $rowBinaan;
}

$guruWaliJurnal = [];
$qGuruWaliJurnal = @mysqli_query($conn, "SELECT j.*, s.nama_siswa
    FROM tbl_guru_wali_jurnal_pendampingan j
    LEFT JOIN tbl_siswa s ON s.no_induk = j.no_induk_siswa
    WHERE j.no_induk_guru='{$nipEsc}'
    ORDER BY j.tanggal DESC, j.id DESC
    LIMIT 10");
while ($qGuruWaliJurnal && ($rowJurnalWali = mysqli_fetch_assoc($qGuruWaliJurnal))) {
    $guruWaliJurnal[] = $rowJurnalWali;
}

?>
<link rel='stylesheet' href='pages/guru/css/guru-2026-scoped.css?v=1782641374'>
<style>
.app-shell {
  background: radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.1) 0%, transparent 40%), radial-gradient(circle at 100% 0%, rgba(139, 92, 246, 0.08) 0%, transparent 40%), radial-gradient(circle at 100% 100%, rgba(59, 130, 246, 0.08) 0%, transparent 40%), radial-gradient(circle at 0% 100%, rgba(236, 72, 153, 0.06) 0%, transparent 40%), linear-gradient(135deg, #F8FAFC 0%, #EEF2F6 100%);
  min-height: calc(100vh - 100px);
}
</style>
<div class="app-shell">
    <!-- HEADER -->
    <header class="hero-header">
        <a href="profil-guru" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px; width: calc(100% - 70px);">
            <div class="profile-section" style="margin-bottom: 0;">
                <div class="profile-photo">
                    <?php if ($dataGuru['foto']): ?>
                        <img src="../../foto/<?= $dataGuru['foto'] ?>" alt="Profile">
                    <?php else: ?>
                        <?= get_guru_avatar_svg(get_guru_gender($dataGuru['no_induk'], $dataGuru['nama_guru'])) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="greet-block" style="margin-top: 0; padding-top: 0; text-align: left;">
                <p class="greet-small">Selamat pagi,</p>
                <h1 style="font-size: 1.35rem; margin-bottom: 2px; color: #fff; font-weight: 700;"><?= $dataGuru['nama_guru'] ?: 'Bu Amanda' ?></h1>
                <p class="greet-school" style="margin: 0;"><?= htmlspecialchars($lembaga['nmsekolah']); ?></p>
            </div>
        </a>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button class="notif-btn" id="btn-open-notif-drawer">
                <i class="bi bi-bell"></i>
                <?php if ($totalNotifCount > 0): ?>
                    <span class="notif-badge"><?= $totalNotifCount ?></span>
                <?php endif; ?>
            </button>
            <a href="../../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="notif-btn" style="text-decoration: none; color: var(--red); border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.05);" title="Keluar">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </header>

    <!-- COMBINED KBM & SUMMARY CARD -->
    <section class="summary-card" id="kbm-summary-card">
        <?php
            $runningPanel = $ongoingJadwal ?? $nextJadwal;
            $hasRunningSchedule = $ongoingJadwal !== null;
            $runningStateIcon = $hasRunningSchedule ? 'bi-broadcast-pin' : ($runningPanel !== null ? 'bi-lightning-charge-fill' : 'bi-power');
            $runningKickerLabel = $hasRunningSchedule ? 'KBM Sedang Berlangsung' : ($runningPanel !== null ? 'KBM Berikutnya Hari Ini' : 'Tidak ada jadwal berjalan');
            $runningCountdownLabel = $hasRunningSchedule ? 'Sisa waktu mengajar:' : ($runningPanel !== null ? 'Mulai dalam:' : 'Status');
            $runningTarget = '';
            if ($ongoingJadwal !== null) {
                $runningTarget = date('Y-m-d') . ' ' . $ongoingJadwal['jam_selesai'];
            } elseif ($nextJadwal !== null) {
                $runningTarget = date('Y-m-d') . ' ' . $nextJadwal['jam_mulai'];
            }
        ?>
        <?php if ($runningPanel !== null): ?>
            <div id="kbm-box" class="running-schedule-3d <?= $hasRunningSchedule ? 'is-live' : 'is-idle'; ?>" <?= $runningTarget !== '' ? 'data-target="' . htmlspecialchars($runningTarget, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                <div class="running-live-orb">
                    <i class="bi <?= $hasRunningSchedule ? 'bi-broadcast-pin' : 'bi-calendar2-week'; ?>"></i>
                </div>
                <div class="running-copy">
                    <div class="running-kicker">
                        <span class="running-kicker-dot"></span>
                        <?= htmlspecialchars($runningKickerLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <h3 class="running-title">
                        <?= htmlspecialchars($runningPanel['nama_mapel']); ?>
                    </h3>
                    <div class="running-meta">
                        <span><i class="bi bi-door-open-fill"></i> Kelas <?= htmlspecialchars($runningPanel['kelas']); ?></span>
                        <span style="opacity:.65;">|</span>
                        <span><i class="bi bi-clock-fill"></i> <?= substr($runningPanel['jam_mulai'], 0, 5); ?> - <?= substr($runningPanel['jam_selesai'], 0, 5); ?></span>
                    </div>
                </div>
                <div class="running-time-pill">
                    <span class="running-state-label">
                        <i class="bi <?= $runningStateIcon; ?>"></i>
                        <?= htmlspecialchars($runningCountdownLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <strong id="kbm-timer">Calculating...</strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bottom Section: Metrics Grid -->
        <div class="summary-grid">
            <div class="summary-metric">
                <div class="metric-icon"><i class="bi bi-house-door-fill"></i></div>
                <p class="summary-label">Kelas yang diampu</p>
                <h3 class="summary-value"><?= $totalKelasAmpu ?> Kelas</h3>
                <a class="switch-btn" href="<?= htmlspecialchars($kelasDetailUrl, ENT_QUOTES, 'UTF-8'); ?>">Lihat Detail <i class="bi bi-arrow-right-circle"></i></a>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-metric">
                <div class="metric-icon"><i class="bi bi-people-fill"></i></div>
                <p class="summary-label">Total Siswa</p>
                <h3 class="summary-value"><?= $totalSiswa ?> <span style="font-size: 13px; font-weight: 500; opacity: 0.8;">Siswa</span></h3>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-metric">
                <div class="metric-icon"><i class="bi bi-activity"></i></div>
                <p class="summary-label">Kehadiran Hari Ini</p>
                <h3 class="summary-value"><?= $hadirPct ?>%</h3>
                <p class="summary-label" style="margin-top: 5px;">(<?= $hadirToday ?> / <?= $totalAbsen ?>)</p>
            </div>
        </div>
        <div class="att-track">
            <div class="att-fill" style="width: <?= $hadirPct ?>%;"></div>
        </div>
    </section>

    <!-- DUAL CARDS -->
    <div class="dual-card-grid">
        <!-- PROGRES HARIAN -->
        <article class="panel-card">
            <h2>Progres Harian</h2>
            <div class="progress-wrap">
                <div style="position: relative; width: fit-content;">
                    <svg class="ring-svg" viewBox="0 0 100 100">
                        <circle class="ring-bg" cx="50" cy="50" r="45"></circle>
                        <circle class="ring-fill" cx="50" cy="50" r="45" id="ringProgress" data-progress="<?= $jurnalProgress ?>"></circle>
                    </svg>
                    <div class="ring-center">
                        <strong><?= $jurnalProgress ?>%</strong>
                        <span>Terisi</span>
                    </div>
                </div>
                <div style="margin-left: 5px;">
                    <ul class="legend-list" style="list-style:none; padding:0; margin:0;">
                        <li style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                            <span class="dot" style="background:var(--primary); width:8px; height:8px; border-radius:50%;"></span>
                            <div style="font-size:10px; color:var(--text-muted);">Terisi<br><b style="color:var(--text-main); font-size:11px;"><?= $jurnalTerisi ?> (<?= $jurnalProgress ?>%)</b></div>
                        </li>
                        <li style="display:flex; align-items:center; gap:8px;">
                            <span class="dot" style="background:var(--border); width:8px; height:8px; border-radius:50%;"></span>
                            <div style="font-size:10px; color:var(--text-muted);">Belum Terisi<br><b style="color:var(--text-main); font-size:11px;"><?= $jurnalBelum ?> (<?= $jurnalBelumPct ?>%)</b></div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="target-box">
                <div class="target-icon"><i class="bi bi-bullseye"></i></div>
                <div class="target-copy">
                    <strong>Target harian</strong>
                    <small>Minimal 100% jurnal terisi</small>
                </div>
                <div class="target-value">100%</div>
            </div>
        </article>

        <!-- INPUT JURNAL -->
        <article class="panel-card">
            <h2>Input Jurnal Mengajar</h2>
            <div class="input-body">
                <div style="width: 80px; height: 80px; border-radius: 24px; background: linear-gradient(135deg, #8b5cf6, #4f46e5); margin: 0 auto 16px; display: grid; place-items: center; color: #fff; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3); position: relative;">
                    <i class="bi bi-journal-text" style="font-size: 40px;"></i>
                    <div style="position: absolute; bottom: -5px; right: -5px; width: 30px; height: 30px; border-radius: 10px; background: #fff; color: #4f46e5; display: grid; place-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        <i class="bi bi-pencil-fill" style="font-size: 14px;"></i>
                    </div>
                </div>
                <p>Catat kegiatan mengajar Anda hari ini dengan mudah dan cepat.</p>
                <button class="cta-btn btn-open-input-jurnal" type="button">Input Sekarang</button>
            </div>
        </article>
    </div>

    <?php if (($isWaliKelas || $isGuruBK) && $pendingIzinCount > 0): ?>
    <!-- BANNER IZIN PENDING -->
    <a href="validasi-izin" style="text-decoration:none; display:block; margin-bottom:14px;">
        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; border-radius:16px; padding:14px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 8px 24px rgba(220,38,38,.3); animation:pendingPulse 2s ease-in-out infinite;">
            <div style="width:48px; height:48px; background:rgba(255,255,255,.2); border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="bi bi-patch-exclamation-fill" style="font-size:26px;"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:800; font-size:14px;">⚠️ <?= $pendingIzinCount ?> Pengajuan Izin Menunggu Validasi Anda</div>
                <div style="font-size:11px; opacity:.85; margin-top:2px;">
                    <?php
                        $names = array_slice(array_column($pendingIzinList, 'nama_siswa'), 0, 2);
                        echo htmlspecialchars(implode(', ', $names));
                        if ($pendingIzinCount > 2) echo ' +' . ($pendingIzinCount - 2) . ' lainnya';
                    ?>
                </div>
            </div>
            <i class="bi bi-chevron-right" style="font-size:18px; flex-shrink:0;"></i>
        </div>
    </a>
    <style>
    @keyframes pendingPulse {
        0%,100% { box-shadow:0 8px 24px rgba(220,38,38,.3); }
        50% { box-shadow:0 8px 36px rgba(220,38,38,.6); }
    }
    </style>
    <?php endif; ?>

    <!-- AKSI CEPAT -->
    <h3 class="section-label">Aksi Cepat</h3>
    <div class="quick-grid">
        <!-- 1. Validasi Izin -->
        <?php if ($isWaliKelas || $isGuruBK): ?>
        <a href="validasi-izin" class="quick-item" style="position:relative;">
            <i class="bi bi-patch-check-fill" style="color:#dc2626;"></i>
            <?php if ($pendingIzinCount > 0): ?>
            <span style="position:absolute; top:6px; right:6px; background:#dc2626; color:#fff; font-size:9px; font-weight:800; min-width:16px; height:16px; border-radius:999px; display:flex; align-items:center; justify-content:center; line-height:1; padding:0 3px;"><?= $pendingIzinCount ?></span>
            <?php endif; ?>
            <span>Validasi<br>Izin</span>
        </a>
        <?php endif; ?>

        <!-- LENTERA Literasi -->
        <?php if ($isPembinaLiterasi): ?>
        <a href="literasi.php" class="quick-item">
            <i class="bi bi-book-half" style="color:#0ea5e9"></i>
            <span>LENTERA<br>Literasi</span>
        </a>
        <?php endif; ?>

        <!-- 2. Data Kehadiran -->
        <a href="rekap-kehadiran" class="quick-item">
            <i class="bi bi-clipboard2-data-fill" style="color:var(--green)"></i>
            <span>Data<br>Kehadiran</span>
        </a>

        <!-- 3. Catat Pelanggaran -->
        <a href="#" class="quick-item btn-open-pelanggaran">
            <i class="bi bi-exclamation-triangle-fill" style="color:var(--red)"></i>
            <span>Catat<br>Pelanggaran</span>
        </a>

        <!-- 4. Setting Jadwal -->
        <a href="setting-jadwal" class="quick-item">
            <i class="bi bi-calendar-week-fill" style="color:#2563EB"></i>
            <span>Setting<br>Jadwal</span>
        </a>

        <!-- 5. Materi Pembelajaran -->
        <a href="materi" class="quick-item">
            <i class="bi bi-book-half" style="color:var(--purple)"></i>
            <span>Materi<br>Pembelajaran</span>
        </a>

        <!-- 6. Nilai Siswa -->
        <a href="nilai" class="quick-item">
            <i class="bi bi-table" style="color:var(--orange)"></i>
            <span>Nilai<br>Siswa</span>
        </a>

        <!-- 7. Wali Kelas -->
        <?php if ($isWaliKelas): ?>
        <a href="walikelas" class="quick-item">
            <i class="bi bi-person-vcard-fill" style="color:var(--blue)"></i>
            <span>Wali<br>Kelas</span>
        </a>
        <?php endif; ?>

        <!-- 8. Guru Wali -->
        <a href="#" class="quick-item btn-open-guru-wali">
            <i class="bi bi-person-workspace" style="color:#14B8A6"></i>
            <span>Guru<br>Wali</span>
        </a>

        <!-- 9. Monitoring Kelas -->
        <a href="laporan-kelas" class="quick-item">
            <i class="bi bi-bar-chart-fill" style="color:var(--orange)"></i>
            <span>Monitoring<br>Kelas</span>
        </a>

        <!-- 10. Ekstra Kulikuler -->
        <a href="ekskul" class="quick-item">
            <i class="bi bi-dribbble" style="color:#EC4899"></i>
            <span>Ekstra<br>kurikuler</span>
        </a>

        <!-- 11. Leger -->
        <a href="leger" class="quick-item">
            <i class="bi bi-file-earmark-spreadsheet-fill" style="color:#059669"></i>
            <span>Leger<br>Nilai</span>
        </a>

        <!-- 12. File Ekin -->
        <a href="ekinerja" class="quick-item">
            <i class="bi bi-file-earmark-bar-graph-fill" style="color:#0F766E"></i>
            <span>File<br>Ekin</span>
        </a>

        <!-- Item Lainnya yang tidak disebutkan tetapi penting untuk dipertahankan -->
        <a href="apresiasi-guru" class="quick-item">
            <i class="bi bi-award-fill" style="color:#F59E0B"></i>
            <span>Apresiasi<br>Guru</span>
        </a>
        <a href="piagam-7kih" class="quick-item">
            <i class="bi bi-patch-check-fill" style="color:#16A34A"></i>
            <span>Piagam<br>7 KAIH</span>
        </a>
        <a href="wks" class="quick-item">
            <i class="bi bi-diagram-3-fill" style="color:#0F766E"></i>
            <span>INFO<br>WKS</span>
        </a>
        <a href="<?= htmlspecialchars($kelasDetailUrl, ENT_QUOTES, 'UTF-8'); ?>" class="quick-item">
            <i class="bi bi-person-badge-fill" style="color:#8B5CF6"></i>
            <span>Data<br>Siswa</span>
        </a>
        <a href="#" class="quick-item">
            <i class="bi bi-three-dots" style="color:#94A3B8"></i>
            <span>Lainnya</span>
        </a>
    </div>

    <!-- BOTTOM PANELS -->
    <div class="bottom-panels">
        <!-- JADWAL -->
        <section class="section-card">
            <div class="card-head">
                <h3>Jadwal Hari Ini</h3>
                <a href="#">Lihat Semua <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="timeline">
                <?php if (empty($jadwalHariIni)): ?>
                    <p style="font-size: 11px; color: var(--text-muted); text-align: center; padding: 20px;">Tidak ada jadwal hari ini.</p>
                <?php else: ?>
                    <?php foreach ($jadwalHariIni as $idx => $j): 
                        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
                        $color = $colors[$idx % count($colors)];
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="--dot-color: <?= $color ?>"></div>
                        <div class="tm-time">
                            <strong><?= substr($j['jam_mulai'], 0, 5) ?></strong>
                            <span style="display:block; font-size:9px;"><?= substr($j['jam_selesai'], 0, 5) ?></span>
                        </div>
                        <div class="tm-info">
                            <strong><?= $j['kelas'] ?></strong>
                            <span><?= $j['nama_mapel'] ?></span>
                        </div>
                        <div class="tm-room"><?= $j['ruang'] ?? 'R. Kelas' ?></div>
                        <i class="bi bi-chevron-right" style="font-size:10px; color:#cbd5e1;"></i>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- TUGAS -->
        <section class="section-card">
            <div class="card-head">
                <h3>Tugas Terbaru</h3>
                <a href="#">Lihat Semua <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="task-list">
                <?php foreach ($tugasTerbaru as $t): ?>
                <div class="task-item">
                    <div class="task-icon" style="background: <?= $t['color'] ?>15; color: <?= $t['color'] ?>;"><i class="bi <?= $t['icon'] ?>"></i></div>
                    <div class="task-info">
                        <strong><?= $t['judul'] ?></strong>
                        <span><?= $t['kelas'] ?></span>
                        <small class="task-status"><?= $t['status'] ?></small>
                    </div>
                    <i class="bi bi-chevron-right" style="font-size:10px; color:#cbd5e1;"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- STATS ROW -->
    <div class="stats-row">
        <div class="stat-box">
            <h4>Ringkasan Mingguan <i class="bi bi-chevron-down"></i></h4>
            <div class="stat-main">
                <div class="stat-icon-mini"><i class="bi bi-people-fill"></i></div>
                <div class="stat-vals">
                    <div><b>88%</b> <span class="trend"><i class="bi bi-arrow-up"></i> 8%</span></div>
                    <p>vs minggu lalu</p>
                </div>
            </div>
        </div>
        <div class="stat-box">
            <h4>Rata-rata Nilai <i class="bi bi-chevron-down"></i></h4>
            <div class="stat-main">
                <div class="stat-icon-mini" style="background:#FFF7ED; color:var(--orange);"><i class="bi bi-star-fill"></i></div>
                <div class="stat-vals">
                    <div><b>82,4</b> <span class="trend"><i class="bi bi-arrow-up"></i> 5,6</span></div>
                    <p>vs minggu lalu</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM NAV -->
    <div class="bottom-nav-wrap">
        
</div>
