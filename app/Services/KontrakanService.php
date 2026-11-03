<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\Grup;
use Illuminate\Support\Facades\DB;

class KontrakanService
{
    /**
     * UC-09 dan UC-17. Keanggotaan dinonaktifkan, tidak dihapus (BR-05), sehingga riwayat tetap utuh.
     * BR-16: bila pembuat keluar, kewenangan pindah ke penghuni aktif yang bergabung paling awal;
     * bila tidak ada lagi penghuni aktif, kontrakan otomatis menjadi arsip.
     *
     * @return string|null nama penerima kewenangan baru, bila terjadi perpindahan
     */
    public function nonaktifkan(Anggota $anggota): ?string
    {
        return DB::transaction(function () use ($anggota) {
            // Baris kontrakan dikunci agar dua penghuni yang keluar bersamaan tidak sama-sama dianggap penerus.
            $grup = Grup::whereKey($anggota->id_grup)->lockForUpdate()->firstOrFail();

            $anggota->update(['status_aktif' => 'nonaktif']);

            $penerus = Anggota::with('pengguna')
                ->where('id_grup', $grup->id_grup)
                ->where('status_aktif', 'aktif')
                ->orderBy('bergabung_pada')
                ->orderBy('id_anggota')
                ->first();

            if (! $penerus) {
                $grup->update(['status_aktif' => 'nonaktif']);

                return null;
            }

            if ((int) $grup->id_pembuat === (int) $anggota->id_pengguna) {
                $grup->update(['id_pembuat' => $penerus->id_pengguna]);

                return $penerus->pengguna->nama;
            }

            return null;
        });
    }

    // UC-18, FR-22: pembubaran berarti penonaktifan; data tetap tersimpan sebagai arsip (BR-16).
    // Kode invitasi otomatis mati karena penggabungan hanya menerima kontrakan aktif (BR-11).
    public function bubarkan(Grup $grup): void
    {
        DB::transaction(function () use ($grup) {
            $grup->update(['status_aktif' => 'nonaktif']);
            Anggota::where('id_grup', $grup->id_grup)->update(['status_aktif' => 'nonaktif']);
        });
    }

    // UC-19, FR-23: kontrakan diaktifkan kembali dengan kode invitasi baru (BR-11).
    // Hanya keanggotaan pembuat yang aktif kembali; penghuni lain bergabung ulang memakai kode baru.
    public function aktifkanKembali(Grup $grup, Anggota $pembuat): void
    {
        DB::transaction(function () use ($grup, $pembuat) {
            $grup->update(['status_aktif' => 'aktif', 'kode_invitasi' => Grup::buatKodeInvitasi()]);
            $pembuat->update(['status_aktif' => 'aktif']);
        });
    }
}
