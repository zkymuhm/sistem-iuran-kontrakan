{{-- Header dua varian (perbaikan temuan pengujian Modul 6):
     tanpa $kembali  -> halaman utama, judul saja, bottom navigation tampil;
     dengan $kembali -> halaman turunan, tombol kembali di kiri atas. --}}
@props(['judul', 'kembali' => null])
<header class="kepala">
    @if ($kembali)
    <a href="{{ $kembali }}" class="kembali" aria-label="Kembali"><x-ikon nama="kembali" /></a>
    @endif
    <h1 class="judul-halaman">{{ $judul }}</h1>
    {{ $slot }}
</header>
