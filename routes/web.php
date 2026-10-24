<?php

use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KontrakanController;
use Illuminate\Support\Facades\Route;


// Wilayah 1 — belum masuk
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formMasuk'])->name('login');
    Route::post('/masuk', [AuthController::class, 'masuk']);
    Route::get('/daftar', [AuthController::class, 'formDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar']);
});

Route::middleware('auth')->group(function () {
    // Setelah masuk, keanggotaan diperiksa oleh middleware 'kontrakan' pada Beranda
    Route::get('/', fn() => redirect()->route('beranda'));
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');

    // Wilayah 2 — sudah masuk, belum tergabung kontrakan
    Route::middleware('tanpa.kontrakan')->group(function () {
        Route::get('/kontrakan/pilihan', [KontrakanController::class, 'pilihan'])->name('pilihan');
        Route::get('/kontrakan/buat', [KontrakanController::class, 'formBuat'])->name('kontrakan.buat');
        Route::post('/kontrakan/buat', [KontrakanController::class, 'buat']);
        Route::get('/kontrakan/gabung', [KontrakanController::class, 'formGabung'])->name('kontrakan.gabung');
        Route::post('/kontrakan/gabung', [KontrakanController::class, 'gabung']);
    });

    // Wilayah 3 — sudah tergabung kontrakan
    Route::middleware('kontrakan')->group(function () {
        Route::view('/beranda', 'beranda')->name('beranda');

        Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('/transaksi/catat', [TransaksiController::class, 'create'])->name('transaksi.create');
        Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
        Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');
    });
});
