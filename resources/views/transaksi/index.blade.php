@extends('layouts.app')

@section('judul', 'Riwayat Transaksi')
@section('navigasi', 'transaksi')

@section('isi')
<x-kepala judul="Riwayat Transaksi">
    @if ($bisaTulis)
    <a href="{{ route('transaksi.create') }}" class="tombol tombol-utama hanya-hp">Catat Transaksi</a>
    @endif
</x-kepala>

<div class="form">
    @if ($bisaTulis)
    <a href="{{ route('transaksi.create') }}" class="tombol tombol-utama">Catat Transaksi</a>
    @endif

    {{-- Filter memakai GET agar hasil penyaringan dapat dimuat ulang dan dibagikan sebagai tautan --}}
    <form method="GET" action="{{ route('transaksi.index') }}" class="saring">
        <div class="isian">
            <label for="cari">Cari nama barang</label>
            <input id="cari" type="search" name="cari" value="{{ $cari }}" placeholder="Contoh: galon">
        </div>
        <div class="saring-baris">
            <div class="isian">
                <label for="bulan">Bulan</label>
                <input id="bulan" type="month" name="bulan" value="{{ $bulan }}">
            </div>
            <div class="isian">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    <option value="belum_lunas" @selected($status === 'belum_lunas')>Belum lunas</option>
                    <option value="lunas" @selected($status === 'lunas')>Lunas semua</option>
                </select>
            </div>
        </div>
        <div class="baris-tombol">
            <button type="submit" class="tombol tombol-sekunder">Terapkan</button>
            @if ($disaring)
            <a href="{{ route('transaksi.index') }}" class="tombol tombol-sekunder">Hapus Filter</a>
            @endif
        </div>
    </form>

    @if ($disaring)
    <p class="teks-sekunder">{{ $daftar->count() }} transaksi ditemukan · total <span class="nominal">@rupiah($daftar->sum('nominal'))</span></p>
    @endif

    <div class="daftar-kartu">
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
    <div class="kartu teks-sekunder">
        {{ $disaring ? 'Tidak ada transaksi yang sesuai dengan filter.' : 'Belum ada transaksi. Tekan Catat Transaksi untuk mencatat pembelian pertama.' }}
    </div>
    @endforelse
    </div>
</div>
@endsection
