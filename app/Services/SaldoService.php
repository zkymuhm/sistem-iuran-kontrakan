<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SaldoService
{
    /**
     * FR-08. Saldo = bagian orang lain yang belum lunas pada transaksi yang ia talangi
     *              − bagiannya sendiri yang belum lunas pada transaksi orang lain.
     * Positif berarti piutang, negatif berarti hutang. Hanya status lunas yang mengurangi
     * kewajiban, sehingga menunggu konfirmasi tetap dihitung sebagai hutang (BR-15).
     * Kontrakan disebut eksplisit karena query builder tidak melewati global scope model.
     *
     * @return array<int, int> id_anggota => saldo
     */
    public function saldoKontrakan(int $idGrup): array
    {
        $belumLunas = DB::table('detail_pembagian as d')
            ->join('transaksi as t', 't.id_transaksi', '=', 'd.id_transaksi')
            ->where('t.id_grup', $idGrup)
            ->where('d.status_bayar', '!=', 'lunas')
            ->whereColumn('d.id_anggota', '!=', 't.id_penalang');

        $piutang = (clone $belumLunas)
            ->selectRaw('t.id_penalang as id, SUM(d.nominal_bagian) as total')
            ->groupBy('t.id_penalang')
            ->pluck('total', 'id');

        $hutang = (clone $belumLunas)
            ->selectRaw('d.id_anggota as id, SUM(d.nominal_bagian) as total')
            ->groupBy('d.id_anggota')
            ->pluck('total', 'id');

        $saldo = [];
        foreach (DB::table('anggota')->where('id_grup', $idGrup)->pluck('id_anggota') as $id) {
            $saldo[$id] = (int) ($piutang[$id] ?? 0) - (int) ($hutang[$id] ?? 0);
        }

        return $saldo;
    }
}
