@extends('layouts.app')

@section('judul', 'Catat Transaksi')

@section('isi')
@php
$terpilih = old('penanggung', $penghuni->pluck('id_anggota')->all());
$penalang = (int) old('id_penalang', $anggotaAktif->id_anggota);
$hariIni = now()->toDateString();
@endphp

<header class="kepala">
    <a href="{{ route('beranda') }}" class="kembali" aria-label="Kembali">←</a>
    <h1 class="judul-halaman">Catat Transaksi</h1>
</header>

<form method="POST" action="{{ route('transaksi.store') }}" class="form" id="form-transaksi" novalidate>
    @csrf

    <div class="isian @error('nama_barang') isian-salah @enderror">
        <label for="nama_barang">Nama Barang</label>
        <input id="nama_barang" type="text" name="nama_barang" value="{{ old('nama_barang') }}">
        @error('nama_barang') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian @error('nominal') isian-salah @enderror">
        <label for="nominal">Nominal (Rp)</label>
        <input id="nominal" type="text" inputmode="numeric" name="nominal" value="{{ old('nominal') }}" class="nominal">
        @error('nominal') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian @error('tanggal') isian-salah @enderror">
        <label for="tanggal">Tanggal</label>
        <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', $hariIni) }}" max="{{ $hariIni }}">
        @error('tanggal') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian @error('id_penalang') isian-salah @enderror">
        <label for="id_penalang">Penalang</label>
        <select id="id_penalang" name="id_penalang">
            @foreach ($penghuni as $p)
            <option value="{{ $p->id_anggota }}" @selected($p->id_anggota === $penalang)>{{ $p->pengguna->nama }}</option>
            @endforeach
        </select>
        @error('id_penalang') <p class="pesan-isian">{{ $message }}</p> @enderror
    </div>

    <div class="isian">
        <span class="label-bagian">Penanggung Biaya</span>
        @php($galatPenanggung = $errors->first('penanggung') ?: $errors->first('penanggung.*'))
        <div class="pilihan-penghuni {{ $galatPenanggung ? 'pilihan-salah' : '' }}">
            @foreach ($penghuni as $p)
            <label>
                <input type="checkbox" name="penanggung[]" value="{{ $p->id_anggota }}"
                    @checked(in_array($p->id_anggota, $terpilih))>
                {{ $p->pengguna->nama }}
            </label>
            @endforeach
        </div>
        @if ($galatPenanggung) <p class="pesan-isian">{{ $galatPenanggung }}</p> @endif
    </div>

    {{-- Kartu rincian pembagian (Modul 6). Perkiraan dihitung di peramban; penyimpanan tetap dihitung ulang oleh server. --}}
    <div class="kartu">
        <span class="kartu-judul">Rincian Pembagian</span>
        <span class="teks-sekunder" id="rincian"></span>
    </div>

    <button type="submit" class="tombol tombol-utama">Simpan</button>
    <a href="{{ route('beranda') }}" class="tombol tombol-sekunder">Batal</a>
</form>

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
@endsection