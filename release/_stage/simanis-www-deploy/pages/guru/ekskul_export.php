<?php
// pages/guru/ekskul_export.php
require_once __DIR__ . '/../../koneksi.php';

if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] == '') {
    die("Akses ditolak");
}

$nipguru = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipguru);

$type = $_GET['type'] ?? 'jurnal'; // jurnal, presensi, nilai
$scope = $_GET['scope'] ?? 'satuan'; // satuan, global
$id_ekskul = isset($_GET['id_ekskul']) ? (int)$_GET['id_ekskul'] : 0;

// Fetch list of ekskul managed by this teacher
$q_myekskul = mysqli_query($conn, "SELECT e.* FROM tbl_ekskul e JOIN tbl_pembina_ekskul p ON e.id_ekskul = p.id_ekskul WHERE p.no_induk_guru = '$nipEsc'");
$my_ekskuls = [];
while ($r = mysqli_fetch_assoc($q_myekskul)) {
    $my_ekskuls[$r['id_ekskul']] = $r;
}

if ($scope === 'satuan') {
    if (!isset($my_ekskuls[$id_ekskul])) {
        die("Anda tidak memiliki akses ke ekstrakurikuler ini.");
    }
    $ekskulList = [$my_ekskuls[$id_ekskul]];
    $filename = "ekskul_" . strtolower(str_replace(' ', '_', $my_ekskuls[$id_ekskul]['nama_ekskul'])) . "_" . $type . "_" . date('Ymd') . ".xls";
} else {
    if (empty($my_ekskuls)) {
        die("Anda tidak membina ekstrakurikuler manapun.");
    }
    $ekskulList = array_values($my_ekskuls);
    $filename = "ekskul_semua_" . $type . "_" . date('Ymd') . ".xls";
}

// Set header for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

function get_grade_label($present, $total) {
    if ($total <= 0) return 'D';
    $pct = ($present / $total) * 100;
    if ($pct >= 90) return 'A';
    if ($pct >= 80) return 'B';
    if ($pct >= 70) return 'C';
    return 'D';
}

function get_grade_desc($grade) {
    switch ($grade) {
        case 'A': return 'Sangat baik, aktif berpartisipasi.';
        case 'B': return 'Baik, aktif berpartisipasi.';
        case 'C': return 'Cukup aktif berpartisipasi.';
        case 'D': return 'Kurang aktif berpartisipasi.';
        default: return '-';
    }
}

if ($type === 'jurnal') {
    echo "<h2>LAPORAN JURNAL KEGIATAN EKSTRAKURIKULER</h2>";
    foreach ($ekskulList as $ekskul) {
        $ide = (int)$ekskul['id_ekskul'];
        echo "<h3>Ekstrakurikuler: " . htmlspecialchars($ekskul['nama_ekskul']) . "</h3>";
        echo "<p>Deskripsi: " . htmlspecialchars($ekskul['deskripsi'] ?? '-') . "</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr style='background-color:#f2f2f2;'>
                <th>No</th>
                <th>Tanggal</th>
                <th>Materi / Kegiatan</th>
                <th>Keterangan</th>
              </tr>";
        
        $q_jurnal = mysqli_query($conn, "SELECT * FROM tbl_jurnal_ekskul WHERE id_ekskul = $ide ORDER BY tanggal ASC");
        $no = 1;
        if (mysqli_num_rows($q_jurnal) === 0) {
            echo "<tr><td colspan='4' align='center'>Belum ada data jurnal.</td></tr>";
        } else {
            while ($j = mysqli_fetch_assoc($q_jurnal)) {
                echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>" . date('d-m-Y', strtotime($j['tanggal'])) . "</td>
                        <td>" . htmlspecialchars($j['materi']) . "</td>
                        <td>" . htmlspecialchars($j['keterangan'] ?? '-') . "</td>
                      </tr>";
            }
        }
        echo "</table><br><br>";
    }
} elseif ($type === 'presensi') {
    echo "<h2>LAPORAN DAFTAR HADIR ANGGOTA EKSTRAKURIKULER</h2>";
    foreach ($ekskulList as $ekskul) {
        $ide = (int)$ekskul['id_ekskul'];
        echo "<h3>Ekstrakurikuler: " . htmlspecialchars($ekskul['nama_ekskul']) . "</h3>";
        
        // Fetch all unique dates for this ekskul
        $q_dates = mysqli_query($conn, "SELECT DISTINCT tanggal FROM tbl_presensi_ekskul WHERE id_ekskul = $ide ORDER BY tanggal ASC");
        $dates = [];
        while ($d = mysqli_fetch_assoc($q_dates)) {
            $dates[] = $d['tanggal'];
        }
        
        // Fetch all students in this ekskul
        $q_students = mysqli_query($conn, "SELECT a.no_induk_siswa, s.nama_siswa, s.kelas FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $ide ORDER BY s.nama_siswa ASC");
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr style='background-color:#f2f2f2;'>
                <th rowspan='2'>No</th>
                <th rowspan='2'>NIS</th>
                <th rowspan='2'>Nama Siswa</th>
                <th rowspan='2'>Kelas</th>
                <th colspan='" . max(1, count($dates)) . "' align='center'>Tanggal Pertemuan</th>
                <th colspan='4' align='center'>Rekap</th>
              </tr>";
              
        echo "<tr style='background-color:#f2f2f2;'>";
        if (empty($dates)) {
            echo "<th>Belum Ada Pertemuan</th>";
        } else {
            foreach ($dates as $date) {
                echo "<th>" . date('d/m', strtotime($date)) . "</th>";
            }
        }
        echo "<th>H</th><th>S</th><th>I</th><th>A</th>";
        echo "</tr>";
        
        $no = 1;
        if (mysqli_num_rows($q_students) === 0) {
            $colSpan = 8 + count($dates);
            echo "<tr><td colspan='$colSpan' align='center'>Belum ada anggota siswa.</td></tr>";
        } else {
            while ($s = mysqli_fetch_assoc($q_students)) {
                $nis = $s['no_induk_siswa'];
                echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>'" . $nis . "</td>
                        <td>" . htmlspecialchars($s['nama_siswa']) . "</td>
                        <td>" . htmlspecialchars($s['kelas']) . "</td>";
                
                $rekap = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0];
                if (empty($dates)) {
                    echo "<td>-</td>";
                } else {
                    foreach ($dates as $date) {
                        $q_status = mysqli_query($conn, "SELECT status FROM tbl_presensi_ekskul WHERE id_ekskul = $ide AND no_induk_siswa = '$nis' AND tanggal = '$date' LIMIT 1");
                        $status_row = mysqli_fetch_assoc($q_status);
                        $status = $status_row['status'] ?? '-';
                        if ($status === 'Hadir') $rekap['Hadir']++;
                        elseif ($status === 'Sakit') $rekap['Sakit']++;
                        elseif ($status === 'Izin') $rekap['Izin']++;
                        elseif ($status === 'Alpa') $rekap['Alpa']++;
                        
                        $code = '-';
                        if ($status === 'Hadir') $code = 'H';
                        elseif ($status === 'Sakit') $code = 'S';
                        elseif ($status === 'Izin') $code = 'I';
                        elseif ($status === 'Alpa') $code = 'A';
                        
                        echo "<td align='center'>$code</td>";
                    }
                }
                echo "<td align='center' style='color:green;'>" . $rekap['Hadir'] . "</td>
                      <td align='center' style='color:blue;'>" . $rekap['Sakit'] . "</td>
                      <td align='center' style='color:orange;'>" . $rekap['Izin'] . "</td>
                      <td align='center' style='color:red;'>" . $rekap['Alpa'] . "</td>";
                echo "</tr>";
            }
        }
        echo "</table><br><br>";
    }
} elseif ($type === 'nilai') {
    echo "<h2>LAPORAN DATA NILAI ANGGOTA EKSTRAKURIKULER</h2>";
    foreach ($ekskulList as $ekskul) {
        $ide = (int)$ekskul['id_ekskul'];
        echo "<h3>Ekstrakurikuler: " . htmlspecialchars($ekskul['nama_ekskul']) . "</h3>";
        
        // Total pertemuan
        $total_meetings_res = mysqli_query($conn, "SELECT COUNT(DISTINCT tanggal) as total FROM tbl_presensi_ekskul WHERE id_ekskul = $ide");
        $total_meetings = mysqli_fetch_assoc($total_meetings_res)['total'] ?? 0;
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr style='background-color:#f2f2f2;'>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Kehadiran (H/Total)</th>
                <th>% Hadir</th>
                <th>Rekomendasi Nilai Absensi</th>
                <th>Nilai Akhir (Input Guru)</th>
                <th>Deskripsi</th>
              </tr>";
              
        $q_students = mysqli_query($conn, "SELECT a.no_induk_siswa, a.nilai, s.nama_siswa, s.kelas FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $ide ORDER BY s.nama_siswa ASC");
        
        $no = 1;
        if (mysqli_num_rows($q_students) === 0) {
            echo "<tr><td colspan='9' align='center'>Belum ada data nilai.</td></tr>";
        } else {
            while ($s = mysqli_fetch_assoc($q_students)) {
                $nis = $s['no_induk_siswa'];
                $q_present = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_presensi_ekskul WHERE id_ekskul = $ide AND no_induk_siswa = '$nis' AND status = 'Hadir'");
                $present = mysqli_fetch_assoc($q_present)['total'] ?? 0;
                
                $pct = $total_meetings > 0 ? round(($present / $total_meetings) * 100, 1) : 0;
                $rec_grade = get_grade_label($present, $total_meetings);
                
                $final_grade = $s['nilai'] !== '' ? $s['nilai'] : $rec_grade;
                $desc = get_grade_desc($final_grade);
                
                echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>'" . $nis . "</td>
                        <td>" . htmlspecialchars($s['nama_siswa']) . "</td>
                        <td>" . htmlspecialchars($s['kelas']) . "</td>
                        <td align='center'>$present / $total_meetings</td>
                        <td align='center'>$pct%</td>
                        <td align='center'>$rec_grade</td>
                        <td align='center'><strong>$final_grade</strong></td>
                        <td>$desc</td>
                      </tr>";
            }
        }
        echo "</table><br><br>";
    }
}
?>
