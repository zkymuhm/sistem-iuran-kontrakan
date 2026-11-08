<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Link berkunci untuk presentasi dan uji coba.
 *
 * Pengunjung masuk lewat link ?kunci=KODE. Bila kode cocok, peramban diberi cookie berisi tanda yang diturunkan
 * dari kode tersebut, sehingga halaman berikutnya tidak perlu kode lagi. Saat kode diganti, tanda yang diharapkan
 * ikut berubah, sehingga semua link dan cookie lama langsung ditolak.
 */
class PastikanAksesKunci
{
    public const COOKIE = 'akses_iuran';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('akses.wajib')) {
            return $next($request);
        }

        $kunci = config('akses.kunci');

        // Fail-closed: bila akses diwajibkan tetapi kode belum diatur, semua pengunjung ditolak.
        if ($kunci === '') {
            return $this->tolak();
        }

        $tanda = hash_hmac('sha256', $kunci, (string) config('app.key'));

        $dariLink = $request->query('kunci');
        if (is_string($dariLink) && hash_equals($kunci, $dariLink)) {
            // Kode dihapus dari alamat agar tidak ikut tersimpan di riwayat atau terbagikan tanpa sengaja.
            return redirect($request->fullUrlWithoutQuery(['kunci']))
                ->withCookie(cookie(self::COOKIE, $tanda, 60 * 24 * 30, null, null, null, true, false, 'lax'));
        }

        $dariCookie = $request->cookie(self::COOKIE);
        if (is_string($dariCookie) && hash_equals($tanda, $dariCookie)) {
            return $next($request);
        }

        return $this->tolak();
    }

    private function tolak(): Response
    {
        return response()->view('errors.akses', [], 403);
    }
}
