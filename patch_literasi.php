<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\literasi.php';
$content = file_get_contents($file);

// Add to PHP logic
$old_php = '$durasi = (int)$_POST[\'durasi_minimal\'];';
$new_php = '$durasi = (int)$_POST[\'durasi_minimal\'];
    $batas_waktu = mysqli_real_escape_string($conn, $_POST[\'batas_waktu\'] ?? \'\');
    $batas_waktu_sql = !empty($batas_waktu) ? "\'$batas_waktu\'" : "NULL";';
$content = str_replace($old_php, $new_php, $content);

$old_query = "INSERT INTO tbl_literasi_tugas (id_sekolah, no_induk_guru, kelas, judul, deskripsi, tipe_media, file_media, durasi_minimal) VALUES (\$idSekolah, '\$nipEsc', '\$kelas', '\$judul', '\$deskripsi', '\$tipe', '\$file_media', \$durasi)";
$new_query = "INSERT INTO tbl_literasi_tugas (id_sekolah, no_induk_guru, kelas, judul, deskripsi, tipe_media, file_media, durasi_minimal, batas_waktu) VALUES (\$idSekolah, '\$nipEsc', '\$kelas', '\$judul', '\$deskripsi', '\$tipe', '\$file_media', \$durasi, \$batas_waktu_sql)";
$content = str_replace($old_query, $new_query, $content);

// Add to HTML form
$old_html = '<div class="form-group" id="containerDurasi">
                                        <label>Minimal Waktu Baca (Detik)</label>
                                        <input type="number" class="form-control" name="durasi_minimal" value="180">
                                        <small class="text-danger">Siswa tidak bisa mengerjakan evaluasi sebelum waktu ini habis.</small>
                                    </div>';
$new_html = '<div class="form-group" id="containerDurasi">
                                        <label>Minimal Waktu Baca (Detik)</label>
                                        <input type="number" class="form-control" name="durasi_minimal" value="180">
                                        <small class="text-danger">Siswa tidak bisa mengerjakan evaluasi sebelum waktu ini habis.</small>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label>Batas Waktu Literasi (Opsional)</label>
                                        <input type="datetime-local" class="form-control" name="batas_waktu">
                                        <small class="text-muted">Tenggat pengerjaan literasi bagi siswa.</small>
                                    </div>';
$content = str_replace($old_html, $new_html, $content);

file_put_contents($file, $content);
echo "Updated literasi.php";
?>
