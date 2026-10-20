{{--
    View de paginação do Livewire (WithPagination), publicada em
    resources/views/vendor/livewire/tailwind.blade.php pra sobrescrever a
    original do pacote — e não resources/views/vendor/pagination/, que só
    vale pra paginação "crua" do Laravel (sem Livewire), nunca usada neste
    projeto. Sem isso, os botões ficavam cinza genérico (bg-white/
    dark:bg-gray-800), sem acompanhar nem o fundo/borda do tema nem a cor
    da marca na página atual. "var(--admin-primary, var(--brand-primary,
    ...))" porque esta view é compartilhada pelo admin (--admin-primary) e
    pelo site público (--brand-primary): o fallback só entra quando a
    variável de fora não existe nessa página.

    $scrollTo fica false (a view padrão do Livewire usa 'body' por
    padrão): isso é o que fazia a página subir pro topo a cada clique de
    paginação — o `x-on:click="...closest('body').scrollIntoView()"` da
    view original manda rolar o <body> pra dentro da área visível, ou
    seja, pro topo da página, mesmo sem nenhuma navegação acontecer.

    Todo botão (número, anterior, próximo) renderiza SEMPRE como <button>,
    em qualquer estado — nunca troca pra <span> nem usa o atributo
    "disabled" de verdade (nem wire:loading.attr="disabled"). Trocar de
    tag ou desabilitar o elemento que acabou de receber o clique faz o
    Livewire destruir/recriar o nó (ou o navegador forçar blur), tirando o
    foco — o que também pode levar o navegador a rolar a página.
--}}
@php
    $scrollTo = false;

    $activeBg = 'background-color: var(--admin-primary, var(--brand-primary, #0C4A34)); color: #fff;';
    $enabledStyle = 'background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary);';
    $disabledStyle = 'background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-muted); cursor: default;';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
            <div class="flex justify-between flex-1 sm:hidden">
                <button type="button" wire:click.preserve-scroll="previousPage('{{ $paginator->getPageName() }}')" class="relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md leading-5 transition {{ $paginator->onFirstPage() ? '' : 'hover:bg-(--bg-hover)' }}" style="{{ $paginator->onFirstPage() ? $disabledStyle : $enabledStyle }}">
                    {!! __('pagination.previous') !!}
                </button>

                <button type="button" wire:click.preserve-scroll="nextPage('{{ $paginator->getPageName() }}')" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium rounded-md leading-5 transition {{ $paginator->hasMorePages() ? 'hover:bg-(--bg-hover)' : '' }}" style="{{ $paginator->hasMorePages() ? $enabledStyle : $disabledStyle }}">
                    {!! __('pagination.next') !!}
                </button>
            </div>

            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm leading-5" style="color: var(--text-secondary);">
                        <span>{!! __('Showing') !!}</span>
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        <span>{!! __('to') !!}</span>
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                        <span>{!! __('of') !!}</span>
                        <span class="font-medium">{{ $paginator->total() }}</span>
                        <span>{!! __('results') !!}</span>
                    </p>
                </div>

                <div>
                    <span class="relative z-0 inline-flex rtl:flex-row-reverse rounded-md shadow-sm">
                        {{-- Previous Page Link --}}
                        <button type="button" wire:click.preserve-scroll="previousPage('{{ $paginator->getPageName() }}')" class="relative inline-flex items-center px-2 py-2 rounded-l-md leading-5 transition {{ $paginator->onFirstPage() ? '' : 'hover:bg-(--bg-hover)' }}" style="{{ $paginator->onFirstPage() ? $disabledStyle : $enabledStyle }}" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium leading-5 cursor-default" style="{{ $enabledStyle }}">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @php $isCurrent = $page == $paginator->currentPage(); @endphp
                                    <button
                                        type="button"
                                        wire:click.preserve-scroll="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}"
                                        class="relative inline-flex items-center px-4 py-2 -ml-px text-sm leading-5 transition {{ $isCurrent ? 'font-bold cursor-default' : 'font-medium hover:bg-(--bg-hover)' }}"
                                        style="{{ $isCurrent ? $activeBg.' border: 1px solid transparent;' : $enabledStyle }}"
                                        @if ($isCurrent) aria-current="page" @endif
                                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    >
                                        {{ $page }}
                                    </button>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        <button type="button" wire:click.preserve-scroll="nextPage('{{ $paginator->getPageName() }}')" class="relative inline-flex items-center px-2 py-2 -ml-px rounded-r-md leading-5 transition {{ $paginator->hasMorePages() ? 'hover:bg-(--bg-hover)' : '' }}" style="{{ $paginator->hasMorePages() ? $enabledStyle : $disabledStyle }}" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
