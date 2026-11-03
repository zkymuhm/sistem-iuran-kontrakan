<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Services\KontrakanService;
use Illuminate\Http\Request;

class ArsipController extends Controller
{
    // UC-20, FR-24: kontrakan yang sudah dibubarkan, atau tempat pengguna sudah tidak aktif.
    public function index(Request $request)
    {
        $idPengguna = $request->user()->id_pengguna;

        $daftar = Anggota::with('grup')
            ->where('id_pengguna', $idPengguna)
            ->where(fn ($q) => $q->where('status_aktif', '!=', 'aktif')
                ->orWhereHas('grup', fn ($g) => $g->where('status_aktif', '!=', 'aktif')))
            ->get();

        return view('kontrakan.arsip', [
            'daftar'      => $daftar,
            'punyaAktif'  => Anggota::aktifMilik($idPengguna) !== null,
        ]);
    }

    // Membuka arsip dalam mode baca. Hanya kontrakan yang pernah diikuti pengguna (NFR-08).
    public function buka(Request $request, int $idGrup)
    {
        $this->keanggotaan($request, $idGrup);
        $request->session()->put('kontrakan_dibuka', $idGrup);

        return redirect()->route('beranda');
    }

    public function tutup(Request $request)
    {
        $request->session()->forget('kontrakan_dibuka');

        return redirect()->route('beranda');
    }

    // UC-19, FR-23: hanya pembuat, dan hanya bila ia belum tergabung pada kontrakan aktif lain (BR-10).
    public function aktifkan(Request $request, int $idGrup, KontrakanService $layanan)
    {
        $anggota = $this->keanggotaan($request, $idGrup);

        abort_unless((int) $anggota->grup->id_pembuat === (int) $anggota->id_pengguna, 403,
            'Hanya pembuat kontrakan yang dapat mengaktifkan kembali kontrakan.');

        if ($anggota->grup->status_aktif === 'aktif') {
            return redirect()->route('arsip.index')->withErrors(['aksi' => 'Kontrakan ini masih aktif.']);
        }

        if (Anggota::aktifMilik($anggota->id_pengguna)) {
            return redirect()->route('arsip.index')->withErrors(['aksi' => 'Keluar dari kontrakan aktif Anda terlebih dahulu, karena satu pengguna hanya dapat tergabung pada satu kontrakan aktif.']);
        }

        $layanan->aktifkanKembali($anggota->grup, $anggota);
        $request->session()->forget('kontrakan_dibuka');

        return redirect()->route('kontrakan.index')
            ->with('berhasil', "{$anggota->grup->nama_grup} aktif kembali dengan kode invitasi baru {$anggota->grup->kode_invitasi}. Bagikan kode ini agar penghuni lain dapat bergabung kembali.");
    }

    private function keanggotaan(Request $request, int $idGrup): Anggota
    {
        return Anggota::with('grup')
            ->where('id_pengguna', $request->user()->id_pengguna)
            ->where('id_grup', $idGrup)
            ->firstOrFail();
    }
}
