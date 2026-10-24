<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    protected $fillable = ['nama', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            // Password otomatis di-hash saat disimpan (NFR-07)
            'password' => 'hashed',
        ];
    }
}
