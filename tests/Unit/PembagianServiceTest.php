<?php

namespace Tests\Unit;

use App\Services\PembagianService;
use PHPUnit\Framework\TestCase;

class PembagianServiceTest extends TestCase
{
    private PembagianService $layanan;

    protected function setUp(): void
    {
        $this->layanan = new PembagianService();
    }

    // FR-02 dan BR-06: nominal habis dibagi, bagian penalang langsung lunas.
    public function test_nominal_habis_dibagi_dan_bagian_penalang_lunas(): void
    {
        $hasil = $this->layanan->hitung(20000, [1, 2, 3, 4], 1);

        $this->assertSame(5000, $hasil[1]['nominal_bagian']);
        $this->assertSame('lunas', $hasil[1]['status_bayar']);

        foreach ([2, 3, 4] as $id) {
            $this->assertSame(5000, $hasil[$id]['nominal_bagian']);
            $this->assertSame('belum', $hasil[$id]['status_bayar']);
        }
    }

    // BR-07: bagian dibulatkan ke bawah, sisa selisih dibebankan kepada penalang.
    public function test_sisa_pembulatan_dibebankan_kepada_penalang(): void
    {
        $hasil = $this->layanan->hitung(100000, [3, 1, 4], 3);

        $this->assertSame(33334, $hasil[3]['nominal_bagian']);
        $this->assertSame(33333, $hasil[1]['nominal_bagian']);
        $this->assertSame(33333, $hasil[4]['nominal_bagian']);
        $this->assertSame(100000, array_sum(array_column($hasil, 'nominal_bagian')));
    }

    // Perubahan 34: penalang tidak wajib ikut menanggung.
    public function test_penalang_yang_tidak_ikut_menanggung_tidak_memperoleh_bagian(): void
    {
        $hasil = $this->layanan->hitung(9000, [2, 3, 4], 1);

        $this->assertArrayNotHasKey(1, $hasil);

        foreach ([2, 3, 4] as $id) {
            $this->assertSame(3000, $hasil[$id]['nominal_bagian']);
            $this->assertSame('belum', $hasil[$id]['status_bayar']);
        }
    }

    // Total pembagian selalu sama dengan nominal dan tidak ada bagian bernilai nol,
    // termasuk pada batas nominal terkecil dan terbesar.
    public function test_total_pembagian_selalu_sama_dengan_nominal(): void
    {
        $kasus = [[1, 1], [7, 3], [10, 4], [4, 4], [99999999, 7]];

        foreach ($kasus as [$nominal, $jumlah]) {
            $hasil = $this->layanan->hitung($nominal, range(1, $jumlah), 1);

            $this->assertSame(
                $nominal,
                array_sum(array_column($hasil, 'nominal_bagian')),
                "Nominal {$nominal} dibagi {$jumlah} penghuni"
            );

            foreach ($hasil as $baris) {
                $this->assertGreaterThan(0, $baris['nominal_bagian']);
            }
        }
    }
}
