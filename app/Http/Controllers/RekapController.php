<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapController extends Controller
{
    // UC-10, FR-11: rekap pengeluaran per rentang tanggal (Kegiatan 7 Modul 8).
    public function index(Request $request)
    {
        return view('rekap', $this->susun($request));
    }

    // Unduhan CSV yang dapat dibuka langsung di Excel atau Google Sheets, tanpa library tambahan.
    public function csv(Request $request): StreamedResponse
    {
        $r = $this->susun($request);
        $namaBerkas = 'rekap-' . $r['dari']->format('Y-m-d') . '-sd-' . $r['sampai']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($r) {
            $keluar = fopen('php://output', 'w');
            // BOM UTF-8 agar Excel membaca huruf dengan benar; pemisah titik koma sesuai pengaturan regional Indonesia.
            fwrite($keluar, "\xEF\xBB\xBF");
            $tulis = fn (array $baris) => fputcsv($keluar, $baris, ';');

            $tulis(['Rekap Pengeluaran ' . $r['grup']->nama_grup]);
            $tulis(['Periode', $r['dari']->translatedFormat('j F Y') . ' s.d. ' . $r['sampai']->translatedFormat('j F Y')]);
            $tulis([]);
            $tulis(['Tanggal', 'Nama Barang', 'Penalang', 'Jumlah Penanggung', 'Nominal']);
            foreach ($r['transaksi'] as $t) {
                $tulis([$t->tanggal->format('Y-m-d'), $t->nama_barang, $t->penalang->pengguna->nama, $t->detail->count(), $t->nominal]);
            }
            $tulis(['', '', '', 'Total', $r['total']]);
            $tulis([]);
            $tulis(['Penghuni', 'Menalangi', 'Menanggung', 'Belum Lunas']);
            foreach ($r['ringkasan'] as $b) {
                $tulis([$b['nama'], $b['menalangi'], $b['menanggung'], $b['belum_lunas']]);
            }
            fclose($keluar);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function susun(Request $request): array
    {
        $saya = app('anggota.aktif');

        // Bawaan: awal bulan berjalan sampai hari ini. Masukan yang tidak sah dikembalikan ke bawaan.
        $dari   = $this->tanggal($request->query('dari')) ?? now()->startOfMonth();
        $sampai = $this->tanggal($request->query('sampai')) ?? now()->startOfDay();
        $galat  = null;

        if ($dari->gt($sampai)) {
            $galat = 'Tanggal awal tidak boleh setelah tanggal akhir. Rentang ditukar otomatis.';
            [$dari, $sampai] = [$sampai, $dari];
        }

        $transaksi = Transaksi::with(['penalang.pengguna', 'detail'])
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('id_transaksi')
            ->get();

        // Ringkasan per penghuni: berapa yang ditalangi, berapa yang menjadi tanggungannya,
        // dan berapa bagiannya yang belum lunas pada periode ini.
        $ringkasan = Anggota::with('pengguna')
            ->where('id_grup', $saya->id_grup)
            ->orderBy('bergabung_pada')
            ->get()
            ->map(function ($a) use ($transaksi) {
                $id = (int) $a->id_anggota;
                $bagian = $transaksi->flatMap->detail->where('id_anggota', $id);

                return [
                    'nama'        => $a->pengguna->nama,
                    'nonaktif'    => $a->status_aktif !== 'aktif',
                    'menalangi'   => (int) $transaksi->where('id_penalang', $id)->sum('nominal'),
                    'menanggung'  => (int) $bagian->sum('nominal_bagian'),
                    'belum_lunas' => (int) $bagian->where('status_bayar', '!=', 'lunas')->sum('nominal_bagian'),
                ];
            })
            ->filter(fn ($b) => ! $b['nonaktif'] || $b['menalangi'] || $b['menanggung'])
            ->values();

        return [
            'grup'      => $saya->grup,
            'dari'      => $dari,
            'sampai'    => $sampai,
            'galat'     => $galat,
            'transaksi' => $transaksi,
            'ringkasan' => $ringkasan,
            'total'     => (int) $transaksi->sum('nominal'),
        ];
    }

    private function tanggal($nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        try {
            $hasil = Carbon::createFromFormat('!Y-m-d', $nilai);
        } catch (\Throwable) {
            return null;
        }

        // Menolak tanggal yang tidak ada, misalnya 31 Februari, yang oleh PHP digeser ke bulan berikutnya.
        return $hasil->format('Y-m-d') === $nilai ? $hasil : null;
    }
}
