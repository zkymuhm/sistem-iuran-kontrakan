@extends('layouts.app')

@section('judul', 'Arsip Kontrakan')

@section('isi')
<x-kepala judul="Arsip Kontrakan" :kembali="$punyaAktif ? route('kontrakan.index') : route('pilihan')" />

<div class="tumpukan">
    <p class="teks-sekunder">Arsip dibuka dalam mode baca. Anda tetap dapat menandai pembayaran dan mengonfirmasi penerimaan yang menyangkut diri Anda.</p>

    @forelse ($daftar as $a)
    @php($dibubarkan = $a->grup->status_aktif !== 'aktif')
    <div class="kartu">
        <span class="kartu-judul">{{ $a->grup->nama_grup }}</span>
        <span class="teks-sekunder">{{ $dibubarkan ? 'Dibubarkan' : 'Anda sudah tidak aktif di kontrakan ini' }}</span>

        <form method="POST" action="{{ route('arsip.buka', $a->id_grup) }}">
            @csrf
            <button type="submit" class="tombol tombol-sekunder">Buka</button>
        </form>

        {{-- UC-19, FR-23: hanya pembuat, dan hanya bila belum tergabung pada kontrakan aktif lain (BR-10) --}}
        @if ($dibubarkan && (int) $a->grup->id_pembuat === (int) $a->id_pengguna && ! $punyaAktif)
        <form method="POST" action="{{ route('arsip.aktifkan', $a->id_grup) }}"
            data-judul="Aktifkan Kembali"
            data-konfirmasi="Aktifkan kembali {{ $a->grup->nama_grup }}? Kontrakan memperoleh kode invitasi baru. Penghuni lain bergabung kembali memakai kode tersebut."
            data-tombol="Aktifkan">
            @csrf
            <button type="submit" class="tombol tombol-utama">Aktifkan Kembali</button>
        </form>
        @endif
    </div>
    @empty
    <div class="kartu teks-sekunder">Belum ada arsip kontrakan.</div>
    @endforelse
</div>
@endsection
