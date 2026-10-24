<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use App\Services\PembagianService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    public function __construct(private PembagianService $pembagian) {}

    // UC-04, FR-04: terbaru dulu. Tanggal tidak menyimpan jam, sehingga id menjadi urutan kedua.
    public function index()
    {
        $daftar = Transaksi::with(['penalang.pengguna', 'detail'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->get();

        return view('transaksi.index', compact('daftar'));
    }

    public function create()
    {
        return view('transaksi.catat', ['penghuni' => $this->penghuniAktif()]);
    }

    // UC-01 Mencatat Transaksi Pembelian
    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $transaksi = $this->pembagian->catat($data, app('anggota.aktif'));

        return redirect()
            ->route('transaksi.show', $transaksi)
            ->with('berhasil', $this->pesanPembagian($data['nominal'], count($data['penanggung'])));
    }

    // Transaksi kontrakan lain menghasilkan 404 karena global scope ikut berlaku pada route model binding.
    public function show(Transaksi $transaksi)
    {
        $transaksi->load(['penalang.pengguna', 'pencatat.pengguna', 'detail.anggota.pengguna']);

        return view('transaksi.detail', compact('transaksi'));
    }

    private function penghuniAktif()
    {
        return Anggota::with('pengguna')
            ->where('id_grup', app('anggota.aktif')->id_grup)
            ->where('status_aktif', 'aktif')
            ->orderBy('bergabung_pada')
            ->get();
    }

    private function validasi(Request $request): array
    {
        $idGrup = app('anggota.aktif')->id_grup;

        // Pemisah ribuan dihapus agar "20.000" terbaca sebagai 20000.
        $request->merge([
            'nominal' => str_replace(['.', ' '], '', (string) $request->input('nominal')),
        ]);

        // BR-09, BR-12: hanya penghuni aktif pada kontrakan yang sama.
        // Rule::exists dibatasi manual karena aturan ini tidak melewati global scope model.
        $penghuniAktif = fn() => Rule::exists('anggota', 'id_anggota')
            ->where('id_grup', $idGrup)
            ->where('status_aktif', 'aktif');

        $data = $request->validate([
            'nama_barang'  => ['required', 'string', 'max:100'],
            'nominal'      => ['required', 'integer', 'min:1', 'max:99999999'],
            'tanggal'      => ['required', 'date', 'before_or_equal:today'],
            'id_penalang'  => ['required', 'integer', $penghuniAktif()],
            'penanggung'   => ['required', 'array', 'min:1'],
            'penanggung.*' => ['integer', 'distinct', $penghuniAktif()],
        ], [
            'nama_barang.required'    => 'Nama barang wajib diisi.',
            'nama_barang.max'         => 'Nama barang maksimal 100 karakter.',
            'nominal.required'        => 'Nominal wajib diisi.',
            'nominal.integer'         => 'Nominal harus berupa angka bulat tanpa koma.',
            'nominal.min'             => 'Nominal harus lebih dari nol.',
            'nominal.max'             => 'Nominal melebihi batas Rp 99.999.999.',
            'tanggal.required'        => 'Tanggal wajib diisi.',
            'tanggal.date'            => 'Format tanggal tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
            'id_penalang.required'    => 'Penalang wajib dipilih.',
            'id_penalang.integer'     => 'Penalang tidak valid.',
            'id_penalang.exists'      => 'Penalang harus penghuni aktif kontrakan ini.',
            'penanggung.required'     => 'Pilih minimal satu penghuni penanggung.',
            'penanggung.min'          => 'Pilih minimal satu penghuni penanggung.',
            'penanggung.*.integer'    => 'Penanggung tidak valid.',
            'penanggung.*.distinct'   => 'Penanggung tidak boleh dipilih dua kali.',
            'penanggung.*.exists'     => 'Penanggung harus penghuni aktif kontrakan ini.',
        ]);

        $data['nominal']     = (int) $data['nominal'];
        $data['id_penalang'] = (int) $data['id_penalang'];
        $data['penanggung']  = array_map('intval', $data['penanggung']);

        // NFR-01 (perubahan 35): nominal minimal sama dengan jumlah penanggung agar tidak ada bagian bernilai nol.
        if ($data['nominal'] < count($data['penanggung'])) {
            throw ValidationException::withMessages([
                'nominal' => 'Nominal minimal Rp ' . count($data['penanggung']) . ' agar setiap penanggung memperoleh bagian.',
            ]);
        }

        return $data;
    }

    private function pesanPembagian(int $nominal, int $jumlah): string
    {
        $rupiah = fn(int $n) => 'Rp ' . number_format($n, 0, ',', '.');
        $bagian = intdiv($nominal, $jumlah);
        $sisa   = $nominal - ($bagian * $jumlah);

        $pesan = 'Transaksi berhasil disimpan. ' . $rupiah($nominal) . ' dibagi kepada ' . $jumlah
            . ' penghuni, masing-masing ' . $rupiah($bagian) . '.';

        if ($sisa > 0) {
            $pesan .= ' Selisih ' . $rupiah($sisa) . ' dibebankan kepada penalang.';
        }

        return $pesan;
    }
}
