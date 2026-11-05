@extends('layouts.app')

@php
$modeUbah = $transaksi !== null;
$hariIni = now()->toDateString();

// BUG-03: setelah validasi gagal, pilihan pengguna dipertahankan apa adanya, termasuk bila semua centang dilepas.
// Tanpa pemeriksaan ini, old('penanggung') kosong dianggap tidak ada dan semua penghuni kembali tercentang.
$terpilih = session()->hasOldInput()
    ? array_map('intval', (array) old('penanggung', []))
    : ($modeUbah
        ? $transaksi->detail->pluck('id_anggota')->all()
        : $penghuni->pluck('id_anggota')->all());

$penalang = (int) old('id_penalang', $modeUbah ? $transaksi->id_penalang : $anggotaAktif->id_anggota);
@endphp

@section('judul', $modeUbah ? 'Ubah Transaksi' : 'Catat Transaksi')

@section('navigasi-lebar', 'transaksi')

@section('isi')
<x-kepala :judul="$modeUbah ? 'Ubah Transaksi' : 'Catat Transaksi'" :kembali="$modeUbah ? route('transaksi.show', $transaksi) : route('beranda')" />

<form method="POST"
    action="{{ $modeUbah ? route('transaksi.update', $transaksi) : route('transaksi.store') }}"
    class="form form-transaksi" id="form-transaksi" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($modeUbah) @method('PUT') @endif

    @if ($terkunci)
    <div class="kartu teks-sekunder">
        Sudah ada pembayaran pada transaksi ini. Hanya nama barang, tanggal, dan foto bukti yang dapat diubah. Koreksi nominal dilakukan dengan mencatat transaksi baru.
    </div>
    @endif

    {{-- Di laptop: kiri data pembelian, kanan pembagian. Di HP pembungkus ini tidak berpengaruh (display: contents). --}}
    <div class="form-kiri">
    <div class="isian @error('nama_barang') isian-salah @enderror">
        <label for="nama_barang">Nama Barang</label>
        <input id="nama_barang" type="text" name="nama_barang" value="{{ old('nama_barang', $transaksi?->nama_barang) }}">
        @error('nama_barang') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian @error('nominal') isian-salah @enderror">
        <label for="nominal">Nominal (Rp)</label>
        <input id="nominal" type="text" inputmode="numeric" name="nominal" class="nominal"
            value="{{ old('nominal', $transaksi?->nominal) }}" @disabled($terkunci)>
        @error('nominal') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian @error('tanggal') isian-salah @enderror">
        <label for="tanggal">Tanggal</label>
        <input id="tanggal" type="date" name="tanggal" max="{{ $hariIni }}"
            value="{{ old('tanggal', $transaksi?->tanggal?->toDateString() ?? $hariIni) }}">
        @error('tanggal') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian @error('id_penalang') isian-salah @enderror">
        <label for="id_penalang">Penalang</label>
        <select id="id_penalang" name="id_penalang" @disabled($terkunci)>
            @foreach ($penghuni as $p)
            <option value="{{ $p->id_anggota }}" @selected((int) $p->id_anggota === $penalang)>{{ $p->pengguna->nama }}</option>
            @endforeach
        </select>
        @error('id_penalang') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    </div>

    <div class="form-kanan">
    <div class="isian">
        <span class="label-bagian">Penanggung Biaya</span>
        @php($galatPenanggung = $errors->first('penanggung') ?: $errors->first('penanggung.*'))
        <div class="pilihan-penghuni {{ $galatPenanggung ? 'pilihan-salah' : '' }}">
            @foreach ($penghuni as $p)
            <label>
                <input type="checkbox" name="penanggung[]" value="{{ $p->id_anggota }}"
                    @checked(in_array($p->id_anggota, $terpilih)) @disabled($terkunci)>
                {{ $p->pengguna->nama }}
            </label>
            @endforeach
        </div>
        @if ($galatPenanggung) <p class="pesan-isian">{{ $galatPenanggung }}</p> @endif
    </div>

    {{-- FR-14: foto bukti opsional; capture membuka kamera belakang di telepon genggam --}}
    <div class="isian @error('foto_bukti') isian-salah @enderror">
        <span class="label-bagian">Foto Bukti (opsional)</span>
        @if ($transaksi?->foto_bukti)
        <img src="{{ route('transaksi.bukti', ['transaksi' => $transaksi, 'v' => substr(md5($transaksi->foto_bukti), 0, 8)]) }}" alt="Foto bukti saat ini" class="foto-bukti foto-kecil">
        <label class="pilihan-sebaris">
            <input type="checkbox" name="hapus_foto" value="1" @checked(old('hapus_foto'))> Hapus foto ini
        </label>
        @endif
        {{-- Masukan berkas bawaan peramban kecil dan samar, sehingga diganti area tombol yang jelas dapat ditekan.
             Input aslinya tetap ada (disembunyikan secara visual) agar tetap dapat dipakai pembaca layar dan keyboard. --}}
        <input id="foto_bukti" type="file" name="foto_bukti" accept="image/*" capture="environment" class="masukan-tersembunyi">
        <label for="foto_bukti" class="unggah-foto" id="unggah-foto">
            <x-ikon nama="kamera" />
            <span class="kartu-judul" id="unggah-judul">{{ $transaksi?->foto_bukti ? 'Ganti Foto Struk' : 'Tambah Foto Struk' }}</span>
            <span class="teks-sekunder" id="unggah-nama">Ketuk untuk memotret atau memilih dari galeri</span>
        </label>
        <img id="pratinjau-foto" alt="Pratinjau foto yang dipilih" class="foto-bukti foto-kecil" hidden>
        <p class="teks-sekunder">Foto diperkecil otomatis sebelum diunggah. Format JPG, PNG, atau WEBP.</p>
        @error('foto_bukti') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    @unless ($terkunci)
    {{-- Kartu rincian pembagian (Modul 6). Perkiraan dihitung di peramban; penyimpanan tetap dihitung ulang oleh server. --}}
    <div class="kartu">
        <span class="kartu-judul">Rincian Pembagian</span>
        <span class="teks-sekunder" id="rincian"></span>
    </div>
    @endunless

    <button type="submit" class="tombol tombol-utama">Simpan</button>
    <a href="{{ $modeUbah ? route('transaksi.show', $transaksi) : route('beranda') }}" class="tombol tombol-sekunder">Batal</a>
    </div>
</form>

<script>
// Foto dari kamera telepon genggam berukuran beberapa MB. Foto diperkecil di peramban (sisi terpanjang 1600 piksel,
// JPEG kualitas 0,8) agar unggahan cepat dan tidak melebihi batas ukuran server. Bila peramban tidak mendukung,
// berkas asli tetap dikirim dan diperiksa batas ukurannya oleh server.
(function () {
    const masukan = document.getElementById('foto_bukti');
    if (!masukan) return;

    // Tanda bahwa foto sudah dipilih: nama berkas dan pratinjau kecil
    const pratinjau = document.getElementById('pratinjau-foto');
    masukan.addEventListener('change', function () {
        const berkas = masukan.files[0];
        if (!berkas) return;
        document.getElementById('unggah-judul').textContent = 'Foto dipilih, ketuk untuk mengganti';
        document.getElementById('unggah-nama').textContent = berkas.name;
        document.getElementById('unggah-foto').classList.add('unggah-terisi');
        if (berkas.type.startsWith('image/')) {
            if (pratinjau.src) URL.revokeObjectURL(pratinjau.src);
            pratinjau.src = URL.createObjectURL(berkas);
            pratinjau.hidden = false;
        }
    });

    if (typeof DataTransfer === 'undefined') return;

    masukan.addEventListener('change', function () {
        const berkas = masukan.files[0];
        if (!berkas || !berkas.type.startsWith('image/') || berkas.name === 'bukti.jpg') return;

        const gambar = new Image();
        const alamat = URL.createObjectURL(berkas);
        gambar.onload = function () {
            const skala = Math.min(1, 1600 / Math.max(gambar.width, gambar.height));
            const kanvas = document.createElement('canvas');
            kanvas.width = Math.round(gambar.width * skala);
            kanvas.height = Math.round(gambar.height * skala);
            kanvas.getContext('2d').drawImage(gambar, 0, 0, kanvas.width, kanvas.height);
            URL.revokeObjectURL(alamat);

            kanvas.toBlob(function (blob) {
                if (!blob || blob.size >= berkas.size) return;
                const kecil = new File([blob], 'bukti.jpg', { type: 'image/jpeg' });
                const wadah = new DataTransfer();
                wadah.items.add(kecil);
                masukan.files = wadah.files;
            }, 'image/jpeg', 0.8);
        };
        gambar.onerror = function () { URL.revokeObjectURL(alamat); };
        gambar.src = alamat;
    });
})();
</script>

@unless ($terkunci)
<script>
    (function() {
        const form = document.getElementById('form-transaksi');
        const rincian = document.getElementById('rincian');
        const rp = (n) => 'Rp ' + n.toLocaleString('id-ID');

        function perbarui() {
            const nominal = parseInt(form.nominal.value.replace(/[.\s]/g, ''), 10);
            const dipilih = [...form.querySelectorAll('input[name="penanggung[]"]:checked')];

            if (!nominal || nominal < 1 || dipilih.length === 0) {
                rincian.textContent = 'Isi nominal dan pilih penanggung untuk melihat pembagian.';
                return;
            }

            const jumlah = dipilih.length;
            const bagian = Math.floor(nominal / jumlah);
            const sisa = nominal - bagian * jumlah;
            const penalangIkut = dipilih.some((c) => c.value === form.id_penalang.value);
            const piutang = bagian * (penalangIkut ? jumlah - 1 : jumlah);

            let teks = rp(nominal) + ' dibagi ' + jumlah + ' penghuni, masing-masing ' + rp(bagian) + '.';
            if (sisa > 0) teks += ' Selisih ' + rp(sisa) + ' dibebankan kepada penalang.';
            if (penalangIkut) teks += ' Bagian penalang langsung terhitung lunas.';
            teks += ' Penalang memperoleh piutang ' + rp(piutang) + '.';

            rincian.textContent = teks;
        }

        form.addEventListener('input', perbarui);
        form.addEventListener('change', perbarui);
        perbarui();
    })();
</script>
@endunless
@endsection