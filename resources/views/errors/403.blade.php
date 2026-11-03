@include('errors.tata-letak', [
    'kode'  => 403,
    'judul' => 'Akses Ditolak',
    'pesan' => $exception->getMessage() ?: 'Anda tidak memiliki kewenangan untuk melakukan tindakan ini.',
])
