@extends('layouts.app')

@section('judul', 'Gabung Kontrakan')

@section('isi')
@php($pesanGalat = $galat ?? $errors->first('kode'))

<header class="kepala">
    <a href="{{ route('pilihan') }}" class="kembali" aria-label="Kembali">←</a>
    <h1 class="judul-halaman">Gabung Kontrakan</h1>
</header>

<div class="tumpukan">
    <form method="GET" action="{{ route('kontrakan.gabung') }}" class="form" novalidate>
        <div class="isian isian-kode {{ $pesanGalat ? 'isian-salah' : '' }}">
            <label for="kode">Kode Invitasi</label>
            <input id="kode" type="text" name="kode" value="{{ $kode }}" maxlength="6"
                autocapitalize="characters" autocomplete="off">
            @if ($pesanGalat) <p class="pesan-isian">{{ $pesanGalat }}</p> @endif
        </div>
        <button type="submit" class="tombol tombol-sekunder">Cari</button>
    </form>

    @if ($grup)
    <div class="kartu">
        <span class="teks-sekunder">Kontrakan ditemukan</span>
        <span class="kartu-judul">{{ $grup->nama_grup }}</span>
        <span class="teks-sekunder">{{ $jumlahPenghuni }} penghuni aktif</span>
    </div>

    <form method="POST" action="{{ route('kontrakan.gabung') }}">
        @csrf
        <input type="hidden" name="kode" value="{{ $grup->kode_invitasi }}">
        <button type="submit" class="tombol tombol-utama">Gabung</button>
    </form>
    @endif
</div>
@endsection