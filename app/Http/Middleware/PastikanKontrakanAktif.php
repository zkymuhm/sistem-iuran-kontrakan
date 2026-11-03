<?php

namespace App\Http\Middleware;

use App\Models\Anggota;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class PastikanKontrakanAktif
{
    /**
     * Menetapkan kontrakan yang sedang dibuka (NFR-08), yaitu:
     *  1. kontrakan arsip yang dibuka pengguna melalui menu Arsip, bila ia pernah menjadi penghuninya; atau
     *  2. kontrakan tempat pengguna menjadi penghuni aktif.
     * BR-14: penghuni nonaktif dan kontrakan arsip tetap dapat dibuka untuk dilihat dan menuntaskan pembayaran;
     * hak menulis diperiksa terpisah oleh middleware 'kontrakan.tulis'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idPengguna = $request->user()->id_pengguna;
        $anggota = null;

        if ($idArsip = $request->session()->get('kontrakan_dibuka')) {
            $anggota = Anggota::with('grup')
                ->where('id_pengguna', $idPengguna)
                ->where('id_grup', $idArsip)
                ->first();

            if (! $anggota) {
                $request->session()->forget('kontrakan_dibuka');
            }
        }

        $anggota ??= Anggota::aktifMilik($idPengguna);

        if (! $anggota) {
            return redirect()->route('pilihan');
        }

        $bisaTulis = $anggota->status_aktif === 'aktif' && $anggota->grup->status_aktif === 'aktif';

        // Kontrakan yang dibuka ternyata kontrakan aktifnya sendiri: kembali ke mode biasa.
        if ($bisaTulis) {
            $request->session()->forget('kontrakan_dibuka');
        }

        // Kontrakan yang sedang dibuka ditetapkan sekali di sini, lalu dipakai
        // controller, tampilan, dan global scope model (NFR-08).
        app()->instance('anggota.aktif', $anggota);
        app()->instance('kontrakan.bisa_tulis', $bisaTulis);
        View::share(['anggotaAktif' => $anggota, 'bisaTulis' => $bisaTulis]);

        return $next($request);
    }
}
