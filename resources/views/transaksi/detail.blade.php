@extends('layouts.app')

@section('judul', $transaksi->nama_barang)

@section('isi')
@php
$labelStatus = ['belum' => 'Belum', 'menunggu_konfirmasi' => 'Menunggu Konfirmasi', 'lunas' => 'Lunas'];
$jumlahLunas = $transaksi->detail->where('status_bayar', 'lunas')->count();
$belumDiterima = $transaksi->detail->where('status_bayar', '!=', 'lunas')->sum('nominal_bagian');
$idSaya = (int) $anggotaAktif->id_anggota;
$sayaPenalang = (int) $transaksi->id_penalang === $idSaya;
$namaPenalang = $transaksi->penalang->pengguna->nama;
$rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp

<x-kepala judul="Detail Transaksi" :kembali="route('transaksi.index')" />

@error('transaksi')
<div class="pesan pesan-gagal">{{ $message }}</div>
@enderror

<div class="tumpukan">
    <div class="kartu">
        <span class="judul-halaman">{{ $transaksi->nama_barang }}</span>
        <span class="judul-aplikasi nominal">@rupiah($transaksi->nominal)</span>
        <span class="teks-sekunder">{{ $transaksi->tanggal->translatedFormat('j F Y') }}</span>
        <span class="teks-sekunder">Penalang: {{ $namaPenalang }}</span>
        <span class="teks-sekunder">Dicatat oleh: {{ $transaksi->pencatat->pengguna->nama }}</span>
        @if ($transaksi->foto_bukti)
        {{-- FR-14: dibuka melalui route terlindung, bukan alamat berkas publik --}}
        <a href="{{ route('transaksi.bukti', $transaksi) }}" target="_blank" rel="noopener">
            <img src="{{ route('transaksi.bukti', $transaksi) }}" alt="Foto bukti pembelian {{ $transaksi->nama_barang }}" class="foto-bukti" loading="lazy">
        </a>
        @endif
    </div>

    <div class="kartu">
        <div class="baris">
            <span class="kartu-judul">Pembagian Biaya</span>
            <span class="teks-sekunder">{{ $jumlahLunas }} dari {{ $transaksi->detail->count() }} Lunas</span>
        </div>
        @php
        $terkumpul = (int) $transaksi->detail->where('status_bayar', 'lunas')->sum('nominal_bagian');
        $persen = $transaksi->nominal > 0 ? (int) floor($terkumpul * 100 / $transaksi->nominal) : 0;
        @endphp
        {{-- Bar kemajuan pelunasan (Modul 6) --}}
        <div class="kemajuan" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $persen }}" aria-label="Kemajuan pelunasan">
            <div class="kemajuan-isi" style="width: {{ $persen }}%"></div>
        </div>
        <div class="baris teks-sekunder">
            <span>Terkumpul <span class="nominal">@rupiah($terkumpul)</span></span>
            <span>Sisa <span class="nominal">@rupiah($belumDiterima)</span></span>
        </div>

        <div class="daftar-baris">
            @foreach ($transaksi->detail as $d)
            @php
            $milikSaya = (int) $d->id_anggota === $idSaya;
            $nama = $d->anggota->pengguna->nama;
            $bagianPenalang = (int) $d->id_anggota === (int) $transaksi->id_penalang;
            @endphp
            <div class="baris-pembagian">
                <div class="baris">
                    <div class="orang">
                        <x-avatar :nama="$nama" />
                        <div>
                        <div>{{ $nama }}{{ $milikSaya ? ' (Anda)' : '' }}</div>
                        <div class="teks-sekunder nominal">
                            @rupiah($d->nominal_bagian)
                            {{-- FR-03: kepada siapa bagian ini harus dibayarkan --}}
                            @if ($milikSaya && ! $bagianPenalang && $d->status_bayar !== 'lunas')
                            · bayar kepada {{ $namaPenalang }}
                            @endif
                        </div>
                        </div>
                    </div>
                    <span class="chip chip-{{ $d->status_bayar }}">{{ $labelStatus[$d->status_bayar] }}</span>
                </div>

                {{-- UC-07: hanya pemilik bagian yang melihat tombol ini (BR-03) --}}
                @if ($milikSaya && $d->status_bayar === 'belum')
                <form method="POST" action="{{ route('pembagian.tandai', $d->id_detail) }}"
                    data-judul="Saya Sudah Bayar"
                    data-konfirmasi="Anda menyatakan sudah membayar {{ $rp($d->nominal_bagian) }} kepada {{ $namaPenalang }}. Status menjadi Menunggu Konfirmasi sampai {{ $namaPenalang }} mengonfirmasi penerimaan."
                    data-tombol="Saya Sudah Bayar">
                    @csrf
                    <button type="submit" class="tombol tombol-utama">Saya Sudah Bayar</button>
                </form>
                @endif

                {{-- UC-16: hanya penalang yang melihat tombol ini (NFR-02) --}}
                @if ($sayaPenalang && $d->status_bayar === 'menunggu_konfirmasi')
                <form method="POST" action="{{ route('pembagian.konfirmasi', $d->id_detail) }}"
                    data-judul="Konfirmasi Penerimaan"
                    data-konfirmasi="Konfirmasi bahwa Anda sudah menerima {{ $rp($d->nominal_bagian) }} dari {{ $nama }}? Status akan menjadi Lunas dan tidak dapat dikembalikan."
                    data-tombol="Konfirmasi">
                    @csrf
                    <button type="submit" class="tombol tombol-utama">Konfirmasi</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if ($bolehKelola && $bisaTulis)
    <div class="form">
        @if ($terkunci)
        <p class="teks-sekunder">Sudah ada pembayaran, sehingga nominal dan penanggung terkunci dan transaksi tidak dapat dihapus.</p>
        @endif

        <div class="baris-tombol">
            <a href="{{ route('transaksi.edit', $transaksi) }}" class="tombol tombol-sekunder">Ubah</a>

            @unless ($terkunci)
            <form method="POST" action="{{ route('transaksi.destroy', $transaksi) }}"
                data-judul="Hapus Transaksi"
                data-konfirmasi="Hapus transaksi {{ $transaksi->nama_barang }}? Pembagian biayanya ikut terhapus."
                data-tombol="Hapus"
                data-risiko>
                @csrf
                @method('DELETE')
                <button type="submit" class="tombol tombol-risiko">Hapus</button>
            </form>
            @endunless
        </div>
    </div>
    @endif
</div>
@endsection
