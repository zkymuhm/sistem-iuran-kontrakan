@extends('layouts.app')

@section('judul', 'Masuk')

@section('isi')
<div class="auth">
    <div>
        <h1 class="judul-aplikasi">Iuran Kontrakan</h1>
        <p class="teks-sekunder">Catat dan bagi kebutuhan bersama kontrakan</p>
    </div>

    {{-- novalidate: validasi dilakukan di server agar pesan kesalahan seragam dan berbahasa Indonesia --}}
    <form method="POST" action="{{ route('login') }}" class="form" novalidate>
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

    <p class="tautan-bawah">Belum punya akun? <a href="{{ route('daftar') }}">Daftar</a></p>
</div>
@endsection