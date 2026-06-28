<?php
// test_cetak_debug.php
// File untuk debug cetak jurnal - cek data di database
session_start();
include "koneksi.php";

// Simulasi parameter
$guru = $_GET['guru'] ?? ($_SESSION['no_induk'] ?? '');
$kelas = $_GET['kelas'] ?? '';
$tglAwal = $_GET['tglAwal'] ?? '2025-07-01';
$tglAkhir = $_GET['tglAkhir'] ?? date('Y-m-d');

echo "<h2>Debug Cetak Jurnal</h2>";
echo "<p><strong>Parameter:</strong></p>";
echo "<ul>";
echo "<li>Guru (no_induk): " . htmlspecialchars($guru) . "</li>";
echo "<li>Kelas: " . htmlspecialchars($kelas ? $kelas : '(semua)') . "</li>";
echo "<li>Periode: " . htmlspecialchars($tglAwal) . " s/d " . htmlspecialchars($tglAkhir) . "</li>";
echo "</ul>";

// 1. Cek guru di tbl_guru
echo "<h3>1. Data Guru</h3>";
$queryGuru = "SELECT * FROM tbl_guru WHERE no_induk = '" . mysqli_real_escape_string($conn, $guru) . "'";
echo "<pre>Query: " . htmlspecialchars($queryGuru) . "</pre>";
$resultGuru = mysqli_query($conn, $queryGuru);
if ($resultGuru && mysqli_num_rows($resultGuru) > 0) {
    $dataGuru = mysqli_fetch_assoc($resultGuru);
    echo "<p style='color:green'>✅ Guru ditemukan: " . htmlspecialchars($dataGuru['nama_guru']) . "</p>";
} else {
    echo "<p style='color:red'>❌ Guru tidak ditemukan!</p>";
    if (!$resultGuru) echo "<p>Error: " . mysqli_error($conn) . "</p>";

    // Tampilkan contoh no_induk yang valid untuk membantu debug
    echo "<h4>Contoh No Induk tersedia (tbl_guru)</h4>";
    $listGuru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru ORDER BY nama_guru LIMIT 10");
    if ($listGuru && mysqli_num_rows($listGuru) > 0) {
        echo "<ul>";
        while ($g = mysqli_fetch_assoc($listGuru)) {
            echo "<li>" . htmlspecialchars($g['no_induk']) . " - " . htmlspecialchars($g['nama_guru']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Tidak ada data guru di tbl_guru.</p>";
    }

    echo "<h4>Contoh No Induk di Jadwal (tbl_mapel_ampu)</h4>";
    $listJadwalGuru = mysqli_query($conn, "SELECT no_induk, COUNT(*) AS total FROM tbl_mapel_ampu GROUP BY no_induk ORDER BY total DESC LIMIT 10");
    if ($listJadwalGuru && mysqli_num_rows($listJadwalGuru) > 0) {
        echo "<ul>";
        while ($jg = mysqli_fetch_assoc($listJadwalGuru)) {
            echo "<li>" . htmlspecialchars($jg['no_induk']) . " (jadwal: " . htmlspecialchars($jg['total']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Tidak ada data jadwal di tbl_mapel_ampu.</p>";
    }
}

// 2. Cek jadwal di tbl_mapel_ampu
echo "<h3>2. Data Jadwal Mengajar (tbl_mapel_ampu)</h3>";
$queryJadwal = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '" . mysqli_real_escape_string($conn, $guru) . "'";
if (!empty($kelas)) {
    $queryJadwal .= " AND kelas = '" . mysqli_real_escape_string($conn, $kelas) . "'";
}
$queryJadwal .= " ORDER BY hari, jam_mulai";
echo "<pre>Query: " . htmlspecialchars($queryJadwal) . "</pre>";
$resultJadwal = mysqli_query($conn, $queryJadwal);
if ($resultJadwal) {
    $countJadwal = mysqli_num_rows($resultJadwal);
    echo "<p><strong>Jumlah jadwal:</strong> $countJadwal</p>";
    if ($countJadwal > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Kelas</th><th>Mapel</th><th>Hari</th><th>Jam</th></tr>";
        while ($jadwal = mysqli_fetch_assoc($resultJadwal)) {
            echo "<tr>";
            echo "<td>" . $jadwal['id_mapel'] . "</td>";
            echo "<td>" . htmlspecialchars($jadwal['kelas']) . "</td>";
            echo "<td>" . htmlspecialchars($jadwal['nama_mapel']) . "</td>";
            echo "<td>" . htmlspecialchars($jadwal['hari']) . "</td>";
            echo "<td>" . htmlspecialchars($jadwal['jam_mulai']) . " - " . htmlspecialchars($jadwal['jam_selesai']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>❌ Tidak ada jadwal ditemukan untuk filter ini!</p>";
    }
} else {
    echo "<p style='color:red'>❌ Query error: " . mysqli_error($conn) . "</p>";
}

// 3. Cek jurnal di tbl_materi
echo "<h3>3. Data Jurnal (tbl_materi)</h3>";
$queryJurnal = "SELECT * FROM tbl_materi WHERE no_induk = '" . mysqli_real_escape_string($conn, $guru) . "' AND tanggal BETWEEN '" . mysqli_real_escape_string($conn, $tglAwal) . "' AND '" . mysqli_real_escape_string($conn, $tglAkhir) . "'";
if (!empty($kelas)) {
    $queryJurnal .= " AND kelas = '" . mysqli_real_escape_string($conn, $kelas) . "'";
}
$queryJurnal .= " ORDER BY tanggal DESC LIMIT 20";
echo "<pre>Query: " . htmlspecialchars($queryJurnal) . "</pre>";
$resultJurnal = mysqli_query($conn, $queryJurnal);
if ($resultJurnal) {
    $countJurnal = mysqli_num_rows($resultJurnal);
    echo "<p><strong>Jumlah jurnal:</strong> $countJurnal</p>";
    if ($countJurnal > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr>";
        while ($jurnal = mysqli_fetch_assoc($resultJurnal)) {
            echo "<tr>";
            echo "<td>" . $jurnal['id_materi'] . "</td>";
            echo "<td>" . htmlspecialchars($jurnal['tanggal']) . "</td>";
            echo "<td>" . htmlspecialchars($jurnal['kelas']) . "</td>";
            echo "<td>" . htmlspecialchars($jurnal['nama_mapel']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($jurnal['materi'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ Tidak ada jurnal dalam periode ini</p>";
    }
} else {
    echo "<p style='color:red'>❌ Query error: " . mysqli_error($conn) . "</p>";
}

// 4. Rekomendasi
echo "<h3>4. Kesimpulan & Rekomendasi</h3>";
if (!isset($resultGuru) || mysqli_num_rows($resultGuru) == 0) {
    echo "<p style='color:red'>⚠️ <strong>Masalah:</strong> Data guru tidak ditemukan. Pastikan parameter 'guru' berisi no_induk yang valid.</p>";
} elseif (!isset($resultJadwal) || mysqli_num_rows($resultJadwal) == 0) {
    echo "<p style='color:red'>⚠️ <strong>Masalah:</strong> Tidak ada jadwal mengajar. Pastikan guru memiliki jadwal di tbl_mapel_ampu untuk kelas yang dipilih.</p>";
} else {
    echo "<p style='color:green'>✅ Data guru dan jadwal ditemukan. Cetak jurnal seharusnya bisa menampilkan jadwal.</p>";
    if (!isset($resultJurnal) || mysqli_num_rows($resultJurnal) == 0) {
        echo "<p style='color:orange'>ℹ️ Belum ada jurnal yang diisi dalam periode ini. Tabel akan menampilkan jadwal dengan status 'Belum Mengisi Jurnal'.</p>";
    }
}

echo "<hr>";
echo "<p><a href='cetak-jurnal.php?guru=" . urlencode($guru) . "&kelas=" . urlencode($kelas) . "&tglAwal=" . urlencode($tglAwal) . "&tglAkhir=" . urlencode($tglAkhir) . "' target='_blank'>Buka Cetak Jurnal</a></p>";
?>
