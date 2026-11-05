@extends('layouts.app')

@section('judul', 'Kontrakan')
@section('navigasi', 'kontrakan')

@section('isi')
@php($rp = fn ($n) => 'Rp ' . number_format(abs((int) $n), 0, ',', '.'))

<x-kepala :judul="$grup->nama_grup"
    :subjudul="$aktif->count() . ' penghuni aktif' . ($grup->status_aktif !== 'aktif' ? ' · Dibubarkan (arsip)' : '')" />

{{-- Di layar lebar: kiri kode invitasi dan tindakan, kanan daftar penghuni. Urutan di HP tidak berubah. --}}
<div class="tumpukan kontrakan">
    {{-- UC-15, FR-20: kode invitasi hanya berlaku selama kontrakan aktif (BR-11) --}}
    @if ($bisaTulis)
    <div class="kartu kartu-kode area-kode">
        <span class="teks-sekunder">Kode invitasi</span>
        <span class="kode" id="kode-invitasi">{{ $grup->kode_invitasi }}</span>
        <span class="teks-sekunder">Bagikan kode ini kepada calon penghuni. Mereka memasukkannya pada menu Gabung Kontrakan.</span>
        <button type="button" class="tombol tombol-sekunder" id="tombol-salin"
            data-kode="{{ $grup->kode_invitasi }}"
            data-pesan="Gabung ke {{ $grup->nama_grup }} di aplikasi Iuran Kontrakan dengan kode invitasi {{ $grup->kode_invitasi }}">Salin</button>
    </div>
    @endif

    <div class="area-penghuni">
    <section class="bagian">
        <h3 class="kartu-judul">Penghuni Aktif</h3>
        <div class="kartu">
            <div class="daftar-baris">
                @forelse ($aktif as $a)
                {{-- Di HP tombol Nonaktifkan turun ke baris sendiri di bawah nama, agar nama dan tanggal tidak terhimpit --}}
                <div class="baris baris-penghuni">
                    <div class="orang">
                        <x-avatar :nama="$a->pengguna->nama" />
                        <div class="orang-teks">
                            <div>{{ $a->pengguna->nama }}{{ (int) $a->id_anggota === (int) $anggotaAktif->id_anggota ? ' (Anda)' : '' }}</div>
                            @if ((int) $grup->id_pembuat === (int) $a->id_pengguna)
                            <div class="teks-sekunder">Pembuat kontrakan</div>
                            @endif
                            <div class="teks-sekunder">Bergabung {{ \Illuminate\Support\Carbon::parse($a->bergabung_pada)->translatedFormat('j M Y') }}</div>
                        </div>
                    </div>
                    {{-- UC-09: hanya pembuat, dan tidak untuk dirinya sendiri --}}
                    @if ($bisaTulis && $sayaPembuat && (int) $a->id_anggota !== (int) $anggotaAktif->id_anggota)
                    <form method="POST" action="{{ route('kontrakan.nonaktifkan', $a->id_anggota) }}"
                        data-judul="Nonaktifkan Penghuni"
                        data-konfirmasi="Nonaktifkan {{ $a->pengguna->nama }}? Ia tidak dapat lagi dipilih sebagai penanggung transaksi baru. Riwayat dan saldonya tetap tersimpan, dan ia tetap dapat menuntaskan pembayaran."
                        data-tombol="Nonaktifkan" data-risiko>
                        @csrf
                        <button type="submit" class="tombol tombol-risiko tombol-kecil">Nonaktifkan</button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="teks-sekunder">Tidak ada penghuni aktif.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if ($nonaktif->isNotEmpty())
    <section class="bagian">
        <h3 class="kartu-judul">Penghuni Nonaktif</h3>
        <div class="kartu">
            <div class="daftar-baris">
                @foreach ($nonaktif as $a)
                <div class="baris">
                    <div class="orang">
                        <x-avatar :nama="$a->pengguna->nama" />
                        <div>{{ $a->pengguna->nama }}{{ (int) $a->id_anggota === (int) $anggotaAktif->id_anggota ? ' (Anda)' : '' }}</div>
                    </div>
                    <span class="label-nonaktif">Nonaktif</span>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    </div>

    <a href="{{ route('arsip.index') }}" class="kartu area-arsip">
        <span class="kartu-judul">Arsip Kontrakan</span>
        <span class="teks-sekunder">Kontrakan yang sudah dibubarkan atau yang pernah Anda tinggalkan.</span>
    </a>

    @if ($bisaTulis)
    <section class="bagian area-anggota">
        <h3 class="kartu-judul">Keanggotaan</h3>

        {{-- UC-17, FR-21 --}}
        <form method="POST" action="{{ route('kontrakan.keluar') }}"
            data-judul="Keluar dari Kontrakan"
            data-konfirmasi="Keluar dari {{ $grup->nama_grup }}? {{ $saldoSaya !== 0 ? 'Saldo Anda masih ' . ($saldoSaya > 0 ? 'piutang ' : 'hutang ') . $rp($saldoSaya) . ' dan tetap dapat dituntaskan melalui Arsip Kontrakan.' : 'Saldo Anda sudah nol.' }}{{ $sayaPembuat && $aktif->count() > 1 ? ' Kewenangan pembuat akan berpindah kepada penghuni aktif yang bergabung paling awal.' : '' }}{{ $aktif->count() === 1 ? ' Anda penghuni aktif terakhir, sehingga kontrakan otomatis menjadi arsip.' : '' }}"
            data-tombol="Keluar" data-risiko>
            @csrf
            <button type="submit" class="tombol tombol-risiko">Keluar dari Kontrakan</button>
        </form>

        {{-- UC-18, FR-22: hanya pembuat (A-04) --}}
        @if ($sayaPembuat)
        <form method="POST" action="{{ route('kontrakan.bubarkan') }}"
            data-judul="Bubarkan Kontrakan"
            data-konfirmasi="Bubarkan {{ $grup->nama_grup }}? Seluruh penghuni menjadi nonaktif dan kode invitasi tidak berlaku lagi. Data tidak dihapus dan tetap dapat dilihat melalui Arsip Kontrakan."
            data-tombol="Bubarkan" data-risiko>
            @csrf
            <button type="submit" class="tombol tombol-risiko">Bubarkan Kontrakan</button>
        </form>
        @endif
    </section>
    @endif

    <section class="bagian area-akun">
        <h3 class="kartu-judul">Akun</h3>
        <div class="kartu">
            <span>{{ auth()->user()->nama }}</span>
            <span class="teks-sekunder">{{ auth()->user()->email }}</span>
        </div>
        <form method="POST" action="{{ route('keluar') }}">
            @csrf
            <button type="submit" class="tombol tombol-sekunder">Keluar dari Akun</button>
        </form>
    </section>
</div>

<script>
(function () {
    const tombol = document.getElementById('tombol-salin');
    if (!tombol) return;

    const tampilkan = (teks) => {
        tombol.textContent = teks;
        setTimeout(() => { tombol.textContent = 'Salin'; }, 2500);
    };

    // Cadangan untuk halaman tanpa HTTPS (misalnya diakses lewat alamat IP), tempat navigator.clipboard tidak tersedia.
    const salinCadangan = (teks) => {
        const area = document.createElement('textarea');
        area.value = teks;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        let berhasil = false;
        try { berhasil = document.execCommand('copy'); } catch (e) { berhasil = false; }
        document.body.removeChild(area);
        return berhasil;
    };

    tombol.addEventListener('click', async function () {
        const kode = tombol.dataset.kode;

        // Di HP (layar sentuh) dibuka lembar berbagi agar kode langsung dapat dikirim lewat WhatsApp dan sejenisnya.
        // Di laptop kode langsung disalin ke clipboard.
        if (navigator.share && window.matchMedia('(pointer: coarse)').matches) {
            try {
                await navigator.share({ text: tombol.dataset.pesan });
                return;
            } catch (e) {
                if (e.name === 'AbortError') return; // pengguna menutup lembar berbagi
            }
        }

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(kode);
                return tampilkan('Kode tersalin');
            }
        } catch (e) { /* lanjut ke cara cadangan */ }

        if (salinCadangan(kode)) return tampilkan('Kode tersalin');

        // Semua cara gagal: kode dipilih agar dapat disalin manual.
        const pilihan = window.getSelection();
        const rentang = document.createRange();
        rentang.selectNodeContents(document.getElementById('kode-invitasi'));
        pilihan.removeAllRanges();
        pilihan.addRange(rentang);
        tampilkan('Tekan Ctrl/⌘+C untuk menyalin');
    });
})();
</script>
@endsection
