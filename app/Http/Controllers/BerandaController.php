<?php

namespace App\Http\Controllers;

use App\Models\DetailPembagian;
use App\Models\Transaksi;
use App\Services\SaldoService;

class BerandaController extends Controller
{
    // Dashboard (Kegiatan 6 Modul 8). Seluruh angka dihitung dari data transaksi, bukan disimpan terpisah.
    public function __invoke(SaldoService $saldo)
    {
        $saya = app('anggota.aktif');
        $id   = (int) $saya->id_anggota;

        $saldoSaya = $saldo->saldoKontrakan($saya->id_grup)[$id] ?? 0;

        // FR-03, FR-09: bagian saya yang belum lunas pada transaksi orang lain, beserta kepada siapa dibayarkan.
        $kewajiban = DetailPembagian::with(['transaksi.penalang.pengguna'])
            ->where('id_anggota', $id)
            ->where('status_bayar', '!=', 'lunas')
            ->whereHas('transaksi', fn ($q) => $q->where('id_penalang', '!=', $id))
            ->get()
            ->sortByDesc(fn ($d) => $d->transaksi->tanggal->format('Ymd') . sprintf('%010d', $d->id_transaksi))
            ->values();

        // UC-16: pembayaran kepada saya yang menunggu konfirmasi.
        $menunggu = DetailPembagian::with(['transaksi', 'anggota.pengguna'])
            ->where('status_bayar', 'menunggu_konfirmasi')
            ->whereHas('transaksi', fn ($q) => $q->where('id_penalang', $id))
            ->orderBy('ditandai_pada')
            ->get();

        $terbaru = Transaksi::with(['penalang.pengguna', 'detail'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->limit(5)
            ->get();

        return view('beranda', [
            'saldoSaya'      => $saldoSaya,
            'kewajiban'      => $kewajiban,
            'menunggu'       => $menunggu,
            'terbaru'        => $terbaru,
            // Indikator: total yang masih harus saya bayar (belum dan menunggu konfirmasi, BR-15).
            'totalKewajiban' => $kewajiban->sum('nominal_bagian'),
            'totalMenunggu'  => $menunggu->sum('nominal_bagian'),
        ]);
    }
}
