<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

// Set session untuk testing (jika belum ada)
if (!isset($_SESSION["username"])) {
    $_SESSION["username"] = "test_user";
    $_SESSION["nama"] = "Test User";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Hapus Wali Kelas</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        button { padding: 10px 20px; margin: 5px; }
        #log { background: #f5f5f5; padding: 10px; margin: 10px 0; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>Test Hapus Wali Kelas</h1>
    
    <div class="test-section">
        <h2>Session Status</h2>
        <p>Username: <?php echo $_SESSION["username"] ?? "Not set"; ?></p>
        <p>Nama: <?php echo $_SESSION["nama"] ?? "Not set"; ?></p>
    </div>
    
    <div class="test-section">
        <h2>Current Data Kelas ID 58</h2>
        <?php
        $query = mysqli_query($conn, "SELECT * FROM tbl_kelas WHERE id_kelas='58'");
        $data = mysqli_fetch_array($query);
        if ($data) {
            echo "<p>Kelas: " . $data['kelas'] . "</p>";
            echo "<p>Wali Kelas: " . ($data['wali_kelas'] == '0' ? '<em>Tidak Ada</em>' : $data['wali_kelas']) . "</p>";
            echo "<p>NIP Wali: " . ($data['nip_wali'] == '0' ? '<em>Tidak Ada</em>' : $data['nip_wali']) . "</p>";
        } else {
            echo "<p>Data tidak ditemukan</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test Actions</h2>
        <button onclick="testHapusWali()">Hapus Wali Kelas (ID: 58)</button>
        <button onclick="resetWali()">Reset Wali Kelas (untuk testing)</button>
        <button onclick="location.reload()">Refresh Data</button>
    </div>
    
    <div class="test-section">
        <h2>Log Output</h2>
        <div id="log"></div>
        <button onclick="clearLog()">Clear Log</button>
    </div>

    <script>
    function log(message) {
        const timestamp = new Date().toLocaleTimeString();
        document.getElementById('log').innerHTML += '[' + timestamp + '] ' + message + '<br>';
        console.log(message);
    }
    
    function clearLog() {
        document.getElementById('log').innerHTML = '';
    }
    
    function testHapusWali() {
        log('Starting testHapusWali()');
        
        $.ajax({
            url: 'ajax_update_wali_kelas.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'hapus_wali',
                id_kelas: '58'
            },
            beforeSend: function() {
                log('AJAX request starting...');
            },
            success: function(response) {
                log('AJAX Success - Response: ' + JSON.stringify(response));
                
                if(response.success) {
                    log('Operation successful!');
                    alert('Berhasil: ' + response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    log('Operation failed: ' + response.message);
                    alert('Gagal: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Error - Status: ' + status + ', Error: ' + error);
                log('Response Text: ' + xhr.responseText);
                alert('Error: ' + error);
            }
        });
    }
    
    function resetWali() {
        log('Starting resetWali()');
        
        $.ajax({
            url: 'ajax_update_wali_kelas.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'update_wali',
                id_kelas: '58',
                nip_wali: '199305062020121010'
            },
            success: function(response) {
                log('Reset Response: ' + JSON.stringify(response));
                if(response.success) {
                    alert('Reset berhasil!');
                    location.reload();
                } else {
                    alert('Reset gagal: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                log('Reset Error: ' + error);
                alert('Reset error: ' + error);
            }
        });
    }
    </script>
</body>
</html>