{{-- Header dua varian (perbaikan temuan pengujian Modul 6):
     tanpa $kembali  -> halaman utama, judul saja, bottom navigation tampil;
     dengan $kembali -> halaman turunan, tombol kembali di kiri atas.
     Isi slot berupa tombol tindakan utama halaman; tampil di kanan header pada layar lebar. --}}
@props(['judul', 'kembali' => null, 'subjudul' => null])
<header class="kepala">
    @if ($kembali)
    <a href="{{ $kembali }}" class="kembali" aria-label="Kembali"><x-ikon nama="kembali" /></a>
    @endif
    <div class="kepala-teks">
        <h1 class="judul-halaman">{{ $judul }}</h1>
        @if ($subjudul) <p class="teks-sekunder">{{ $subjudul }}</p> @endif
    </div>
    @if (trim($slot) !== '')
    <div class="aksi-kepala">{{ $slot }}</div>
    @endif
</header>
