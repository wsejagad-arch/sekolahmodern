<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $table = 'tbl_guru';
    protected $primaryKey = 'no_induk';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'no_induk', 'no_induk');
    }
}
