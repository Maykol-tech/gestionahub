{{-- Paginacion con el estilo del panel. Uso: {{ $lista->links('vendor.pagination.gestionahub') }} --}}
@if ($paginator->hasPages())
    <nav class="pg" role="navigation" aria-label="Paginación">
        <div class="pg-info">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </div>
        <ul class="pg-list">
            @if ($paginator->onFirstPage())
                <li class="off"><span aria-hidden="true"><x-icon name="chev-l" size="14" /></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior"><x-icon name="chev-l" size="14" /></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="off"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="on" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente"><x-icon name="chev-r" size="14" /></a></li>
            @else
                <li class="off"><span aria-hidden="true"><x-icon name="chev-r" size="14" /></span></li>
            @endif
        </ul>
    </nav>
@endif
