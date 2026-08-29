<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;

class LegacyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PENTING:
        // Pastikan Anda telah menambahkan koneksi 'legacy' di config/database.php
        // yang mengarah ke database MySQL lama (sekolahku/sijurnal).
        // 
        // Contoh di config/database.php -> connections:
        // 'legacy' => [
        //     'driver' => 'mysql',
        //     'host' => env('DB_LEGACY_HOST', '127.0.0.1'),
        //     'port' => env('DB_LEGACY_PORT', '3306'),
        //     'database' => env('DB_LEGACY_DATABASE', 'sijurnal'),
        //     'username' => env('DB_LEGACY_USERNAME', 'root'),
        //     'password' => env('DB_LEGACY_PASSWORD', ''),
        //     'charset' => 'utf8mb4',
        //     'collation' => 'utf8mb4_unicode_ci',
        //     'prefix' => '',
        //     'strict' => true,
        //     'engine' => null,
        // ],

        $this->command->info('Memulai migrasi dari tabel legacy...');

        // 1. Migrasi Admin
        if (DB::getSchemaBuilder()->hasTable('tbl_admin_pusat')) {
            $admins = DB::table('tbl_admin_pusat')->get();
            foreach ($admins as $admin) {
                User::updateOrCreate(
                    ['username' => $admin->username],
                    [
                        'name' => $admin->nama,
                        'email' => $admin->email ?? $admin->username . '@admin.com',
                        'password' => $admin->password, 
                        'role' => 'admin',
                    ]
                );
            }
        }

        // 2. Migrasi Guru
        if (DB::getSchemaBuilder()->hasTable('tbl_guru')) {
            $teachers = DB::table('tbl_guru')->get();
            foreach ($teachers as $t) {
                // Get old password or use default 'password123'
                // Assuming legacy uses MD5 or plain text, we set a default to be safe, 
                // OR we can keep old password if it was already bcrypt (unlikely).
                // Usually for migration to Laravel, we reset to a default or ask users to reset.
                // In this case, we use 'password' so they can login.
                $user = User::updateOrCreate(
                    ['username' => $t->no_induk],
                    [
                        'name' => $t->nama_guru,
                        'email' => $t->email ?? $t->no_induk . '@guru.com',
                        'password' => Hash::make($t->password ?? 'password'), // use their old password column as text if it exists, but hashed
                        'role' => 'teacher',
                    ]
                );

                Teacher::updateOrCreate(
                    ['nip' => $t->nip_guru ?: $t->no_induk],
                    [
                        'user_id' => $user->id,
                        'name' => $t->nama_guru,
                        'phone' => $t->no_wa,
                        'address' => $t->alamat,
                        'employment_status' => $t->status_kepegawaian,
                        'is_bk' => $t->is_guru_bk ?? false,
                    ]
                );
            }
        }

        // 3. Migrasi Siswa
        if (DB::getSchemaBuilder()->hasTable('tbl_siswa')) {
            $students = DB::table('tbl_siswa')->get();
            foreach ($students as $s) {
                $user = User::updateOrCreate(
                    ['username' => $s->no_induk],
                    [
                        'name' => $s->nama_siswa,
                        'email' => $s->no_induk . '@siswa.com',
                        'password' => Hash::make($s->password ?? 'password'), 
                        'role' => 'student',
                    ]
                );

                Student::updateOrCreate(
                    ['nis' => $s->no_induk],
                    [
                        'user_id' => $user->id,
                        'nisn' => $s->nisn ?? null,
                        'name' => $s->nama_siswa,
                        'phone' => $s->no_wa ?? null,
                        'address' => $s->alamat ?? null,
                        'gender' => $s->jk ?? null,
                        'religion' => $s->agama ?? 'Islam',
                    ]
                );
            }
        }
        
        $this->command->info('Migrasi data legacy selesai.');

        // Create Default Users for Development
        $this->command->info('Membuat user dummy untuk development lokal...');
        User::updateOrCreate(['email' => 'admin@sekolahku.com'], [
            'name' => 'Admin Sekolah',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        
        User::updateOrCreate(['email' => 'guru@sekolahku.com'], [
            'name' => 'Guru Teladan',
            'username' => 'guru01',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        User::updateOrCreate(['email' => 'siswa@sekolahku.com'], [
            'name' => 'Siswa Pintar',
            'username' => 'siswa01',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        User::updateOrCreate(['username' => '199303012022211013'], [
            'name' => 'Bapak/Ibu Guru Test',
            'email' => '199303012022211013@guru.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);
    }
}
