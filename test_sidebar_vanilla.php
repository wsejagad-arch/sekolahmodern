<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Test Sidebar - Vanilla JS (No Bootstrap Collapse)</title>
    
    <!-- Bootstrap CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/mycss.css" rel="stylesheet">
    
    <style>
        /* Manual collapse animation */
        .collapse {
            display: none;
        }
        .collapse.show {
            display: block;
        }
    </style>
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
            
            <div class="sidebar-heading">Data Guru dan Siswa</div>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:void(0)" onclick="toggleCollapse('collapseGuru', this)">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Guru</span>
                </a>
                <div id="collapseGuru" class="collapse">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="home.php?page=data-guru">Lihat Data Guru</a>
                        <a class="collapse-item" href="home.php?page=tambah-guru">Tambah Data Guru</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:void(0)" onclick="toggleCollapse('collapseSiswa', this)">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Siswa</span>
                </a>
                <div id="collapseSiswa" class="collapse">
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
                    <h1 class="h3 mb-4 text-gray-800">✅ Test Sidebar - Vanilla JavaScript</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Instruksi Testing</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <strong>⚠️ SOLUSI:</strong> File ini menggunakan <strong>VANILLA JAVASCRIPT</strong> 
                                karena Bootstrap collapse plugin tidak tersedia. Ini adalah solusi fallback yang stabil.
                            </div>
                            
                            <ol>
                                <li><strong>Buka Browser Console (F12)</strong></li>
                                <li><strong>Klik menu "Data Guru" di sidebar</strong> - Harus expand</li>
                                <li><strong>Klik menu "Data Siswa" di sidebar</strong> - Harus expand</li>
                                <li><strong>TIDAK ADA ERROR</strong> di console</li>
                            </ol>
                            
                            <div class="alert alert-info mt-3">
                                <strong>INFO:</strong> Fungsi ini TIDAK menggunakan Bootstrap collapse plugin, 
                                melainkan JavaScript DOM manipulation langsung.
                            </div>
                            
                            <div id="consoleOutput" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        console.clear();
        console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        console.log('%c✅ TEST SIDEBAR - VANILLA JS', 'color: #1cc88a; font-weight: bold;');
        console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        console.log('jQuery:', $.fn.jquery);
        console.log('Bootstrap collapse:', typeof $.fn.collapse);
        console.log('Status: Bootstrap collapse NOT required - using vanilla JS');
        console.log('');
        
        // Vanilla JavaScript Toggle Function
        function toggleCollapse(targetId, linkElement) {
            console.log('✅ Toggling:', targetId);
            
            var target = document.getElementById(targetId);
            if (!target) {
                console.error('❌ Target not found:', targetId);
                return;
            }
            
            // Toggle show class
            if (target.classList.contains('show')) {
                target.classList.remove('show');
                linkElement.classList.add('collapsed');
                console.log('✅ Collapsed:', targetId);
            } else {
                // Close other menus first (accordion behavior)
                var allCollapses = document.querySelectorAll('#accordionSidebar .collapse');
                allCollapses.forEach(function(collapse) {
                    collapse.classList.remove('show');
                });
                
                // Mark all links as collapsed
                var allLinks = document.querySelectorAll('#accordionSidebar .nav-link');
                allLinks.forEach(function(link) {
                    link.classList.add('collapsed');
                });
                
                // Open this one
                target.classList.add('show');
                linkElement.classList.remove('collapsed');
                console.log('✅ Expanded:', targetId);
            }
        }
        
        // Make function global
        window.toggleCollapse = toggleCollapse;
        
        $(document).ready(function() {
            console.log('DOM Ready');
            
            $('#consoleOutput').html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> <strong>✅ Sidebar ready!</strong><br>Klik menu untuk test. Tidak ada error collapse!</div>');
            
            console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
            console.log('%c✅ READY - Click sidebar menus now!', 'color: #1cc88a; font-weight: bold;');
            console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        });
    </script>
</body>
</html>
