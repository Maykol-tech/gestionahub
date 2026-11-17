@php
    $nombreNegocio = $config['negocio']['nombre'] ?? 'Tu negocio';
    $iniciales = collect(explode(' ', trim($nombreNegocio)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp
<div class="neg">
    @if ($config['negocio']['logo'] ?? null)
        <img src="{{ $config['negocio']['logo'] }}" alt="">
    @else
        <div class="ini">{{ $iniciales }}</div>
    @endif
    <div><b>{{ $nombreNegocio }}</b><span>{{ $leyenda ?? 'Membresías' }}</span></div>
</div>
