@extends('layouts.app')

@section('judul', 'Daftar Akun')

@section('kelas-halaman', 'halaman-auth')

@section('isi')
<div class="auth">
    <div class="auth-intro">
        <h1 class="judul-aplikasi">Daftar Akun</h1>
        <p class="teks-sekunder">Akun dipakai untuk mengenali Anda pada setiap kontrakan</p>
        <ul class="auth-poin hanya-lebar-blok">
            <li>Catat setiap pembelian bersama beserta penalangnya</li>
            <li>Biaya dibagi otomatis kepada penghuni yang ikut menanggung</li>
            <li>Lihat siapa yang sudah membayar dan siapa yang belum</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('daftar') }}" class="form auth-form" novalidate>
        @csrf

        <div class="isian @error('nama') isian-salah @enderror">
            <label for="nama">Nama</label>
            <input id="nama" type="text" name="nama" value="{{ old('nama') }}" autocomplete="name">
            @error('nama') <p class="pesan-isian">{{ $message }}</p> @enderror
        </div>

        <div class="isian @error('email') isian-salah @enderror">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">
            @error('email') <p class="pesan-isian">{{ $message }}</p> @enderror
        </div>

        <div class="isian @error('password') isian-salah @enderror">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="new-password">
            @error('password') <p class="pesan-isian">{{ $message }}</p> @enderror
        </div>

        <div class="isian">
            <label for="password_confirmation">Konfirmasi Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
        </div>

        <button type="submit" class="tombol tombol-utama">Daftar</button>
    </form>

    <p class="tautan-bawah auth-tautan">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</div>
@endsection