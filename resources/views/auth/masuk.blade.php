@extends('layouts.app')

@section('judul', 'Masuk')

@section('kelas-halaman', 'halaman-auth')

@section('isi')
<div class="auth">
    <div class="auth-intro">
        <h1 class="judul-aplikasi">Iuran Kontrakan</h1>
        <p class="teks-sekunder">Catat dan bagi kebutuhan bersama kontrakan</p>
        <ul class="auth-poin hanya-lebar-blok">
            <li>Catat setiap pembelian bersama beserta penalangnya</li>
            <li>Biaya dibagi otomatis kepada penghuni yang ikut menanggung</li>
            <li>Lihat siapa yang sudah membayar dan siapa yang belum</li>
        </ul>
    </div>

    {{-- novalidate: validasi dilakukan di server agar pesan kesalahan seragam dan berbahasa Indonesia --}}
    <form method="POST" action="{{ route('login') }}" class="form auth-form" novalidate>
        @csrf

        <div class="isian @error('email') isian-salah @enderror">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">
            @error('email') <p class="pesan-isian">{{ $message }}</p> @enderror
        </div>

        <div class="isian @error('password') isian-salah @enderror">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password">
            @error('password') <p class="pesan-isian">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="tombol tombol-utama">Masuk</button>
    </form>

    <p class="tautan-bawah auth-tautan">Belum punya akun? <a href="{{ route('daftar') }}">Daftar</a></p>
</div>
@endsection