<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanBisaTulis
{
    // BR-09, BR-14, BR-16: penghuni nonaktif dan kontrakan arsip tidak dapat menambah atau mengubah data.
    // Middleware ini dipasang setelah 'kontrakan'.
    public function handle(Request $request, Closure $next): Response
    {
        if (! app('kontrakan.bisa_tulis')) {
            return redirect()->route('beranda')->withErrors([
                'aksi' => 'Kontrakan ini dalam mode baca. Penghuni nonaktif dan kontrakan yang dibubarkan tidak dapat menambah atau mengubah data.',
            ]);
        }

        return $next($request);
    }
}
