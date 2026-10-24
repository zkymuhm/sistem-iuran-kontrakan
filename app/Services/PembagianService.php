<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

            $this->simpanPembagian($transaksi, $data);

            return $transaksi;
        });
    }

    // UC-02, FR-05, FR-06, BR-04: pembagian dihitung ulang dari awal.
    public function ubah(Transaksi $transaksi, array $data): void
    {
        DB::transaction(function () use ($transaksi, $data) {
            $this->kunciDanPastikanBelumDibayar($transaksi, 'nominal');

            $transaksi->update([
                'id_penalang' => $data['id_penalang'],
                'nama_barang' => $data['nama_barang'],
                'nominal'     => $data['nominal'],
                'tanggal'     => $data['tanggal'],
            ]);

            // Aman dihapus: BR-19 menjamin belum ada pembayaran pada baris lama.
            $transaksi->detail()->delete();
            $this->simpanPembagian($transaksi, $data);
        });
    }

    // UC-03, FR-07: pembagian ikut terhapus melalui ON DELETE CASCADE.
    public function hapus(Transaksi $transaksi): void
    {
        DB::transaction(function () use ($transaksi) {
            $this->kunciDanPastikanBelumDibayar($transaksi, 'transaksi');

            $transaksi->delete();
        });
    }

    private function simpanPembagian(Transaksi $transaksi, array $data): void
    {
        $pembagian = $this->hitung($data['nominal'], $data['penanggung'], $data['id_penalang']);

        foreach ($pembagian as $idAnggota => $baris) {
            $transaksi->detail()->create(['id_anggota' => $idAnggota] + $baris);
        }
    }

    // BR-19. Baris pembagian dikunci (SELECT ... FOR UPDATE) agar status pembayaran
    // tidak dapat berubah di antara pemeriksaan dan penyimpanan.
    private function kunciDanPastikanBelumDibayar(Transaksi $transaksi, string $kolomPesan): void
    {
        $transaksi->detail()->lockForUpdate()->get();

        if ($transaksi->sudahAdaPembayaran()) {
            throw ValidationException::withMessages([
                $kolomPesan => 'Sudah ada pembayaran pada transaksi ini, sehingga nominal, penalang, dan penanggung tidak dapat diubah dan transaksi tidak dapat dihapus. Catat transaksi baru untuk koreksi.',
            ]);
        }
    }
}
