<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = page.props.auth.user;
const role = user.role;

const props = defineProps({
    aduanDashboardCount: {
        type: Number,
        default: 0
    },
    aduanDashboardOpen: {
        type: Number,
        default: 0
    },
    aduanDashboardRows: {
        type: Array,
        default: () => []
    }
});

const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour >= 5 && hour < 12) return 'Selamat Pagi';
    if (hour >= 12 && hour < 15) return 'Selamat Siang';
    if (hour >= 15 && hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
};

const currentDate = computed(() => {
    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const now = new Date();
    return `${hari[now.getDay()]}, ${now.getDate()} ${bulan[now.getMonth()]} ${now.getFullYear()}`;
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="row mx-auto">
            <!-- Kartu ucapan selamat datang -->
            <div class="col-md-12 mb-4">
                <div class="card border-0 shadow-sm" style="border-radius:18px; background:#fff; overflow:hidden;">
                    <div style="height:5px; background:#ffffff;"></div>
                    <div class="card-body py-4 px-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
                            <div>
                                <div style="font-size:13px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">
                                    <i class="fas fa-hand-wave mr-1" style="color:#f0b429;"></i>
                                    {{ getGreeting() }}
                                </div>
                                <h4 style="font-size:22px; font-weight:800; color:#1e293b; margin-bottom:4px;">
                                    {{ user.name }}
                                    <span style="font-size:14px; font-weight:500; color:#64748b; margin-left:8px;">
                                        <span v-if="role === 'admin'" style="background:#dbeafe; color:#1d4ed8; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">Super Admin</span>
                                        <span v-else-if="role === 'guru'" style="background:#dcfce7; color:#15803d; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">Guru</span>
                                        <span v-else-if="role === 'siswa'" style="background:#fef9c3; color:#92400e; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">Siswa</span>
                                    </span>
                                </h4>
                                <p style="font-size:13.5px; color:#64748b; margin:0;">
                                    <i class="fas fa-calendar-alt mr-1" style="color:#0ea5e9;"></i>
                                    {{ currentDate }}
                                    &nbsp;&bull;&nbsp;Berikut ringkasan data sistem.
                                </p>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:56px; height:56px; border-radius:50%; background:#ffffff; display:flex; align-items:center; justify-content:center; ">
                                    <i class="fas fa-school" style="font-size:24px; color:#fff;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Khusus Admin -->
            <template v-if="role === 'admin'">
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm" style="border-radius:18px; background:#fff; overflow:hidden;">
                        <div style="height:5px; background:#ffffff;"></div>
                        <div class="card-body py-3 px-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                                <div>
                                    <div style="font-size:12px; font-weight:800; color:#be123c; letter-spacing:0.8px; text-transform:uppercase;">Histori Aduan Siswa</div>
                                    <h5 style="margin:0; color:#0f172a; font-weight:800;">Aduan terbaru dan status alur</h5>
                                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Total {{ aduanDashboardCount }} laporan, {{ aduanDashboardOpen }} masih aktif.</div>
                                </div>
                                <a href="/sekolahku/home.php?page=aduan-siswa" class="btn btn-sm btn-danger" style="border-radius:999px; font-weight:700;">
                                    <i class="fas fa-shield-heart mr-1"></i>Kelola Aduan
                                </a>
                            </div>
                            <div class="row mt-3">
                                <div v-if="aduanDashboardRows.length === 0" class="col-12">
                                    <div class="p-3" style="border:1px dashed #fecdd3;border-radius:14px;color:#64748b;">Belum ada aduan siswa.</div>
                                </div>
                                
                                <div v-for="aduanDash in aduanDashboardRows" :key="aduanDash.kode_aduan" class="col-lg-6 mb-3">
                                    <div class="p-3" style="border:1px solid #fee2e2; border-radius:14px; background:#ffffff; height:100%;">
                                        <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                                            <div>
                                                <div style="font-size:11px;color:#be123c;font-weight:800;">{{ aduanDash.kode_aduan }} • {{ aduanDash.prioritas.toUpperCase() }}</div>
                                                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ aduanDash.judul }}</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">
                                                    {{ aduanDash.nama_pelapor }} - {{ aduanDash.kelas_pelapor }}
                                                </div>
                                            </div>
                                            <span :class="['badge', aduanDash.status === 'selesai' ? 'badge-success' : 'badge-warning']">
                                                {{ aduanDash.status }}
                                            </span>
                                        </div>
                                        <div style="font-size:12px;color:#334155;margin-top:8px;">
                                            {{ aduanDash.kategori }} • Tahap {{ aduanDash.tahap_aktif.toUpperCase() }} • {{ aduanDash.created_at }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
