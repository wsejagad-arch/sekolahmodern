<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;

class LegacyUser implements Authenticatable
{
    public $id;
    public $username;
    public $role; // 'admin', 'guru', 'siswa'
    public $password;
    public $name;
    public $no_induk;

    public function __construct(array $attributes)
    {
        $this->id = $attributes['id'] ?? null;
        $this->username = $attributes['username'] ?? null;
        $this->role = $attributes['role'] ?? null;
        $this->password = $attributes['password'] ?? null;
        $this->name = $attributes['name'] ?? null;
        $this->no_induk = $attributes['no_induk'] ?? null;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        // To ensure global uniqueness across tables, we prefix the ID with the role
        return $this->role . '_' . $this->id;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Not supported
    }

    public function getRememberTokenName()
    {
        return null;
    }
    
    // For Laravel 11 / getAuthPasswordName interface if needed
    public function getAuthPasswordName()
    {
        return 'password';
    }
}
