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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Dashboard Guru</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap">
    <link rel="stylesheet" href="css/guru-2026.css?v=<?= time(); ?>">
    <style>
        .summary-card {
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%) !important;
            border-radius: var(--radius-lg);
            padding: 20px !important;
            color: #fff;
            margin: 0 4px 20px 4px;
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.3);
            position: relative;
            overflow: hidden;
        }
        .ongoing-kbm-section {
            border-radius: var(--radius-md);
            padding: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .ongoing-kbm-section.active {
            background: linear-gradient(135deg, #ef4444 0%, #f97316 50%, #facc15 100%); /* Strong Red-Orange-Yellow Gradient for active KBM */
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .ongoing-kbm-section.upcoming {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 60%, #eab308 100%); /* Glowing Amber-Yellow Gradient */
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .ongoing-kbm-section.inactive {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.12) 100%);
            border: 1.5px solid rgba(16, 185, 129, 0.35);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.15);
            backdrop-filter: blur(12px);
        }
        /* Custom overrides when KBM is finished (inactive) to make the text contrast super rich and glamorous */
        .ongoing-kbm-section.inactive .ongoing-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
        }
        .ongoing-kbm-section.inactive .ongoing-title {
            color: #ffffff !important;
            font-weight: 800 !important;
            background: linear-gradient(135deg, #ffffff 60%, #a7f3d0 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.15) !important;
        }
        .ongoing-kbm-section.inactive .ongoing-sub {
            color: #a7f3d0 !important;
            font-weight: 500 !important;
            text-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
        }
        /* Greeting Typography Styles with High Contrast and Gradient */
        .greet-small {
            font-size: 11.5px !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            color: var(--text-muted) !important;
            margin: 0 0 2px 0 !important;
        }
        .greet-block h1 {
            font-size: 20px !important;
            font-weight: 800 !important;
            background: linear-gradient(135deg, var(--primary) 0%, var(--text-main) 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            margin: 1px 0 3px 0 !important;
            display: inline-block !important;
            word-break: break-word !important;
        }
        .greet-school {
            font-size: 11.5px !important;
            font-weight: 500 !important;
            color: var(--text-muted) !important;
            opacity: 0.95 !important;
            margin: 0 !important;
        }
        .ongoing-kbm-section::before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%);
            top: -40px;
            right: -40px;
            border-radius: 50%;
        }
        .ongoing-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 30px;
            padding: 3px 10px;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(255, 255, 255, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }
        .ongoing-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 4px 0;
            letter-spacing: -0.2px;
            color: #fff;
        }
        .ongoing-sub {
            font-size: 11.5px;
            opacity: 0.9;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #fff;
        }
        .ongoing-countdown {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .countdown-text {
            font-size: 10.5px;
            opacity: 0.85;
            color: #fff;
        }
        .countdown-val {
            font-size: 13px;
            font-weight: 600;
            font-family: monospace;
            background: rgba(255, 255, 255, 0.18);
            padding: 1px 6px;
            border-radius: 5px;
            color: #fff;
        }
        .kbm-summary-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.15);
            margin: 16px 0;
        }
        .running-schedule-3d {
            position: relative;
            isolation: isolate;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 12px;
            margin: 0 0 16px 0;
            padding: 15px 14px;
            border-radius: 18px;
            color: #fff;
            background: linear-gradient(135deg, #dc2626 0%, #f97316 42%, #facc15 100%);
            border: 2px solid rgba(255,255,255,.88);
            box-shadow:
                0 14px 0 rgba(120, 53, 15, .55),
                0 22px 34px rgba(127, 29, 29, .42),
                inset 0 2px 0 rgba(255,255,255,.45);
            transform: translateY(-3px);
            overflow: hidden;
            animation: runningBorderBlink 1.05s ease-in-out infinite, runningFloat3d 2.6s ease-in-out infinite;
            font-family: 'Poppins', var(--font-main, system-ui), sans-serif;
        }
        .running-schedule-3d::before {
            content: '';
            position: absolute;
            inset: -3px;
            z-index: -1;
            border-radius: 20px;
            background: conic-gradient(from 0deg, #fff, #22c55e, #06b6d4, #facc15, #ef4444, #fff);
            animation: runningBorderSpin 2.4s linear infinite;
        }
        .running-schedule-3d::after {
            content: '';
            position: absolute;
            inset: 2px;
            z-index: -1;
            border-radius: 16px;
            background:
                radial-gradient(circle at 12% 20%, rgba(255,255,255,.34), transparent 22%),
                linear-gradient(135deg, #dc2626 0%, #f97316 42%, #facc15 100%);
        }
        .running-schedule-3d.is-idle {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 52%, #06b6d4 100%);
            box-shadow:
                0 14px 0 rgba(30, 41, 59, .5),
                0 22px 34px rgba(30, 64, 175, .36),
                inset 0 2px 0 rgba(255,255,255,.38);
        }
        .running-schedule-3d.is-idle::after {
            background:
                radial-gradient(circle at 12% 20%, rgba(255,255,255,.28), transparent 22%),
                linear-gradient(135deg, #2563eb 0%, #7c3aed 52%, #06b6d4 100%);
        }
        .running-schedule-3d.is-idle .running-live-orb {
            animation: idleBlink 1.25s ease-in-out infinite;
        }
        .running-schedule-3d.is-idle .running-kicker-dot {
            background: #facc15;
            box-shadow: 0 0 0 0 rgba(250,204,21,.9);
            animation: idleDotPulse 1.3s infinite;
        }
        .running-live-orb {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.55);
            box-shadow: inset 0 2px 8px rgba(255,255,255,.22), 0 10px 18px rgba(127,29,29,.25);
            animation: liveBlink 760ms steps(2, end) infinite;
        }
        .running-live-orb i {
            font-size: 25px;
            color: #fff;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,.25));
        }
        .running-copy {
            min-width: 0;
        }
        .running-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255,255,255,.24);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: rgba(255,255,255,.88);
        }
        .running-kicker-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34,197,94,.9);
            animation: liveDotPulse 1s infinite;
        }
        .running-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.18;
            color: rgba(255,255,255,.94);
            text-shadow: 0 2px 8px rgba(15,23,42,.18);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .running-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 7px 12px;
            margin-top: 7px;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,.82);
        }
        .running-time-pill {
            align-self: stretch;
            min-width: 104px;
            border-radius: 14px;
            padding: 8px 10px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.32);
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            box-shadow: inset 0 2px 7px rgba(255,255,255,.14);
            color: rgba(255,255,255,.92);
        }
        .running-state-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .9;
        }
        .running-state-label i {
            font-size: 12px;
        }
        .running-time-pill strong {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            margin-top: 3px;
            color: rgba(255,255,255,.9);
        }
        @keyframes runningBorderSpin {
            to { transform: rotate(360deg); }
        }
        @keyframes runningBorderBlink {
            0%, 100% { border-color: rgba(255,255,255,.95); filter: saturate(1.05) brightness(1); }
            50% { border-color: #22c55e; filter: saturate(1.4) brightness(1.18); }
        }
        @keyframes runningFloat3d {
            0%, 100% { transform: translateY(-3px) scale(1); }
            50% { transform: translateY(-7px) scale(1.012); }
        }
        @keyframes liveBlink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .55; transform: scale(.94); }
        }
        @keyframes liveDotPulse {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,.9); }
            70% { box-shadow: 0 0 0 9px rgba(34,197,94,0); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
        }
        @keyframes idleBlink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .72; transform: scale(.97); }
        }
        @keyframes idleDotPulse {
            0% { box-shadow: 0 0 0 0 rgba(250,204,21,.9); }
            70% { box-shadow: 0 0 0 9px rgba(250,204,21,0); }
            100% { box-shadow: 0 0 0 0 rgba(250,204,21,0); }
        }
        @media (max-width: 520px) {
            .running-schedule-3d {
                grid-template-columns: auto 1fr;
            }
            .running-time-pill {
                grid-column: 1 / -1;
                min-height: 48px;
            }
            .running-title {
                white-space: normal;
            }
        }
        .demo-toggle-btn {
            position: absolute;
            bottom: 6px;
            right: 12px;
            font-size: 8.5px;
            color: rgba(255, 255, 255, 0.6);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            z-index: 10;
        }
        .dual-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
            align-items: stretch;
        }
        .dual-card-grid .panel-card {
            margin-bottom: 0;
            height: 100%;
        }
        @media (max-width: 768px) {
            .dual-card-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
<link rel='stylesheet' href='pages/guru/css/guru-desktop.css?v=<?= time() ?>'>
</head>
<body>

<?php if (isset($_GET['akses_ditolak']) && $_GET['akses_ditolak'] === 'literasi'): ?>
<div id="toastAksesDitolak" style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:99999; background:#fef2f2; border:1px solid #fca5a5; border-radius:14px; padding:14px 24px; box-shadow:0 8px 25px rgba(0,0,0,0.12); display:flex; align-items:center; gap:12px; max-width:90%; animation: slideDown 0.4s ease;">
    <i class="fas fa-lock" style="color:#ef4444; font-size:20px;"></i>
    <div>
        <div style="font-weight:800; color:#991b1b; font-size:14px;">Akses Ditolak</div>
        <div style="color:#7f1d1d; font-size:12px;">Anda belum ditugaskan sebagai Pembina LENTERA Literasi. Hubungi Administrator.</div>
    </div>
    <button onclick="document.getElementById('toastAksesDitolak').remove()" style="background:none;border:none;cursor:pointer;color:#ef4444; font-size:18px; padding:0 0 0 10px;">&times;</button>
</div>
<style>@keyframes slideDown { from { opacity:0; top:-10px; } to { opacity:1; top:20px; } }</style>
<script>setTimeout(function(){ var t = document.getElementById('toastAksesDitolak'); if(t) t.remove(); }, 5000);</script>
<?php endif; ?>

  <div class="background">
    <div class="shape one"></div>
    <div class="shape two"></div>
    <div class="shape three"></div>
    <div class="shape four"></div>
    <div class="wave"></div>
    <div class="dots"></div>
  </div>


<!-- DESKTOP SIDEBAR -->
<div class="desktop-sidebar">
    <div class="desktop-logo">
        <i class="bi bi-book-half"></i> SIMANIS
    </div>
    <div class="desktop-nav">
        <a href="?page=beranda" class="active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="?page=kelas-saya"><i class="bi bi-calendar3"></i> Kelas Saya</a>
        <a href="?page=siswa"><i class="bi bi-people"></i> Data Siswa</a>
        <a href="inputnilai"><i class="bi bi-journal-check"></i> Nilai & Tugas</a>
        <a href="?page=materi"><i class="bi bi-book"></i> Materi</a>
        <a href="laporan-kelas"><i class="bi bi-cpu"></i> Laporan & AI</a>
        <a href="ekinerja"><i class="bi bi-speedometer2"></i> e-Kinerja</a>
        <a href="?page=pengaturan"><i class="bi bi-gear"></i> Pengaturan</a>
    </div>
    <div class="desktop-profile">
        <?php 
            $foto = !empty($dataGuru['foto']) ? "img/guru/" . $dataGuru['foto'] : "img/avatar.png";
        ?>
        <img src="<?= htmlspecialchars($foto) ?>" alt="Profile">
        <div class="desktop-profile-info">
            <strong><?= htmlspecialchars($dataGuru['nama_guru']) ?></strong>
            <span><?= htmlspecialchars($dataGuru['jabatan'] ?? 'Guru Bidang Studi') ?></span>
        </div>
    </div>
</div>

<div class="app-shell">
    <!-- DESKTOP TOPBAR -->
    <div class="desktop-topbar">
        <h1>Teacher Dashboard</h1>
        <div class="desktop-topbar-actions">
            <!-- Search Bar -->
            <div class="topbar-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search students, classes...">
            </div>

            <!-- Notifications -->
            <div class="topbar-btn" onclick="toggleNotif(event)">
                <i class="bi bi-bell"></i>
                <?php if($totalNotifCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="width: 8px; height: 8px;"></span>
                <?php endif; ?>
                
                <div class="notif-dropdown" id="notifDropdownDesktop" onclick="event.stopPropagation()">
                    <div class="notif-header">
                        <span>Notifikasi Anda</span>
                        <?php if($totalNotifCount > 0): ?>
                            <span class="notif-badge-inline"><?= $totalNotifCount ?> Baru</span>
                        <?php endif; ?>
                    </div>
                    <div class="notif-list">
                        <?php if($totalNotifCount > 0): ?>
                            <?php foreach($guru_all_notifications as $n): ?>
                                <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-item" <?= isset($n['action_onclick']) ? 'onclick="'.htmlspecialchars($n['action_onclick']).'"' : '' ?>>
                                    <div class="notif-icon-wrap" style="color: <?= htmlspecialchars($n['color']) ?>; background: <?= htmlspecialchars($n['color']) ?>15;">
                                        <i class="<?= htmlspecialchars($n['icon']) ?>"></i>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                                        <div class="notif-desc"><?= htmlspecialchars($n['text']) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notif-empty">
                                <i class="bi bi-bell-slash"></i>
                                Tidak ada notifikasi baru
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <img src="<?= htmlspecialchars($foto) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
        </div>
    </div>

    <!-- FIRST ROW: Class Overview, Upcoming Activities, Quick Actions -->
    <div class="desktop-grid">
        <!-- Widget 1: Class Overview -->
        <div class="dk-widget">
            <div class="dk-widget-title-wrap">
                <h3 class="dk-widget-title">Class Overview</h3>
                <span class="dk-widget-badge"><?= $totalKelasAmpu ?> Kelas</span>
            </div>
            
            <div class="dk-overview-grid">
                <div class="dk-overview-subcard blue">
                    <span>Active Students</span>
                    <strong><?= $totalSiswa ?></strong>
                </div>
                <div class="dk-overview-subcard green">
                    <span>Jurnal Progress</span>
                    <strong><?= $jurnalProgress ?>%</strong>
                </div>
                <div class="dk-overview-subcard orange">
                    <span>Recent Alerts</span>
                    <strong><?= $unfilledJurnalCount ?></strong>
                </div>
            </div>

            <a href="javascript:void(0)" onclick="$('html, body').animate({scrollTop: $('#list-jadwal-mengajar').offset().top}, 500);" class="dk-widget-footer-link">
                <span>
                    <i class="bi bi-exclamation-circle-fill <?= $unfilledJurnalCount > 0 ? 'warn-icon' : '' ?>"></i>
                    <?= $unfilledJurnalCount > 0 ? $unfilledJurnalCount . ' jurnal belum diisi hari ini' : 'Semua jurnal hari ini telah terisi' ?>
                </span>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <!-- Widget 2: Upcoming Activities -->
        <div class="dk-widget">
            <div class="dk-widget-title-wrap">
                <h3 class="dk-widget-title"><i class="bi bi-calendar-event text-primary"></i> Jadwal Mengajar Hari Ini</h3>
                <button class="icon-btn" title="Detail Jadwal" onclick="window.location='setting-jadwal'"><i class="bi bi-three-dots"></i></button>
            </div>
            
            <div class="dk-upcoming-list">
                <?php if($totalJadwalHari == 0): ?>
                    <p style="font-size:0.8rem; color:#64748b; padding: 10px 0;">Tidak ada jadwal mengajar hari ini.</p>
                <?php else: ?>
                    <?php 
                    $colors = ['blue', 'orange', 'purple', 'pink', 'green'];
                    $cIndex = 0;
                    foreach(array_slice($jadwalHariIni, 0, 3) as $j): 
                        $col = $colors[$cIndex % 5];
                        $cIndex++;
                    ?>
                        <div class="dk-upcoming-item <?= $col ?>">
                            <span class="dk-upcoming-time"><?= substr($j['jam_mulai'],0,5) ?></span>
                            <div class="dk-upcoming-divider"></div>
                            <span class="dk-upcoming-name"><?= htmlspecialchars($j['nama_mapel']) ?> (<?= htmlspecialchars($j['kelas']) ?>)</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Widget 3: Quick Actions -->
        <div class="dk-widget">
            <div class="dk-widget-title-wrap">
                <h3 class="dk-widget-title">Quick Actions</h3>
            </div>
            
            <div class="dk-quick-grid">
                <a href="validasi-izin" class="dk-quick-btn">
                    <i class="bi bi-person-check-fill text-success"></i>
                    <span>Validasi Izin</span>
                </a>
                <a href="#" class="dk-quick-btn btn-open-pelanggaran">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    <span>Pelanggaran</span>
                </a>
                <a href="jurnal" class="dk-quick-btn">
                    <i class="bi bi-journal-plus text-primary"></i>
                    <span>Isi Jurnal</span>
                </a>
                <a href="literasi.php" class="dk-quick-btn">
                    <i class="bi bi-book-half text-info"></i>
                    <span>Literasi</span>
                </a>
                <a href="ekinerja" class="dk-quick-btn">
                    <i class="bi bi-speedometer2 text-warning"></i>
                    <span>e-Kinerja</span>
                </a>
                <a href="apresiasi-guru" class="dk-quick-btn">
                    <i class="bi bi-award-fill text-primary"></i>
                    <span>Apresiasi</span>
                </a>
            </div>
        </div>
    </div>

    <!-- SECOND ROW: Class Performance Analytics, Student Progress Snapshot, Attendance Overview & Class Roster -->
    <div class="desktop-second-grid">
        <!-- Widget 4: Class Performance Analytics -->
        <div class="dk-widget">
            <div class="dk-widget-title-wrap">
                <h3 class="dk-widget-title">Class Analytics</h3>
                <button class="icon-btn"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
            
            <div class="dk-chart-flex">
                <div>
                    <span class="dk-chart-label">Jurnal Kehadiran Kelas (Mingguan)</span>
                    <div class="dk-bar-chart-container">
                        <?php
                            $bars = [
                                ['label' => 'Sen', 'val' => 60, 'c' => 'c1'],
                                ['label' => 'Sel', 'val' => 85, 'c' => 'c2'],
                                ['label' => 'Rab', 'val' => $hadirPct, 'c' => 'c3'],
                                ['label' => 'Kam', 'val' => 70, 'c' => 'c4'],
                                ['label' => 'Jum', 'val' => 90, 'c' => 'c5'],
                            ];
                            foreach($bars as $b):
                                $h = ($b['val'] ?: 10) . '%';
                        ?>
                            <div class="dk-bar-chart-col">
                                <div class="dk-bar-chart-bar <?= $b['c'] ?>" style="height: <?= $h ?>;" data-value="<?= $b['val'] ?>%"></div>
                                <span class="dk-bar-chart-label"><?= $b['label'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <span class="dk-chart-label">Rata-rata Nilai Kelas Binaan</span>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px;">
                        <span style="font-size: 1.4rem; font-weight: 800; color: #1e293b;">B+ (84%)</span>
                        <span style="font-size: 0.75rem; color: #22c55e; font-weight: 700; background: #d1fae5; padding: 2px 8px; border-radius: 6px;"><i class="bi bi-arrow-up"></i> +2.4%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 5: Student Progress Snapshot -->
        <div class="dk-widget">
            <div class="dk-widget-title-wrap">
                <h3 class="dk-widget-title">Student Snapshot</h3>
                <button class="icon-btn"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
            
            <div class="dk-student-snapshot-section">
                <span class="dk-student-snapshot-section-title">Top List (Kehadiran Terbaik)</span>
                <div class="dk-student-snapshot-list">
                    <?php if (empty($posterApresiasiTanpaAlpha)): ?>
                        <p style="font-size:0.75rem; color:#64748b;">Belum ada data prestasi.</p>
                    <?php else: ?>
                        <?php 
                        $pColors = ['blue', 'teal', 'purple'];
                        $idx = 0;
                        foreach(array_slice($posterApresiasiTanpaAlpha, 0, 2) as $sItem): 
                            $initials = strtoupper(substr($sItem['nama_siswa'], 0, 2));
                            $clr = $pColors[$idx % 3];
                            $idx++;
                        ?>
                            <div class="dk-student-snapshot-item">
                                <div class="dk-student-snapshot-avatar-placeholder"><?= $initials ?></div>
                                <div class="dk-student-snapshot-info">
                                    <div class="dk-student-snapshot-name">
                                        <span><?= htmlspecialchars(substr($sItem['nama_siswa'], 0, 14)) ?>...</span>
                                        <span class="pct"><?= $sItem['attendance_rate'] ?>%</span>
                                    </div>
                                    <div class="dk-student-snapshot-progress-bg">
                                        <div class="dk-student-snapshot-progress-bar <?= $clr ?>" style="width: <?= $sItem['attendance_rate'] ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dk-student-snapshot-section" style="margin-top: 8px;">
                <span class="dk-student-snapshot-section-title">Needs Attention</span>
                <div class="dk-student-snapshot-list">
                    <?php if (empty($problematicStudents)): ?>
                        <p style="font-size:0.75rem; color:#64748b;">Semua siswa dalam kondisi baik.</p>
                    <?php else: ?>
                        <?php 
                        $idx = 0;
                        foreach(array_slice($problematicStudents, 0, 2) as $sItem): 
                            $initials = strtoupper(substr($sItem['nama_siswa'], 0, 2));
                            $pct = max(50, 100 - ($sItem['indeks_masalah'] * 3));
                        ?>
                            <div class="dk-student-snapshot-item">
                                <div class="dk-student-snapshot-avatar-placeholder" style="background:#fee2e2; color:#ef4444;"><?= $initials ?></div>
                                <div class="dk-student-snapshot-info">
                                    <div class="dk-student-snapshot-name">
                                        <span><?= htmlspecialchars(substr($sItem['nama_siswa'], 0, 14)) ?>...</span>
                                        <span class="pct" style="color: #ef4444;"><?= $pct ?>%</span>
                                    </div>
                                    <div class="dk-student-snapshot-progress-bg">
                                        <div class="dk-student-snapshot-progress-bar orange" style="width: <?= $pct ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Widget 6: Attendance Overview & Class Roster -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Attendance Overview -->
            <div class="dk-widget" style="min-height: auto; padding: 20px;">
                <div class="dk-widget-title-wrap" style="margin-bottom: 12px;">
                    <h3 class="dk-widget-title">Attendance Overview</h3>
                </div>
                
                <div class="dk-attendance-content">
                    <div class="dk-attendance-chart-wrap">
                        <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#f1f5f9" stroke-width="3.5" />
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#10b981" stroke-width="3.5" stroke-dasharray="<?= $hadirPct ?>, 100" />
                        </svg>
                        <div class="dk-attendance-chart-center">
                            <strong><?= $hadirPct ?>%</strong>
                            <span>Present</span>
                        </div>
                    </div>

                    <div class="dk-attendance-legend">
                        <div class="dk-attendance-legend-item">
                            <div class="dk-attendance-legend-label">
                                <div class="dk-attendance-legend-dot green"></div>
                                <span>Present</span>
                            </div>
                            <span><?= $hadirToday ?></span>
                        </div>
                        <div class="dk-attendance-legend-item">
                            <div class="dk-attendance-legend-label">
                                <div class="dk-attendance-legend-dot orange"></div>
                                <span>Excused (I/S)</span>
                            </div>
                            <span><?= $totalAbsen - $hadirToday ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Roster -->
            <div class="dk-widget" style="min-height: auto; padding: 20px;">
                <div class="dk-widget-title-wrap" style="margin-bottom: 8px;">
                    <h3 class="dk-widget-title">Class Roster</h3>
                    <button class="icon-btn"><i class="bi bi-three-dots"></i></button>
                </div>
                
                <table class="dk-roster-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($problematicStudents)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#94a3b8; font-size:0.75rem; padding: 12px 0;">No student data.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach(array_slice($problematicStudents, 0, 3) as $rowP): ?>
                                <tr>
                                    <td>
                                        <div class="dk-roster-student">
                                            <span><?= htmlspecialchars(substr($rowP['nama_siswa'], 0, 10)) ?>...</span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($rowP['kelas']) ?></td>
                                    <td>
                                        <?php if ($rowP['indeks_masalah'] > 10): ?>
                                            <span class="dk-badge-status intervention">Needs Attention</span>
                                        <?php else: ?>
                                            <span class="dk-badge-status on-track">On Track</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- End Desktop Grid (Redesigned) -->
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
        <nav class="bottom-nav">
            <a href="#" class="nav-link active"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
            <a href="<?= htmlspecialchars($kelasDetailUrl, ENT_QUOTES, 'UTF-8'); ?>" class="nav-link"><i class="bi bi-journal-bookmark"></i><span>Kelas</span></a>
            <a href="#" class="nav-center btn-open-input-jurnal" aria-label="Input jurnal"><i class="bi bi-fingerprint"></i></a>
            <a href="#" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Tugas</span></a>
            <a href="profil-guru" class="nav-link">
                <div style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden; border: 1.5px solid #cbd5e1; margin-bottom: 2px; position: relative;">
                    <?php if ($dataGuru['foto']): ?>
                        <img src="../../foto/<?= $dataGuru['foto'] ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?= get_guru_avatar_svg(get_guru_gender($dataGuru['no_induk'], $dataGuru['nama_guru'])) ?>
                    <?php endif; ?>
                </div>
                <span>Profil</span>
            </a>
        </nav>
    </div>

</div>

<div class="journal-modal-backdrop" id="schedulePickerModal" aria-hidden="true">
    <div class="journal-modal journal-modal-sm" role="dialog" aria-modal="true" aria-labelledby="schedulePickerTitle">
        <div class="journal-modal-head">
            <div>
                <h5 id="schedulePickerTitle">Pilih Jadwal</h5>
                <p>Jurnal akan diisi sesuai jadwal yang dipilih.</p>
            </div>
            <button class="journal-modal-close" type="button" data-close-modal aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="journal-modal-body">
            <?php if (empty($jadwalHariIni)): ?>
                <div class="journal-empty">Belum ada jadwal mengajar hari ini.</div>
            <?php else: ?>
                <div class="journal-picker-list">
                    <?php foreach ($jadwalHariIni as $j):
                        $idMapel = (int)($j['id_mapel'] ?? 0);
                        $kelasJadwal = (string)($j['kelas'] ?? '');
                        $mapelJadwal = (string)($j['nama_mapel'] ?? '');
                        $isJurnalTerisi = isset($jurnalStatusByMapel[$idMapel]);
                    ?>
                        <div class="journal-picker-row <?= $isJurnalTerisi ? 'is-filled' : 'is-empty'; ?>" data-id="<?= $idMapel; ?>">
                            <button class="journal-picker-main" type="button" data-id="<?= $idMapel; ?>">
                                <span class="journal-picker-meta">
                                    <strong><?= htmlspecialchars($kelasJadwal, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?= htmlspecialchars($mapelJadwal, ENT_QUOTES, 'UTF-8'); ?></small>
                                    <span class="journal-status-badge <?= $isJurnalTerisi ? 'done' : 'todo'; ?>">
                                        <i class="bi <?= $isJurnalTerisi ? 'bi-check2-circle' : 'bi-clock-history'; ?>"></i>
                                        <?= $isJurnalTerisi ? 'Sudah terisi' : 'Belum terisi'; ?>
                                    </span>
                                </span>
                                <em class="journal-time"><?= htmlspecialchars(substr((string)($j['jam_mulai'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars(substr((string)($j['jam_selesai'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8'); ?></em>
                            </button>
                            <div class="journal-picker-actions">
                                <button class="journal-action-btn primary btn-open-schedule-journal" type="button" data-id="<?= $idMapel; ?>">
                                    <i class="bi bi-journal-plus"></i> <?= $isJurnalTerisi ? 'Lihat/Edit Jurnal' : 'Input Jurnal'; ?>
                                </button>
                                <a class="journal-action-btn score" href="inputnilai?getDetail=<?= $idMapel; ?>">
                                    <i class="bi bi-clipboard2-check"></i> Input Nilai
                                </a>
                                <button class="journal-action-btn danger btn-reset-jurnal" type="button" data-id="<?= $idMapel; ?>" data-kelas="<?= htmlspecialchars($kelasJadwal, ENT_QUOTES, 'UTF-8'); ?>" <?= $isJurnalTerisi ? '' : 'disabled'; ?>>
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Jurnal
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="journal-modal-backdrop" id="journalInputModal" aria-hidden="true">
    <div class="journal-modal journal-modal-lg" role="dialog" aria-modal="true" aria-labelledby="journalInputTitle">
        <div class="journal-modal-head">
            <div>
                <h5 id="journalInputTitle">Input Jurnal Mengajar</h5>
                <p>Lengkapi materi, kegiatan, dan presensi siswa.</p>
            </div>
            <button class="journal-modal-close" type="button" data-close-modal aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="journal-modal-body">
            <div class="modal-data">
                <div class="journal-loading">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                    <span>Memuat form jurnal...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="journal-modal-backdrop" id="notifPanelModal" aria-hidden="true">
    <div class="journal-modal" role="dialog" aria-modal="true" aria-labelledby="notifPanelTitle" style="max-height: 85vh; width: min(100%, 460px);">
        <div class="journal-modal-head" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: #fff; padding: 16px 20px;">
            <div>
                <h5 id="notifPanelTitle" style="color:#fff; font-size:16px; font-weight:600; margin:0;"><i class="bi bi-bell-fill me-2"></i> Notifikasi & Tindak Lanjut</h5>
                <p style="color:rgba(255,255,255,0.8); font-size:11px; margin:4px 0 0 0;">Informasi penting hari ini.</p>
            </div>
            <button class="journal-modal-close" type="button" data-close-modal aria-label="Tutup" style="background:rgba(255,255,255,0.15); color:#fff; border:none; width:32px; height:32px; border-radius:10px;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="journal-modal-body" style="padding: 16px; background: #f8fafc;">
            <div class="notif-sections-list" style="display: flex; flex-direction: column; gap: 16px;">
                
                <!-- ADUAN SISWA: Hanya tampil untuk Tim Aduan yang ditugaskan admin -->
                <?php if ($isTimAduan): ?>
                <div class="notif-card" style="background: #fff; border-radius: 16px; border: 1px solid rgba(220, 38, 38, 0.15); padding: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-shield-fill-exclamation"></i> Aduan Siswa
                            <span style="background: #fef2f2; color: #9f1239; font-size: 8px; padding: 1px 5px; border-radius: 4px; font-weight: 600; text-transform: none; letter-spacing: 0;"><i class="bi bi-incognito"></i> Anonim</span>
                        </span>
                        <span style="background: #fee2e2; color: #dc2626; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 99px;">
                            <?= $aduanGuruCount ?> Aktif
                        </span>
                    </div>
                    <?php if ($aduanGuruCount === 0): ?>
                        <p style="font-size: 11px; color: #64748b; margin: 0; text-align: left;">Tidak ada aduan siswa yang masuk saat ini.</p>
                    <?php else: ?>
                        <p style="font-size: 10px; color: #9f1239; background: #fef2f2; border-radius: 8px; padding: 6px 10px; margin: 0 0 10px 0; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-eye-slash-fill"></i> <strong>Aduan bersifat anonim.</strong> Identitas pelapor dirahasiakan. Tim aduan bertanggung jawab mencari fakta.
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ($aduanGuruRows as $ad): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff1f2; border-radius: 10px; border: 1px dashed rgba(220, 38, 38, 0.15);">
                                    <div style="text-align: left;">
                                        <strong style="font-size: 12px; color: #1e293b; display: block; text-align: left;"><?= htmlspecialchars($ad['judul']) ?></strong>
                                        <span style="font-size: 10px; color: #64748b; display: block; text-align: left; margin-top: 2px;">
                                            <?= htmlspecialchars($ad['kode_aduan']) ?> &bull; <?= htmlspecialchars($ad['kategori']) ?>
                                        </span>
                                        <div style="margin-top: 4px;">
                                            <span style="background: #fee2e2; color: #dc2626; font-size: 8.5px; padding: 1px 5px; border-radius: 4px; font-weight: 600;">Prioritas: <?= htmlspecialchars($ad['prioritas']) ?></span>
                                            <span style="background: #fef3c7; color: #92400e; font-size: 8.5px; padding: 1px 5px; border-radius: 4px; font-weight: 600;">Masuk: <?= date('d M', strtotime($ad['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <a href="../../home.php?page=aduan-siswa" style="background: #dc2626; color: #fff; font-size: 10px; padding: 5px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; flex-shrink: 0; margin-left: 8px;">Tindak Lanjut</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; /* end isTimAduan */ ?>
                
                <!-- 1. SISWA BUTUH PENDAMPINGAN (wali kelas + guru mapel) -->
                <?php if (!empty($pendampinganKelasAll)): ?>
                <div class="notif-card" id="notifPendampinganCard" style="background: #fff; border-radius: 16px; border: 1px solid rgba(239, 68, 68, 0.15); padding: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-heart-pulse-fill"></i> Siswa Butuh Pendampingan
                        </span>
                        <span style="background: #fee2e2; color: #ef4444; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 99px;">
                            <?= $problematicCount ?> Siswa
                        </span>
                    </div>
                    <?php if ($problematicCount === 0): ?>
                        <p style="font-size: 11px; color: #64748b; margin: 0; text-align: left;">Alhamdulillah, tidak ada siswa yang membutuhkan pendampingan khusus saat ini.</p>
                    <?php else: ?>
                        <!-- MENU SPLIT PER KELAS: klik kelas untuk expand/collapse daftar siswa -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ($pendampinganByKelas as $kelasGrp => $siswaGrp): ?>
                                <?php $grpId = 'pendamp-grp-' . preg_replace('/[^a-zA-Z0-9]/', '_', $kelasGrp); ?>
                                <!-- Header Kelas - bisa diklik -->
                                <div class="pendamp-kelas-header" data-target="<?= $grpId ?>" 
                                     style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; background: linear-gradient(135deg, #fef2f2, #fff5f5); border-radius: 10px; border: 1px solid rgba(239,68,68,0.18); cursor: pointer; user-select: none;">
                                    <span style="font-size: 12px; font-weight: 700; color: #dc2626; display: inline-flex; align-items: center; gap: 7px;">
                                        <i class="bi bi-people-fill"></i>
                                        Kelas <?= htmlspecialchars($kelasGrp) ?>
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <span style="background: #fee2e2; color: #dc2626; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 99px;"><?= count($siswaGrp) ?> siswa</span>
                                        <i class="bi bi-chevron-down pendamp-chevron" style="font-size: 11px; color: #ef4444; transition: transform 0.25s;"></i>
                                    </span>
                                </div>
                                <!-- Daftar Siswa per Kelas - collapsed by default -->
                                <div id="<?= $grpId ?>" style="display: none; flex-direction: column; gap: 6px; padding: 0 2px;">
                                    <?php foreach ($siswaGrp as $s): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #fff5f5; border-radius: 10px; border: 1px dashed rgba(239, 68, 68, 0.1);">
                                            <div style="text-align: left; min-width: 0;">
                                                <strong style="font-size: 12px; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($s['nama_siswa']) ?></strong>
                                                <div style="margin-top: 4px; display: flex; gap: 5px; flex-wrap: wrap;">
                                                    <?php if ($s['alpha_count'] > 0): ?>
                                                        <span style="background: #fee2e2; color: #ef4444; font-size: 8px; padding: 1px 5px; border-radius: 4px; font-weight: 600;">Alpha: <?= $s['alpha_count'] ?>x</span>
                                                    <?php endif; ?>
                                                    <?php if ($s['telat_count'] > 0): ?>
                                                        <span style="background: #fef3c7; color: #d97706; font-size: 8px; padding: 1px 5px; border-radius: 4px; font-weight: 600;">Telat: <?= $s['telat_count'] ?>x</span>
                                                    <?php endif; ?>
                                                    <?php if ($s['pelanggaran_count'] > 0): ?>
                                                        <span style="background: #f3e8ff; color: #7c3aed; font-size: 8px; padding: 1px 5px; border-radius: 4px; font-weight: 600;">Pelanggaran: <?= $s['pelanggaran_count'] ?>x</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="laporan-kelas?kelas=<?= urlencode($s['kelas']) ?>" style="background: #ef4444; color: #fff; font-size: 10px; padding: 5px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; flex-shrink: 0; margin-left: 8px; white-space: nowrap;">Lihat</a>
                                        </div>
                                    <?php endforeach; ?>
                                    <div style="text-align: right; margin-top: 2px;">
                                        <a href="laporan-kelas?kelas=<?= urlencode($kelasGrp) ?>" style="font-size: 10px; color: #ef4444; text-decoration: none; font-weight: 600;"><i class="bi bi-arrow-right"></i> Tindak Lanjut Kelas <?= htmlspecialchars($kelasGrp) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; /* end pendampinganKelasAll */ ?>

                <!-- 2. JURNAL BELUM TERISI: hidden jika tidak ada jadwal hari ini -->
                <?php if ($totalJadwalHari > 0): ?>
                <div class="notif-card" style="background: #fff; border-radius: 16px; border: 1px solid rgba(245, 158, 11, 0.15); padding: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-journal-x"></i> Jurnal Belum Terisi
                        </span>
                        <span style="background: #fef3c7; color: #d97706; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 99px;">
                            <?= $unfilledJurnalCount ?> Jadwal
                        </span>
                    </div>
                    <?php if ($unfilledJurnalCount === 0): ?>
                        <p style="font-size: 11px; color: #64748b; margin: 0; text-align: left;">Hebat! Semua jurnal mengajar Anda hari ini sudah terisi dengan lengkap.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ($unfilledJadwal as $j): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fffbeb; border-radius: 10px; border: 1px dashed rgba(245, 158, 11, 0.15);">
                                    <div style="text-align: left;">
                                        <strong style="font-size: 12px; color: #1e293b; display: block; text-align: left;"><?= htmlspecialchars($j['nama_mapel']) ?></strong>
                                        <span style="font-size: 10px; color: #64748b; display: block; text-align: left; margin-top: 2px;">Kelas: <?= htmlspecialchars($j['kelas']) ?> (<?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>)</span>
                                    </div>
                                    <button class="btn-open-jurnal-from-notif" data-id="<?= $j['id_mapel'] ?>" style="background: #f59e0b; color: #fff; border: none; font-size: 10px; padding: 5px 10px; border-radius: 8px; font-weight: 600; cursor: pointer; flex-shrink: 0; margin-left: 8px;">Isi Sekarang</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; /* end totalJadwalHari */ ?>

                <?php if ($nextJadwal !== null): ?>
                <div class="notif-card" style="background: #fff; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.15); padding: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                    <div style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-calendar-event"></i> Jadwal Berikutnya
                        </span>
                        <span style="background: #eff6ff; color: #3b82f6; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 99px;"><?= substr($nextJadwal['jam_mulai'],0,5) ?></span>
                    </div>
                    <div style="padding: 10px; background: #eff6ff; border-radius: 10px; border: 1px solid rgba(59, 130, 246, 0.1); text-align: left;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong style="font-size: 13px; color: #1e293b; display: block; text-align: left;"><?= htmlspecialchars($nextJadwal['nama_mapel']) ?></strong>
                        </div>
                        <span style="font-size: 11px; color: #64748b; display:block; margin-top:4px; text-align: left;">Kelas: <?= htmlspecialchars($nextJadwal['kelas']) ?></span>
                        <span style="font-size: 11px; color: #64748b; display:block; text-align: left;">Ruang: <?= htmlspecialchars($nextJadwal['ruang'] ?? 'R. Kelas') ?></span>
                    </div>
                </div>
                <?php endif; /* end nextJadwal */ ?>

                <!-- 4. PENGUMUMAN ADMIN: hidden jika tidak ada pengumuman -->
                <?php if ($announcementCount > 0): ?>
                <div class="notif-card" style="background: #fff; border-radius: 16px; border: 1px solid rgba(124, 58, 237, 0.15); padding: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-megaphone-fill"></i> Pengumuman dari Admin
                        </span>
                        <span style="background: #f3e8ff; color: #7c3aed; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 99px;">
                            <?= $announcementCount ?> Baru
                        </span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($announcements as $ann): ?>
                            <div style="padding: 10px; background: #faf5ff; border-radius: 10px; border: 1px solid rgba(124, 58, 237, 0.1); text-align: left;">
                                <strong style="font-size: 12px; color: #1e293b; display: block; text-align: left;"><?= htmlspecialchars($ann['judul']) ?></strong>
                                <p style="font-size: 10.5px; color: #475569; margin: 4px 0 0 0; line-height: 1.4; text-align: left;"><?= nl2br(htmlspecialchars($ann['isi'])) ?></p>
                                <small style="font-size: 9px; color: #94a3b8; display: block; margin-top: 6px; text-align: left;"><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($ann['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; /* end announcementCount */ ?>

            </div>
        </div>
    </div>
</div>

<div class="journal-modal-backdrop" id="guruWaliModal" aria-hidden="true">
    <div class="journal-modal" role="dialog" aria-modal="true" aria-labelledby="guruWaliTitle" style="max-height: 88vh; width: min(100%, 520px);">
        <div class="journal-modal-head" style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff; padding: 16px 20px;">
            <div>
                <h5 id="guruWaliTitle" style="color:#fff; font-size:16px; font-weight:700; margin:0;"><i class="bi bi-person-workspace me-2"></i>Guru Wali</h5>
                <p style="color:rgba(255,255,255,0.85); font-size:11px; margin:4px 0 0 0;">Tambahkan siswa yang menjadi binaan pribadi Anda.</p>
            </div>
            <button class="journal-modal-close" type="button" data-close-modal aria-label="Tutup" style="background:rgba(255,255,255,0.15); color:#fff; border:none; width:32px; height:32px; border-radius:10px;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="journal-modal-body" style="padding: 16px; background: #f8fafc;">
            <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                <a href="guru-wali-siswa" style="display:flex; align-items:center; gap:14px; padding:16px; background:#fff; border:1px solid rgba(20,184,166,.18); border-radius:18px; text-decoration:none; box-shadow:0 10px 24px rgba(15,23,42,.06);">
                    <span style="width:52px; height:52px; border-radius:16px; display:grid; place-items:center; background:#ccfbf1; color:#0f766e; font-size:25px; flex:0 0 auto;"><i class="bi bi-person-plus-fill"></i></span>
                    <span style="min-width:0;">
                        <strong style="display:block; color:#0f172a; font-size:15px;">Tambah Siswa</strong>
                        <small style="display:block; color:#64748b; margin-top:3px; line-height:1.35;">Pilih kelas dan siswa untuk menambahkan daftar binaan guru wali.</small>
                    </span>
                    <i class="bi bi-chevron-right" style="margin-left:auto; color:#94a3b8;"></i>
                </a>
                <a href="guru-wali-jurnal" style="display:flex; align-items:center; gap:14px; padding:16px; background:#fff; border:1px solid rgba(67,56,202,.18); border-radius:18px; text-decoration:none; box-shadow:0 10px 24px rgba(15,23,42,.06);">
                    <span style="width:52px; height:52px; border-radius:16px; display:grid; place-items:center; background:#e0e7ff; color:#4338ca; font-size:25px; flex:0 0 auto;"><i class="bi bi-journal-text"></i></span>
                    <span style="min-width:0;">
                        <strong style="display:block; color:#0f172a; font-size:15px;">Jurnal Pendampingan</strong>
                        <small style="display:block; color:#64748b; margin-top:3px; line-height:1.35;">Catat pendampingan, tindak lanjut, dan status siswa binaan.</small>
                    </span>
                    <i class="bi bi-chevron-right" style="margin-left:auto; color:#94a3b8;"></i>
                </a>
            </div>
            <div style="display:none;">
            <?php if ($guruWaliFlash !== ''): ?>
                <div class="alert alert-<?= htmlspecialchars($guruWaliFlashType, ENT_QUOTES, 'UTF-8'); ?> py-2 mb-3" style="font-size:12px; border-radius:12px;">
                    <?= htmlspecialchars($guruWaliFlash, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="guruWaliForm" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:14px; box-shadow:0 8px 20px rgba(15,23,42,.05);">
                <input type="hidden" name="guru_wali_action" value="add">
                <div class="mb-3">
                    <label class="form-label" for="kelasBinaan" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Pilih Kelas</label>
                    <select class="form-select" id="kelasBinaan" name="kelas_binaan" required style="border-radius:12px; font-size:13px;">
                        <option value="">Pilih kelas</option>
                        <?php foreach ($guruWaliKelasOptions as $kelasOption): ?>
                            <option value="<?= htmlspecialchars($kelasOption, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($kelasOption, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="siswaBinaan" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Pilih Siswa</label>
                    <select class="form-select" id="siswaBinaan" name="siswa_binaan" required disabled style="border-radius:12px; font-size:13px;">
                        <option value="">Pilih kelas terlebih dahulu</option>
                    </select>
                </div>
                <button type="submit" class="btn w-100" style="background:#0f766e; color:#fff; border-radius:12px; font-weight:800; font-size:13px;">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Siswa Binaan
                </button>
            </form>

            <div style="margin-top:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <strong style="font-size:13px; color:#0f172a;">Daftar Siswa Binaan</strong>
                    <span style="background:#ccfbf1; color:#0f766e; font-size:10px; font-weight:800; padding:3px 8px; border-radius:999px;"><?= count($guruWaliBinaan); ?> siswa</span>
                </div>
                <?php if (empty($guruWaliBinaan)): ?>
                    <div style="background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:16px; color:#64748b; font-size:12px; text-align:center;">
                        Belum ada siswa binaan. Pilih kelas dan siswa untuk mulai menambahkan.
                    </div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px; max-height:260px; overflow:auto;">
                        <?php foreach ($guruWaliBinaan as $binaan): ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:10px 12px;">
                                <div style="min-width:0;">
                                    <strong style="display:block; font-size:12.5px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars((string)($binaan['nama_siswa'] ?: $binaan['no_induk_siswa']), ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>
                                    <span style="display:block; font-size:10.5px; color:#64748b; margin-top:2px;">
                                        Kelas <?= htmlspecialchars((string)$binaan['kelas'], ENT_QUOTES, 'UTF-8'); ?> · NIS <?= htmlspecialchars((string)$binaan['no_induk_siswa'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <form method="post" onsubmit="return confirm('Hapus siswa dari daftar binaan?');" style="margin:0;">
                                    <input type="hidden" name="guru_wali_action" value="delete">
                                    <input type="hidden" name="id_binaan" value="<?= (int)$binaan['id']; ?>">
                                    <button type="submit" title="Hapus" style="width:32px; height:32px; border:0; border-radius:10px; background:#fee2e2; color:#dc2626; display:grid; place-items:center;">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top:18px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <strong style="font-size:13px; color:#0f172a;"><i class="bi bi-journal-text me-1"></i>Jurnal Pendampingan Guru Wali</strong>
                    <span style="background:#e0e7ff; color:#4338ca; font-size:10px; font-weight:800; padding:3px 8px; border-radius:999px;"><?= count($guruWaliJurnal); ?> catatan</span>
                </div>

                <form method="post" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:14px; box-shadow:0 8px 20px rgba(15,23,42,.05);">
                    <input type="hidden" name="guru_wali_action" value="journal_add">
                    <div class="row g-2">
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="jurnalSiswaBinaan" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Siswa Binaan</label>
                            <select class="form-select" id="jurnalSiswaBinaan" name="jurnal_siswa_binaan" required style="border-radius:12px; font-size:13px;">
                                <option value="">Pilih siswa binaan</option>
                                <?php foreach ($guruWaliBinaan as $binaan): ?>
                                    <option value="<?= htmlspecialchars((string)$binaan['no_induk_siswa'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars((string)($binaan['nama_siswa'] ?: $binaan['no_induk_siswa']), ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars((string)$binaan['kelas'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label" for="jurnalTanggal" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Tanggal</label>
                            <input class="form-control" type="date" id="jurnalTanggal" name="jurnal_tanggal" value="<?= date('Y-m-d'); ?>" required style="border-radius:12px; font-size:13px;">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="jurnalCatatan" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Catatan Pendampingan</label>
                            <textarea class="form-control" id="jurnalCatatan" name="jurnal_catatan" rows="3" required placeholder="Contoh: Siswa perlu pendampingan terkait kedisiplinan, motivasi belajar, atau komunikasi dengan orang tua." style="border-radius:12px; font-size:13px;"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="jurnalTindakLanjut" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Tindak Lanjut</label>
                            <textarea class="form-control" id="jurnalTindakLanjut" name="jurnal_tindak_lanjut" rows="2" placeholder="Rencana tindak lanjut atau hasil komunikasi." style="border-radius:12px; font-size:13px;"></textarea>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label" for="jurnalStatus" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Status</label>
                            <select class="form-select" id="jurnalStatus" name="jurnal_status" style="border-radius:12px; font-size:13px;">
                                <option value="Dipantau">Dipantau</option>
                                <option value="Perlu Tindak Lanjut">Perlu Tindak Lanjut</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7 d-flex align-items-end">
                            <button type="submit" class="btn w-100" style="background:#4338ca; color:#fff; border-radius:12px; font-weight:800; font-size:13px;" <?= empty($guruWaliBinaan) ? 'disabled title="Tambahkan siswa binaan terlebih dahulu"' : ''; ?>>
                                <i class="bi bi-journal-plus me-1"></i> Simpan Jurnal Pendampingan
                            </button>
                        </div>
                    </div>
                </form>

                <div style="margin-top:12px;">
                    <?php if (empty($guruWaliJurnal)): ?>
                        <div style="background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:16px; color:#64748b; font-size:12px; text-align:center;">
                            Belum ada jurnal pendampingan guru wali.
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:8px; max-height:280px; overflow:auto;">
                            <?php foreach ($guruWaliJurnal as $jurnalWali): ?>
                                <?php
                                    $statusWali = (string)($jurnalWali['status'] ?? 'Dipantau');
                                    $statusColor = $statusWali === 'Selesai' ? '#16a34a' : ($statusWali === 'Perlu Tindak Lanjut' ? '#dc2626' : '#4338ca');
                                ?>
                                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:11px 12px;">
                                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                                        <div style="min-width:0;">
                                            <strong style="display:block; font-size:12.5px; color:#0f172a;"><?= htmlspecialchars((string)($jurnalWali['nama_siswa'] ?: $jurnalWali['no_induk_siswa']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span style="display:block; font-size:10.5px; color:#64748b; margin-top:2px;">
                                                <?= date('d M Y', strtotime((string)$jurnalWali['tanggal'])); ?> · Kelas <?= htmlspecialchars((string)$jurnalWali['kelas'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                        <span style="background:<?= $statusColor; ?>18; color:<?= $statusColor; ?>; font-size:9.5px; font-weight:800; padding:3px 7px; border-radius:999px; white-space:nowrap;">
                                            <?= htmlspecialchars($statusWali, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                    <div style="font-size:11.5px; color:#334155; margin-top:8px; line-height:1.45;"><?= nl2br(htmlspecialchars((string)$jurnalWali['catatan'], ENT_QUOTES, 'UTF-8')); ?></div>
                                    <?php if (!empty($jurnalWali['tindak_lanjut'])): ?>
                                        <div style="font-size:11px; color:#64748b; margin-top:6px; padding-top:6px; border-top:1px dashed #e2e8f0;">
                                            <strong>Tindak lanjut:</strong> <?= nl2br(htmlspecialchars((string)$jurnalWali['tindak_lanjut'], ENT_QUOTES, 'UTF-8')); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<div class="journal-modal-backdrop" id="pelanggaranModal" aria-hidden="true">
    <div class="journal-modal" style="max-width:620px;">
        <div class="journal-modal-head" style="background:linear-gradient(135deg,#dc2626,#991b1b); color:#fff;">
            <div>
                <h3 style="margin:0; color:#fff;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Catat Pelanggaran Siswa</h3>
                <p style="margin:4px 0 0; color:rgba(255,255,255,.78); font-size:12px;">Pilih siswa, kategori, jenis pelanggaran, dan tindak lanjut.</p>
            </div>
            <button type="button" data-close-modal aria-label="Tutup" style="width:36px; height:36px; border:0; border-radius:12px; background:rgba(255,255,255,.16); color:#fff;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="journal-modal-body">
            <form id="formPelanggaran" style="display:grid; gap:12px;">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="selectKelasP" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Kelas</label>
                        <select class="form-select" id="selectKelasP" name="kelas" required style="border-radius:12px; font-size:13px;">
                            <option value="">Pilih kelas</option>
                            <?php foreach ($guruWaliKelasOptions as $kelasOption): ?>
                                <option value="<?= htmlspecialchars($kelasOption, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($kelasOption, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="selectSiswaP" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Siswa</label>
                        <select class="form-select" id="selectSiswaP" name="no_induk" required disabled style="border-radius:12px; font-size:13px;">
                            <option value="">Pilih kelas terlebih dahulu</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="kategoriPelanggaran" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Kategori</label>
                        <select class="form-select" id="kategoriPelanggaran" name="kategori_pelanggaran" required style="border-radius:12px; font-size:13px;">
                            <option value="">Pilih kategori</option>
                            <option value="Ringan">Ringan</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Berat">Berat</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="jenisPelanggaran" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Jenis Pelanggaran</label>
                        <select class="form-select" id="jenisPelanggaran" name="jenis_pelanggaran" required disabled style="border-radius:12px; font-size:13px;">
                            <option value="">Pilih kategori terlebih dahulu</option>
                        </select>
                    </div>
                    <div class="col-12" id="jenisPelanggaranKustomWrapper" style="display:none; margin-top:8px;">
                        <label class="form-label" for="jenisPelanggaranKustom" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Jenis Pelanggaran Lainnya</label>
                        <input class="form-control" type="text" id="jenisPelanggaranKustom" name="jenis_pelanggaran_kustom" placeholder="Sebutkan jenis pelanggaran..." style="border-radius:12px; font-size:13px;">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="deskripsiPelanggaran" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Deskripsi</label>
                    <textarea class="form-control" id="deskripsiPelanggaran" name="deskripsi_pelanggaran" rows="3" placeholder="Jelaskan detail pelanggaran yang dilakukan." style="border-radius:12px; font-size:13px;"></textarea>
                </div>
                <div>
                    <label class="form-label" for="tindakanGuru" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Tindakan yang Diambil</label>
                    <textarea class="form-control" id="tindakanGuru" name="tindakan_guru" rows="2" placeholder="Teguran, pembinaan, komunikasi wali, atau tindak lanjut lain." style="border-radius:12px; font-size:13px;"></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label" for="statusPelanggaran" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase;">Status</label>
                        <select class="form-select" id="statusPelanggaran" name="status_pelanggaran" style="border-radius:12px; font-size:13px;">
                            <option value="Aktif">Aktif</option>
                            <option value="Diselesaikan">Diselesaikan</option>
                            <option value="Follow Up">Perlu Follow Up</option>
                        </select>
                    </div>
                </div>
                <div id="pelanggaranStatus" style="display:none; font-size:12px; border-radius:12px; padding:10px 12px;"></div>
                <div style="display:flex; gap:10px; justify-content:flex-end; padding-top:4px;">
                    <button type="button" data-close-modal class="btn btn-light" style="border-radius:12px; font-weight:800;">Batal</button>
                    <button type="submit" id="btnSimpanPelanggaran" class="btn" style="background:#dc2626; color:#fff; border-radius:12px; font-weight:800;">
                        <i class="bi bi-check-lg me-1"></i>Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            var jadwalToday = <?= json_encode(array_map(static function ($j) use ($jurnalStatusByMapel) {
                $idMapel = (int)($j['id_mapel'] ?? 0);
                return [
                    'id_mapel' => $idMapel,
                    'kelas' => (string)($j['kelas'] ?? ''),
                    'nama_mapel' => (string)($j['nama_mapel'] ?? ''),
                    'jam_mulai' => (string)($j['jam_mulai'] ?? ''),
                    'jam_selesai' => (string)($j['jam_selesai'] ?? ''),
                    'jurnal_terisi' => isset($jurnalStatusByMapel[$idMapel])
                ];
            }, $jadwalHariIni), JSON_UNESCAPED_SLASHES); ?>;
            var guruWaliStudentsByClass = <?= json_encode($guruWaliStudentsByClass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

            window.showToast = window.showToast || function(message) {
                alert(message);
            };

            function openDashboardModal(selector) {
                $(selector).addClass('is-open').attr('aria-hidden', 'false');
                $('body').addClass('modal-open-dashboard');
            }

            function closeDashboardModal(selector) {
                $(selector).removeClass('is-open').attr('aria-hidden', 'true');
                if ($('.journal-modal-backdrop.is-open').length === 0) {
                    $('body').removeClass('modal-open-dashboard');
                }
            }

            function openInputJurnal(idMapel) {
                if (!idMapel) {
                    alert('Jadwal tidak valid.');
                    return;
                }
                var $modalData = $('.modal-data').removeClass('journal-loading');
                $modalData.html('<div class="journal-loading"><span class="spinner-border spinner-border-sm text-primary me-2"></span><span>Memuat form jurnal...</span></div>');
                openDashboardModal('#journalInputModal');
                $.post('detailmateri.php', { getDetail: idMapel }, function(data) {
                    $modalData.html(data);
                }).fail(function() {
                    $modalData.html('<div class="alert alert-danger mb-0">Gagal memuat form jurnal.</div>');
                });
            }

            function startInputJurnal() {
                if (jadwalToday.length === 0) {
                    alert('Belum ada jadwal mengajar hari ini.');
                    return;
                }
                openDashboardModal('#schedulePickerModal');
            }

            // Animasi Ring Progress
            setTimeout(function() {
                var ring = $('#ringProgress');
                var pct = ring.data('progress');
                var r = 45;
                var c = 2 * Math.PI * r;
                var offset = c - (pct / 100) * c;
                ring.css('stroke-dashoffset', offset);
            }, 100);

            // Handler Input Jurnal
            $('.btn-open-input-jurnal').on('click', function() {
                startInputJurnal();
            });

            // Auto-open schedule picker if coming from other pages with open_jurnal parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('open_jurnal')) {
                startInputJurnal();
            }

            $('.journal-picker-main, .btn-open-schedule-journal').on('click', function() {
                var idMapel = parseInt($(this).data('id'), 10);
                closeDashboardModal('#schedulePickerModal');
                openInputJurnal(idMapel);
            });

            $('.btn-reset-jurnal').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var idMapel = parseInt($btn.data('id'), 10);
                var kelas = $btn.data('kelas') || 'kelas ini';

                if (!idMapel || $btn.prop('disabled')) {
                    return;
                }

                if (!confirm('Reset jurnal untuk ' + kelas + ' hari ini? Data jurnal dan presensi pada jadwal ini akan dihapus.')) {
                    return;
                }

                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Reset...');

                $.post('reset-jurnal.php', { idmapel: idMapel }, function(response) {
                    var payload = response;
                    if (typeof response === 'string') {
                        try {
                            payload = JSON.parse(response);
                        } catch (err) {
                            payload = null;
                        }
                    }

                    if (payload && payload.success) {
                        window.location.href = 'guru_legacy?reset=jurnal';
                        return;
                    }

                    alert((payload && payload.message) ? payload.message : 'Reset jurnal gagal.');
                    $btn.prop('disabled', false).html(originalHtml);
                }).fail(function() {
                    alert('Gagal menghubungi server reset jurnal.');
                    $btn.prop('disabled', false).html(originalHtml);
                });
            });

            $('[data-close-modal]').on('click', function() {
                closeDashboardModal('#schedulePickerModal');
                closeDashboardModal('#journalInputModal');
                closeDashboardModal('#notifPanelModal');
                closeDashboardModal('#guruWaliModal');
                closeDashboardModal('#pelanggaranModal');
            });

            $('.journal-modal-backdrop').on('click', function(e) {
                if (e.target === this) {
                    closeDashboardModal('#' + this.id);
                }
            });

            $(document).on('click', '[data-bs-dismiss="modal"]', function() {
                closeDashboardModal('#journalInputModal');
            });

            // Navigasi Bottom
            $('.nav-link, .nav-center').on('click', function(e) {
                var href = $(this).attr('href') || '#';
                if (href !== '#') {
                    return;
                }
                e.preventDefault();
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
            });

            // Notifikasi Toggle
            $('#btn-open-notif-drawer').on('click', function() {
                openDashboardModal('#notifPanelModal');
            });

            $('.btn-open-guru-wali').on('click', function(e) {
                e.preventDefault();
                openDashboardModal('#guruWaliModal');
            });

            $('.btn-open-pelanggaran').on('click', function(e) {
                e.preventDefault();
                openDashboardModal('#pelanggaranModal');
            });

            <?php if ($guruWaliFlash !== ''): ?>
            openDashboardModal('#guruWaliModal');
            <?php endif; ?>

            $('#kelasBinaan').on('change', function() {
                var selectedClass = $(this).val();
                var students = guruWaliStudentsByClass[selectedClass] || [];
                var $siswa = $('#siswaBinaan');

                $siswa.empty();
                if (!selectedClass) {
                    $siswa.append('<option value="">Pilih kelas terlebih dahulu</option>');
                    $siswa.prop('disabled', true);
                    return;
                }

                if (students.length === 0) {
                    $siswa.append('<option value="">Tidak ada siswa aktif di kelas ini</option>');
                    $siswa.prop('disabled', true);
                    return;
                }

                $siswa.append('<option value="">Pilih siswa</option>');
                students.forEach(function(student) {
                    var label = (student.nama_siswa || student.no_induk) + ' - ' + student.no_induk;
                    $('<option></option>').val(student.no_induk).text(label).appendTo($siswa);
                });
                $siswa.prop('disabled', false);
            });

            var jenisPelanggaranData = {
                'Berat': ['Tindak kekerasan', 'Membawa minuman keras', 'Membawa senjata tajam/berbahaya', 'Merokok di area sekolah', 'Membawa/menggunakan narkoba', 'Perbuatan asusila', 'Bullying/intimidasi', 'Mencuri', 'Bolos berkepanjangan (>3 hari berturut)', 'Lainnya'],
                'Sedang': ['Seragam tidak sesuai aturan', 'Terlambat berulang kali', 'Alpha tanpa keterangan (2-3 kali)', 'Tidak mengerjakan tugas berulang kali', 'Membawa HP saat ujian', 'Berkelahi ringan', 'Tidak hormat pada guru', 'Lainnya'],
                'Ringan': ['Terlambat masuk kelas', 'Alpha tanpa keterangan (1 kali)', 'Tidak mengerjakan PR', 'Ramai di kelas', 'Tidak membawa buku/alat tulis', 'Makan di kelas saat pelajaran', 'Tidur di kelas', 'Lainnya']
            };

            $('#selectKelasP').on('change', function() {
                var selectedClass = $(this).val();
                var students = guruWaliStudentsByClass[selectedClass] || [];
                var $siswa = $('#selectSiswaP');

                $siswa.empty();
                if (!selectedClass) {
                    $siswa.append('<option value="">Pilih kelas terlebih dahulu</option>');
                    $siswa.prop('disabled', true);
                    return;
                }
                if (students.length === 0) {
                    $siswa.append('<option value="">Tidak ada siswa aktif di kelas ini</option>');
                    $siswa.prop('disabled', true);
                    return;
                }
                $siswa.append('<option value="">Pilih siswa</option>');
                students.forEach(function(student) {
                    var label = (student.nama_siswa || student.no_induk) + ' - ' + student.no_induk;
                    $('<option></option>').val(student.no_induk).text(label).appendTo($siswa);
                });
                $siswa.prop('disabled', false);
            });

            $('#kategoriPelanggaran').on('change', function() {
                var kategori = $(this).val();
                var $jenis = $('#jenisPelanggaran');

                $jenis.empty();
                $('#jenisPelanggaranKustomWrapper').hide();
                $('#jenisPelanggaranKustom').prop('required', false).val('');
                if (!kategori || !jenisPelanggaranData[kategori]) {
                    $jenis.append('<option value="">Pilih kategori terlebih dahulu</option>');
                    $jenis.prop('disabled', true);
                    return;
                }
                $jenis.append('<option value="">Pilih jenis pelanggaran</option>');
                jenisPelanggaranData[kategori].forEach(function(jenis) {
                    $('<option></option>').val(jenis).text(jenis).appendTo($jenis);
                });
                $jenis.prop('disabled', false);
            });

            $('#jenisPelanggaran').on('change', function() {
                var val = $(this).val();
                if (val === 'Lainnya') {
                    $('#jenisPelanggaranKustomWrapper').show();
                    $('#jenisPelanggaranKustom').prop('required', true).focus();
                } else {
                    $('#jenisPelanggaranKustomWrapper').hide();
                    $('#jenisPelanggaranKustom').prop('required', false).val('');
                }
            });

            $('#formPelanggaran').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                var $btn = $('#btnSimpanPelanggaran');
                var $status = $('#pelanggaranStatus');
                var originalHtml = $btn.html();

                $status.hide().removeClass('alert alert-success alert-danger').text('');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');

                $.ajax({
                    url: '../../simpan_pelanggaran.php',
                    method: 'POST',
                    data: new FormData(form),
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                }).done(function(response) {
                    if (response && response.success) {
                        $status.addClass('alert alert-success').text(response.message || 'Catatan pelanggaran berhasil disimpan.').show();
                        form.reset();
                        $('#selectSiswaP').prop('disabled', true).html('<option value="">Pilih kelas terlebih dahulu</option>');
                        $('#jenisPelanggaran').prop('disabled', true).html('<option value="">Pilih kategori terlebih dahulu</option>');
                        $('#jenisPelanggaranKustomWrapper').hide();
                        $('#jenisPelanggaranKustom').prop('required', false).val('');
                        setTimeout(function() {
                            window.location.reload();
                        }, 700);
                    } else {
                        $status.addClass('alert alert-danger').text((response && response.message) ? response.message : 'Gagal menyimpan catatan pelanggaran.').show();
                    }
                }).fail(function(xhr) {
                    var message = 'Gagal menghubungi server simpan pelanggaran.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    $status.addClass('alert alert-danger').text(message).show();
                }).always(function() {
                    $btn.prop('disabled', false).html(originalHtml);
                });
            });

            $(document).on('click', '.btn-open-jurnal-from-notif', function() {
                var idMapel = parseInt($(this).data('id'), 10);
                closeDashboardModal('#notifPanelModal');
                openInputJurnal(idMapel);
            });

            // === TOGGLE EXPAND/COLLAPSE SISWA BUTUH PENDAMPINGAN ===
            $(document).on('click', '.pendamp-kelas-header', function() {
                var targetId = $(this).data('target');
                var $panel = $('#' + targetId);
                var $chevron = $(this).find('.pendamp-chevron');

                if ($panel.css('display') === 'none') {
                    $panel.css('display', 'flex');
                    $chevron.css('transform', 'rotate(180deg)');
                } else {
                    $panel.css('display', 'none');
                    $chevron.css('transform', 'rotate(0deg)');
                }
            });
        });
    </script>
    <script>
        // KBM Countdown Timer
        (function() {
            var timerInterval = null;
            
            function initTimer() {
                if (timerInterval) clearInterval(timerInterval);
                
                var timerVal = document.getElementById("kbm-timer");
                if (!timerVal) return;
                
                var kbmBox = document.getElementById("kbm-box");
                var targetStr = kbmBox.getAttribute("data-target");
                if (!targetStr) return;
                
                var targetDate = new Date(targetStr).getTime();
                
                function updateTimer() {
                    var now = new Date().getTime();
                    var diff = targetDate - now;
                    
                    if (diff <= 0) {
                        timerVal.textContent = "00m 00s";
                        clearInterval(timerInterval);
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                        return;
                    }
                    
                    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    var display = "";
                    if (hours > 0) display += String(hours).padStart(2, '0') + "j ";
                    display += String(minutes).padStart(2, '0') + "m " + String(seconds).padStart(2, '0') + "s";
                    
                    timerVal.textContent = display;
                }
                
                updateTimer();
                timerInterval = setInterval(updateTimer, 1000);
            }

            initTimer();
        })();
    </script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>

<script>
function toggleNotif(event) {
    event.stopPropagation();
    var dropdown = document.getElementById("notifDropdownDesktop");
    if(dropdown) {
        dropdown.classList.toggle("show");
    }
}
window.addEventListener("click", function(e) {
    var dropdown = document.getElementById("notifDropdownDesktop");
    if(dropdown && dropdown.classList.contains("show")) {
        dropdown.classList.remove("show");
    }
});
</script>
</body>
</html>
