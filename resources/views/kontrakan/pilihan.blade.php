@extends('layouts.app')

@section('judul', 'Pilihan Kontrakan')

@section('isi')
<div class="auth">
    <div>
        <h1 class="judul-halaman">Halo, {{ auth()->user()->nama }}</h1>
        <p class="teks-sekunder">{{ auth()->user()->email }}</p>
    </div>

    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="tombol tombol-sekunder">Keluar</button>
    </form>
</div>
@endsection