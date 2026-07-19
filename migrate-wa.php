<?php
include "koneksi.php";
echo "<h1>Migrasi Database Manual</h1>";

// 1. Tambah no_wa
$q1 = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
if (mysqli_num_rows($q1) == 0) {
    if (mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru")) {
        echo "<p style='color:green'>Berhasil membuat kolom <b>no_wa</b>.</p>";
    } else {
        echo "<p style='color:red'>Gagal membuat kolom no_wa: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:blue'>Kolom <b>no_wa</b> sudah ada.</p>";
}

// 2. Tambah jabatan
$q2 = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'jabatan'");
if (mysqli_num_rows($q2) == 0) {
    if (mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian")) {
        echo "<p style='color:green'>Berhasil membuat kolom <b>jabatan</b>.</p>";
    } else {
        echo "<p style='color:red'>Gagal membuat kolom jabatan: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:blue'>Kolom <b>jabatan</b> sudah ada.</p>";
}

// 3. Tambah alamat
$q3 = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'alamat'");
if (mysqli_num_rows($q3) == 0) {
    if (mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN alamat TEXT DEFAULT NULL AFTER no_wa")) {
        echo "<p style='color:green'>Berhasil membuat kolom <b>alamat</b>.</p>";
    } else {
        echo "<p style='color:red'>Gagal membuat kolom alamat: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:blue'>Kolom <b>alamat</b> sudah ada.</p>";
}

// 4. Tambah is_guru_bk dkk
$cols = [
    'is_guru_bk' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
    'is_pendamping_literasi' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_guru_bk',
    'is_tim_aduan' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pendamping_literasi'
];

foreach ($cols as $col => $def) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE '$col'");
    if (mysqli_num_rows($q) == 0) {
        if (mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN $col $def")) {
            echo "<p style='color:green'>Berhasil membuat kolom <b>$col</b>.</p>";
        } else {
            echo "<p style='color:red'>Gagal membuat kolom $col: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:blue'>Kolom <b>$col</b> sudah ada.</p>";
    }
}

echo "<h3>Migrasi selesai. Silakan cek kembali fitur Edit Guru.</h3>";
?>
