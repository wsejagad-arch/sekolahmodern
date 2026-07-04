<?php
include '../../koneksi.php';

// Sample data untuk testing
$sampleTasks = [
    [
        'tanggal' => '2024-01-20',
        'id_mapel' => 1,
        'kelas' => 'X IPA 1',
        'mapel' => 'Matematika',
        'no_induk_guru' => '199001012020121001', // Ganti dengan NIP guru yang ada
        'judul_tugas' => 'Latihan Soal Persamaan Linear',
        'deskripsi' => 'Kerjakan soal-soal persamaan linear pada buku paket halaman 45-50. Kerjakan dengan rapi dan teliti.',
        'link_tugas' => 'https://classroom.google.com/u/0/c/abc123',
        'tanggal_pengumpulan' => '2024-01-25',
        'status' => 'aktif'
    ],
    [
        'tanggal' => '2024-01-19',
        'id_mapel' => 2,
        'kelas' => 'X IPA 2',
        'mapel' => 'Fisika',
        'no_induk_guru' => '199001012020121001',
        'judul_tugas' => 'Praktikum Gerak Lurus',
        'deskripsi' => 'Buat laporan praktikum gerak lurus beraturan yang telah dilakukan kemarin. Format laporan sesuai dengan template yang telah diberikan.',
        'link_tugas' => null,
        'file_tugas' => null,
        'tanggal_pengumpulan' => '2024-01-22',
        'status' => 'aktif'
    ],
    [
        'tanggal' => '2024-01-18',
        'id_mapel' => 3,
        'kelas' => 'X IPS 1',
        'mapel' => 'Sejarah',
        'no_induk_guru' => '199001012020121001',
        'judul_tugas' => 'Essay Sejarah Kemerdekaan',
        'deskripsi' => 'Tulis essay tentang perjuangan kemerdekaan Indonesia. Minimal 500 kata, maksimal 1000 kata. Gunakan sumber yang credible.',
        'link_tugas' => 'https://drive.google.com/file/d/1abc2def3ghi/view',
        'tanggal_pengumpulan' => '2024-01-20',
        'status' => 'selesai'
    ]
];

// Get actual guru NIP from database untuk testing
$guruQuery = "SELECT no_induk FROM tbl_guru LIMIT 1";
$guruResult = mysqli_query($conn, $guruQuery);
if ($guruResult && mysqli_num_rows($guruResult) > 0) {
    $guru = mysqli_fetch_assoc($guruResult);
    $actualNipGuru = $guru['no_induk'];
} else {
    echo "Tidak ada data guru di database. Buat data guru terlebih dahulu.\n";
    exit;
}

// Insert sample tasks
foreach ($sampleTasks as $task) {
    // Update NIP guru dengan yang aktual
    $task['no_induk_guru'] = $actualNipGuru;
    
    $tanggal = mysqli_real_escape_string($conn, $task['tanggal']);
    $id_mapel = (int)$task['id_mapel'];
    $kelas = mysqli_real_escape_string($conn, $task['kelas']);
    $mapel = mysqli_real_escape_string($conn, $task['mapel']);
    $no_induk_guru = mysqli_real_escape_string($conn, $task['no_induk_guru']);
    $judul_tugas = mysqli_real_escape_string($conn, $task['judul_tugas']);
    $deskripsi = mysqli_real_escape_string($conn, $task['deskripsi']);
    $link_tugas = $task['link_tugas'] ? "'" . mysqli_real_escape_string($conn, $task['link_tugas']) . "'" : 'NULL';
    $file_tugas = $task['file_tugas'] ? "'" . mysqli_real_escape_string($conn, $task['file_tugas']) . "'" : 'NULL';
    $tanggal_pengumpulan = $task['tanggal_pengumpulan'] ? "'" . mysqli_real_escape_string($conn, $task['tanggal_pengumpulan']) . "'" : 'NULL';
    $status = mysqli_real_escape_string($conn, $task['status']);
    
    $insertQuery = "INSERT INTO tbl_tugas (tanggal, id_mapel, kelas, mapel, no_induk_guru, judul_tugas, deskripsi, link_tugas, file_tugas, tanggal_pengumpulan, status, created_at) 
                   VALUES ('$tanggal', $id_mapel, '$kelas', '$mapel', '$no_induk_guru', '$judul_tugas', '$deskripsi', $link_tugas, $file_tugas, $tanggal_pengumpulan, '$status', NOW())";
    
    if (mysqli_query($conn, $insertQuery)) {
        echo "✓ Sample tugas '{$task['judul_tugas']}' berhasil ditambahkan\n";
    } else {
        echo "✗ Gagal menambahkan tugas '{$task['judul_tugas']}': " . mysqli_error($conn) . "\n";
    }
}

echo "\nSample data tugas telah ditambahkan ke database.\n";
echo "NIP Guru yang digunakan: $actualNipGuru\n";
echo "Login dengan akun guru tersebut untuk melihat history tugas.\n";

mysqli_close($conn);
?>