@props(['t', 'i' => 0])
{{-- GEST-89: tarjeta de testimonio --}}
@php($estrellas = max(1, min(5, (int) ($t['estrellas'] ?? 5))))
<figure class="testi rv" style="margin:0;--d:{{ $i * 100 }}ms">
    <div class="stars" aria-label="{{ $estrellas }} estrellas">{{ str_repeat('★', $estrellas) }}<span style="opacity:.25">{{ str_repeat('★', 5 - $estrellas) }}</span></div>
    <p>“{{ $t['texto'] }}”</p>
    <figcaption class="testi-who">
        @if (! empty($t['foto_url']))
            <img class="avatar" src="{{ $t['foto_url'] }}" alt="" loading="lazy" style="object-fit:cover;">
        @else
            <span class="avatar">{{ collect(explode(' ', $t['nombre']))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('') }}</span>
        @endif
        <div><strong>{{ $t['nombre'] }}</strong><span>{{ $t['negocio'] ?? '' }}</span></div>
    </figcaption>
</figure>
