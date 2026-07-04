<?php
// Test hapus kelas sederhana untuk debug
include "koneksi.php";

echo "<h2>Test Hapus Kelas - Debug Simple</h2>";

// Test koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
echo "✅ Koneksi database berhasil<br>";

// Tampilkan semua kelas
echo "<h3>Daftar Kelas:</h3>";
$result = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY kelas");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nama Kelas</th><th>Jumlah Murid</th><th>Aksi</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    $id_kelas = $row['id_kelas'];
    $nama_kelas = $row['kelas'];
    
    // Cek jumlah murid
    $nama_kelas_escaped = mysqli_real_escape_string($conn, $nama_kelas);
    $cek_murid = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas_escaped'");
    $row_murid = mysqli_fetch_assoc($cek_murid);
    $jumlah_murid = $row_murid['total'];
    
    echo "<tr>";
    echo "<td>$id_kelas</td>";
    echo "<td>$nama_kelas</td>";
    echo "<td>$jumlah_murid murid</td>";
    echo "<td>";
    if ($jumlah_murid == 0) {
        echo "<a href='?hapus_test=$id_kelas' onclick='return confirm(\"Hapus kelas $nama_kelas?\")' style='color: red;'>🗑️ Hapus Test</a>";
    } else {
        echo "<span style='color: gray;'>❌ Ada murid</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Proses hapus test
if (isset($_GET['hapus_test'])) {
    $id_kelas = (int)$_GET['hapus_test'];
    
    echo "<h3>Proses Hapus Kelas ID: $id_kelas</h3>";
    
    // Get nama kelas
    $get_kelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
    if (!$get_kelas || mysqli_num_rows($get_kelas) == 0) {
        echo "❌ Kelas tidak ditemukan!<br>";
    } else {
        $kelas_data = mysqli_fetch_assoc($get_kelas);
        $nama_kelas = $kelas_data['kelas'];
        
        echo "📋 Nama kelas: $nama_kelas<br>";
        
        // Cek jumlah murid
        $nama_kelas_escaped = mysqli_real_escape_string($conn, $nama_kelas);
        $cek_murid = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas_escaped'");
        $row_murid = mysqli_fetch_assoc($cek_murid);
        
        echo "👥 Jumlah murid: " . $row_murid['total'] . "<br>";
        
        if ($row_murid['total'] > 0) {
            echo "❌ Tidak bisa hapus karena masih ada murid!<br>";
        } else {
            echo "✅ Kelas kosong, bisa dihapus<br>";
            
            // Mulai hapus
            mysqli_autocommit($conn, FALSE);
            
            try {
                // 1. Hapus wali kelas
                $del_wali = mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas = $id_kelas");
                echo "🧹 Hapus wali kelas: " . ($del_wali ? "✅ berhasil" : "❌ gagal - " . mysqli_error($conn)) . "<br>";
                
                // 2. Hapus mapel ampu
                $del_mapel = mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE kelas = '$nama_kelas_escaped'");
                echo "📚 Hapus mapel ampu: " . ($del_mapel ? "✅ berhasil" : "❌ gagal - " . mysqli_error($conn)) . "<br>";
                
                // 3. Hapus kelas
                $del_kelas = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");
                echo "🏫 Hapus kelas: " . ($del_kelas ? "✅ berhasil" : "❌ gagal - " . mysqli_error($conn)) . "<br>";
                
                if ($del_kelas) {
                    mysqli_commit($conn);
                    echo "<br>🎉 <strong>BERHASIL!</strong> Kelas '$nama_kelas' telah dihapus.<br>";
                    echo "<a href='test-hapus-simple.php'>🔄 Refresh untuk lihat hasil</a><br>";
                } else {
                    throw new Exception("Gagal menghapus kelas");
                }
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo "<br>💥 <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
            }
            
            mysqli_autocommit($conn, TRUE);
        }
    }
}

echo "<br><hr>";
echo "<a href='home.php?page=input-kelas'>🔙 Kembali ke Input Kelas</a><br>";
echo "<a href='test-hapus-simple.php'>🔄 Refresh halaman ini</a>";
?>