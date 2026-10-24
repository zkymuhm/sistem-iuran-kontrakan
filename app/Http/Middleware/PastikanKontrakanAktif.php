<?php

namespace App\Http\Middleware;

use App\Models\Anggota;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class PastikanKontrakanAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $anggota = Anggota::aktifMilik($request->user()->id_pengguna);

        if (! $anggota) {
            return redirect()->route('pilihan');
        }

        // Kontrakan yang sedang dibuka ditetapkan sekali di sini, lalu dipakai
        // controller, tampilan, dan global scope model (NFR-08).
        app()->instance('anggota.aktif', $anggota);
        View::share('anggotaAktif', $anggota);

        return $next($request);
    }
}
