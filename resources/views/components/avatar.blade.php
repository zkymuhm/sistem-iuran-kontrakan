{{-- Avatar inisial (Modul 6): huruf pertama dari maksimal dua kata nama. --}}
@props(['nama'])
@php
$inisial = collect(preg_split('/\s+/', trim($nama)))
    ->filter()
    ->take(2)
    ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
    ->implode('');
@endphp
<span class="avatar" aria-hidden="true">{{ $inisial }}</span>
