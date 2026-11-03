@extends('layouts.app')

@section('judul', 'Beranda')
@section('navigasi', 'beranda')

@section('isi')
@php($rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.'))

{{-- Urutan di HP: salam, saldo, indikator, Catat, daftar tindakan, transaksi terbaru.
     Di layar lebar bagian yang sama disusun ulang dengan grid (lihat app.css), tanpa mengubah urutan HP. --}}
<div class="tumpukan beranda">
    <div class="area-salam">
        <p class="teks-sekunder">Halo, {{ auth()->user()->nama }}</p>
        <h1 class="judul-halaman">{{ $anggotaAktif->grup->nama_grup }}</h1>
    </div>

    {{-- Indikator utama --}}
    <div class="kartu area-saldo">
        <span class="teks-sekunder">Saldo Anda</span>
        <span class="judul-aplikasi nominal {{ $saldoSaya > 0 ? 'piutang' : ($saldoSaya < 0 ? 'hutang' : '') }}">
            {{ $saldoSaya > 0 ? '+' : ($saldoSaya < 0 ? '−' : '') }}@rupiah(abs($saldoSaya))
        </span>
        <span class="teks-sekunder">
            @if ($saldoSaya > 0)
            Piutang: penghuni lain masih berhutang kepada Anda
            @elseif ($saldoSaya < 0)
            Hutang: Anda masih berhutang kepada penghuni lain
            @else
            Tidak ada hutang maupun piutang
            @endif
        </span>
    </div>

    <div class="indikator">
        <a href="#kewajiban" class="kartu">
            <span class="teks-sekunder">Harus Anda bayar</span>
            <span class="kartu-judul nominal">@rupiah($totalKewajiban)</span>
            <span class="teks-sekunder">{{ $kewajiban->count() }} bagian</span>
        </a>
        <a href="#menunggu" class="kartu">
            <span class="teks-sekunder">Menunggu konfirmasi Anda</span>
            <span class="kartu-judul nominal">@rupiah($totalMenunggu)</span>
            <span class="teks-sekunder">{{ $menunggu->count() }} pembayaran</span>
        </a>
    </div>

    @if ($bisaTulis)
    <a href="{{ route('transaksi.create') }}" class="tombol tombol-utama area-catat">Catat Transaksi</a>
    @endif

    {{-- Daftar yang perlu ditindaklanjuti --}}
    <div class="kolom-tindak">

    {{-- UC-16: penalang mengonfirmasi langsung dari Beranda --}}
    <section id="menunggu" class="bagian">
        <h2 class="kartu-judul">Menunggu Konfirmasi Anda</h2>
        @forelse ($menunggu as $d)
        @php($nama = $d->anggota->pengguna->nama)
        <div class="kartu">
            <div class="baris">
                <div class="orang">
                    <x-avatar :nama="$nama" />
                    <div>
                        <div>{{ $nama }}</div>
                        <a href="{{ route('transaksi.show', $d->id_transaksi) }}" class="teks-sekunder">{{ $d->transaksi->nama_barang }}</a>
                    </div>
                </div>
                <span class="kartu-judul nominal">@rupiah($d->nominal_bagian)</span>
            </div>
            <form method="POST" action="{{ route('pembagian.konfirmasi', $d->id_detail) }}"
                data-judul="Konfirmasi Penerimaan"
                data-konfirmasi="Konfirmasi bahwa Anda sudah menerima {{ $rp($d->nominal_bagian) }} dari {{ $nama }} untuk {{ $d->transaksi->nama_barang }}? Status akan menjadi Lunas dan tidak dapat dikembalikan."
                data-tombol="Konfirmasi">
                @csrf
                <button type="submit" class="tombol tombol-utama">Konfirmasi</button>
            </form>
        </div>
        @empty
        <p class="teks-sekunder">Tidak ada pembayaran yang menunggu konfirmasi Anda.</p>
        @endforelse
    </section>

    {{-- FR-03: kewajiban beserta kepada siapa harus dibayarkan --}}
    <section id="kewajiban" class="bagian">
        <h2 class="kartu-judul">Kewajiban Anda</h2>
        @forelse ($kewajiban as $d)
        @php($penalang = $d->transaksi->penalang->pengguna->nama)
        <div class="kartu">
            <div class="baris">
                <div>
                    <a href="{{ route('transaksi.show', $d->id_transaksi) }}" class="kartu-judul">{{ $d->transaksi->nama_barang }}</a>
                    <div class="teks-sekunder">Bayar kepada {{ $penalang }}</div>
                </div>
                <span class="kartu-judul nominal">@rupiah($d->nominal_bagian)</span>
            </div>
            @if ($d->status_bayar === 'belum')
            <form method="POST" action="{{ route('pembagian.tandai', $d->id_detail) }}"
                data-judul="Saya Sudah Bayar"
                data-konfirmasi="Anda menyatakan sudah membayar {{ $rp($d->nominal_bagian) }} kepada {{ $penalang }} untuk {{ $d->transaksi->nama_barang }}. Status menjadi Menunggu Konfirmasi sampai {{ $penalang }} mengonfirmasi penerimaan."
                data-tombol="Saya Sudah Bayar">
                @csrf
                <button type="submit" class="tombol tombol-sekunder">Saya Sudah Bayar</button>
            </form>
            @else
            <span class="chip chip-menunggu_konfirmasi">Menunggu Konfirmasi {{ $penalang }}</span>
            @endif
        </div>
        @empty
        <p class="teks-sekunder">Tidak ada kewajiban. Semua bagian Anda sudah lunas.</p>
        @endforelse
    </section>

    </div>

    <section class="bagian area-terbaru">
        <div class="baris">
            <h2 class="kartu-judul">Transaksi Terbaru</h2>
            <a href="{{ route('transaksi.index') }}" class="teks-sekunder">Lihat semua</a>
        </div>
        @forelse ($terbaru as $t)
        <a href="{{ route('transaksi.show', $t) }}" class="kartu">
            <div class="baris">
                <span class="kartu-judul">{{ $t->nama_barang }}</span>
                <span class="kartu-judul nominal">@rupiah($t->nominal)</span>
            </div>
            <div class="baris teks-sekunder">
                <span>{{ $t->tanggal->translatedFormat('j F Y') }} · {{ $t->penalang->pengguna->nama }}</span>
                <span>{{ $t->detail->where('status_bayar', 'lunas')->count() }} dari {{ $t->detail->count() }} lunas</span>
            </div>
        </a>
        @empty
        <p class="teks-sekunder">Belum ada transaksi. Tekan Catat Transaksi untuk mencatat pembelian pertama.</p>
        @endforelse
    </section>

</div>
@endsection
