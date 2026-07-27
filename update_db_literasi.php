<?php
require 'koneksi.php';

echo "<h2>Update Database Literasi (Fitur AKM)</h2>";

function column_exists($conn, $table, $column) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return (mysqli_num_rows($res) > 0);
}

// 1. Add opsi_e
if (!column_exists($conn, 'tbl_literasi_soal', 'opsi_e')) {
    if (mysqli_query($conn, "ALTER TABLE `tbl_literasi_soal` ADD `opsi_e` VARCHAR(255) NULL AFTER `opsi_d`")) {
        echo "<p>Berhasil menambahkan kolom <b>opsi_e</b>.</p>";
    } else {
        echo "<p>Gagal menambahkan kolom opsi_e: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Kolom <b>opsi_e</b> sudah ada.</p>";
}

// 2. Add tipe_soal
if (!column_exists($conn, 'tbl_literasi_soal', 'tipe_soal')) {
    if (mysqli_query($conn, "ALTER TABLE `tbl_literasi_soal` ADD `tipe_soal` ENUM('pg','pg_majemuk','menjodohkan','benar_salah') NOT NULL DEFAULT 'pg' AFTER `pertanyaan`")) {
        echo "<p>Berhasil menambahkan kolom <b>tipe_soal</b>.</p>";
    } else {
        echo "<p>Gagal menambahkan kolom tipe_soal: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Kolom <b>tipe_soal</b> sudah ada.</p>";
}

// 3. Add data_soal
if (!column_exists($conn, 'tbl_literasi_soal', 'data_soal')) {
    if (mysqli_query($conn, "ALTER TABLE `tbl_literasi_soal` ADD `data_soal` TEXT NULL AFTER `jawaban_benar`")) {
        echo "<p>Berhasil menambahkan kolom <b>data_soal</b>.</p>";
    } else {
        echo "<p>Gagal menambahkan kolom data_soal: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Kolom <b>data_soal</b> sudah ada.</p>";
}

// 4. Modify jawaban_benar to be VARCHAR(255) instead of CHAR(1)
if (mysqli_query($conn, "ALTER TABLE `tbl_literasi_soal` MODIFY `jawaban_benar` VARCHAR(255) NULL")) {
    echo "<p>Berhasil mengubah struktur <b>jawaban_benar</b> menjadi VARCHAR(255).</p>";
} else {
    echo "<p>Gagal mengubah struktur jawaban_benar: " . mysqli_error($conn) . "</p>";
}

echo "<h3>Selesai. Anda sudah bisa menggunakan fitur baru.</h3>";
?>
