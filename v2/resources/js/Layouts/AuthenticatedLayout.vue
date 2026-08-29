<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const role = user.role; // 'admin', 'guru', 'siswa'

const isSidebarOpen = ref(true);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    if (isSidebarOpen.value) {
        document.body.classList.add('sidebar-open');
    } else {
        document.body.classList.remove('sidebar-open');
    }
};

onMounted(() => {
    // Topbar Clock
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    function updateClock() {
        const now = new Date();
        const dateEl = document.getElementById('topbar-date');
        const timeEl = document.getElementById('topbar-time');
        if (dateEl) dateEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        if (timeEl) {
            let h = String(now.getHours()).padStart(2, '0');
            let m = String(now.getMinutes()).padStart(2, '0');
            let s = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = h + ':' + m + ':' + s + ' WIB';
        }
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Fullscreen Toggle Script
    const fsButton = document.getElementById('btn-fullscreen');
    const fsIcon = document.getElementById('icon-fullscreen');
    if (fsButton && fsIcon) {
        fsButton.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });

        const changeHandler = function() {
            if (document.fullscreenElement) {
                fsIcon.classList.remove('fa-expand');
                fsIcon.classList.add('fa-compress');
            } else {
                fsIcon.classList.remove('fa-compress');
                fsIcon.classList.add('fa-expand');
            }
        };
        document.addEventListener('fullscreenchange', changeHandler);
    }
});

const roleLabels = {
    'admin': 'Admin Sekolah',
    'guru': 'Guru',
    'siswa': 'Siswa',
};
const roleLabel = roleLabels[role] || 'Pengguna';
</script>

<template>
    <div id="wrapper">
        <!-- Sidebar -->
        <ul v-if="role !== 'guru'" class="navbar-nav backgroundna sidebar sidebar-dark accordion" id="accordionSidebar" :class="{ 'toggled': !isSidebarOpen }">
            <!-- Sidebar - Brand -->
            <Link :href="route('dashboard')" class="sidebar-brand d-flex align-items-center" style="text-decoration:none; padding: 16px 14px !important;">
                <div class="sidebar-brand-icon" style="width:40px; height:40px; min-width:40px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--school-secondary); box-shadow:0 3px 10px rgba(240,180,41,0.35);">
                    <i class="fas fa-graduation-cap" style="font-size:18px; color:#1a3c6e;"></i>
                </div>
                <div class="sidebar-brand-text mx-2" style="overflow:hidden; min-width:0;">
                    <div style="font-size:12px; font-weight:800; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:0.3px;">
                        SIMANIS v2
                    </div>
                    <div style="font-size:10px; font-weight:400; color:rgba(255,255,255,0.65); letter-spacing:0.4px; margin-top:1px;">Sistem Manajemen Sekolah</div>
                </div>
            </Link>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item" :class="{ 'active': route().current('dashboard') }">
                <Link class="nav-link" :href="route('dashboard')">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </Link>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                <i class="fas fa-users mr-1" style="font-size:9px;"></i> Data Guru &amp; Siswa
            </div>

            <!-- Nav Item - Pages Collapse Menu Data Guru -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-chalkboard-teacher"></i>
                    <span>Data Guru</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=data-guru"><i class="fas fa-list text-info mr-1" style="font-size:11px"></i>Lihat Data Guru</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=tambah-guru"><i class="fas fa-user-plus text-primary mr-1" style="font-size:11px"></i>Tambah Data Guru</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=import-guru"><i class="fas fa-file-excel text-success mr-1" style="font-size:11px"></i>Import dari Excel</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Pages Collapse Menu Data Siswa -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSiswa" aria-expanded="false" aria-controls="collapseSiswa">
                    <i class="fas fa-fw fa-user-graduate"></i>
                    <span>Data Siswa</span>
                </a>
                <div id="collapseSiswa" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=data-siswa"><i class="fas fa-list text-info mr-1" style="font-size:11px"></i>Lihat Data Siswa</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=tambah-siswa"><i class="fas fa-user-plus text-primary mr-1" style="font-size:11px"></i>Tambah Siswa</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=input-kelas"><i class="fas fa-chalkboard text-secondary mr-1" style="font-size:11px"></i>Input Kelas</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=kenaikan-kelas"><i class="fas fa-level-up-alt text-primary mr-1" style="font-size:11px"></i>Naik Kelas</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=data-alumni"><i class="fas fa-user-graduate text-success mr-1" style="font-size:11px"></i>Data Alumni</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=ketua-kelas"><i class="fas fa-crown text-warning mr-1" style="font-size:11px"></i>Setting Ketua Kelas</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Wali Kelas (Hanya Admin) -->
            <li v-if="role === 'admin'" class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseWaliKelas" aria-expanded="false" aria-controls="collapseWaliKelas">
                    <i class="fas fa-fw fa-user-tie"></i>
                    <span>Wali Kelas & Guru BK</span>
                </a>
                <div id="collapseWaliKelas" class="collapse" aria-labelledby="headingWaliKelas" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Wali Kelas:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=kelola-wali-kelas"><i class="fas fa-users-cog text-primary mr-1" style="font-size:11px"></i>Kelola Wali Kelas</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=data-wali-kelas"><i class="fas fa-address-book text-info mr-1" style="font-size:11px"></i>Data Wali Kelas</a>
                        <h6 class="collapse-header mt-2">Bimbingan Konseling:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=data-guru-bk"><i class="fas fa-user-shield text-success mr-1" style="font-size:11px"></i>Data Guru BK</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                <i class="fas fa-chart-line mr-1" style="font-size:9px;"></i> Monitoring
            </div>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMonitoring" aria-expanded="false" aria-controls="collapseMonitoring">
                    <i class="fas fa-fw fa-chart-line"></i>
                    <span>Monitoring</span>
                </a>
                <div id="collapseMonitoring" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Cek:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=jurnal"><i class="fas fa-book text-primary mr-1" style="font-size:11px"></i>Jurnal Guru</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=kelas"><i class="fas fa-chalkboard text-info mr-1" style="font-size:11px"></i>Jurnal Kelas</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=monitoring-guru"><i class="fas fa-eye text-warning mr-1" style="font-size:11px"></i>Monitoring Guru</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=lacak-siswa"><i class="fas fa-search-location text-success mr-1" style="font-size:11px"></i>Lacak Siswa</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=monitoring"><i class="fas fa-user-check text-secondary mr-1" style="font-size:11px"></i>Kehadiran Guru</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=cek-nilai"><i class="fas fa-star text-warning mr-1" style="font-size:11px"></i>Cek Nilai</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=nilai-perkembangan"><i class="fas fa-chart-line text-info mr-1" style="font-size:11px"></i>Nilai & Grafik Perkembangan</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=cetak-kehadiran-siswa&tab=kelas"><i class="fas fa-users text-primary mr-1" style="font-size:11px"></i>Kehadiran Siswa</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=monitoring-izin"><i class="fas fa-file-signature text-warning mr-1" style="font-size:11px"></i>Monitoring Izin</a>
                        <a v-if="role === 'admin'" class="collapse-item" href="/sekolahku/home.php?page=aduan-siswa"><i class="fas fa-shield-heart text-danger mr-1" style="font-size:11px"></i>Aduan Siswa</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                <i class="fas fa-cog mr-1" style="font-size:9px;"></i> Manajemen
            </div>

            <!-- Nav Item - Admin -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                    <i class="fas fa-fw fa-address-book"></i>
                    <span>Admin</span>
                </a>
                <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Data Admin:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=lihatuser"><i class="fas fa-users-cog text-info mr-1" style="font-size:11px"></i>Tampilkan Admin</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=tambahuser"><i class="fas fa-user-plus text-primary mr-1" style="font-size:11px"></i>Tambah Admin</a>
                        <h6 class="collapse-header mt-2">LENTERA:</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=literasi-admin"><i class="fas fa-book-reader text-warning mr-1" style="font-size:11px"></i>Mapping Guru Literasi</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Pengaturan -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSetting" aria-expanded="false" aria-controls="collapseSetting">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span>Pengaturan</span>
                </a>
                <div id="collapseSetting" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Setting</h6>
                        <a class="collapse-item" href="/sekolahku/home.php?page=setting"><i class="fas fa-school text-info mr-1" style="font-size:11px"></i>Data Sekolah</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=presensi-settings"><i class="fas fa-user-clock text-secondary mr-1" style="font-size:11px"></i>Pengaturan Presensi</a>
                        <a class="collapse-item" href="/sekolahku/pengaturan-wa.php"><i class="fab fa-whatsapp text-success mr-1" style="font-size:11px"></i>Notifikasi WhatsApp</a>
                        <a class="collapse-item" href="/sekolahku/home.php?page=broadcast-wa"><i class="fas fa-bullhorn text-primary mr-1" style="font-size:11px"></i>Broadcast WA</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Pengumuman -->
            <li class="nav-item">
                <a class="nav-link" href="/sekolahku/home.php?page=pengumuman">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Pengumuman</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">
        </ul>
        
        <!-- GURU SIDEBAR FALLBACK (We will route them to old system for now) -->
        <ul v-else class="navbar-nav bg-gradient-success sidebar sidebar-dark accordion" id="accordionSidebar">
            <Link :href="route('dashboard')" class="sidebar-brand d-flex align-items-center justify-content-center">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Portal Guru</div>
            </Link>
            <hr class="sidebar-divider my-0">
            <li class="nav-item active">
                <Link class="nav-link" :href="route('dashboard')">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard Guru</span>
                </Link>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/sekolahku/home.php">
                    <i class="fas fa-fw fa-arrow-left"></i>
                    <span>Kembali ke Sistem Lama</span>
                </a>
            </li>
        </ul>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button @click="toggleSidebar" id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" type="button" aria-label="Toggle sidebar" style="color:#1a3c6e;font-size:18px;padding:4px 10px;">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- School Name & Logo (Topbar Left) -->
                    <div class="d-none d-sm-flex align-items-center flex-grow-1 ml-md-3 ms-md-3 my-2 my-md-0">
                        <img src="/sekolahku/img/foto-profil.png" width="38" height="38" class="mr-2" style="border-radius:8px; object-fit:contain; border:2px solid rgba(26,60,110,0.12);">
                        <div>
                            <div style="font-size:15px; font-weight:800; color:#1a3c6e; line-height:1.2;">
                                SIMANIS SMAN 1 SUMBER
                            </div>
                            <div style="font-size:11px; color:#64748b; font-weight:500; letter-spacing:0.3px;">
                                <i class="fas fa-map-marker-alt mr-1" style="color:#f0b429;font-size:10px;"></i>
                                Sistem Manajemen Sekolah
                            </div>
                        </div>
                    </div>

                    <!-- Topbar Navbar (Right) -->
                    <ul class="navbar-nav ml-auto ms-auto align-items-center justify-content-end" style="flex: 0 0 auto;">
                        <!-- Current Date/Time Info -->
                        <li class="nav-item d-none d-lg-flex align-items-center mr-2">
                            <div style="text-align:right;">
                                <div style="font-size:12px; font-weight:700; color:#1a3c6e;" id="topbar-date"></div>
                                <div style="font-size:11px; color:#64748b; font-weight:500;" id="topbar-time"></div>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-lg-block"></div>

                        <!-- Nav Item - Logout Button -->
                        <li class="nav-item mr-1">
                            <Link :href="route('logout')" method="post" as="button" class="nav-link btn btn-link" title="Keluar" style="color:#dc2626 !important; padding:8px 12px !important; border-radius:8px !important;">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="d-none d-lg-inline ml-1" style="font-size:12px; font-weight:600;">Keluar</span>
                            </Link>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Fullscreen Toggle Button -->
                        <li class="nav-item mr-2">
                            <button class="btn btn-link nav-link" id="btn-fullscreen" title="Fullscreen" style="padding: 8px 12px !important; border-radius: 8px !important; border: none; background: transparent; cursor: pointer;">
                                <i class="fas fa-expand" id="icon-fullscreen" style="color: #64748b; font-size: 16px;"></i>
                            </button>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 6px 10px !important;">
                                <div class="d-none d-lg-flex align-items-center mr-2" style="text-align:right;">
                                    <div>
                                        <div style="font-size:13px; font-weight:700; color:#1e293b; line-height:1.2;">{{ user.name }}</div>
                                        <div style="font-size:11px; color:#64748b; font-weight:500;">
                                            {{ roleLabel }}
                                        </div>
                                    </div>
                                </div>
                                <div style="position:relative; display:inline-block;">
                                    <img class="img-profile rounded-circle" src="/sekolahku/img/foto-profil.png" width="38" height="38" style="object-fit:cover; border:2px solid #e2e8f0; width:38px; height:38px;">
                                    <span style="position:absolute; bottom:1px; right:1px; width:9px; height:9px; background:#16a34a; border:2px solid #fff; border-radius:50%; display:block;"></span>
                                </div>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown" style="min-width:220px; border:none; border-radius:12px; box-shadow:0 8px 32px rgba(26,60,110,0.15); overflow:hidden; padding:8px 0;">
                                <div style="padding:14px 18px 12px; border-bottom:1px solid #f0f4f8;">
                                    <div style="font-size:14px; font-weight:700; color:#1a3c6e;">{{ user.name }}</div>
                                    <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ user.username }}</div>
                                </div>
                                <Link :href="route('profile.edit')" class="dropdown-item" style="padding:10px 18px; font-size:13.5px; font-weight:500; color:#1e293b; display:flex; align-items:center; gap:10px; transition:all .2s;">
                                    <i class="fas fa-user fa-sm text-info" style="width:16px;"></i>
                                    Profil Saya
                                </Link>
                                <div class="dropdown-divider" style="margin:4px 0; border-color:#f0f4f8;"></div>
                                <Link :href="route('logout')" method="post" as="button" class="dropdown-item btn-link" style="padding:10px 18px; font-size:13.5px; font-weight:500; color:#dc2626; display:flex; align-items:center; gap:10px; transition:all .2s; width: 100%; text-align: left;">
                                    <i class="fas fa-sign-out-alt fa-sm" style="width:16px;"></i>
                                    Keluar
                                </Link>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Main Content Slot -->
                <div class="container-fluid">
                    <slot />
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white mt-5">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto font-weight-bold">
                        <span>Hak Cipta &copy; SIMANIS Laravel v2 2026</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
    </div>
</template>

<style>
/* Adjust body background to match old style */
body {
    background-color: #f8f9fc;
}
</style>
