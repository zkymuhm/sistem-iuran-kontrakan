<?php

namespace App\Http\Controllers;

use App\Services\SaldoService;

class BerandaController extends Controller
{
    public function __invoke(SaldoService $saldo)
    {
        $saya = app('anggota.aktif');

        $saldoSaya = $saldo->saldoKontrakan($saya->id_grup)[$saya->id_anggota] ?? 0;

        return view('beranda', compact('saldoSaya'));
    }
}
