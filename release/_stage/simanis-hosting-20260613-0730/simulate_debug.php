<?php
// Simulasi data untuk testing cetak jurnal
echo "=== SIMULASI CETAK JURNAL DEBUG ===\n\n";

// Simulasi session guru
$_SESSION['no_induk'] = '123456789';
$_SESSION['nama_guru'] = 'Guru Test';

// Simulasi parameter GET
$_GET['tgl1'] = '';
$_GET['tgl2'] = '';
$_GET['kelas'] = '';

// Simulasi tanggal sekarang
$currentYear = date('Y');
$currentMonth = date('n');

// Hitung periode seperti di cetak_jurnal.php
if ($currentMonth >= 7) {
    $startDate = $currentYear . '-07-01';
} else {
    $startDate = ($currentYear - 1) . '-07-01';
}
$endDate = date('Y-m-d');

echo "Current Date: " . date('Y-m-d') . "\n";
echo "Current Month: $currentMonth\n";
echo "Calculated Start Date: $startDate\n";
echo "Calculated End Date: $endDate\n\n";

// Simulasi query jadwal (karena tidak bisa akses DB)
$scheduleCount = 0; // Simulasi tidak ada jadwal

echo "Simulated Schedule Count: $scheduleCount\n";

if ($scheduleCount == 0) {
    echo "\n=== PESAN ERROR YANG AKAN DITAMPILKAN ===\n";
    echo "TIDAK ADA JADWAL MENGAJAR\n";
    echo "Guru dengan NIP: 123456789 belum memiliki jadwal mengajar\n";
    echo "Silakan hubungi admin untuk menambahkan jadwal mengajar\n";
    echo "\n=== ANALISIS MASALAH ===\n";
    echo "1. Database connection: FAILED (Access denied)\n";
    echo "2. Schedule data: NOT FOUND (0 records)\n";
    echo "3. Solution: Import database or create sample data\n";
} else {
    echo "Schedule data found - would proceed with journal generation\n";
}

echo "\n=== REKOMENDASI SOLUSI ===\n";
echo "1. Import database dari file include/db_appsiswa.sql\n";
echo "2. Atau buat data sample untuk testing\n";
echo "3. Pastikan MySQL service running dan user root memiliki akses\n";
?>