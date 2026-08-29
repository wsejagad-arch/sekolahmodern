<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'role' => request()->user()->role,
        'aduanDashboardCount' => 3,
        'aduanDashboardOpen' => 2,
        'aduanDashboardRows' => [
            [
                'kode_aduan' => 'ADU-20260728-001',
                'prioritas' => 'tinggi',
                'judul' => 'Fasilitas Toilet Rusak',
                'nama_pelapor' => 'Siswa A',
                'kelas_pelapor' => 'XII IPA 1',
                'status' => 'baru',
                'kategori' => 'Sarana Prasarana',
                'tahap_aktif' => 'tahap 1',
                'created_at' => '2026-07-28 08:00:00'
            ],
            [
                'kode_aduan' => 'ADU-20260728-002',
                'prioritas' => 'normal',
                'judul' => 'Kehilangan Barang',
                'nama_pelapor' => 'Siswa B',
                'kelas_pelapor' => 'XI IPS 2',
                'status' => 'proses',
                'kategori' => 'Keamanan',
                'tahap_aktif' => 'tahap 2',
                'created_at' => '2026-07-28 09:30:00'
            ]
        ]
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('students', App\Http\Controllers\StudentController::class);
});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Temporary route to create an admin user for hosting
Route::get('/setup-admin', function () {
    try {
        // Create admin user in tbl_pengguna
        $admin = App\Models\User::updateOrCreate(
            ['no_induk' => 'admin'],
            [
                'password' => Illuminate\Support\Facades\Hash::make('admin123'),
                'hak_akses' => 1,
                'password_plain' => 'admin123',
            ]
        );

        // Make sure tbl_admin_pusat exists and insert name if needed
        Illuminate\Support\Facades\DB::table('tbl_admin_pusat')->updateOrInsert(
            ['username' => 'admin'],
            ['nama' => 'Administrator Utama']
        );

        return 'Admin user created successfully! Username: admin, Password: admin123. Please remove this route for security.';
    } catch (\Exception $e) {
        return 'Error creating admin: ' . $e->getMessage();
    }
});
