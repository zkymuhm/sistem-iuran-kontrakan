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
        return view('transaksi.form', [
            'transaksi' => null,
            'terkunci'  => false,
            'penghuni'  => $this->penghuniPilihan(),
        ]);
    }

    // UC-01 Mencatat Transaksi Pembelian
    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $transaksi = $this->pembagian->catat($data, app('anggota.aktif'));

        return redirect()->route('transaksi.show', $transaksi)
            ->with('berhasil', 'Transaksi berhasil disimpan. ' . $this->pesanPembagian($data['nominal'], count($data['penanggung'])));
    }

    // Transaksi kontrakan lain menghasilkan 404 karena global scope ikut berlaku pada route model binding.
    public function show(Transaksi $transaksi)
    {
        $transaksi->load(['penalang.pengguna', 'pencatat.pengguna', 'detail.anggota.pengguna']);

        return view('transaksi.detail', [
            'transaksi'   => $transaksi,
            'bolehKelola' => $transaksi->bolehDikelolaOleh(app('anggota.aktif')),
            'terkunci'    => $transaksi->sudahAdaPembayaran(),
        ]);
    }

    // UC-02 Mengubah Transaksi
    public function edit(Transaksi $transaksi)
    {
        $this->pastikanBolehDikelola($transaksi);

        $transaksi->load('detail');

        return view('transaksi.form', [
            'transaksi' => $transaksi,
            'terkunci'  => $transaksi->sudahAdaPembayaran(),
            'penghuni'  => $this->penghuniPilihan($transaksi),
        ]);
    }

    public function update(Request $request, Transaksi $transaksi)
    {
        $this->pastikanBolehDikelola($transaksi);

        // BR-19: setelah ada pembayaran, hanya keterangan yang dapat diperbaiki.
        if ($transaksi->sudahAdaPembayaran()) {
            $data = $request->validate($this->aturanKeterangan(), $this->pesanValidasi());
            $transaksi->update($data);

            return redirect()->route('transaksi.show', $transaksi)
                ->with('berhasil', 'Nama barang dan tanggal berhasil diubah. Nominal, penalang, dan penanggung tidak diubah karena sudah ada pembayaran.');
        }

        $data = $this->validasi($request, $transaksi);
        $this->pembagian->ubah($transaksi, $data);

        return redirect()->route('transaksi.show', $transaksi)
            ->with('berhasil', 'Transaksi berhasil diubah. ' . $this->pesanPembagian($data['nominal'], count($data['penanggung'])));
    }

    // UC-03 Menghapus Transaksi
    public function destroy(Transaksi $transaksi)
    {
        $this->pastikanBolehDikelola($transaksi);

        $nama = $transaksi->nama_barang;
        $this->pembagian->hapus($transaksi);

        return redirect()->route('transaksi.index')
            ->with('berhasil', "Transaksi {$nama} berhasil dihapus.");
    }

    private function pastikanBolehDikelola(Transaksi $transaksi): void
    {
        abort_unless(
            $transaksi->bolehDikelolaOleh(app('anggota.aktif')),
            403,
            'Hanya penalang atau pencatat yang dapat mengubah dan menghapus transaksi ini.'
        );
    }

    // Penghuni aktif, ditambah penghuni yang sudah terlibat pada transaksi yang sedang diubah.
    private function penghuniPilihan(?Transaksi $transaksi = null)
    {
        $idTerlibat = $this->idTerlibat($transaksi);

        return Anggota::with('pengguna')
            ->where('id_grup', app('anggota.aktif')->id_grup)
            ->where(fn($q) => $q->where('status_aktif', 'aktif')->orWhereIn('id_anggota', $idTerlibat))
            ->orderBy('bergabung_pada')
            ->get();
    }

    private function idTerlibat(?Transaksi $transaksi): array
    {
        if (! $transaksi) {
            return [];
        }

        return $transaksi->detail()->pluck('id_anggota')
            ->push($transaksi->id_penalang)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function aturanKeterangan(): array
    {
        return [
            'nama_barang' => ['required', 'string', 'max:100'],
            'tanggal'     => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    private function validasi(Request $request, ?Transaksi $transaksi = null): array
    {
        $idGrup     = app('anggota.aktif')->id_grup;
        $idTerlibat = $this->idTerlibat($transaksi);

        // Pemisah ribuan dihapus agar "20.000" terbaca sebagai 20000.
        $request->merge([
            'nominal' => str_replace(['.', ' '], '', (string) $request->input('nominal')),
        ]);

        // BR-09, BR-12: penghuni aktif pada kontrakan yang sama. Saat mengubah, penghuni yang
        // sudah terlibat tetap boleh dipertahankan walaupun kini nonaktif (BR-13: snapshot).
        // Rule::exists dibatasi manual karena aturan ini tidak melewati global scope model.
        $penghuniSah = fn() => Rule::exists('anggota', 'id_anggota')
            ->where('id_grup', $idGrup)
            ->where(fn($q) => $q->where('status_aktif', 'aktif')->orWhereIn('id_anggota', $idTerlibat));

        $data = $request->validate(array_merge($this->aturanKeterangan(), [
            'nominal'      => ['required', 'integer', 'min:1', 'max:99999999'],
            'id_penalang'  => ['required', 'integer', $penghuniSah()],
            'penanggung'   => ['required', 'array', 'min:1'],
            'penanggung.*' => ['integer', 'distinct', $penghuniSah()],
        ]), $this->pesanValidasi());

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

    private function pesanValidasi(): array
    {
        return [
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
        ];
    }

    private function pesanPembagian(int $nominal, int $jumlah): string
    {
        $rupiah = fn(int $n) => 'Rp ' . number_format($n, 0, ',', '.');
        $bagian = intdiv($nominal, $jumlah);
        $sisa   = $nominal - ($bagian * $jumlah);

        $pesan = $rupiah($nominal) . ' dibagi kepada ' . $jumlah . ' penghuni, masing-masing ' . $rupiah($bagian) . '.';

        if ($sisa > 0) {
            $pesan .= ' Selisih ' . $rupiah($sisa) . ' dibebankan kepada penalang.';
        }

        return $pesan;
    }
}
