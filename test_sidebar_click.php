<!DOCTYPE html>
<html>
<head>
<title>Test Sidebar Clickability</title>
<link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
<link href="css/sb-admin-2.min.css" rel="stylesheet">
<link href="css/mycss.css" rel="stylesheet">
<style>
body { padding: 20px; background: #f8f9fc; }
.test-container { display: flex; }
.sidebar-test { width: 250px; }
.content-test { flex: 1; padding: 20px; background: white; margin-left: 20px; }
</style>
</head>
<body>

<h2>🔍 Test Sidebar Links</h2>
<p>Klik setiap link di sidebar untuk test. URL akan berubah di address bar.</p>
<hr>

<div class="test-container">
<div class="sidebar-test">
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['hak_akses'] = 1; // Simulasi admin
$conn = null; // Simulasi koneksi
include 'sidebar.php';
?>
</div>

<div class="content-test">
<h3>📋 Daftar Cek:</h3>
<ul id="checkList">
<li>✅ Sidebar muncul dengan benar</li>
<li id="check-dashboard">⏳ Link Dashboard (home.php)</li>
<li id="check-dataguru">⏳ Menu Data Guru bisa expand</li>
<li id="check-lihatguru">⏳ Link "Lihat Data Guru" bisa diklik</li>
<li id="check-tambahguru">⏳ Link "Tambah Data Guru" bisa diklik</li>
</ul>

<h3>📊 Console Log:</h3>
<div id="console" style="background: #f4f4f4; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;"></div>
</div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<script>
const log = (msg) => {
    const d = new Date();
    const time = d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds();
    $('#console').append(`[${time}] ${msg}<br>`);
    console.log(msg);
};

$(document).ready(function() {
    log('✅ jQuery loaded: ' + $.fn.jquery);
    log('✅ Bootstrap loaded');
    
    // Test klik Dashboard
    $('a[href="home.php"]').on('click', function(e) {
        log('✅ Dashboard link clicked!');
        $('#check-dashboard').html('✅ Link Dashboard (home.php) - BERFUNGSI');
        // Jangan prevent, biarkan navigate
    });
    
    // Test collapse Data Guru
    $('a[data-target="#collapseTwo"]').on('click', function(e) {
        e.preventDefault();
        log('✅ Data Guru collapse toggled!');
        $('#check-dataguru').html('✅ Menu Data Guru bisa expand - BERFUNGSI');
    });
    
    // Test submenu clicks
    $('a[href="home.php?page=data-guru"]').on('click', function(e) {
        log('✅ Link "Lihat Data Guru" clicked!');
        $('#check-lihatguru').html('✅ Link "Lihat Data Guru" bisa diklik - BERFUNGSI');
    });
    
    $('a[href="home.php?page=tambah-guru"]').on('click', function(e) {
        log('✅ Link "Tambah Data Guru" clicked!');
        $('#check-tambahguru').html('✅ Link "Tambah Data Guru" bisa diklik - BERFUNGSI');
    });
    
    // Log all sidebar links
    log('Found ' + $('#accordionSidebar a').length + ' links in sidebar');
    
    // Check z-index
    const sidebarZ = $('#accordionSidebar').css('z-index');
    log('Sidebar z-index: ' + sidebarZ);
    
    // Check pointer events
    const pointerEvents = $('#accordionSidebar .nav-link').first().css('pointer-events');
    log('Pointer events: ' + pointerEvents);
});
</script>

</body>
</html>
