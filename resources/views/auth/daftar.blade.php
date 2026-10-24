@extends('layouts.app')

@section('judul', 'Daftar Akun')

@section('isi')
<div class="auth">
    <div>
        <h1 class="judul-aplikasi">Daftar Akun</h1>
        <p class="teks-sekunder">Akun dipakai untuk mengenali Anda pada setiap kontrakan</p>
    </div>

    <form method="POST" action="{{ route('daftar') }}" class="form" novalidate>
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

    <p class="tautan-bawah">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</div>
@endsection