@extends('layouts.app')

@section('judul', 'Riwayat Transaksi')

@section('isi')
<header class="kepala">
    <a href="{{ route('beranda') }}" class="kembali" aria-label="Kembali">←</a>
    <h1 class="judul-halaman">Riwayat Transaksi</h1>
</header>

<div class="form">
    <a href="{{ route('transaksi.create') }}" class="tombol tombol-utama">Catat Transaksi</a>

    @forelse ($daftar as $t)
    @php($jumlahLunas = $t->detail->where('status_bayar', 'lunas')->count())
    <a href="{{ route('transaksi.show', $t) }}" class="kartu">
        <div class="baris">
            <span class="kartu-judul">{{ $t->nama_barang }}</span>
            <span class="kartu-judul nominal">@rupiah($t->nominal)</span>
        </div>
        <div class="baris teks-sekunder">
            <span>{{ $t->tanggal->translatedFormat('j F Y') }} · {{ $t->penalang->pengguna->nama }}</span>
            <span>{{ $jumlahLunas }} dari {{ $t->detail->count() }} lunas</span>
        </div>
    </a>
    @empty
    <div class="kartu teks-sekunder">Belum ada transaksi. Tekan Catat Transaksi untuk mencatat pembelian pertama.</div>
    @endforelse
</div>
@endsection