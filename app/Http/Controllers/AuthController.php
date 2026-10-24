<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function formMasuk()
    {
        return view('auth.masuk');
    }

    public function masuk(Request $request)
    {
        $kredensial = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::attempt($kredensial)) {
            // Pesan sengaja tidak membedakan email atau password yang salah,
            // agar tidak membocorkan email mana yang terdaftar.
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        // ID sesi diganti setelah masuk untuk mencegah session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('pilihan'));
    }

    public function formDaftar()
    {
        return view('auth.daftar');
    }

    public function daftar(Request $request)
    {
        $data = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:pengguna,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama.required'      => 'Nama wajib diisi.',
            'nama.max'           => 'Nama maksimal 100 karakter.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.max'          => 'Email maksimal 255 karakter.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $pengguna = Pengguna::create($data);

        Auth::login($pengguna);
        $request->session()->regenerate();

        return redirect()->route('pilihan')->with('berhasil', 'Akun berhasil dibuat.');
    }

    public function keluar(Request $request)
    {
        Auth::logout();

        // Sesi dihapus dan token CSRF diganti agar sesi lama tidak dapat dipakai lagi.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('berhasil', 'Anda telah keluar.');
    }
}
