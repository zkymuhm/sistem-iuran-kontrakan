<?php

namespace App\Http\Middleware;

use App\Models\Anggota;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanTanpaKontrakan
{
    // BR-10: pengguna yang sudah tergabung tidak dapat membuat atau bergabung ke kontrakan lain.
    public function handle(Request $request, Closure $next): Response
    {
        if (Anggota::aktifMilik($request->user()->id_pengguna)) {
            return redirect()->route('beranda');
        }

        return $next($request);
    }
}
