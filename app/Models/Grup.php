<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grup extends Model
{
    protected $table = 'grup';
    protected $primaryKey = 'id_grup';
    public $timestamps = false;

    protected $fillable = ['nama_grup', 'kode_invitasi', 'id_pembuat', 'status_aktif'];

    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class, 'id_grup', 'id_grup');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pembuat', 'id_pengguna');
    }

    // BR-11: kode unik 6 karakter. Huruf O, I dan angka 0, 1 tidak dipakai
    // karena mudah tertukar saat kode dibacakan atau diketik ulang.
    public static function buatKodeInvitasi(): string
    {
        $karakter = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $kode = '';
            for ($i = 0; $i < 6; $i++) {
                $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
            }
        } while (static::where('kode_invitasi', $kode)->exists());

        return $kode;
    }
}
