<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Membuat atau mengganti kode link berkunci, lalu menampilkan link siap dibagikan.
// php artisan akses:kunci-baru            -> kode acak
// php artisan akses:kunci-baru Presentasi -> kode buatan sendiri
Artisan::command('akses:kunci-baru {kode? : Kode buatan sendiri, huruf dan angka 4 sampai 32 karakter}', function (?string $kode = null) {
    if ($kode === null) {
        // Tanpa huruf atau angka yang mirip (0/O, 1/l/I) agar mudah dibaca dan diketik
        $abjad = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $kode = '';
        for ($i = 0; $i < 10; $i++) {
            $kode .= $abjad[random_int(0, strlen($abjad) - 1)];
        }
    } elseif (! preg_match('/^[A-Za-z0-9]{4,32}$/', $kode)) {
        $this->error('Kode hanya boleh berisi huruf dan angka, 4 sampai 32 karakter.');

        return 1;
    }

    $jalur = base_path('.env');
    $isi = file_get_contents($jalur);
    foreach (['AKSES_WAJIB' => 'true', 'AKSES_KUNCI' => $kode] as $nama => $nilai) {
        $isi = preg_match("/^{$nama}=.*$/m", $isi)
            ? preg_replace("/^{$nama}=.*$/m", "{$nama}={$nilai}", $isi)
            : rtrim($isi) . "\n{$nama}={$nilai}\n";
    }
    file_put_contents($jalur, $isi);

    if (app()->configurationIsCached()) {
        $this->call('config:cache');
    }

    $this->info('Kode akses baru: ' . $kode);
    $this->line('Link: ' . rtrim((string) config('app.url'), '/') . '/?kunci=' . $kode);
    $this->line('Semua link dan perangkat yang sebelumnya sudah masuk tidak berlaku lagi.');
})->purpose('Membuat atau mengganti kode link berkunci');
