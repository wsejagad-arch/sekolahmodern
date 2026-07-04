<?php
include "koneksi.php";

echo "<h1>Menambahkan Data Absen Sample</h1>";

// Cek apakah sudah ada data
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_absen");
$row = mysqli_fetch_assoc($result);

if ($row['total'] > 0) {
    echo "<p style='color:orange'>⚠️ Sudah ada {$row['total']} data absen. Tidak akan menambahkan data sample.</p>";
} else {
    echo "<p>📝 Menambahkan data absen sample...</p>";

    // Ambil beberapa siswa untuk sample
    $siswa = mysqli_query($conn, "SELECT no_induk, kelas FROM tbl_siswa WHERE status='Aktif' LIMIT 10");
    $siswaData = [];
    while ($s = mysqli_fetch_assoc($siswa)) {
        $siswaData[] = $s;
    }

    if (empty($siswaData)) {
        echo "<p style='color:red'>❌ Tidak ada data siswa aktif untuk membuat sample.</p>";
        exit;
    }

    // Ambil mapel yang ada
    $mapel = mysqli_query($conn, "SELECT id_mapel FROM tbl_mapel_ampu LIMIT 5");
    $mapelData = [];
    while ($m = mysqli_fetch_assoc($mapel)) {
        $mapelData[] = $m['id_mapel'];
    }

    if (empty($mapelData)) {
        echo "<p style='color:red'>❌ Tidak ada data mapel untuk membuat sample.</p>";
        exit;
    }

    // Generate data absen untuk bulan Januari 2026
    $inserted = 0;
    $currentDate = strtotime('2026-01-01');
    $endDate = strtotime('2026-01-31');

    while ($currentDate <= $endDate) {
        $tanggal = date('Y-m-d', $currentDate);

        // Skip weekend (Sabtu = 6, Minggu = 0)
        $dayOfWeek = date('w', $currentDate);
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $currentDate = strtotime('+1 day', $currentDate);
            continue;
        }

        foreach ($siswaData as $s) {
            foreach ($mapelData as $idMapel) {
                // Random status dengan probabilitas
                $rand = rand(1, 100);
                if ($rand <= 80) {
                    $status = 'Hadir';
                } elseif ($rand <= 90) {
                    $status = 'Ijin';
                } elseif ($rand <= 95) {
                    $status = 'Sakit';
                } elseif ($rand <= 98) {
                    $status = 'Dispen';
                } else {
                    $status = 'Alpha';
                }

                $query = "INSERT INTO tbl_absen (tanggal, no_induk, kelas, id_mapel, status)
                         VALUES ('$tanggal', '{$s['no_induk']}', '{$s['kelas']}', '$idMapel', '$status')";

                if (mysqli_query($conn, $query)) {
                    $inserted++;
                } else {
                    echo "<p style='color:red'>❌ Error insert: " . mysqli_error($conn) . "</p>";
                }
            }
        }

        $currentDate = strtotime('+1 day', $currentDate);
    }

    echo "<p style='color:green'>✅ Berhasil menambahkan $inserted data absen sample.</p>";
    echo "<p><a href='home.php?page=rekap_absen_siswa'>🔗 Lihat Rekap Absen Siswa</a></p>";
}

mysqli_close($conn);
?>