<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anggota extends Model
{
    protected $table = 'anggota';
    protected $primaryKey = 'id_anggota';
    public $timestamps = false;

    protected $fillable = ['id_grup', 'id_pengguna', 'status_aktif', 'bergabung_pada'];

    public function grup(): BelongsTo
    {
        return $this->belongsTo(Grup::class, 'id_grup', 'id_grup');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    // Keanggotaan aktif milik pengguna pada kontrakan yang masih aktif (BR-10).
    public static function aktifMilik(int $idPengguna): ?self
    {
        return static::with('grup')
            ->where('id_pengguna', $idPengguna)
            ->where('status_aktif', 'aktif')
            ->whereHas('grup', fn($q) => $q->where('status_aktif', 'aktif'))
            ->first();
    }
}
