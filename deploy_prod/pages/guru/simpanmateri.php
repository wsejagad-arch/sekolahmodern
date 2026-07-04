<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['no_induk'])) {
    header("location:index.php?haruslogin");
    exit();
} else if($_SESSION['hak_akses'] != 2) { ?>
    <script>window.location='404.html';</script>
<?php
}

include "../../koneksi.php";
include "../../functions.php";

$tanggal    = $_POST['tanggal'];
$nip        = $_POST['nip'];
$idmapel    = $_POST['idmapel'];
$namamapel  = $_POST['namamapel'];
$kelas      = $_POST['kelas'];
$materi     = $_POST['materi'];
$kegiatan   = $_POST['kegiatan'];
$keterangan = $_POST['keterangan'];

$absenData  = $_POST['absen'] ?? []; // ✅ array per siswa

// File upload opsional
$namafile   = $_FILES['file']['name'] ?? '';
$ukuranFile = $_FILES['file']['size'] ?? 0;
$error      = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
$tmpName    = $_FILES['file']['tmp_name'] ?? '';
$cekfoto    = '';
if ($error === UPLOAD_ERR_OK && !empty($namafile) && is_uploaded_file($tmpName)) {
    $cekfoto = cek_foto($namafile);
}

// 1️⃣ simpan ke tbl_materi (pakai implode biar bisa ditampilkan ringkas)
$absenText = "";
if (!empty($absenData)) {
    $temp = [];
    foreach($absenData as $no_induk => $status) {
        // Hanya tampilkan siswa yang tidak hadir dalam ringkasan jurnal
        if ($status !== 'Hadir') {
            // ambil nama siswa
            $qNama = mysqli_query($conn, "SELECT nama_siswa FROM tbl_siswa WHERE no_induk='$no_induk' LIMIT 1");
            $namaSiswa = mysqli_fetch_assoc($qNama)['nama_siswa'];

            $temp[] = $namaSiswa . " : " . $status;
        }
    }
    $absenText = implode(", ", $temp);
}

// Cek apakah jurnal untuk id_mapel dan tanggal ini sudah ada
$cekJurnal = mysqli_query($conn, "SELECT id_materi FROM tbl_materi WHERE id_mapel='$idmapel' AND `tanggal`='$tanggal' LIMIT 1");

if(mysqli_num_rows($cekJurnal) > 0) {
    // Sudah ada, lakukan UPDATE (replace)
    $dataExisting = mysqli_fetch_assoc($cekJurnal);
    $id_materi = $dataExisting['id_materi'];
    
    // Update data jurnal
    $sql = mysqli_query($conn, "UPDATE tbl_materi SET 
        no_induk='$nip',
        nama_mapel='$namamapel',
        kelas='$kelas',
        file_materi='$cekfoto',
        materi='$materi',
        kegiatan='$kegiatan',
        absen='$absenText',
        keterangan='$keterangan'
        WHERE id_materi='$id_materi'");
} else {
    // Belum ada, lakukan INSERT
    $sql = mysqli_query($conn, "INSERT INTO tbl_materi 
        (id_mapel, no_induk, nama_mapel, `tanggal`, kelas, file_materi, materi, kegiatan, absen, keterangan) 
        VALUES 
        ('$idmapel','$nip','$namamapel','$tanggal','$kelas','$cekfoto','$materi','$kegiatan','$absenText','$keterangan')");
}


// Simpan absensi siswa (untuk semua status termasuk Hadir)
foreach ($absenData as $no_induk => $status) {
    if (!empty($status)) {
        // simpan ke tbl_absen
        $cek = mysqli_query($conn, "SELECT id FROM tbl_absen 
                                    WHERE no_induk='$no_induk' 
                                    AND tanggal='$tanggal' 
                                    AND kelas='$kelas'
                                    AND id_mapel='$idmapel'");
        if(mysqli_num_rows($cek) > 0) {
            mysqli_query($conn, "UPDATE tbl_absen 
                                 SET status='$status', no_induk_guru='$nip'
                                 WHERE no_induk='$no_induk' 
                                 AND tanggal='$tanggal' 
                                 AND kelas='$kelas'
                                 AND id_mapel='$idmapel'");
        } else {
            mysqli_query($conn, "INSERT INTO tbl_absen (tanggal,kelas,id_mapel,no_induk_guru,no_induk,status) 
                                 VALUES ('$tanggal','$kelas','$idmapel','$nip','$no_induk','$status')");
        }
    }
}

if($sql) { 
    if ($error === UPLOAD_ERR_OK && !empty($cekfoto) && is_uploaded_file($tmpName)) {
        @move_uploaded_file($tmpName, '../../file_materi/'. $cekfoto);
    }
    header("location:guru.php?sukses");    
} else { 
    header("location:guru.php?gagal");
}
?>

