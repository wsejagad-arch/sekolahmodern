<?php
/**
 * Quick Test - Cek koneksi dan data untuk cetak jurnal
 */

// Konfigurasi database langsung (bukan menggunakan koneksi.php)
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'jurnal';
$port = 3307;

echo "=== QUICK TEST CETAK JURNAL ===\n\n";

// Test koneksi
$conn = mysqli_connect($host, $user, $password, $db, $port);

if (!$conn) {
    echo "❌ KONEKSI GAGAL: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "✅ KONEKSI BERHASIL\n\n";

// Test parameter yang akan digunakan
$nip_guru = '0029'; // Salah satu NIP yang ada di database
$tgl_awal = '2025-07-01';
$tgl_akhir = '2025-09-16';

echo "Test Parameter:\n";
echo "- NIP Guru: $nip_guru\n";
echo "- Periode: $tgl_awal sampai $tgl_akhir\n\n";

// 1. Cek data guru
$guru_query = "SELECT * FROM tbl_guru WHERE no_induk = '$nip_guru'";
$guru_result = mysqli_query($conn, $guru_query);

if (mysqli_num_rows($guru_result) > 0) {
    $guru = mysqli_fetch_assoc($guru_result);
    echo "✅ GURU DITEMUKAN: {$guru['nama_guru']}\n";
} else {
    echo "❌ GURU TIDAK DITEMUKAN\n";
}

// 2. Cek jadwal mengajar
$jadwal_query = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$nip_guru'";
$jadwal_result = mysqli_query($conn, $jadwal_query);
$jadwal_count = mysqli_num_rows($jadwal_result);

echo "📅 JADWAL MENGAJAR: $jadwal_count entries\n";

if ($jadwal_count > 0) {
    echo "Sample jadwal:\n";
    $counter = 0;
    while ($jadwal = mysqli_fetch_assoc($jadwal_result)) {
        if ($counter < 3) { // Tampilkan 3 sample saja
            echo "  - {$jadwal['nama_mapel']} | {$jadwal['kelas']} | {$jadwal['hari']} | {$jadwal['jam_mulai']}-{$jadwal['jam_selesai']}\n";
            $counter++;
        }
    }
    if ($jadwal_count > 3) {
        echo "  ... dan " . ($jadwal_count - 3) . " jadwal lainnya\n";
    }
}
echo "\n";

$jurnal_query = "SELECT COUNT(*) as total FROM tbl_materi WHERE no_induk = '$nip_guru' AND tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'";
$jurnal_result = mysqli_query($conn, $jurnal_query);
$jurnal_count = mysqli_fetch_assoc($jurnal_result)['total'];

echo "📝 JURNAL TERISI: $jurnal_count entries\n\n";

// 4. Test logika generate jadwal
echo "🔄 TEST LOGIKA GENERATE JADWAL:\n";

// Reset jadwal result
mysqli_data_seek($jadwal_result, 0);
$jadwal_array = [];
while ($jadwal = mysqli_fetch_assoc($jadwal_result)) {
    $jadwal_array[] = $jadwal;
}

$data_array = [];
$current_date = strtotime($tgl_awal);
$end_date = strtotime($tgl_akhir);
$generated_count = 0;

function namaHariIndonesia($hariInggris) {
    $hariInggris = strtolower($hariInggris);
    switch ($hariInggris) {
        case 'monday': return 'Senin';
        case 'tuesday': return 'Selasa';
        case 'wednesday': return 'Rabu';
        case 'thursday': return 'Kamis';
        case 'friday': return 'Jumat';
        case 'saturday': return 'Sabtu';
        case 'sunday': return 'Minggu';
        default: return 'Tidak diketahui';
    }
}

while ($current_date <= $end_date && $generated_count < 10) { // Batasi 10 entries untuk test
    $tanggal = date('Y-m-d', $current_date);
    $nama_hari_inggris = date('l', $current_date);
    $nama_hari_indonesia = namaHariIndonesia($nama_hari_inggris);

    // Untuk setiap jadwal di hari tersebut
    foreach ($jadwal_array as $jadwal) {
        if (strtolower($jadwal['hari']) == strtolower($nama_hari_indonesia)) {
            $generated_count++;
            $data_array[] = [
                'tanggal' => $tanggal,
                'nama_hari' => $nama_hari_indonesia,
                'nama_mapel' => $jadwal['nama_mapel'],
                'kelas' => $jadwal['kelas'],
                'jam_mulai' => $jadwal['jam_mulai'],
                'jam_selesai' => $jadwal['jam_selesai'],
                'status' => 'Generated'
            ];
        }
    }

    $current_date = strtotime('+1 day', $current_date);
}

echo "Generated entries: $generated_count\n";
if ($generated_count > 0) {
    echo "Sample generated data:\n";
    for ($i = 0; $i < min(3, count($data_array)); $i++) {
        $data = $data_array[$i];
        echo "  - {$data['tanggal']} ({$data['nama_hari']}): {$data['nama_mapel']} {$data['kelas']} {$data['jam_mulai']}-{$data['jam_selesai']}\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TEST SELESAI\n";
echo "Jika semua ✅ maka sistem cetak jurnal siap digunakan!\n";
echo str_repeat("=", 50) . "\n";

mysqli_close($conn);
?>