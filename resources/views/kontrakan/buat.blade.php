@extends('layouts.app')

@section('judul', 'Buat Kontrakan')

@section('isi')
<header class="kepala">
    <a href="{{ route('pilihan') }}" class="kembali" aria-label="Kembali">←</a>
    <h1 class="judul-halaman">Buat Kontrakan</h1>
</header>

<form method="POST" action="{{ route('kontrakan.buat') }}" class="form" novalidate>
    @csrf

    <div class="isian @error('nama_grup') isian-salah @enderror">
        <label for="nama_grup">Nama Kontrakan</label>
        <input id="nama_grup" type="text" name="nama_grup" value="{{ old('nama_grup') }}">
        @error('nama_grup') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="kartu">
        <span class="teks-sekunder">Sebagai pembuat, Anda dapat menonaktifkan penghuni dan membubarkan kontrakan. Kode invitasi dibuat otomatis setelah kontrakan tersimpan.</span>
    </div>

    <button type="submit" class="tombol tombol-utama">Buat Kontrakan</button>
    <a href="{{ route('pilihan') }}" class="tombol tombol-sekunder">Batal</a>
</form>
@endsection