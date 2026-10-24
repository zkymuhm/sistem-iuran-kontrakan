@extends('layouts.app')

@section('judul', $transaksi->nama_barang)

@section('isi')
@php
$labelStatus = ['belum' => 'Belum', 'menunggu_konfirmasi' => 'Menunggu Konfirmasi', 'lunas' => 'Lunas'];
$jumlahLunas = $transaksi->detail->where('status_bayar', 'lunas')->count();
$belumDiterima = $transaksi->detail->where('status_bayar', '!=', 'lunas')->sum('nominal_bagian');
@endphp

<header class="kepala">
    <a href="{{ route('transaksi.index') }}" class="kembali" aria-label="Kembali">←</a>
    <h1 class="judul-halaman">Detail Transaksi</h1>
</header>

<div class="tumpukan">
    <div class="kartu">
        <span class="judul-halaman">{{ $transaksi->nama_barang }}</span>
        <span class="judul-aplikasi nominal">@rupiah($transaksi->nominal)</span>
        <span class="teks-sekunder">{{ $transaksi->tanggal->translatedFormat('j F Y') }}</span>
        <span class="teks-sekunder">Penalang: {{ $transaksi->penalang->pengguna->nama }}</span>
        <span class="teks-sekunder">Dicatat oleh: {{ $transaksi->pencatat->pengguna->nama }}</span>
    </div>

    <div class="kartu">
        <div class="baris">
            <span class="kartu-judul">Pembagian Biaya</span>
            <span class="teks-sekunder">{{ $jumlahLunas }} dari {{ $transaksi->detail->count() }} Lunas</span>
        </div>
        <span class="teks-sekunder">Belum diterima penalang: <span class="nominal">@rupiah($belumDiterima)</span></span>

        <div class="daftar-baris">
            @foreach ($transaksi->detail as $d)
            <div class="baris">
                <div>
                    <div>{{ $d->anggota->pengguna->nama }}</div>
                    <div class="teks-sekunder nominal">@rupiah($d->nominal_bagian)</div>
                </div>
                <span class="chip chip-{{ $d->status_bayar }}">{{ $labelStatus[$d->status_bayar] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection