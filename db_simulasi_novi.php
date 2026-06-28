<?php
require 'koneksi.php';

$nip = '197811182007012007';
$kelas = 'XII F 5';
$idSekolah = 1; // Assuming default 1

// 1. Assign to class
$q_cek = mysqli_query($conn, "SELECT id FROM tbl_literasi_ampuh WHERE no_induk_guru='$nip' AND kelas='$kelas'");
if (mysqli_num_rows($q_cek) == 0) {
    mysqli_query($conn, "INSERT INTO tbl_literasi_ampuh (no_induk_guru, kelas, id_sekolah) VALUES ('$nip', '$kelas', $idSekolah)");
    echo "Assigned Noviyanti to XII F 5.\n";
}

// 2. Create Task
$judul = "Sejarah Kemerdekaan Indonesia";
$deskripsi = "Tontonlah dokumenter sejarah kemerdekaan Indonesia berikut ini hingga selesai.";
$tipe = "video";
$file_media = "https://www.youtube.com/watch?v=MhQ5678n_8w"; // random dummy youtube link
$durasi = 0;

mysqli_query($conn, "INSERT INTO tbl_literasi_tugas (id_sekolah, no_induk_guru, kelas, judul, deskripsi, tipe_media, file_media, durasi_minimal) VALUES ($idSekolah, '$nip', '$kelas', '$judul', '$deskripsi', '$tipe', '$file_media', $durasi)");
$tugas_id = mysqli_insert_id($conn);
echo "Created Literacy Task (ID: $tugas_id)\n";

// 3. Create Question
$pertanyaan = "Kapan proklamasi kemerdekaan Indonesia dibacakan?";
$a = "17 Agustus 1945";
$b = "18 Agustus 1945";
$c = "1 Juni 1945";
$d = "10 November 1945";
$ans = "A";

mysqli_query($conn, "INSERT INTO tbl_literasi_soal (id_tugas, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar) VALUES ($tugas_id, '$pertanyaan', '$a', '$b', '$c', '$d', '$ans')");
echo "Added multiple-choice question.\n";
?>
