<?php
include "koneksi.php";
include "SimpleXLSX.php";

$filename = "jadwal_extracted.xlsx";

$xlsx = SimpleXLSX::parse($filename);
if ($xlsx) {
    $rows = $xlsx->rows();
    $count = 0;
    $skipped = 0;
    $missing_no_induk = [];
    
    foreach ($rows as $index => $row) {
        if ($index == 0) continue;
        
        $no_induk   = mysqli_real_escape_string($conn, $row[0] ?? '');
        $nama_guru  = mysqli_real_escape_string($conn, $row[1] ?? '');
        $nama_mapel = mysqli_real_escape_string($conn, $row[2] ?? '');
        $kelas      = mysqli_real_escape_string($conn, $row[3] ?? '');
        $hari       = mysqli_real_escape_string($conn, $row[4] ?? '');
        $jam_mulai  = mysqli_real_escape_string($conn, $row[5] ?? '');
        $jam_selesai= mysqli_real_escape_string($conn, $row[6] ?? '');
        $ruang      = mysqli_real_escape_string($conn, $row[7] ?? '');

        if (empty($no_induk) && !empty($nama_guru)) {
            // Trim space or titles?
            $qGuru = @mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE nama_guru LIKE '%$nama_guru%' LIMIT 1");
            if ($qGuru && mysqli_num_rows($qGuru) > 0) {
                $rowGuru = mysqli_fetch_assoc($qGuru);
                $no_induk = $rowGuru['no_induk'];
            } else {
                // Let's try splitting by comma (remove degree)
                $name_parts = explode(',', $nama_guru);
                $name_no_degree = trim($name_parts[0]);
                $qGuru2 = @mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE nama_guru LIKE '%$name_no_degree%' LIMIT 1");
                if ($qGuru2 && mysqli_num_rows($qGuru2) > 0) {
                    $rowGuru2 = mysqli_fetch_assoc($qGuru2);
                    $no_induk = $rowGuru2['no_induk'];
                }
            }
        }

        if (empty($no_induk)) {
            $missing_no_induk[$nama_guru] = true;
            $skipped++;
        } else if (empty($nama_mapel) || empty($kelas) || empty($hari)) {
            $skipped++;
        } else {
            $count++;
        }
    }
    
    echo "Success: $count\n";
    echo "Skipped: $skipped\n";
    if (count($missing_no_induk) > 0) {
        echo "Missing NIP for these teachers:\n";
        foreach ($missing_no_induk as $name => $val) {
            echo "- $name\n";
        }
    }
} else {
    echo "Failed to parse XLSX: " . SimpleXLSX::parseError();
}
?>
