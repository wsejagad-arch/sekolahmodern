<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

// Set session untuk testing (jika belum ada)
if (!isset($_SESSION["username"])) {
    $_SESSION["username"] = "admin";
    $_SESSION["nama"] = "Test Admin";
    $_SESSION["hakakses"] = "1";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Fungsi Tambah Data Kelas</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        #log { background: #f8f9fa; padding: 15px; margin: 10px 0; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>🧪 Test Fungsi Tambah Data Kelas</h1>
    
    <div class="test-section">
        <h2>📋 Data Kelas Tersedia</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kelas</th>
                    <th>Wali Kelas</th>
                    <th>NIP Wali</th>
                    <th>Action Test</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($conn, "SELECT id_kelas, kelas, wali_kelas, nip_wali FROM tbl_kelas ORDER BY id_kelas LIMIT 5");
                while($data = mysqli_fetch_array($query)) {
                    $hasWali = !empty($data['wali_kelas']) && $data['wali_kelas'] != '0';
                    echo "<tr>";
                    echo "<td>" . $data['id_kelas'] . "</td>";
                    echo "<td>" . $data['kelas'] . "</td>";
                    echo "<td>" . ($hasWali ? $data['wali_kelas'] : '<em>Belum ada</em>') . "</td>";
                    echo "<td>" . ($hasWali ? $data['nip_wali'] : '<em>-</em>') . "</td>";
                    echo "<td>";
                    
                    // Test Edit Wali Kelas
                    echo "<button class='btn btn-info' onclick=\"testEditWali('{$data['id_kelas']}', '{$data['kelas']}', '{$data['nip_wali']}')\">Edit</button>";
                    
                    // Test Hapus Wali (hanya jika ada wali)
                    if ($hasWali) {
                        echo "<button class='btn btn-warning' onclick=\"testHapusWali('{$data['id_kelas']}', '{$data['kelas']}')\">Hapus Wali</button>";
                    }
                    
                    // Test Hapus Kelas (dengan konfirmasi)
                    echo "<button class='btn btn-danger' onclick=\"testHapusKelas('{$data['id_kelas']}', '{$data['kelas']}')\">Hapus Kelas</button>";
                    
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="test-section">
        <h2>🔧 Test Functions</h2>
        <button class="btn btn-info" onclick="testAjaxEndpoint()">Test AJAX Endpoint</button>
        <button class="btn btn-warning" onclick="testSession()">Test Session</button>
        <button class="btn btn-danger" onclick="clearLog()">Clear Log</button>
        <button onclick="location.reload()" class="btn" style="background: #6c757d; color: white;">Refresh</button>
    </div>
    
    <div class="test-section">
        <h2>📝 Test Log</h2>
        <div id="log"></div>
    </div>

    <script>
    function log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            'info': '#17a2b8',
            'success': '#28a745', 
            'error': '#dc3545',
            'warning': '#ffc107'
        };
        document.getElementById('log').innerHTML += 
            `<div style="color: ${colors[type]}; margin: 5px 0;">[${timestamp}] ${message}</div>`;
        console.log(message);
    }
    
    function clearLog() {
        document.getElementById('log').innerHTML = '';
    }
    
    function testEditWali(idKelas, namaKelas, nipWali) {
        log(`🔧 Testing Edit Wali untuk kelas: ${namaKelas} (ID: ${idKelas})`, 'info');
        
        // Simulasi fungsi edit - test AJAX call untuk update wali
        $.ajax({
            url: 'ajax_update_wali_kelas.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'update_wali',
                id_kelas: idKelas,
                nip_wali: '199305062020121010' // NIP test
            },
            success: function(response) {
                log(`✅ Edit Test Success: ${JSON.stringify(response)}`, 'success');
                if(response.success) {
                    alert('✅ Edit Wali Test: BERHASIL');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    log(`❌ Edit failed: ${response.message}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                log(`❌ Edit Test Error: ${error} - ${xhr.responseText}`, 'error');
                alert('❌ Edit Wali Test: GAGAL - ' + error);
            }
        });
    }
    
    function testHapusWali(idKelas, namaKelas) {
        log(`🗑️ Testing Hapus Wali untuk kelas: ${namaKelas} (ID: ${idKelas})`, 'warning');
        
        if(!confirm(`Test hapus wali kelas dari ${namaKelas}?`)) return;
        
        $.ajax({
            url: 'ajax_update_wali_kelas.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'hapus_wali',
                id_kelas: idKelas
            },
            success: function(response) {
                log(`✅ Hapus Wali Test Success: ${JSON.stringify(response)}`, 'success');
                if(response.success) {
                    alert('✅ Hapus Wali Test: BERHASIL');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    log(`❌ Hapus Wali failed: ${response.message}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                log(`❌ Hapus Wali Test Error: ${error} - ${xhr.responseText}`, 'error');
                alert('❌ Hapus Wali Test: GAGAL - ' + error);
            }
        });
    }
    
    function testHapusKelas(idKelas, namaKelas) {
        log(`🗑️ Testing Hapus Kelas: ${namaKelas} (ID: ${idKelas})`, 'error');
        
        // Hanya simulasi, tidak benar-benar hapus
        if(confirm(`⚠️ SIMULASI ONLY - Test redirect hapus kelas ${namaKelas}?`)) {
            log(`🔗 Would redirect to: delete-kelas.php?id_kelas=${idKelas}&kelas=${encodeURIComponent(namaKelas)}`, 'warning');
            alert(`✅ Hapus Kelas Test: URL redirect OK\nTarget: delete-kelas.php?id_kelas=${idKelas}`);
        }
    }
    
    function testAjaxEndpoint() {
        log('🌐 Testing AJAX endpoint...', 'info');
        
        $.ajax({
            url: 'ajax_update_wali_kelas.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'test'
            },
            success: function(response) {
                log(`✅ AJAX Endpoint Response: ${JSON.stringify(response)}`, 'success');
            },
            error: function(xhr, status, error) {
                log(`❌ AJAX Endpoint Error: ${status} - ${xhr.responseText}`, 'error');
            }
        });
    }
    
    function testSession() {
        log('👤 Testing session...', 'info');
        
        $.get('debug_admin.php', function(data) {
            log('✅ Session test completed - check debug_admin.php', 'success');
            window.open('debug_admin.php', '_blank');
        }).fail(function() {
            log('❌ Session test failed', 'error');
        });
    }
    </script>
</body>
</html>