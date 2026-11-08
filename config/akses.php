<?php

// Link berkunci: bila AKSES_WAJIB=true, seluruh halaman hanya dapat dibuka melalui link yang memuat ?kunci=AKSES_KUNCI.
// Mengganti AKSES_KUNCI membatalkan semua link dan perangkat yang sebelumnya sudah masuk.
return [
    'wajib' => (bool) env('AKSES_WAJIB', false),
    'kunci' => (string) env('AKSES_KUNCI', ''),
];
