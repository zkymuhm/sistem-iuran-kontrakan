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

@error('transaksi')
<div class="pesan pesan-gagal">{{ $message }}</div>
@enderror

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

    @if ($bolehKelola)
    <div class="form">
        @if ($terkunci)
        <p class="teks-sekunder">Sudah ada pembayaran, sehingga nominal dan penanggung terkunci dan transaksi tidak dapat dihapus.</p>
        @endif

        <div class="baris-tombol">
            <a href="{{ route('transaksi.edit', $transaksi) }}" class="tombol tombol-sekunder">Ubah</a>

            @unless ($terkunci)
            <form method="POST" action="{{ route('transaksi.destroy', $transaksi) }}"
                data-konfirmasi="Hapus transaksi {{ $transaksi->nama_barang }}? Pembagian biayanya ikut terhapus."
                onsubmit="return confirm(this.dataset.konfirmasi)">
                @csrf
                @method('DELETE')
                <button type="submit" class="tombol tombol-risiko">Hapus</button>
            </form>
            @endunless
        </div>
    </div>
    @endif
</div>
@endsection