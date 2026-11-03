<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Services\SaldoService;

class SaldoController extends Controller
{
    // UC-05, FR-08: saldo bersih seluruh penghuni (BR-17: transparansi penuh).
    public function __invoke(SaldoService $layanan)
    {
        $saya  = app('anggota.aktif');
        $saldo = $layanan->saldoKontrakan($saya->id_grup);

        // BR-09: penghuni nonaktif tetap ditampilkan selama saldonya belum nol.
        $daftar = Anggota::with('pengguna')
            ->where('id_grup', $saya->id_grup)
            ->get()
            ->map(fn ($a) => ['anggota' => $a, 'saldo' => $saldo[$a->id_anggota] ?? 0])
            ->filter(fn ($b) => $b['anggota']->status_aktif === 'aktif' || $b['saldo'] !== 0)
            ->sortByDesc('saldo')
            ->values();

        return view('saldo', [
            'daftar' => $daftar,
            // Pemeriksaan kebenaran: jumlah seluruh saldo dalam satu kontrakan selalu nol.
            'jumlah' => array_sum($saldo),
        ]);
    }
}
