<?php

use App\Http\Controllers\ArsipController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\KontrakanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SaldoController;
use App\Http\Controllers\TransaksiController;
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
    Route::get('/', fn () => redirect()->route('beranda'));
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');

    // Arsip Kontrakan (UC-19, UC-20): dapat dibuka dari wilayah 2 maupun 3
    Route::get('/arsip', [ArsipController::class, 'index'])->name('arsip.index');
    Route::post('/arsip/tutup', [ArsipController::class, 'tutup'])->name('arsip.tutup');
    Route::post('/arsip/{grup}/buka', [ArsipController::class, 'buka'])->whereNumber('grup')->name('arsip.buka');
    Route::post('/arsip/{grup}/aktifkan', [ArsipController::class, 'aktifkan'])->whereNumber('grup')->name('arsip.aktifkan');

    // Wilayah 2 — sudah masuk, belum tergabung kontrakan
    Route::middleware('tanpa.kontrakan')->group(function () {
        Route::get('/kontrakan/pilihan', [KontrakanController::class, 'pilihan'])->name('pilihan');
        Route::get('/kontrakan/buat', [KontrakanController::class, 'formBuat'])->name('kontrakan.buat');
        Route::post('/kontrakan/buat', [KontrakanController::class, 'buat']);
        Route::get('/kontrakan/gabung', [KontrakanController::class, 'formGabung'])->name('kontrakan.gabung');
        Route::post('/kontrakan/gabung', [KontrakanController::class, 'gabung']);
    });

    // Wilayah 3 — kontrakan yang sedang dibuka (aktif, atau arsip dalam mode baca)
    Route::middleware('kontrakan')->group(function () {
        // Membaca: tersedia juga bagi penghuni nonaktif dan kontrakan arsip (BR-14, BR-16)
        Route::get('/beranda', BerandaController::class)->name('beranda');
        Route::get('/saldo', SaldoController::class)->name('saldo');
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap');
        Route::get('/rekap/unduh', [RekapController::class, 'csv'])->name('rekap.csv');
        Route::get('/kontrakan', [KontrakanController::class, 'index'])->name('kontrakan.index');
        Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');

        // Menuntaskan pembayaran: tetap diizinkan bagi penghuni nonaktif dan arsip (BR-14)
        Route::post('/pembagian/{pembagian}/bayar', [PembayaranController::class, 'tandai'])->name('pembagian.tandai');
        Route::post('/pembagian/{pembagian}/konfirmasi', [PembayaranController::class, 'konfirmasi'])->name('pembagian.konfirmasi');

        // Menulis: hanya penghuni aktif pada kontrakan aktif
        Route::middleware('kontrakan.tulis')->group(function () {
            Route::get('/transaksi/catat', [TransaksiController::class, 'create'])->name('transaksi.create');
            Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
            Route::get('/transaksi/{transaksi}/ubah', [TransaksiController::class, 'edit'])->name('transaksi.edit');
            Route::put('/transaksi/{transaksi}', [TransaksiController::class, 'update'])->name('transaksi.update');
            Route::delete('/transaksi/{transaksi}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');

            Route::post('/kontrakan/penghuni/{anggota}/nonaktifkan', [KontrakanController::class, 'nonaktifkanPenghuni'])->whereNumber('anggota')->name('kontrakan.nonaktifkan');
            Route::post('/kontrakan/keluar', [KontrakanController::class, 'keluarKontrakan'])->name('kontrakan.keluar');
            Route::post('/kontrakan/bubarkan', [KontrakanController::class, 'bubarkan'])->name('kontrakan.bubarkan');
        });

        // Diletakkan setelah /transaksi/catat agar kata "catat" tidak dianggap ID transaksi
        Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->whereNumber('transaksi')->name('transaksi.show');
    });
});
