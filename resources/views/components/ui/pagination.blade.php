@props([
    'paginator' => null,
    'compact' => false,
])

@if($paginator && $paginator->hasPages())
    <nav class="flex items-center justify-between" aria-label="Pagination">
        @if(!$compact)
            <div class="text-sm" style="color: var(--text-secondary);">
                Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} resultados
            </div>
        @endif

        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if($paginator->onFirstPage())
                <span class="px-3 py-2 rounded-lg text-sm cursor-not-allowed" style="color: var(--text-muted);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="px-3 py-2 rounded-lg text-sm transition-colors hover:bg-[var(--bg-hover)]" style="color: var(--text-secondary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Pages --}}
            @foreach($paginator->getUrlRange($paginator->currentPage() - 2, $paginator->currentPage() + 2) as $page => $url)
                @if($page == $paginator->currentPage())
                    <span class="px-3 py-2 rounded-lg text-sm bg-emerald-500/20 text-emerald-400 font-medium border border-emerald-500/30">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $url }}"
                       class="px-3 py-2 rounded-lg text-sm transition-colors hover:bg-[var(--bg-hover)]" style="color: var(--text-secondary);">
                        {{ $page }}
                    </a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="px-3 py-2 rounded-lg text-sm transition-colors hover:bg-[var(--bg-hover)]" style="color: var(--text-secondary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="px-3 py-2 rounded-lg text-sm cursor-not-allowed" style="color: var(--text-muted);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
