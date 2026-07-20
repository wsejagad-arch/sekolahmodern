<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/nocache.php';
require_login();

include "koneksi.php";
include "SimpleXLSX.php";

$hak_akses = $_SESSION['hak_akses'] ?? 0;

if (isset($_POST['import'])) {
    $filename = $_FILES['file']['tmp_name'];
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    
    if ($ext != 'xlsx') {
        echo "<script>alert('Harap upload file Excel dengan format .xlsx'); window.location.href='/home.php?page=import-jadwal';</script>";
        exit();
    }

    $xlsx = SimpleXLSX::parse($filename);
    if ($xlsx) {
        $rows = $xlsx->rows();
        $count = 0;
        $skipped = 0;
        $unfound_teachers = [];
        
        foreach ($rows as $index => $row) {
            // Lewati header (baris 1)
            if ($index == 0) continue;
            
            // Cek apakah baris kosong
            if (empty(array_filter($row))) continue;

            // Template format:
            // no_induk(0), nama_guru(1), nama_mapel(2), kelas(3), hari(4), jam_mulai(5), jam_selesai(6), ruang(7)
            $no_induk   = mysqli_real_escape_string($conn, $row[0] ?? '');
            $nama_guru  = mysqli_real_escape_string($conn, $row[1] ?? '');
            $nama_mapel = mysqli_real_escape_string($conn, $row[2] ?? '');
            $kelas      = mysqli_real_escape_string($conn, $row[3] ?? '');
            $hari       = mysqli_real_escape_string($conn, $row[4] ?? '');
            $jam_mulai  = mysqli_real_escape_string($conn, $row[5] ?? '');
            $jam_selesai= mysqli_real_escape_string($conn, $row[6] ?? '');
            $ruang      = mysqli_real_escape_string($conn, $row[7] ?? '');

            if (empty($no_induk) && !empty($nama_guru)) {
                // Bersihkan nama dari gelar untuk pencarian yang lebih fleksibel
                $name_clean = explode(',', $nama_guru)[0]; // Ambil sebelum koma
                $name_clean = str_ireplace(['Drs.', 'Dra.', 'H.', 'Hj.', 'Dr.', 'M.Pd', 'S.Pd'], '', $name_clean);
                $name_clean = trim($name_clean);
                
                // Ambil 1 atau 2 kata pertama
                $words = explode(' ', $name_clean);
                $search_name = $words[0];
                if (isset($words[1]) && strlen($words[1]) > 2) {
                    $search_name .= ' ' . $words[1];
                }

                $qGuru = @mysqli_query($conn, "SELECT no_induk FROM tbl_guru WHERE nama_guru LIKE '%$search_name%' LIMIT 1");
                if ($qGuru && mysqli_num_rows($qGuru) > 0) {
                    $rowGuru = mysqli_fetch_assoc($qGuru);
                    $no_induk = $rowGuru['no_induk'];
                }
            }

            if (empty($no_induk)) {
                if (!empty($nama_guru)) {
                    $unfound_teachers[$nama_guru] = true;
                }
                $skipped++;
                continue; 
            }

            if (empty($nama_mapel) || empty($kelas) || empty($hari)) {
                continue; // Harus ada data minimum
            }

            // Insert ke tbl_mapel_ampu
            // Cek duplikasi terlebih dahulu? Kalau ya, update. Kalau tidak, insert
            $check = @mysqli_query($conn, "SELECT id_mapel FROM tbl_mapel_ampu WHERE no_induk='$no_induk' AND nama_mapel='$nama_mapel' AND kelas='$kelas' AND hari='$hari' AND jam_mulai='$jam_mulai'");
            
            if ($check && mysqli_num_rows($check) > 0) {
                // Update
                @mysqli_query($conn, "UPDATE tbl_mapel_ampu SET jam_selesai='$jam_selesai', ruang='$ruang' WHERE no_induk='$no_induk' AND nama_mapel='$nama_mapel' AND kelas='$kelas' AND hari='$hari' AND jam_mulai='$jam_mulai'");
                $count++;
            } else {
                // Insert
                @mysqli_query($conn, "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai, ruang) VALUES ('$no_induk', '$nama_mapel', '$kelas', '$hari', '$jam_mulai', '$jam_selesai', '$ruang')");
                $count++;
            }
        }
        
        $msg = "Berhasil mengimpor $count data jadwal mengajar.";
        if ($skipped > 0) {
            $msg .= "\\nAda $skipped jadwal yang dilewati karena Guru tidak ditemukan di database.";
            if (count($unfound_teachers) > 0) {
                $msg .= "\\nGuru yang tidak ditemukan: " . implode(", ", array_keys($unfound_teachers));
            }
        }
        
        echo "<script>alert('$msg'); window.location.href='/home/import-jadwal';</script>";
        
    } else {
        $err = SimpleXLSX::parseError();
        echo "<script>alert('Gagal membaca file Excel: $err'); window.location.href='/home/import-jadwal';</script>";
    }
} else {
    header("Location: /home/import-jadwal");
}
?>
