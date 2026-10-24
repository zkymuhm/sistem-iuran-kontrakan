<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Wilayah 1 — belum masuk
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formMasuk'])->name('login');
    Route::post('/masuk', [AuthController::class, 'masuk']);
    Route::get('/daftar', [AuthController::class, 'formDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar']);
});

// Wilayah 2 dan 3 — sudah masuk
Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('pilihan'));
    Route::view('/kontrakan/pilihan', 'kontrakan.pilihan')->name('pilihan');
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');
});
