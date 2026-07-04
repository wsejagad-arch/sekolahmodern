<?php
include "koneksi.php";

if (isset($_POST['import'])) {
    $filename = $_FILES['file']['tmp_name'];

    if ($_FILES['file']['size'] > 0) {
        $file = fopen($filename, "r");
        $row = 0;
        while (($data = fgetcsv($file, 1000, ";")) !== FALSE) {
            $row++;
            if ($row == 1) continue; // skip header baris pertama

            $no_induk   = mysqli_real_escape_string($conn, $data[0]);
            $nama_siswa = mysqli_real_escape_string($conn, $data[1]);
            $kelas      = mysqli_real_escape_string($conn, $data[2]);
            $status     = mysqli_real_escape_string($conn, $data[3]);

            $sql = "INSERT INTO tbl_siswa (no_induk, nama_siswa, kelas, status)
                    VALUES ('$no_induk', '$nama_siswa', '$kelas', '$status')
                    ON DUPLICATE KEY UPDATE 
                        nama_siswa=VALUES(nama_siswa),
                        kelas=VALUES(kelas),
                        status=VALUES(status)";
            mysqli_query($conn, $sql);

            // Pastikan akun login siswa tersedia di tbl_pengguna.
            // Gunakan default password = md5(NIS). Jangan timpa password jika sudah ada.
            $sqlUser = "INSERT INTO tbl_pengguna (no_induk, password, hak_akses)
                        VALUES ('$no_induk', MD5('$no_induk'), 3)
                        ON DUPLICATE KEY UPDATE hak_akses=VALUES(hak_akses)";
            @mysqli_query($conn, $sqlUser);
        }
        fclose($file);
        echo "<script>alert('Import berhasil!'); window.location='home.php?page=data-siswa';</script>";
    } else {
        echo "<script>alert('File kosong!'); window.location='?page=data-siswa';</script>";
    }
}
?>

