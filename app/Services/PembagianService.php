<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class PembagianService
{
    /**
     * FR-02, BR-06, BR-07.
     * Bagian dibulatkan ke bawah ke satuan rupiah; sisa selisih dibebankan kepada penalang.
     * Bila penalang ikut menanggung, bagiannya ditambah sisa dan langsung berstatus lunas.
     * Bila penalang tidak ikut menanggung, sisa ditanggung penalang tanpa ditagihkan.
     *
     * @param  int[]  $idPenanggung
     * @return array<int, array{nominal_bagian: int, status_bayar: string}>
     */
    public function hitung(int $nominal, array $idPenanggung, int $idPenalang): array
    {
        $jumlah = count($idPenanggung);
        $bagian = intdiv($nominal, $jumlah);
        $sisa   = $nominal - ($bagian * $jumlah);

        $hasil = [];
        foreach ($idPenanggung as $idAnggota) {
            $milikPenalang = $idAnggota === $idPenalang;

            $hasil[$idAnggota] = [
                'nominal_bagian' => $milikPenalang ? $bagian + $sisa : $bagian,
                'status_bayar'   => $milikPenalang ? 'lunas' : 'belum',
            ];
        }

        return $hasil;
    }

    // UC-01: transaksi dan seluruh pembagiannya disimpan bersama, atau tidak sama sekali.
    public function catat(array $data, Anggota $pencatat): Transaksi
    {
        return DB::transaction(function () use ($data, $pencatat) {
            $transaksi = Transaksi::create([
                'id_grup'     => $pencatat->id_grup,
                'id_penalang' => $data['id_penalang'],
                'id_pencatat' => $pencatat->id_anggota,
                'nama_barang' => $data['nama_barang'],
                'nominal'     => $data['nominal'],
                'tanggal'     => $data['tanggal'],
            ]);

            $pembagian = $this->hitung($data['nominal'], $data['penanggung'], $data['id_penalang']);

            foreach ($pembagian as $idAnggota => $baris) {
                $transaksi->detail()->create(['id_anggota' => $idAnggota] + $baris);
            }

            return $transaksi;
        });
    }
}
