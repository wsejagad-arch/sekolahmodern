<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Test Sidebar - Simple (No Manual JS)</title>
    
    <!-- Bootstrap CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/mycss.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav backgroundna sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Ruang Admin</div>
            </a>
            
            <hr class="sidebar-divider my-0">
            
            <li class="nav-item active">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">
                Data Guru dan Siswa
            </div>
            
            <!-- NATIVE BOOTSTRAP - TANPA MANUAL JS -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Guru</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="home.php?page=data-guru">Lihat Data Guru</a>
                        <a class="collapse-item" href="home.php?page=tambah-guru">Tambah Data Guru</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSiswa" aria-expanded="false" aria-controls="collapseSiswa">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Siswa</span>
                </a>
                <div id="collapseSiswa" class="collapse" aria-labelledby="headingSiswa" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="home.php?page=data-siswa">Lihat Data Siswa</a>
                        <a class="collapse-item" href="home.php?page=tambah-siswa">Tambah Data Siswa</a>
                    </div>
                </div>
            </li>
        </ul>
        <!-- End of Sidebar -->
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid mt-5">
                    <h1 class="h3 mb-4 text-gray-800">✅ Test Sidebar - Native Bootstrap</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Instruksi Testing</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong>INFO:</strong> File ini menggunakan <strong>NATIVE Bootstrap collapse</strong> 
                                tanpa manual JavaScript handler.
                            </div>
                            
                            <ol>
                                <li><strong>Buka Browser Console (F12)</strong></li>
                                <li><strong>Klik menu "Data Guru" di sidebar</strong> - Harus expand</li>
                                <li><strong>Klik menu "Data Siswa" di sidebar</strong> - Harus expand</li>
                                <li><strong>Cek console</strong> - Lihat log Bootstrap</li>
                            </ol>
                            
                            <div id="consoleOutput" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap core JavaScript - URUTAN PENTING! -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    
    <script>
        // MINIMAL LOGGING - BIARKAN BOOTSTRAP HANDLE COLLAPSE
        console.clear();
        console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        console.log('%c✅ TEST SIDEBAR - NATIVE BOOTSTRAP', 'color: #1cc88a; font-weight: bold;');
        console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        
        $(document).ready(function() {
            console.log('jQuery:', $.fn.jquery);
            console.log('Bootstrap collapse:', typeof $.fn.collapse !== 'undefined' ? '✅ Available' : '❌ Not available');
            
            // Monitor collapse events
            $('#accordionSidebar .collapse').on('show.bs.collapse', function() {
                console.log('✅ Collapse opening:', this.id);
            });
            
            $('#accordionSidebar .collapse').on('hide.bs.collapse', function() {
                console.log('✅ Collapse closing:', this.id);
            });
            
            // Display status
            setTimeout(function() {
                var status = typeof $.fn.collapse !== 'undefined' ? 'success' : 'danger';
                var message = typeof $.fn.collapse !== 'undefined' 
                    ? '✅ Bootstrap collapse tersedia! Klik menu sidebar untuk test.'
                    : '❌ Bootstrap collapse TIDAK tersedia! Periksa file bootstrap.bundle.min.js';
                
                $('#consoleOutput').html('<div class="alert alert-' + status + '"><strong>' + message + '</strong></div>');
                
                console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
                console.log('%c✅ READY - Sidebar menggunakan NATIVE Bootstrap', 'color: #1cc88a; font-weight: bold;');
                console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
            }, 200);
        });
    </script>
</body>
</html>
