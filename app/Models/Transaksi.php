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

    // BR-19: sudah ada bagian penghuni lain yang menunggu konfirmasi atau lunas.
    // Bagian penalang tidak dihitung karena lunas sejak transaksi dibuat (BR-06).
    public function sudahAdaPembayaran(): bool
    {
        return $this->detail()
            ->where('id_anggota', '!=', $this->id_penalang)
            ->whereIn('status_bayar', ['menunggu_konfirmasi', 'lunas'])
            ->exists();
    }

    // BR-08: penalang atau pencatat; bila keduanya nonaktif, pembuat kontrakan.
    public function bolehDikelolaOleh(Anggota $anggota): bool
    {
        $idAnggota = (int) $anggota->id_anggota;

        if ($idAnggota === (int) $this->id_penalang || $idAnggota === (int) $this->id_pencatat) {
            return true;
        }

        $this->loadMissing(['penalang', 'pencatat', 'grup']);

        $keduanyaNonaktif = $this->penalang->status_aktif !== 'aktif'
            && $this->pencatat->status_aktif !== 'aktif';

        return $keduanyaNonaktif && (int) $this->grup->id_pembuat === (int) $anggota->id_pengguna;
    }
}
