@extends('layouts.app')

@section('judul', 'Rekap Pengeluaran')
@section('navigasi', 'rekap')

@section('isi')
<x-kepala judul="Rekap Pengeluaran">
    <button type="button" class="tombol tombol-utama" onclick="window.print()">Cetak / PDF</button>
    <a href="{{ route('rekap.csv', ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}" class="tombol tombol-sekunder">Unduh CSV</a>
</x-kepala>

{{-- Di layar lebar: filter dan tombol unduh sebaris di atas, tabel transaksi dan ringkasan berdampingan.
     Urutan di HP dan tata letak cetak tidak berubah. --}}
<div class="tumpukan rekap">
    <form method="GET" action="{{ route('rekap') }}" class="saring tidak-dicetak area-saring">
        <div class="saring-baris">
            <div class="isian">
                <label for="dari">Dari tanggal</label>
                <input id="dari" type="date" name="dari" value="{{ $dari->toDateString() }}">
            </div>
            <div class="isian">
                <label for="sampai">Sampai tanggal</label>
                <input id="sampai" type="date" name="sampai" value="{{ $sampai->toDateString() }}">
            </div>
        </div>
        <button type="submit" class="tombol tombol-sekunder">Terapkan</button>
    </form>

    @if ($galat)
    <div class="pesan pesan-gagal tidak-dicetak">{{ $galat }}</div>
    @endif

    {{-- Bagian laporan: ini yang tercetak atau tersimpan sebagai PDF --}}
    <article class="laporan">
        <header class="laporan-kepala">
            <h2 class="judul-halaman">Rekap Pengeluaran {{ $grup->nama_grup }}</h2>
            <p class="teks-sekunder">Periode {{ $dari->translatedFormat('j F Y') }} s.d. {{ $sampai->translatedFormat('j F Y') }}</p>
            <p class="teks-sekunder hanya-dicetak">Dicetak oleh {{ auth()->user()->nama }} pada {{ now()->translatedFormat('j F Y, H.i') }}</p>
        </header>

        <div class="rekap-angka">
            <div class="kartu">
                <span class="teks-sekunder">Total pengeluaran</span>
                <span class="judul-aplikasi nominal">@rupiah($total)</span>
                <span class="teks-sekunder">{{ $transaksi->count() }} transaksi</span>
            </div>
            {{-- Dua kartu ringkas tambahan hanya di layar lebar dan saat dicetak; di HP angkanya sudah ada pada tabel di bawah --}}
            <div class="kartu hanya-lebar">
                <span class="teks-sekunder">Rata-rata per transaksi</span>
                <span class="kartu-judul nominal">@rupiah($transaksi->count() ? intdiv($total, $transaksi->count()) : 0)</span>
                <span class="teks-sekunder">dibulatkan ke bawah</span>
            </div>
            <div class="kartu hanya-lebar">
                <span class="teks-sekunder">Belum lunas pada periode ini</span>
                <span class="kartu-judul nominal {{ $ringkasan->sum('belum_lunas') > 0 ? 'hutang' : '' }}">@rupiah($ringkasan->sum('belum_lunas'))</span>
                <span class="teks-sekunder">{{ $ringkasan->where('belum_lunas', '>', 0)->count() }} penghuni belum tuntas</span>
            </div>
        </div>

        <section class="bagian area-daftar">
            <h3 class="kartu-judul">Daftar Transaksi</h3>
            @if ($transaksi->isEmpty())
            <p class="teks-sekunder">Tidak ada transaksi pada periode ini.</p>
            @else
            <div class="tabel-gulir">
                <table class="tabel">
                    <thead>
                        <tr><th>Tanggal</th><th>Barang</th><th>Penalang</th><th class="angka">Nominal</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($transaksi as $t)
                        <tr>
                            <td>{{ $t->tanggal->translatedFormat('j M') }}</td>
                            <td>{{ $t->nama_barang }}</td>
                            <td>{{ $t->penalang->pengguna->nama }}</td>
                            <td class="angka nominal">@rupiah($t->nominal)</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3">Total</td><td class="angka nominal">@rupiah($total)</td></tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </section>

        <section class="bagian area-ringkasan">
            <h3 class="kartu-judul">Ringkasan per Penghuni</h3>
            <div class="tabel-gulir">
                <table class="tabel">
                    <thead>
                        <tr><th>Penghuni</th><th class="angka">Menalangi</th><th class="angka">Menanggung</th><th class="angka">Belum Lunas</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($ringkasan as $b)
                        <tr>
                            <td>{{ $b['nama'] }}@if ($b['nonaktif']) <span class="label-nonaktif">(nonaktif)</span>@endif</td>
                            <td class="angka nominal">@rupiah($b['menalangi'])</td>
                            <td class="angka nominal">@rupiah($b['menanggung'])</td>
                            <td class="angka nominal {{ $b['belum_lunas'] > 0 ? 'hutang' : '' }}">@rupiah($b['belum_lunas'])</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="teks-sekunder">Menalangi: total yang dibayarkan lebih dahulu. Menanggung: total bagian penghuni tersebut. Belum lunas: bagian yang belum dikonfirmasi lunas oleh penalang. Saldo keseluruhan dapat dilihat pada menu Saldo.</p>
        </section>
    </article>

    <div class="baris-tombol tidak-dicetak hanya-hp">
        <button type="button" class="tombol tombol-utama" onclick="window.print()">Cetak / PDF</button>
        <a href="{{ route('rekap.csv', ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}" class="tombol tombol-sekunder">Unduh CSV</a>
    </div>
</div>
@endsection
