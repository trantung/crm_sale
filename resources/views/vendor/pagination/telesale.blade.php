@if ($paginator->hasPages())
    <nav class="flex items-center gap-1 flex-wrap">
        @if ($paginator->onFirstPage())
            <span class="ts-page-link" aria-disabled="true">|&lt;</span>
            <span class="ts-page-link" aria-disabled="true">&lt;</span>
        @else
            <a class="ts-page-link" href="{{ $paginator->url(1) }}">|&lt;</a>
            <a class="ts-page-link" href="{{ $paginator->previousPageUrl() }}">&lt;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="ts-page-link" aria-disabled="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="ts-page-link active">{{ $page }}</span>
                    @else
                        <a class="ts-page-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="ts-page-link" href="{{ $paginator->nextPageUrl() }}">&gt;</a>
            <a class="ts-page-link" href="{{ $paginator->url($paginator->lastPage()) }}">&gt;|</a>
        @else
            <span class="ts-page-link" aria-disabled="true">&gt;</span>
            <span class="ts-page-link" aria-disabled="true">&gt;|</span>
        @endif
    </nav>
@endif
