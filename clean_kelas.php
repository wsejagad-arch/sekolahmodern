<?php
// Script Pembersih Kelas Ganda dan Alumni
require_once 'koneksi.php';

$output = "Memulai proses pembersihan...<br>";

// 1. Set kelas = NULL untuk siswa yang berstatus Lulus atau Alumni
$q1 = mysqli_query($conn, "UPDATE tbl_siswa SET kelas = NULL WHERE status IN ('Lulus', 'Alumni') AND kelas IS NOT NULL");
if ($q1) {
    $affected = mysqli_affected_rows($conn);
    $output .= "[OK] Berhasil mengosongkan kelas untuk $affected siswa berstatus Lulus/Alumni.<br>";
} else {
    $output .= "[FAIL] Gagal mengupdate siswa alumni: " . mysqli_error($conn) . "<br>";
}

// 2. Hapus duplikat di tbl_kelas
// Kita cari kelas yang memiliki jumlah > 1 untuk id_sekolah yang sama, pertahankan yang id paling kecil
$q2 = mysqli_query($conn, "
    SELECT kelas, id_sekolah, COUNT(*) as c, MIN(id) as min_id 
    FROM tbl_kelas 
    GROUP BY kelas, id_sekolah 
    HAVING c > 1
");

$deleted_count = 0;
if ($q2) {
    while($r = mysqli_fetch_assoc($q2)) {
        $kelas = mysqli_real_escape_string($conn, $r['kelas']);
        $id_sekolah = (int) $r['id_sekolah'];
        $min_id = (int) $r['min_id'];
        
        $q_del = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE kelas='$kelas' AND id_sekolah=$id_sekolah AND id != $min_id");
        if ($q_del) {
            $deleted_count += mysqli_affected_rows($conn);
        }
    }
    $output .= "[OK] Berhasil menghapus $deleted_count entri kelas ganda di tbl_kelas.<br>";
} else {
    $output .= "[FAIL] Gagal mengecek kelas ganda: " . mysqli_error($conn) . "<br>";
}

$output .= "Pembersihan selesai.";
echo $output;
?>
