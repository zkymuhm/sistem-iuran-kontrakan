@extends('layouts.app')

@section('judul', 'Pilihan Kontrakan')

@section('isi')
<div class="tumpukan">
    <div>
        <h1 class="judul-halaman">Halo, {{ auth()->user()->nama }}</h1>
        <p class="teks-sekunder">Anda belum tergabung pada kontrakan mana pun.</p>
    </div>

    <div class="form">
        <a href="{{ route('kontrakan.buat') }}" class="kartu">
            <span class="kartu-judul">Buat Kontrakan</span>
            <span class="teks-sekunder">Untuk penghuni pertama yang mulai mencatat iuran.</span>
        </a>
        <a href="{{ route('kontrakan.gabung') }}" class="kartu">
            <span class="kartu-judul">Gabung Kontrakan</span>
            <span class="teks-sekunder">Masukkan kode invitasi dari penghuni lain.</span>
        </a>
    </div>

    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="tombol tombol-sekunder">Keluar</button>
    </form>
</div>
@endsection