{{--
    Tela de Documentos.
    Pastas fixas por categoria (Resoluções, Contratos etc.) com busca por
    palavra-chave e filtro por período, igual ao padrão do Mural. Dentro da
    pasta/filtro escolhido, lista os documentos em cards com título,
    tamanho real do arquivo (lido do storage) e descrição.
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="file-lines" title="Documentos" subtitle="Documentos institucionais disponíveis para consulta." />

    <!-- Filtros e busca -->
    <div class="ui-card p-5 sm:p-6">
        @if ($search !== '' || $categoryId !== '' || $period !== '')
            <div class="flex justify-end mb-3 -mt-1">
                <button type="button" wire:click="clearFilters" class="flex items-center gap-1.5 text-xs font-semibold text-slate-400 dark:text-(--text-muted) hover:text-rose-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                    Limpar filtros
                </button>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 dark:text-(--text-muted)"></i>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Buscar por palavra-chave..."
                    class="w-full rounded-full pl-11 pr-4 py-3 text-sm border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) dark:text-(--text-primary) focus:bg-white dark:focus:bg-(--bg-input) focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200"
                />
            </div>

            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <i class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 dark:text-(--text-muted) pointer-events-none z-10"></i>
                    <x-ui.select wire:model.live="period" placeholder="Período" class="rounded-full! pl-11! pr-8! py-3! border-slate-200! dark:border-(--border-color)! bg-slate-50! dark:bg-(--bg-hover)! dark:text-(--text-primary)! w-44">
                        <option value="7d">Últimos 7 dias</option>
                        <option value="30d">Últimos 30 dias</option>
                        <option value="year">Este ano</option>
                    </x-ui.select>
                </div>

                <button
                    type="button"
                    wire:click="$refresh"
                    class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition shrink-0"
                    style="background-color: var(--brand-primary, #0C4A34);"
                >
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    <span>Buscar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Pastas (uma por categoria, com contagem real) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-3">
        @foreach ($categories as $category)
            <button
                type="button"
                wire:click="selectCategory({{ $category->id }})"
                class="flex flex-col items-center gap-2 p-3 rounded-2xl border transition text-center {{ (string) $categoryId === (string) $category->id ? 'border-(--brand-primary)/40 bg-(--brand-primary)/5' : 'border-slate-200 dark:border-(--border-color) bg-white dark:bg-(--bg-card) hover:border-slate-300' }}"
            >
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $category->lightColor() }};">
                    <i class="fa-solid fa-{{ $category->icon }} text-lg" style="color: {{ $category->color }};"></i>
                </div>
                <span class="text-xs font-semibold text-slate-700 dark:text-(--text-secondary) leading-tight">{{ $category->name }}</span>
                <span class="text-[10px] text-slate-400 dark:text-(--text-muted)">{{ $category->documents_count }} {{ $category->documents_count === 1 ? 'arquivo' : 'arquivos' }}</span>
            </button>
        @endforeach
    </div>

    @if ($documents->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhum documento encontrado.
        </div>
    @else
        <!-- Lista de documentos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($documents as $document)
                <div class="ui-card flex items-start gap-3 p-4 hover:shadow-md transition">
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($document->fileUrl())
                            <a href="{{ $document->fileUrl() }}" target="_blank" title="Abrir documento" class="w-11 h-11 rounded-xl flex items-center justify-center {{ $document->fileIconClasses()['bg'] }} transition">
                                <i class="fa-solid fa-{{ $document->fileIcon() }} text-lg {{ $document->fileIconClasses()['text'] }}"></i>
                            </a>
                        @endif
                        @if ($document->isExternalLink())
                            <a href="{{ $document->link }}" target="_blank" title="Abrir link" class="w-11 h-11 rounded-xl flex items-center justify-center {{ $document->linkIconClasses()['bg'] }} transition">
                                <i class="fa-solid fa-link text-lg {{ $document->linkIconClasses()['text'] }}"></i>
                            </a>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800 dark:text-(--text-primary) leading-snug block">{{ $document->title }}</span>
                        <span class="text-[11px] text-slate-400 dark:text-(--text-muted) block mt-0.5">
                            @if ($document->category)
                                {{ $document->category->name }}
                            @endif
                            @if ($document->fileUrl())
                                @if ($document->category) · @endif {{ \App\Models\Document::formatSize($document->size()) }}
                            @endif
                        </span>
                        @if ($document->description)
                            <span class="text-xs text-slate-500 dark:text-(--text-secondary) line-clamp-2 block mt-1">{{ $document->description }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="pt-4">
            {{ $documents->links() }}
        </div>
    @endif
</div>
