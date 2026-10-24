<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $sekarang = now();
            $password = Hash::make('password123');

            // Pengguna (akun berlaku global)
            $pengguna = [];
            foreach (['Arzaki', 'Budi', 'Citra', 'Dedi', 'Eka', 'Fajar'] as $nama) {
                $pengguna[$nama] = DB::table('pengguna')->insertGetId([
                    'nama'       => $nama,
                    'email'      => strtolower($nama) . '@contoh.test',
                    'password'   => $password,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ]);
            }

            // Dua kontrakan untuk pengujian isolasi data (NFR-08)
            $joyosuko = DB::table('grup')->insertGetId([
                'nama_grup'     => 'Kontrakan Joyosuko',
                'kode_invitasi' => 'JYS7K2',
                'id_pembuat'    => $pengguna['Arzaki'],
            ]);

            $sumbersari = DB::table('grup')->insertGetId([
                'nama_grup'     => 'Kontrakan Sumbersari',
                'kode_invitasi' => 'SMB4Q9',
                'id_pembuat'    => $pengguna['Eka'],
            ]);

            // Keanggotaan (penghuni per kontrakan)
            $a = [];
            foreach (['Arzaki', 'Budi', 'Citra', 'Dedi'] as $nama) {
                $a[$nama] = DB::table('anggota')->insertGetId([
                    'id_grup'     => $joyosuko,
                    'id_pengguna' => $pengguna[$nama],
                ]);
            }
            foreach (['Eka', 'Fajar'] as $nama) {
                $a[$nama] = DB::table('anggota')->insertGetId([
                    'id_grup'     => $sumbersari,
                    'id_pengguna' => $pengguna[$nama],
                ]);
            }

            // Mencatat satu transaksi beserta pembagiannya.
            // $bagian berisi [id_anggota, nominal_bagian, status_bayar].
            $catat = function (int $idGrup, int $idPenalang, string $barang, int $nominal, string $tanggal, array $bagian) {
                $idTransaksi = DB::table('transaksi')->insertGetId([
                    'id_grup'     => $idGrup,
                    'id_penalang' => $idPenalang,
                    'id_pencatat' => $idPenalang,
                    'nama_barang' => $barang,
                    'nominal'     => $nominal,
                    'tanggal'     => $tanggal,
                ]);

                foreach ($bagian as [$idAnggota, $nominalBagian, $status]) {
                    $bukanPenalang = $idAnggota !== $idPenalang;

                    DB::table('detail_pembagian')->insert([
                        'id_transaksi'      => $idTransaksi,
                        'id_anggota'        => $idAnggota,
                        'nominal_bagian'    => $nominalBagian,
                        'status_bayar'      => $status,
                        'ditandai_pada'     => $bukanPenalang && $status !== 'belum' ? now() : null,
                        'dikonfirmasi_pada' => $bukanPenalang && $status === 'lunas' ? now() : null,
                    ]);
                }
            };

            // Galon Rp 20.000 dibagi 4, penalang Arzaki (bagiannya lunas, BR-06)
            $catat($joyosuko, $a['Arzaki'], 'Galon Air', 20000, '2026-10-01', [
                [$a['Arzaki'], 5000, 'lunas'],
                [$a['Budi'],   5000, 'menunggu_konfirmasi'],
                [$a['Citra'],  5000, 'lunas'],
                [$a['Dedi'],   5000, 'belum'],
            ]);

            // Sabun Rp 15.000 dibagi 3, Dedi tidak ikut menanggung
            $catat($joyosuko, $a['Budi'], 'Sabun Cuci Piring', 15000, '2026-10-05', [
                [$a['Budi'],   5000, 'lunas'],
                [$a['Arzaki'], 5000, 'belum'],
                [$a['Citra'],  5000, 'belum'],
            ]);

            // Listrik Rp 100.000 dibagi 3: 33.333 dibulatkan ke bawah, sisa 1 ke penalang (BR-07)
            $catat($joyosuko, $a['Citra'], 'Listrik Bulanan', 100000, '2026-10-10', [
                [$a['Citra'],  33334, 'lunas'],
                [$a['Arzaki'], 33333, 'belum'],
                [$a['Dedi'],   33333, 'belum'],
            ]);

            // Transaksi kontrakan lain, tidak boleh terlihat dari Joyosuko (NFR-08)
            $catat($sumbersari, $a['Eka'], 'Gas LPG', 22000, '2026-10-12', [
                [$a['Eka'],   11000, 'lunas'],
                [$a['Fajar'], 11000, 'belum'],
            ]);
        });
    }
}
