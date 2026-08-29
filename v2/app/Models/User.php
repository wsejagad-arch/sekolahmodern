<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'tbl_pengguna';
    protected $primaryKey = 'no_induk';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'no_induk',
        'password',
        'hak_akses',
        'password_plain',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'name',
        'role',
    ];

    /**
     * Get the password for the user.
     * Overriding standard Laravel authentication.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Helper accessors for role based on hak_akses.
     */
    public function getRoleAttribute()
    {
        return match ((int) $this->hak_akses) {
            1 => 'admin',
            2 => 'teacher',
            3 => 'student',
            4 => 'satpam',
            default => 'unknown',
        };
    }

    /**
     * Helper accessor for user's full name.
     */
    public function getNameAttribute()
    {
        $role = $this->role;
        $no_induk = $this->no_induk;

        if ($role === 'teacher') {
            $guru = \Illuminate\Support\Facades\DB::table('tbl_guru')
                ->where('no_induk', $no_induk)
                ->first();
            return $guru ? $guru->nama_guru : 'Unknown Teacher';
        }

        if ($role === 'student') {
            $siswa = \Illuminate\Support\Facades\DB::table('tbl_siswa')
                ->where('no_induk', $no_induk)
                ->first();
            return $siswa ? $siswa->nama_siswa : 'Unknown Student';
        }

        if ($role === 'admin') {
            $admin = \Illuminate\Support\Facades\DB::table('tbl_admin_pusat')
                ->where('username', $no_induk)
                ->first();
            return $admin ? $admin->nama : 'Administrator';
        }

        return 'User ' . $no_induk;
    }
}
