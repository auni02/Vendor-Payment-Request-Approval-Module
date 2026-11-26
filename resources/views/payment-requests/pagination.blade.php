@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        <ul class="pager">
            <li @class(['disabled' => $paginator->onFirstPage()])>
                @if ($paginator->onFirstPage()) <span>&lsaquo;</span> @else <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a> @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li @class(['active' => $page == $paginator->currentPage()])>
                            @if ($page == $paginator->currentPage()) <span>{{ $page }}</span> @else <a href="{{ $url }}">{{ $page }}</a> @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li @class(['disabled' => ! $paginator->hasMorePages()])>
                @if ($paginator->hasMorePages()) <a href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a> @else <span>&rsaquo;</span> @endif
            </li>
        </ul>
    </nav>
@endif
