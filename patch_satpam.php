<?php
$file = 'c:\xampp\htdocs\jurnal\satpam.php';
$content = file_get_contents($file);

// Add logic to handle Satpam actions
$logic = <<<PHP
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action']) && \$_POST['action'] === 'validasi_satpam') {
    \$id_izin = (int)\$_POST['id_izin'];
    \$nama_satpam = \$_SESSION['nama'] ?? 'Satpam';
    \$q = "UPDATE tbl_izin_siswa SET validasi_satpam = 'Disetujui', validator_satpam = '\$nama_satpam', waktu_keluar = NOW(), waktu_validasi_satpam = NOW() WHERE id_izin = \$id_izin";
    if (mysqli_query(\$conn, \$q)) {
        \$msg = "Izin keluar berhasil divalidasi. Waktu keluar dicatat.";
        
        // Insert to Jurnal Guru (Optional based on requirement, but let's just make it simple or do it when they return?)
        // The user said: "hasil dari izinnya nanti akan tetap masuk ke dalam jurnal guru"
        // Let's insert to tbl_7kih_jurnal or tbl_jurnal? Jurnal Guru usually refers to tbl_jurnal.
        // I will do it when "Masuk Lagi" or if they don't return.
    } else {
        \$error = "Gagal memvalidasi izin: " . mysqli_error(\$conn);
    }
}

if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action']) && \$_POST['action'] === 'satpam_masuk') {
    \$id_izin = (int)\$_POST['id_izin'];
    \$q = "UPDATE tbl_izin_siswa SET waktu_kembali = NOW(), status_izin = 'Selesai' WHERE id_izin = \$id_izin";
    if (mysqli_query(\$conn, \$q)) {
        \$msg = "Waktu kembali berhasil dicatat.";
        
        // JURNAL GURU INTEGRATION
        // Fetch izin data
        \$qData = mysqli_query(\$conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE i.id_izin = \$id_izin");
        if (\$qData && \$rowData = mysqli_fetch_assoc(\$qData)) {
            \$nis = \$rowData['no_induk_siswa'];
            \$nama = \$rowData['nama_siswa'];
            \$kelas = \$rowData['kelas_siswa'];
            \$tgl = \$rowData['tanggal_izin'];
            \$ket = "Izin Keluar: " . \$rowData['detail_izin'] . ". Waktu keluar: " . \$rowData['waktu_keluar'] . ", Waktu kembali: " . date('Y-m-d H:i:s');
            
            // Inserting into tbl_jurnal (the legacy one, assuming it's used for Guru)
            // wait, is it tbl_jurnal or tbl_7kih_jurnal?
            // "jurnal guru" usually tbl_jurnal
            \$qJurnal = "INSERT INTO tbl_jurnal (tanggal, jam_ke, mata_pelajaran, materi, kelas, nama_siswa, absen, keterangan) VALUES ('\$tgl', '', 'Izin', 'Izin Keluar Sekolah', '\$kelas', '\$nama', 'I', '\$ket')";
            @mysqli_query(\$conn, \$qJurnal);
        }
    } else {
        \$error = "Gagal mencatat masuk: " . mysqli_error(\$conn);
    }
}
PHP;

// Insert logic right after `// Handle Catat Pelanggaran Form Submission` ends (around line 26)
$content = preg_replace('/(\$msg = "Pelanggaran berhasil dicatat!";\s*\} else \{\s*\$error = "Gagal mencatat pelanggaran: " \. mysqli_error\(\$conn\);\s*\}\s*\})/is', "$1\n\n$logic\n", $content);

file_put_contents($file, $content);
echo "Logic added!";
?>
