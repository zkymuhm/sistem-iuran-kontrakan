<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Grup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KontrakanController extends Controller
{
    public function pilihan()
    {
        return view('kontrakan.pilihan');
    }

    public function formBuat()
    {
        return view('kontrakan.buat');
    }

    // UC-13 Membuat Kontrakan
    public function buat(Request $request)
    {
        $data = $request->validate([
            'nama_grup' => ['required', 'string', 'max:100'],
        ], [
            'nama_grup.required' => 'Nama kontrakan wajib diisi.',
            'nama_grup.max'      => 'Nama kontrakan maksimal 100 karakter.',
        ]);

        $idPengguna = $request->user()->id_pengguna;

        // Kontrakan dan keanggotaan pembuat disimpan bersama: keduanya berhasil atau keduanya batal.
        $grup = DB::transaction(function () use ($data, $idPengguna) {
            $grup = Grup::create([
                'nama_grup'     => $data['nama_grup'],
                'kode_invitasi' => Grup::buatKodeInvitasi(),
                'id_pembuat'    => $idPengguna,
            ]);

            Anggota::create([
                'id_grup'     => $grup->id_grup,
                'id_pengguna' => $idPengguna,
            ]);

            return $grup;
        });

        return redirect()->route('beranda')
            ->with('berhasil', "Kontrakan {$grup->nama_grup} berhasil dibuat. Bagikan kode {$grup->kode_invitasi} kepada penghuni lain.");
    }

    // UC-14 langkah Cari: hanya membaca, sehingga memakai GET
    public function formGabung(Request $request)
    {
        $kode  = strtoupper(trim((string) $request->query('kode', '')));
        $grup  = null;
        $galat = null;

        if ($kode !== '') {
            [$grup, $galat] = $this->periksaKode($kode);
        }

        return view('kontrakan.gabung', [
            'kode'           => $kode,
            'grup'           => $grup,
            'galat'          => $galat,
            'jumlahPenghuni' => $grup?->anggota()->where('status_aktif', 'aktif')->count(),
        ]);
    }

    // UC-14 langkah Gabung
    public function gabung(Request $request)
    {
        $kode = strtoupper(trim((string) $request->input('kode', '')));

        // Kode diperiksa ulang, karena kontrakan bisa saja dibubarkan setelah pratinjau ditampilkan.
        [$grup, $galat] = $this->periksaKode($kode);

        if ($galat) {
            return redirect()->route('kontrakan.gabung')->withErrors(['kode' => $galat]);
        }

        $idPengguna = $request->user()->id_pengguna;

        // Pasangan kontrakan-pengguna bersifat unik. Penghuni yang pernah keluar
        // dari kontrakan ini memakai keanggotaan lamanya yang diaktifkan kembali.
        $anggota = Anggota::where('id_grup', $grup->id_grup)
            ->where('id_pengguna', $idPengguna)
            ->first();

        if ($anggota) {
            $anggota->update(['status_aktif' => 'aktif', 'bergabung_pada' => now()]);
        } else {
            Anggota::create(['id_grup' => $grup->id_grup, 'id_pengguna' => $idPengguna]);
        }

        return redirect()->route('beranda')
            ->with('berhasil', "Anda telah bergabung dengan {$grup->nama_grup}.");
    }

    private function periksaKode(string $kode): array
    {
        if (! preg_match('/^[A-Z0-9]{6}$/', $kode)) {
            return [null, 'Kode invitasi terdiri atas 6 huruf atau angka.'];
        }

        $grup = Grup::where('kode_invitasi', $kode)->first();

        if (! $grup) {
            return [null, 'Kode invitasi tidak ditemukan.'];
        }

        if ($grup->status_aktif !== 'aktif') {
            return [null, 'Kontrakan dengan kode ini sudah dibubarkan.'];
        }

        return [$grup, null];
    }
}
