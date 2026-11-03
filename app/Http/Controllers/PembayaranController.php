<?php

namespace App\Http\Controllers;

use App\Models\DetailPembagian;

class PembayaranController extends Controller
{
    // UC-07 Menyatakan Pembayaran (FR-10).
    // BR-03, NFR-02: hanya pemilik bagian yang dapat menyatakan dirinya sudah membayar.
    public function tandai(DetailPembagian $pembagian)
    {
        $saya = app('anggota.aktif');
        $pembagian->load('transaksi.penalang.pengguna');

        abort_unless(
            (int) $pembagian->id_anggota === (int) $saya->id_anggota,
            403,
            'Anda hanya dapat menandai bagian milik Anda sendiri.'
        );

        // Perubahan dilakukan sekali jalan dengan syarat status masih "belum", sehingga
        // klik ganda atau perubahan dari perangkat lain tidak menimpa status yang sudah berubah.
        $berubah = DetailPembagian::whereKey($pembagian->id_detail)
            ->where('status_bayar', 'belum')
            ->update(['status_bayar' => 'menunggu_konfirmasi', 'ditandai_pada' => now()]);

        if ($berubah === 0) {
            return back()->withErrors(['aksi' => 'Status bagian ini sudah berubah. Muat ulang halaman untuk melihat status terbaru.']);
        }

        $penalang = $pembagian->transaksi->penalang->pengguna->nama;

        return back()->with('berhasil', 'Pembayaran Rp ' . number_format($pembagian->nominal_bagian, 0, ',', '.')
            . " ditandai. Menunggu konfirmasi dari {$penalang}.");
    }

    // UC-16 Mengonfirmasi Penerimaan Pembayaran (FR-25).
    // NFR-02: hanya penalang, yang menerima uang, yang dapat menyatakan pembayaran lunas.
    // BR-15: sistem tidak pernah mengubah status sendiri; lunas hanya terjadi melalui konfirmasi ini.
    public function konfirmasi(DetailPembagian $pembagian)
    {
        $saya = app('anggota.aktif');
        $pembagian->load(['transaksi', 'anggota.pengguna']);

        abort_unless(
            (int) $pembagian->transaksi->id_penalang === (int) $saya->id_anggota,
            403,
            'Hanya penalang yang dapat mengonfirmasi penerimaan pembayaran.'
        );

        $berubah = DetailPembagian::whereKey($pembagian->id_detail)
            ->where('status_bayar', 'menunggu_konfirmasi')
            ->update(['status_bayar' => 'lunas', 'dikonfirmasi_pada' => now()]);

        if ($berubah === 0) {
            return back()->withErrors(['aksi' => 'Pembayaran ini belum ditandai atau sudah dikonfirmasi. Muat ulang halaman untuk melihat status terbaru.']);
        }

        return back()->with('berhasil', 'Penerimaan Rp ' . number_format($pembagian->nominal_bagian, 0, ',', '.')
            . ' dari ' . $pembagian->anggota->pengguna->nama . ' dikonfirmasi. Status menjadi Lunas.');
    }
}
