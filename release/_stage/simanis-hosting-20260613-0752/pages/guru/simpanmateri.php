<?php
// Access control is now handled in home.php

include "../../koneksi.php";
include "../../functions.php";

$tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
$nip        = mysqli_real_escape_string($conn, $_POST['nip']);
$idmapel    = mysqli_real_escape_string($conn, $_POST['idmapel']);
$namamapel  = mysqli_real_escape_string($conn, $_POST['namamapel']);
$kelas      = mysqli_real_escape_string($conn, $_POST['kelas']);
$materi     = mysqli_real_escape_string($conn, $_POST['materi']);
$kegiatan   = mysqli_real_escape_string($conn, $_POST['kegiatan']);
$keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

$absenData  = isset($_POST['absen']) ? $_POST['absen'] : []; // ✅ array per siswa

// File upload opsional
$namafile   = isset($_FILES['file']['name']) ? $_FILES['file']['name'] : '';
$ukuranFile = isset($_FILES['file']['size']) ? $_FILES['file']['size'] : 0;
$error      = isset($_FILES['file']['error']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
$tmpName    = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
$cekfoto    = NULL; // Default NULL for no file
if ($error === UPLOAD_ERR_OK && !empty($namafile) && is_uploaded_file($tmpName)) {
    $cekfoto = cek_foto($namafile);
}

// Debug: check connection
if (!$conn) {
    header("location: guru.php?error=connection");
    exit;
}

// 1️⃣ simpan ke tbl_materi (pakai implode biar bisa ditampilkan ringkas)
$absenText = "";
if (!empty($absenData)) {
    $temp = [];
    foreach($absenData as $no_induk => $status) {
        // Hanya tampilkan siswa yang tidak hadir dalam ringkasan jurnal
        if ($status !== 'Hadir') {
            // ambil nama siswa
            $no_induk_escaped = mysqli_real_escape_string($conn, $no_induk);
            $qNama = mysqli_query($conn, "SELECT nama_siswa FROM tbl_siswa WHERE no_induk='$no_induk_escaped' LIMIT 1");
            if (!$qNama) {
                header("location: guru.php?error=query_nama");
                exit;
            }
            $row = mysqli_fetch_assoc($qNama);
            if (!$row) {
                header("location: guru.php?error=no_nama");
                exit;
            }
            $namaSiswa = $row['nama_siswa'];

            $temp[] = $namaSiswa . " : " . $status;
        }
    }
    $absenText = implode(", ", $temp);
}
$absenText = mysqli_real_escape_string($conn, $absenText);

// Cek apakah jurnal untuk id_mapel dan tanggal ini sudah ada
$cekJurnal = mysqli_query($conn, "SELECT id_materi FROM tbl_materi WHERE id_mapel='$idmapel' AND `tanggal`='$tanggal' LIMIT 1");

if (!$cekJurnal) {
    header("location: guru.php?error=query_jurnal");
    exit;
}

if(mysqli_num_rows($cekJurnal) > 0) {
    // Sudah ada, lakukan UPDATE (replace)
    $dataExisting = mysqli_fetch_assoc($cekJurnal);
    $id_materi = $dataExisting['id_materi'];
    
    // Update data jurnal
    $sql = mysqli_query($conn, "UPDATE tbl_materi SET 
        no_induk='$nip',
        nama_mapel='$namamapel',
        kelas='$kelas',
        file_materi=" . ($cekfoto ? "'$cekfoto'" : "NULL") . ",
        materi='$materi',
        kegiatan='$kegiatan',
        absen='$absenText',
        keterangan='$keterangan'
        WHERE id_materi='$id_materi'");
    $isUpdate = true;
} else {
    // Belum ada, lakukan INSERT
    $sql = mysqli_query($conn, "INSERT INTO tbl_materi 
        (id_mapel, no_induk, nama_mapel, `tanggal`, kelas, file_materi, materi, kegiatan, absen, keterangan) 
        VALUES 
        ('$idmapel','$nip','$namamapel','$tanggal','$kelas'," . ($cekfoto ? "'$cekfoto'" : "NULL") . ",'$materi','$kegiatan','$absenText','$keterangan')");
    $isUpdate = false;
}

if (!$sql) {
    $error_msg = mysqli_error($conn);
    error_log("SQL Error in simpanmateri.php: " . $error_msg . " | Query: " . ($isUpdate ? "UPDATE" : "INSERT"));
    header("location: guru.php?error=sql&msg=" . urlencode($error_msg));
    exit;
}


// Simpan absensi siswa (untuk semua status termasuk Hadir)
foreach ($absenData as $no_induk => $status) {
    $status = mysqli_real_escape_string($conn, $status);
    $no_induk_escaped = mysqli_real_escape_string($conn, $no_induk);
    if (!empty($status)) {
        // simpan ke tbl_absen
        $cek = mysqli_query($conn, "SELECT id FROM tbl_absen 
                                    WHERE no_induk='$no_induk_escaped' 
                                    AND tanggal='$tanggal' 
                                    AND kelas='$kelas'
                                    AND id_mapel='$idmapel'");
        if (!$cek) {
            error_log("Error checking attendance for student $no_induk: " . mysqli_error($conn));
            continue; // Skip this student
        }
        if(mysqli_num_rows($cek) > 0) {
            $update_result = mysqli_query($conn, "UPDATE tbl_absen 
                                 SET status='$status', no_induk_guru='$nip'
                                 WHERE no_induk='$no_induk_escaped' 
                                 AND tanggal='$tanggal' 
                                 AND kelas='$kelas'
                                 AND id_mapel='$idmapel'");
            if (!$update_result) {
                error_log("Error updating attendance for student $no_induk: " . mysqli_error($conn));
            }
        } else {
            $insert_result = mysqli_query($conn, "INSERT INTO tbl_absen (tanggal,kelas,id_mapel,no_induk_guru,no_induk,status) 
                                 VALUES ('$tanggal','$kelas','$idmapel','$nip','$no_induk_escaped','$status')");
            if (!$insert_result) {
                error_log("Error inserting attendance for student $no_induk: " . mysqli_error($conn));
            }
        }
    }
}

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if($sql) { 
    if ($error === UPLOAD_ERR_OK && !empty($cekfoto) && is_uploaded_file($tmpName)) {
        @move_uploaded_file($tmpName, '../../file_materi/'. $cekfoto);
    }
    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Jurnal berhasil disimpan']);
    } else {
        header("location: guru.php?sukses");
    }
} else { 
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan jurnal']);
    } else {
        header("location: guru.php?gagal");
    }
}
exit;
?>

