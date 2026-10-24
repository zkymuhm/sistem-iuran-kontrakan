<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPembagian extends Model
{
    protected $table = 'detail_pembagian';
    protected $primaryKey = 'id_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_transaksi',
        'id_anggota',
        'nominal_bagian',
        'status_bayar',
        'ditandai_pada',
        'dikonfirmasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'nominal_bagian'    => 'integer',
            'ditandai_pada'     => 'datetime',
            'dikonfirmasi_pada' => 'datetime',
        ];
    }

    // NFR-08: dibatasi melalui transaksi induknya, yang sudah memiliki global scope kontrakan.
    protected static function booted(): void
    {
        static::addGlobalScope('kontrakan', function (Builder $query) {
            if (app()->bound('anggota.aktif') || ! app()->runningInConsole()) {
                $query->whereHas('transaksi');
            }
        });
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id_transaksi');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_anggota', 'id_anggota');
    }
}
