@extends('layouts.app')

@section('judul', 'Saldo Penghuni')
@section('navigasi', 'saldo')

@section('isi')
<x-kepala judul="Saldo Penghuni" />

<div class="tumpukan">
    <p class="teks-sekunder">Positif berarti piutang (penghuni lain berhutang kepadanya). Negatif berarti hutang. Pembayaran yang masih menunggu konfirmasi tetap dihitung sebagai hutang.</p>

    <div class="kartu">
        <div class="daftar-baris">
            @foreach ($daftar as $b)
            @php($s = $b['saldo'])
            <div class="baris">
                <div class="orang">
                    <x-avatar :nama="$b['anggota']->pengguna->nama" />
                    <div>
                        <div>{{ $b['anggota']->pengguna->nama }}{{ (int) $b['anggota']->id_anggota === (int) $anggotaAktif->id_anggota ? ' (Anda)' : '' }}</div>
                        <div class="teks-sekunder">
                            {{ $s > 0 ? 'Piutang' : ($s < 0 ? 'Hutang' : 'Tidak ada hutang maupun piutang') }}
                            @if ($b['anggota']->status_aktif !== 'aktif') · <span class="label-nonaktif">Nonaktif</span> @endif
                        </div>
                    </div>
                </div>
                <span class="kartu-judul nominal {{ $s > 0 ? 'piutang' : ($s < 0 ? 'hutang' : '') }}">
                    {{ $s > 0 ? '+' : ($s < 0 ? '−' : '') }}@rupiah(abs($s))
                </span>
            </div>
            @endforeach

            <div class="baris baris-total">
                <span>Jumlah seluruh saldo</span>
                <span class="nominal">@rupiah($jumlah)</span>
            </div>
        </div>
    </div>
</div>
@endsection
