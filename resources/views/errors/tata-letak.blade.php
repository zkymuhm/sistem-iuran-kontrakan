{{-- Halaman galat berdiri sendiri: tidak memakai layout utama agar tetap tampil walaupun sesi atau data kontrakan bermasalah. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="halaman">
        <div class="galat">
            <span class="galat-kode">{{ $kode }}</span>
            <h1 class="judul-halaman">{{ $judul }}</h1>
            <p class="teks-sekunder">{{ $pesan }}</p>
            <a href="{{ url('/') }}" class="tombol tombol-utama">Kembali ke Beranda</a>
        </div>
    </main>
</body>
</html>
