<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'kontrakan'       => \App\Http\Middleware\PastikanKontrakanAktif::class,
            'tanpa.kontrakan' => \App\Http\Middleware\PastikanTanpaKontrakan::class,
            'kontrakan.tulis' => \App\Http\Middleware\PastikanBisaTulis::class,
        ]);

        // Link berkunci (lihat config/akses.php). Dipasang di grup web agar cookie sudah terbaca saat diperiksa.
        $middleware->web(append: [
            \App\Http\Middleware\PastikanAksesKunci::class,
        ]);

        // Saat diakses melalui tunnel atau hosting, permintaan HTTPS diteruskan ke aplikasi sebagai HTTP.
        // Header X-Forwarded-* dipercaya agar tautan, CSS, dan formulir tetap memakai https:// dan tidak diblokir peramban.
        $middleware->trustProxies(at: '*');

        // Kontrakan harus ditetapkan sebelum route model binding mencari data,
        // agar global scope NFR-08 sudah aktif saat {transaksi} di-resolve.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\PastikanKontrakanAktif::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
