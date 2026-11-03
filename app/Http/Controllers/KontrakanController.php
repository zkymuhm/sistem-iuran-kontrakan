<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Grup;
use App\Services\KontrakanService;
use App\Services\SaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KontrakanController extends Controller
{
    public function __construct(private KontrakanService $layanan) {}

    // Menu Kontrakan: kode invitasi (FR-20), daftar penghuni, dan tindakan keanggotaan.
    public function index(SaldoService $saldo)
    {
        $saya = app('anggota.aktif');
        $grup = $saya->grup;

        $penghuni = Anggota::with('pengguna')
            ->where('id_grup', $grup->id_grup)
            ->orderBy('bergabung_pada')
            ->orderBy('id_anggota')
            ->get();

        return view('kontrakan.index', [
            'grup'      => $grup,
            'aktif'     => $penghuni->where('status_aktif', 'aktif')->values(),
            'nonaktif'  => $penghuni->where('status_aktif', '!=', 'aktif')->values(),
            'sayaPembuat' => (int) $grup->id_pembuat === (int) $saya->id_pengguna,
            'saldoSaya' => $saldo->saldoKontrakan($grup->id_grup)[$saya->id_anggota] ?? 0,
        ]);
    }

    // UC-09, FR-13: hanya pembuat kontrakan (A-04) yang dapat menonaktifkan penghuni lain.
    public function nonaktifkanPenghuni(int $idAnggota)
    {
        $saya = app('anggota.aktif');

        abort_unless((int) $saya->grup->id_pembuat === (int) $saya->id_pengguna, 403,
            'Hanya pembuat kontrakan yang dapat menonaktifkan penghuni.');

        // Dicari dalam kontrakan yang sedang dibuka saja (NFR-08); model Anggota tidak memiliki global scope.
        $target = Anggota::with('pengguna')
            ->where('id_grup', $saya->id_grup)
            ->where('status_aktif', 'aktif')
            ->findOrFail($idAnggota);

        if ((int) $target->id_anggota === (int) $saya->id_anggota) {
            return back()->withErrors(['aksi' => 'Gunakan Keluar dari Kontrakan untuk menonaktifkan diri sendiri.']);
        }

        $this->layanan->nonaktifkan($target);

        return back()->with('berhasil', "{$target->pengguna->nama} dinonaktifkan. Riwayat dan saldonya tetap tersimpan, dan ia tetap dapat menuntaskan pembayaran.");
    }

    // UC-17, FR-21
    public function keluarKontrakan(Request $request)
    {
        $saya = app('anggota.aktif');
        $nama = $saya->grup->nama_grup;

        $penerus = $this->layanan->nonaktifkan($saya);

        $pesan = "Anda telah keluar dari {$nama}. Pembayaran yang belum tuntas tetap dapat diselesaikan melalui Arsip Kontrakan.";
        if ($penerus) {
            $pesan .= " Kewenangan pembuat kontrakan berpindah kepada {$penerus}.";
        }

        return redirect()->route('pilihan')->with('berhasil', $pesan);
    }

    // UC-18, FR-22
    public function bubarkan()
    {
        $saya = app('anggota.aktif');
        $grup = $saya->grup;

        abort_unless((int) $grup->id_pembuat === (int) $saya->id_pengguna, 403,
            'Hanya pembuat kontrakan yang dapat membubarkan kontrakan.');

        $this->layanan->bubarkan($grup);

        return redirect()->route('pilihan')
            ->with('berhasil', "{$grup->nama_grup} telah dibubarkan dan menjadi arsip. Seluruh data tetap dapat dilihat melalui Arsip Kontrakan.");
    }

    public function pilihan()
    {
        return view('kontrakan.pilihan');
    }

    public function formBuat()
    {
        return view('kontrakan.buat');
    }

    // UC-13 Membuat Kontrakan
    public function buat(Request $request)
    {
        $data = $request->validate([
            'nama_grup' => ['required', 'string', 'max:100'],
        ], [
            'nama_grup.required' => 'Nama kontrakan wajib diisi.',
            'nama_grup.max'      => 'Nama kontrakan maksimal 100 karakter.',
        ]);

        $idPengguna = $request->user()->id_pengguna;

        // Kontrakan dan keanggotaan pembuat disimpan bersama: keduanya berhasil atau keduanya batal.
        $grup = DB::transaction(function () use ($data, $idPengguna) {
            $grup = Grup::create([
                'nama_grup'     => $data['nama_grup'],
                'kode_invitasi' => Grup::buatKodeInvitasi(),
                'id_pembuat'    => $idPengguna,
            ]);

            Anggota::create([
                'id_grup'     => $grup->id_grup,
                'id_pengguna' => $idPengguna,
            ]);

            return $grup;
        });

        return redirect()->route('beranda')
            ->with('berhasil', "Kontrakan {$grup->nama_grup} berhasil dibuat. Bagikan kode {$grup->kode_invitasi} kepada penghuni lain.");
    }

    // UC-14 langkah Cari: hanya membaca, sehingga memakai GET
    public function formGabung(Request $request)
    {
        $kode  = strtoupper(trim((string) $request->query('kode', '')));
        $grup  = null;
        $galat = null;

        if ($kode !== '') {
            [$grup, $galat] = $this->periksaKode($kode);
        }

        return view('kontrakan.gabung', [
            'kode'           => $kode,
            'grup'           => $grup,
            'galat'          => $galat,
            'jumlahPenghuni' => $grup?->anggota()->where('status_aktif', 'aktif')->count(),
        ]);
    }

    // UC-14 langkah Gabung
    public function gabung(Request $request)
    {
        $kode = strtoupper(trim((string) $request->input('kode', '')));

        // Kode diperiksa ulang, karena kontrakan bisa saja dibubarkan setelah pratinjau ditampilkan.
        [$grup, $galat] = $this->periksaKode($kode);

        if ($galat) {
            return redirect()->route('kontrakan.gabung')->withErrors(['kode' => $galat]);
        }

        $idPengguna = $request->user()->id_pengguna;

        // Pasangan kontrakan-pengguna bersifat unik. Penghuni yang pernah keluar
        // dari kontrakan ini memakai keanggotaan lamanya yang diaktifkan kembali.
        $anggota = Anggota::where('id_grup', $grup->id_grup)
            ->where('id_pengguna', $idPengguna)
            ->first();

        if ($anggota) {
            $anggota->update(['status_aktif' => 'aktif', 'bergabung_pada' => now()]);
        } else {
            Anggota::create(['id_grup' => $grup->id_grup, 'id_pengguna' => $idPengguna]);
        }

        return redirect()->route('beranda')
            ->with('berhasil', "Anda telah bergabung dengan {$grup->nama_grup}.");
    }

    private function periksaKode(string $kode): array
    {
        if (! preg_match('/^[A-Z0-9]{6}$/', $kode)) {
            return [null, 'Kode invitasi terdiri atas 6 huruf atau angka.'];
        }

        $grup = Grup::where('kode_invitasi', $kode)->first();

        if (! $grup) {
            return [null, 'Kode invitasi tidak ditemukan.'];
        }

        if ($grup->status_aktif !== 'aktif') {
            return [null, 'Kontrakan dengan kode ini sudah dibubarkan.'];
        }

        return [$grup, null];
    }
}
