<?php
include "koneksi.php";

if (isset($_POST['import'])) {
    $filename = $_FILES['file']['tmp_name'];
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

    if ($_FILES['file']['size'] > 0) {
        $data_siswa = [];

        if (strtolower($ext) == 'xlsx') {
            require_once "SimpleXLSX.php";
            if ( $xlsx = Shuchkin\SimpleXLSX::parse($filename) ) {
                $rows = $xlsx->rows();
                $isHeader = true;
                foreach ($rows as $r) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    if (!empty($r[0]) && !empty($r[1])) {
                        $data_siswa[] = [
                            'nis' => $r[0],
                            'nama' => $r[1],
                            'kelas' => isset($r[2]) ? $r[2] : ''
                        ];
                    }
                }
            } else {
                echo "<script>alert('Gagal membaca file Excel: ".Shuchkin\SimpleXLSX::parseError()."'); window.location='home.php?page=data-siswa';</script>";
                exit;
            }
        } else {
            $file = fopen($filename, "r");
            $row = 0;
            // Deteksi separator
            $line = fgets($file);
            $separator = (strpos($line, ';') !== false) ? ';' : ',';
            rewind($file);

            while (($data = fgetcsv($file, 1000, $separator)) !== FALSE) {
                $row++;
                if ($row == 1) continue; // skip header
                
                if (!empty($data[0]) && !empty($data[1])) {
                    $data_siswa[] = [
                        'nis' => $data[0],
                        'nama' => $data[1],
                        'kelas' => isset($data[2]) ? $data[2] : ''
                    ];
                }
            }
            fclose($file);
        }

        $success_count = 0;
        foreach ($data_siswa as $s) {
            $no_induk   = mysqli_real_escape_string($conn, $s['nis']);
            $nama_siswa = mysqli_real_escape_string($conn, $s['nama']);
            $kelas      = mysqli_real_escape_string($conn, $s['kelas']);
            $status     = 'Aktif';

            $sql = "INSERT INTO tbl_siswa (no_induk, nama_siswa, kelas, status)
                    VALUES ('$no_induk', '$nama_siswa', '$kelas', '$status')
                    ON DUPLICATE KEY UPDATE 
                        nama_siswa=VALUES(nama_siswa),
                        kelas=VALUES(kelas),
                        status=VALUES(status)";
            mysqli_query($conn, $sql);

            $sqlUser = "INSERT INTO tbl_pengguna (no_induk, password, hak_akses)
                        VALUES ('$no_induk', MD5('$no_induk'), 3)
                        ON DUPLICATE KEY UPDATE hak_akses=VALUES(hak_akses)";
            @mysqli_query($conn, $sqlUser);
            
            $success_count++;
        }

        echo "<script>alert('Import $success_count data berhasil!'); window.location='home.php?page=data-siswa';</script>";
    } else {
        echo "<script>alert('File kosong!'); window.location='home.php?page=data-siswa';</script>";
    }
}
?>

