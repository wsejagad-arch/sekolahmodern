<?php

/**
 * GURU NOTIFICATIONS LOGIC
 * File untuk menghitung notifikasi yang akan ditampilkan di header
 * Include setelah session & database connection
 * 
 * Dependencies:
 * - $conn (mysqli connection)
 * - $nipguru (guru NIP)
 * - $tglskr (current date in Y-m-d format)
 * - $hariini (current day in Indonesian)
 */

if (!isset($notifikasiData)) {
    $notifikasiData = [];
}

// 1. Jurnal Belum Terisi Hari Ini
$jurnalBelumTerisi = 0;
if (!empty($jadwalHariIni)) {
    $idListJadwal = implode(',', array_column($jadwalHariIni, 'id_mapel'));
    $qJT = mysqli_query($conn, "SELECT COUNT(DISTINCT id_mapel) as jml FROM tbl_mapel_ampu m WHERE m.no_induk='$nipguru' AND m.hari='$hariini' AND m.id_mapel NOT IN (SELECT id_mapel FROM tbl_materi WHERE tanggal='$tglskr')");
    if ($qJT) {
        $rowJT = mysqli_fetch_assoc($qJT);
        $jurnalBelumTerisi = (int)($rowJT['jml'] ?? 0);
    }
}

if ($jurnalBelumTerisi > 0) {
    $notifikasiData[] = [
        'type' => 'jurnal',
        'title' => 'Jurnal Belum Terisi',
        'message' => $jurnalBelumTerisi . ' jurnal hari ini masih kosong',
        'icon' => 'bi-notebook',
        'color' => 'warning',
        'count' => $jurnalBelumTerisi,
        'link' => 'guru_jurnal.php'
    ];
}

// 2. Siswa Izin yang Sudah Di-ACC
$siswaDiAccIzin = 0;
$checkTableIzin = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_siswa_izin'");
if ($checkTableIzin && mysqli_num_rows($checkTableIzin) > 0) {
    $qIzinAcc = @mysqli_query($conn, "
    SELECT COUNT(DISTINCT si.no_induk) as jml 
    FROM tbl_siswa_izin si 
    JOIN tbl_siswa s ON si.no_induk = s.no_induk 
    WHERE si.status_pengajuan='Diterima' 
      AND (si.tanggal_awal <= '$tglskr' AND si.tanggal_akhir >= '$tglskr')
      AND s.status='Aktif'
      AND s.kelas IN (SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$nipguru')
  ");
    if ($qIzinAcc) {
        $rowIzin = mysqli_fetch_assoc($qIzinAcc);
        $siswaDiAccIzin = (int)($rowIzin['jml'] ?? 0);
    }
}

if ($siswaDiAccIzin > 0) {
    $notifikasiData[] = [
        'type' => 'izin',
        'title' => 'Siswa Izin Di-ACC',
        'message' => $siswaDiAccIzin . ' siswa memiliki izin yang sudah disetujui',
        'icon' => 'bi-person-check',
        'color' => 'info',
        'count' => $siswaDiAccIzin,
        'link' => 'validasi-izin.php'
    ];
}

// 3. Siswa Bermasalah (Alpha > 3x atau Sakit > 3 hari) - Khusus Wali Kelas
$siswaBermasalah = 0;

// Check if tbl_wali_kelas exists
$checkTableWali = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
if ($checkTableWali && mysqli_num_rows($checkTableWali) > 0) {
    // Cek guru adalah wali kelas
    $qKelasWali = @mysqli_query($conn, "SELECT k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON wk.id_kelas = k.id_kelas WHERE wk.nip_wali='$nipguru' LIMIT 1");
    if ($qKelasWali && mysqli_num_rows($qKelasWali) > 0) {
        $rowKelas = mysqli_fetch_assoc($qKelasWali);
        $kelasWali = $rowKelas['kelas'];

        // Check if tbl_absen exists
        $checkTableAbsen = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_absen'");
        if ($checkTableAbsen && mysqli_num_rows($checkTableAbsen) > 0) {
            // Alpha > 3x
            $qAlpha = @mysqli_query($conn, "
        SELECT COUNT(DISTINCT s.no_induk) as jml 
        FROM tbl_siswa s
        WHERE s.kelas='$kelasWali' AND s.status='Aktif'
          AND s.no_induk IN (
            SELECT no_induk FROM tbl_absen 
            WHERE status='Alpha' 
            GROUP BY no_induk 
            HAVING COUNT(*) > 3
          )
      ");

            if ($qAlpha) {
                $rowAlpha = mysqli_fetch_assoc($qAlpha);
                $siswaBermasalah += (int)($rowAlpha['jml'] ?? 0);
            }
        }

        // Check if tbl_siswa_izin exists
        $checkTableIzin = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_siswa_izin'");
        if ($checkTableIzin && mysqli_num_rows($checkTableIzin) > 0) {
            // Sakit > 3 hari
            $qSakit = @mysqli_query($conn, "
        SELECT COUNT(DISTINCT s.no_induk) as jml 
        FROM tbl_siswa s
        WHERE s.kelas='$kelasWali' AND s.status='Aktif'
          AND s.no_induk IN (
            SELECT no_induk FROM tbl_siswa_izin 
            WHERE status_pengajuan='Diterima' 
              AND (keterangan LIKE '%Sakit%' OR keterangan LIKE '%sakit%')
            GROUP BY no_induk 
            HAVING SUM(DATEDIFF(tanggal_akhir, tanggal_awal)) > 3
          )
      ");

            if ($qSakit) {
                $rowSakit = mysqli_fetch_assoc($qSakit);
                $siswaBermasalah += (int)($rowSakit['jml'] ?? 0);
            }
        }
    }
}

if ($siswaBermasalah > 0) {
    $notifikasiData[] = [
        'type' => 'siswa_bermasalah',
        'title' => 'Siswa Bermasalah',
        'message' => $siswaBermasalah . ' siswa Alpha > 3x atau Sakit > 3 hari',
        'icon' => 'bi-exclamation-circle',
        'color' => 'danger',
        'count' => $siswaBermasalah,
        'link' => 'presensi.php'
    ];
}

// Total notifikasi
$totalNotifikasi = count($notifikasiData);
