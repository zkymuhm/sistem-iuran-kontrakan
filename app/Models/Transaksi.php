<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $primaryKey = 'id_transaksi';
    public $timestamps = false;

    protected $fillable = [
        'id_grup',
        'id_penalang',
        'id_pencatat',
        'nama_barang',
        'nominal',
        'tanggal',
        'foto_bukti',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'tanggal' => 'date',
        ];
    }

    // NFR-08: setiap query hanya melihat transaksi kontrakan yang sedang dibuka.
    protected static function booted(): void
    {
        static::addGlobalScope('kontrakan', function (Builder $query) {
            if (app()->bound('anggota.aktif')) {
                $query->where('transaksi.id_grup', app('anggota.aktif')->id_grup);
            } elseif (! app()->runningInConsole()) {
                // Gagal tertutup: permintaan web tanpa kontrakan yang ditetapkan tidak melihat data apa pun.
                $query->whereRaw('1 = 0');
            }
        });
    }

    public function grup(): BelongsTo
    {
        return $this->belongsTo(Grup::class, 'id_grup', 'id_grup');
    }

    public function penalang(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_penalang', 'id_anggota');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_pencatat', 'id_anggota');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPembagian::class, 'id_transaksi', 'id_transaksi');
    }
}
