<!DOCTYPE html>
<html>
<head>
    <title>Test Batch Delete Form</title>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="js/sweetalert2.all.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { padding: 10px 15px; margin: 5px; cursor: pointer; }
        .btn-danger { background-color: #dc3545; color: white; border: none; }
        .btn-success { background-color: #28a745; color: white; border: none; }
    </style>
</head>
<body>
    <h1>Test Batch Delete - Simplified</h1>
    
    <?php
    include "koneksi.php";
    
    // Debug POST
    if ($_POST) {
        echo "<div style='background: #e7f3ff; padding: 10px; margin: 10px 0; border: 1px solid #0066cc;'>";
        echo "<h3>✅ POST Data Diterima:</h3>";
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
        
        if (isset($_POST['test_batch_delete']) && !empty($_POST['kelas_terpilih'])) {
            echo "<h3>🔄 Proses Batch Delete:</h3>";
            
            foreach ($_POST['kelas_terpilih'] as $id_kelas) {
                $id_kelas = (int)$id_kelas;
                
                // Get nama kelas
                $get_kelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE id_kelas = $id_kelas");
                if ($get_kelas && mysqli_num_rows($get_kelas) > 0) {
                    $kelas_data = mysqli_fetch_assoc($get_kelas);
                    $nama_kelas = $kelas_data['kelas'];
                    
                    echo "📋 Kelas: $nama_kelas (ID: $id_kelas)<br>";
                    
                    // Cek murid
                    $nama_kelas_escaped = mysqli_real_escape_string($conn, $nama_kelas);
                    $cek_murid = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas_escaped'");
                    $row_murid = mysqli_fetch_assoc($cek_murid);
                    
                    if ($row_murid['total'] == 0) {
                        // Hapus kelas kosong
                        $del_result = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");
                        echo "✅ Berhasil dihapus: $nama_kelas<br>";
                    } else {
                        echo "❌ Tidak bisa hapus $nama_kelas (ada {$row_murid['total']} murid)<br>";
                    }
                } else {
                    echo "❌ Kelas ID $id_kelas tidak ditemukan<br>";
                }
            }
        }
        echo "</div>";
    }
    ?>
    
    <form method="POST" action="" id="test-form">
        <h3>Pilih Kelas untuk Dihapus:</h3>
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>ID</th>
                    <th>Nama Kelas</th>
                    <th>Jumlah Murid</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY kelas");
                while ($row = mysqli_fetch_assoc($result)) {
                    $id_kelas = $row['id_kelas'];
                    $nama_kelas = $row['kelas'];
                    
                    // Cek jumlah murid
                    $nama_kelas_escaped = mysqli_real_escape_string($conn, $nama_kelas);
                    $cek_murid = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE kelas = '$nama_kelas_escaped'");
                    $row_murid = mysqli_fetch_assoc($cek_murid);
                    $jumlah_murid = $row_murid['total'];
                    
                    echo "<tr>";
                    echo "<td><input type='checkbox' name='kelas_terpilih[]' value='$id_kelas' class='checkbox-kelas'></td>";
                    echo "<td>$id_kelas</td>";
                    echo "<td>$nama_kelas</td>";
                    echo "<td>$jumlah_murid</td>";
                    echo "<td>" . ($jumlah_murid == 0 ? "✅ Kosong" : "❌ Ada murid") . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        
        <br>
        <button type="submit" name="test_batch_delete" value="1" class="btn btn-danger" id="btn-test-delete">
            🗑️ Test Batch Delete
        </button>
        
        <button type="button" class="btn btn-success" onclick="testForm()">
            🧪 Test Form (JS)
        </button>
    </form>
    
    <br>
    <a href="home.php?page=input-kelas">🔙 Kembali ke Input Kelas</a> | 
    <a href="test-hapus-simple.php">🔧 Test Hapus Simple</a>
    
    <script>
    // Test JavaScript
    function testForm() {
        console.log('Test form function called');
        
        const checkedBoxes = $('.checkbox-kelas:checked');
        console.log('Checked boxes:', checkedBoxes.length);
        
        if (checkedBoxes.length === 0) {
            alert('Pilih kelas terlebih dahulu!');
            return;
        }
        
        const selectedIds = [];
        checkedBoxes.each(function() {
            selectedIds.push($(this).val());
        });
        
        console.log('Selected IDs:', selectedIds);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Test JavaScript',
                html: `Selected ${checkedBoxes.length} kelas:<br>${selectedIds.join(', ')}`,
                icon: 'info'
            });
        } else {
            alert(`Selected ${checkedBoxes.length} kelas: ${selectedIds.join(', ')}`);
        }
    }
    
    // Select all functionality
    $('#select-all').on('change', function() {
        $('.checkbox-kelas').prop('checked', $(this).is(':checked'));
    });
    
    // Individual checkbox change
    $('.checkbox-kelas').on('change', function() {
        const total = $('.checkbox-kelas').length;
        const checked = $('.checkbox-kelas:checked').length;
        $('#select-all').prop('checked', checked === total);
    });
    
    console.log('Test form loaded successfully');
    console.log('jQuery version:', $.fn.jquery);
    console.log('SweetAlert available:', typeof Swal !== 'undefined');
    </script>
</body>
</html>