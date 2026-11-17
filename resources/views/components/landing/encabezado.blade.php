@props(['eyebrow' => null, 'titulo', 'subtitulo' => null])
{{-- GEST-89: encabezado de sección de la landing (etiqueta, título y bajada) --}}
<div {{ $attributes->merge(['class' => 'sec-hd rv']) }}>
    @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
    <h2>{{ $titulo }}</h2>
    @if ($subtitulo)<p>{{ $subtitulo }}</p>@endif
</div>
