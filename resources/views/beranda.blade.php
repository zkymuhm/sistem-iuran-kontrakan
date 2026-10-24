@extends('layouts.app')

@section('judul', 'Beranda')

@section('isi')
<div class="tumpukan">
    <div>
        <p class="teks-sekunder">Halo, {{ auth()->user()->nama }}</p>
        <h1 class="judul-halaman">{{ $anggotaAktif->grup->nama_grup }}</h1>
    </div>

    <div class="form">
        <a href="{{ route('transaksi.create') }}" class="tombol tombol-utama">Catat Transaksi</a>
        <a href="{{ route('transaksi.index') }}" class="tombol tombol-sekunder">Riwayat Transaksi</a>
    </div>

    <div class="kartu">
        <span class="teks-sekunder">Kode invitasi</span>
        <span class="kode">{{ $anggotaAktif->grup->kode_invitasi }}</span>
    </div>

    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="tombol tombol-sekunder">Keluar</button>
    </form>
</div>
@endsection