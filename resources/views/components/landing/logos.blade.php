@props(['logos' => []])
{{-- GEST-89: franja de logos de clientes que se desplaza sola --}}
@if (count($logos))
    <div class="logos-cinta rv" aria-label="Negocios que usan GestionaHub">
        <div class="logos-pista">
            @foreach (array_merge($logos, $logos) as $l)
                @php($contenidoLogo = ! empty($l['imagen_url']) ? '<img src="' . e($l['imagen_url']) . '" alt="' . e($l['nombre']) . '" loading="lazy">' : '<span>' . e($l['nombre']) . '</span>')
                @if (! empty($l['enlace']))
                    <a class="logo-item" href="{{ $l['enlace'] }}" target="_blank" rel="noopener">{!! $contenidoLogo !!}</a>
                @else
                    <div class="logo-item">{!! $contenidoLogo !!}</div>
                @endif
            @endforeach
        </div>
    </div>
@endif
