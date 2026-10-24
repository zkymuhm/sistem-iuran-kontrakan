<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama bulan berbahasa Indonesia, misalnya "10 Oktober 2026"
        Carbon::setLocale('id');

        // @rupiah($nilai) menghasilkan "Rp 20.000"
        Blade::directive('rupiah', function (string $nilai) {
            return "<?php echo 'Rp ' . number_format((int) ($nilai), 0, ',', '.'); ?>";
        });
    }
}
