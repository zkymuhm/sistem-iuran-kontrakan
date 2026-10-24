@extends('layouts.app')

@section('judul', 'Beranda')

@section('isi')

<div class="tumpukan">
    <div>
        <p class="teks-sekunder">Halo, {{ auth()->user()->nama }}</p>
        <h1 class="judul-halaman">{{ $anggotaAktif->grup->nama_grup }}</h1>
    </div>

    <div class="kartu">
        <span class="teks-sekunder">Saldo Anda</span>
        <span class="judul-aplikasi nominal {{ $saldoSaya > 0 ? 'piutang' : ($saldoSaya < 0 ? 'hutang' : '') }}">
            {{ $saldoSaya > 0 ? '+' : ($saldoSaya < 0 ? '−' : '') }}@rupiah(abs($saldoSaya))
        </span>
        <span class="teks-sekunder">
            @if ($saldoSaya > 0)
            Piutang: penghuni lain masih berhutang kepada Anda
            @elseif ($saldoSaya < 0)
                Hutang: Anda masih berhutang kepada penghuni lain
                @else
                Tidak ada hutang maupun piutang
                @endif
                </span>
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