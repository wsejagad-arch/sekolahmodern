<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use App\Models\LegacyUser;
use Illuminate\Support\Facades\Hash;

class LegacyUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        // identifier is "role_id"
        $parts = explode('_', $identifier, 2);
        if (count($parts) !== 2) return null;
        
        $role = $parts[0];
        $id = $parts[1];

        if ($role === 'admin') {
            $user = DB::table('tbl_user')->where('id_user', $id)->first();
            if ($user) {
                return new LegacyUser([
                    'id' => $user->id_user,
                    'username' => $user->username,
                    'role' => 'admin',
                    'password' => $user->password,
                    'name' => $user->nama,
                ]);
            }
        } elseif ($role === 'guru') {
            $guru = DB::table('tbl_guru')
                ->leftJoin('tbl_pengguna', 'tbl_guru.no_induk', '=', 'tbl_pengguna.no_induk')
                ->where('tbl_guru.id_guru', $id) // or no_induk
                ->first();
                
            if ($guru) {
                return new LegacyUser([
                    'id' => $guru->id_guru,
                    'username' => $guru->no_induk,
                    'role' => 'guru',
                    'password' => $guru->password,
                    'name' => $guru->nama_guru,
                    'no_induk' => $guru->no_induk
                ]);
            }
        } elseif ($role === 'siswa') {
            $siswa = DB::table('tbl_siswa')
                ->leftJoin('tbl_pengguna', 'tbl_siswa.no_induk', '=', 'tbl_pengguna.no_induk')
                ->where('tbl_siswa.id_siswa', $id)
                ->first();
                
            if ($siswa) {
                return new LegacyUser([
                    'id' => $siswa->id_siswa,
                    'username' => $siswa->no_induk,
                    'role' => 'siswa',
                    'password' => $siswa->password,
                    'name' => $siswa->nama_siswa,
                    'no_induk' => $siswa->no_induk
                ]);
            }
        }

        return null;
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token)
    {
        // Not implemented
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        if (empty($credentials['login'])) {
            return null;
        }

        $login = $credentials['login'];

        // 1. Try Admin (tbl_user)
        $admin = DB::table('tbl_user')->where('username', $login)->first();
        if ($admin) {
            return new LegacyUser([
                'id' => $admin->id_user,
                'username' => $admin->username,
                'role' => 'admin',
                'password' => $admin->password,
                'name' => $admin->nama,
            ]);
        }

        // 2. Try Guru (tbl_guru)
        $login_clean = ltrim($login, '0');
        $guru = DB::table('tbl_guru')
            ->leftJoin('tbl_pengguna', 'tbl_guru.no_induk', '=', 'tbl_pengguna.no_induk')
            ->where(function($q) use ($login, $login_clean) {
                $q->where('tbl_guru.no_induk', $login)
                  ->orWhereRaw("TRIM(LEADING '0' FROM tbl_guru.no_induk) = ?", [$login_clean])
                  ->orWhere('tbl_guru.nama_guru', 'like', "%{$login}%");
            })
            ->where(function($q) {
                $q->where('tbl_guru.status', 'Aktif')
                  ->orWhere('tbl_guru.status', 'aktif')
                  ->orWhereNull('tbl_guru.status')
                  ->orWhere('tbl_guru.status', '');
            })
            ->first();

        if ($guru) {
            return new LegacyUser([
                'id' => $guru->id_guru ?? $guru->no_induk, // Some tables might not have id_guru
                'username' => $guru->no_induk,
                'role' => 'guru',
                'password' => $guru->password,
                'name' => $guru->nama_guru,
                'no_induk' => $guru->no_induk
            ]);
        }

        // 3. Try Siswa (tbl_siswa)
        $siswa = DB::table('tbl_siswa')
            ->leftJoin('tbl_pengguna', 'tbl_siswa.no_induk', '=', 'tbl_pengguna.no_induk')
            ->where(function($q) use ($login, $login_clean) {
                $q->where('tbl_siswa.no_induk', $login)
                  ->orWhereRaw("TRIM(LEADING '0' FROM tbl_siswa.no_induk) = ?", [$login_clean])
                  ->orWhere('tbl_siswa.nama_siswa', 'like', "%{$login}%");
            })
            ->where(function($q) {
                $q->where('tbl_siswa.status', 'Aktif')
                  ->orWhere('tbl_siswa.status', 'aktif')
                  ->orWhereNull('tbl_siswa.status')
                  ->orWhere('tbl_siswa.status', '');
            })
            ->first();

        if ($siswa) {
            return new LegacyUser([
                'id' => $siswa->id_siswa ?? $siswa->no_induk,
                'username' => $siswa->no_induk,
                'role' => 'siswa',
                'password' => $siswa->password,
                'name' => $siswa->nama_siswa,
                'no_induk' => $siswa->no_induk
            ]);
        }

        return null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials)
    {
        $rawPassword = $credentials['password'];
        $storedHash = $user->getAuthPassword();
        $noInduk = $user->no_induk ?? '';

        if ($storedHash === null || $storedHash === '') {
            $storedHash = md5('12345');
        }

        // Bcrypt check
        if (preg_match('/^\$2[aby]\$/', $storedHash)) {
            return Hash::check($rawPassword, $storedHash);
        }

        // MD5 check
        $rawMd5 = md5($rawPassword);
        if (hash_equals($rawMd5, $storedHash)) return true;

        $rawTrim = ltrim($rawPassword, '0');
        if ($rawTrim !== '' && hash_equals(md5($rawTrim), $storedHash)) return true;
        if (hash_equals(md5('0' . $rawPassword), $storedHash)) return true;
        
        // Default password bypass (12345 or NISN/NIP)
        if ($noInduk !== '') {
            $noIndukClean = ltrim($noInduk, '0');
            if ($rawPassword === '12345' && (hash_equals(md5($noInduk), $storedHash) || hash_equals(md5($noIndukClean), $storedHash))) return true;
            if (($rawPassword === $noInduk || $rawPassword === $noIndukClean || $rawTrim === $noIndukClean) && (hash_equals(md5('12345'), $storedHash) || hash_equals(md5($noInduk), $storedHash) || hash_equals(md5($noIndukClean), $storedHash))) return true;
        }

        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {
        // For legacy systems with MD5, we might want to rehash to Bcrypt later.
        // For now, we do nothing to prevent breaking the old native PHP system
        // that still relies on MD5.
    }
}
