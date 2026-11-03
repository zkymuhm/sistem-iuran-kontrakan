<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Iuran Kontrakan')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">

    {{-- PWA: dapat dipasang ke layar utama dan terbuka tanpa kolom alamat. Service worker tidak dipasang karena akses offline di luar ruang lingkup. --}}
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#FAFAFA">
    <link rel="icon" href="{{ asset('ikon/ikon-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('ikon/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Iuran Kontrakan">
</head>

<body>
    <main class="halaman @hasSection('navigasi') ada-navigasi @endif">
        @if (session('berhasil'))
        <div class="pesan pesan-berhasil">{{ session('berhasil') }}</div>
        @endif

        {{-- BR-14, BR-16: mode baca untuk penghuni nonaktif dan kontrakan arsip --}}
        @if (isset($bisaTulis) && ! $bisaTulis)
        <div class="pesan pesan-baca">
            <span>Mode baca: {{ $anggotaAktif->grup->status_aktif !== 'aktif' ? 'kontrakan ini sudah dibubarkan' : 'Anda sudah tidak aktif di kontrakan ini' }}. Pembayaran yang menyangkut Anda tetap dapat dituntaskan.</span>
            <form method="POST" action="{{ route('arsip.tutup') }}">
                @csrf
                <button type="submit" class="tombol tombol-sekunder tombol-kecil">Tutup Arsip</button>
            </form>
        </div>
        @endif

        @error('aksi')
        <div class="pesan pesan-gagal">{{ $message }}</div>
        @enderror

        @yield('isi')
    </main>

    @hasSection('navigasi')
    @php
    $aktif = trim($__env->yieldContent('navigasi'));
    // Menu hanya ditampilkan bila halamannya sudah ada, sehingga tidak ada menu yang tidak merespons (temuan pengujian Modul 6).
    $menu = collect([
        ['kunci' => 'beranda',   'route' => 'beranda',         'label' => 'Beranda'],
        ['kunci' => 'transaksi', 'route' => 'transaksi.index', 'label' => 'Transaksi'],
        ['kunci' => 'saldo',     'route' => 'saldo',           'label' => 'Saldo'],
        ['kunci' => 'rekap',     'route' => 'rekap',           'label' => 'Rekap'],
        ['kunci' => 'kontrakan', 'route' => 'kontrakan.index', 'label' => 'Kontrakan'],
    ])->filter(fn ($m) => Route::has($m['route']));
    @endphp
    <nav class="navigasi-bawah" aria-label="Navigasi utama">
        @foreach ($menu as $m)
        <a href="{{ route($m['route']) }}" class="menu {{ $aktif === $m['kunci'] ? 'menu-aktif' : '' }}"
            @if ($aktif === $m['kunci']) aria-current="page" @endif>
            <x-ikon :nama="$m['kunci']" />
            <span>{{ $m['label'] }}</span>
        </a>
        @endforeach
    </nav>
    @endif

    {{-- Dialog konfirmasi bersama (frame F18 Modul 6). Dipakai oleh setiap form yang memiliki atribut data-konfirmasi. --}}
    <dialog id="dialog-konfirmasi" class="dialog" aria-labelledby="dialog-judul">
        <form method="dialog" class="dialog-isi">
            <h2 class="kartu-judul" id="dialog-judul"></h2>
            <p class="teks-sekunder" id="dialog-pesan"></p>
            <div class="baris-tombol">
                <button type="submit" value="batal" class="tombol tombol-sekunder" autofocus>Batal</button>
                <button type="submit" value="ya" class="tombol tombol-utama" id="dialog-ya"></button>
            </div>
        </form>
    </dialog>

    <script>
    (function () {
        const dialog = document.getElementById('dialog-konfirmasi');
        // Peramban tanpa dukungan <dialog>: form langsung terkirim tanpa dialog.
        if (!dialog || typeof dialog.showModal !== 'function') return;

        const judul = document.getElementById('dialog-judul');
        const pesan = document.getElementById('dialog-pesan');
        const ya = document.getElementById('dialog-ya');
        let formTujuan = null;

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.matches('form[data-konfirmasi]')) return;

            e.preventDefault();
            formTujuan = form;
            judul.textContent = form.dataset.judul || 'Konfirmasi';
            pesan.textContent = form.dataset.konfirmasi;
            ya.textContent = form.dataset.tombol || 'Konfirmasi';
            ya.className = 'tombol ' + ('risiko' in form.dataset ? 'tombol-risiko' : 'tombol-utama');
            dialog.returnValue = '';
            dialog.showModal();
        });

        dialog.addEventListener('close', function () {
            if (dialog.returnValue === 'ya' && formTujuan) {
                // form.submit() tidak memicu event submit lagi, sehingga dialog tidak muncul dua kali.
                formTujuan.submit();
            }
            formTujuan = null;
        });
    })();
    </script>
</body>

</html>