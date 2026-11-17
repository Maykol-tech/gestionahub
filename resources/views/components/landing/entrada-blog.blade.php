@props(['p', 'i' => 0])
{{-- GEST-97: tarjeta de una entrada del blog WordPress --}}
<a class="blog-card rv" href="{{ $p['enlace'] }}" target="_blank" rel="noopener" style="--d:{{ $i * 100 }}ms" data-evento="blog_click">
    <div class="blog-img">
        @if ($p['imagen'])<img src="{{ $p['imagen'] }}" alt="" loading="lazy">@else<span>📰</span>@endif
    </div>
    <div class="blog-body">
        <small>{{ $p['fecha'] }}</small>
        <strong>{{ $p['titulo'] }}</strong>
        <p>{{ $p['resumen'] }}</p>
        <span class="blog-mas">Leer artículo →</span>
    </div>
</a>
