<?php
include "koneksi.php";

echo "=== TEST DATA ABSEN UNTUK SCROLL FEATURE ===" . PHP_EOL;

// Cek data absen yang ada
$result = mysqli_query($conn, "SELECT tanggal, kelas, nama_mapel, absen FROM tbl_kehadiran WHERE absen IS NOT NULL AND absen != '' ORDER BY tanggal DESC LIMIT 5");

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Tanggal: " . $row['tanggal'] . " | Kelas: " . $row['kelas'] . " | Mapel: " . $row['nama_mapel'] . PHP_EOL;
        echo "Absen: " . $row['absen'] . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} else {
    echo "Tidak ada data absen, mari buat sample data dengan banyak siswa..." . PHP_EOL;
    
    // Buat data sample dengan banyak siswa absen untuk test scroll
    $longAbsenData = "Ahmad Azis Savudin : Sakit, ANANDA FIRDA NUR AISYAH : Sakit, FEBRIAN : Ijin, MUHAMMAD AGUS WICAKSONO : Sakit, SITI RAHMA WATI : Ijin, BAYU SETIAWAN : Alpha, RINA SARI DEWI : Sakit, ANDI PRASETYO : Ijin, LINA MARLINA : Sakit, DEDI KURNIAWAN : Alpha, MAYA SARI PUTRI : Ijin, AGUS SANTOSO : Sakit";
    
    // Update salah satu record yang ada
    $updateQuery = "UPDATE tbl_kehadiran SET absen = '$longAbsenData' WHERE tanggal = '2024-09-15' LIMIT 1";
    
    if (mysqli_query($conn, $updateQuery)) {
        echo "✅ Sample data absen dengan banyak siswa telah ditambahkan!" . PHP_EOL;
        echo "Data: " . $longAbsenData . PHP_EOL;
    } else {
        echo "❌ Error: " . mysqli_error($conn) . PHP_EOL;
    }
}

mysqli_close($conn);
?>