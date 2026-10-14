{{--
    Tela do Mural (feed de notícias). Lista as matérias publicadas da
    categoria "Mural" (Notícias e SOS DPEMT têm páginas próprias), com
    busca por palavra-chave e filtro por período.
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="newspaper" title="Mural" subtitle="Acompanhe as últimas notícias e informações." />

    <!-- Filtros e busca -->
    <div class="ui-card p-5 sm:p-6">
        @if ($search !== '' || $period !== '')
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

    @if ($articles->isEmpty())
        <!-- Estado vazio -->
        <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
            Nenhuma notícia encontrada.
        </div>
    @else
        <!-- Grade de notícias -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($articles as $article)
                <article class="ui-card card hover:shadow-md transition flex flex-col">
                    <!-- Capa (imagem ou cor + ícone) -->
                    <div class="p-2.5">
                        <div class="relative h-48 w-full rounded-lg flex items-center justify-center overflow-hidden" style="background-color: {{ $article->cover_color }};">
                            @if ($article->featuredImageUrl())
                                <img src="{{ $article->featuredImageUrl() }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $article->title }}" />
                            @endif
                            <div class="absolute top-2 right-2 z-10 flex items-center gap-1.5">
                                <x-ui.view-count :count="$article->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-white/90 dark:bg-(--bg-card) px-3 py-1.5 text-xs font-bold text-slate-500 dark:text-(--text-secondary)" />
                                <button wire:click="toggleLike({{ $article->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-white/90 dark:bg-(--bg-card) px-3 py-1.5 text-xs font-bold transition hover:bg-white dark:hover:bg-(--bg-hover) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}">
                                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                    <span>{{ $article->likedByUsers()->count() }}</span>
                                </button>
                            </div>
                            @unless ($article->featuredImageUrl())
                                <div class="text-center text-white space-y-2 z-10 p-2">
                                    <i class="fa-solid fa-{{ $article->cover_icon }} text-3xl"></i>
                                </div>
                            @endunless
                            @if ($article->cover_label)
                                <span class="absolute bottom-2 left-2 z-10 rounded-md px-2 py-1 text-xs font-bold uppercase tracking-widest text-white" style="background-color: rgba(0, 0, 0, 0.4);">
                                    {{ $article->cover_label }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Conteúdo -->
                    <div class="flex grow flex-col px-4 pb-5 pt-1 text-center sm:px-5">
                        @php($muralCategory = $article->categories->firstWhere('slug', 'mural'))
                        <div>
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded" style="background-color: {{ $muralCategory?->lightColor() ?? 'var(--brand-primary-light, #0C4A341a)' }}; color: {{ $muralCategory?->color ?? 'var(--brand-primary, #0C4A34)' }};">{{ $muralCategory?->name ?? 'Geral' }}</span>
                        </div>
                        <div class="mt-1">
                            <a href="{{ Route::has('mural.show') ? route('mural.show', $article) : '#' }}" class="text-lg font-medium text-slate-700 dark:text-(--text-primary) hover:text-emerald-700 focus:text-emerald-700">
                                {{ $article->title }}
                            </a>
                        </div>
                        <div class="my-2 flex items-center space-x-3 text-xs text-slate-400 dark:text-(--text-muted)">
                            <div class="h-px flex-1 bg-slate-200 dark:bg-(--border-color)"></div>
                            <p>{{ $article->published_at?->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
                            <div class="h-px flex-1 bg-slate-200 dark:bg-(--border-color)"></div>
                        </div>
                        <p class="my-2 grow text-left text-sm text-slate-600 dark:text-(--text-secondary) line-clamp-3">{{ $article->excerpt }}</p>
                        <div>
                            <a href="{{ Route::has('mural.show') ? route('mural.show', $article) : '#' }}" class="inline-flex items-center justify-center rounded-full px-5 py-2 mt-4 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition" style="background-color: var(--brand-primary, #0C4A34);">
                                Leia o artigo
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="pt-4">
            {{ $articles->links() }}
        </div>
    @endif
</div>
