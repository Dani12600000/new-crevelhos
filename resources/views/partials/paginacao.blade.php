@if ($paginator->hasPages())
    <nav aria-label="Páginas">
        <ul class="paginacao">
            @if ($paginator->onFirstPage())
                <li class="desativado"><span>‹ Anterior</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Anterior</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="desativado"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Seguinte ›</a></li>
            @else
                <li class="desativado"><span>Seguinte ›</span></li>
            @endif
        </ul>
    </nav>
@endif
