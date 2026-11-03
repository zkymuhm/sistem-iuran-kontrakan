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
</head>

<body>
    <main class="halaman">
        @if (session('berhasil'))
        <div class="pesan pesan-berhasil">{{ session('berhasil') }}</div>
        @endif

        @error('aksi')
        <div class="pesan pesan-gagal">{{ $message }}</div>
        @enderror

        @yield('isi')
    </main>

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