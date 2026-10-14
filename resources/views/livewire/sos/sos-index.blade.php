{{--
    Tela SOS DPEMT.
    Mesmo comportamento do Mural (busca, filtro por período, paginação de
    9 e curtidas), só filtrando a categoria "sos-dpemt" — em grade de
    cards (capa com foto/cor, card sobreposto por baixo com selo da
    categoria, título, resumo, autor e data), com link pra matéria completa.
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header
        icon="triangle-exclamation"
        title="SOS DPEMT"
        subtitle="Avisos e informativos urgentes do órgão."
        icon-bg="background-color: #fee2e2;"
        icon-color="text-red-600"
    />

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
            Nenhum informativo urgente no momento.
        </div>
    @else
        <!-- Grade de avisos -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6 xl:grid-cols-4">
            @foreach ($articles as $article)
                <div class="flex flex-col group" wire:key="sos-{{ $article->id }}">
                    <div class="relative h-44 w-full rounded-2xl overflow-hidden flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
                        @if ($article->featuredImageUrl())
                            <img src="{{ $article->featuredImageUrl() }}" alt="{{ $article->title }}" class="absolute inset-0 h-full w-full object-cover object-center group-hover:scale-105 transition duration-500" />
                        @else
                            <i class="fa-solid fa-{{ $article->cover_icon }} text-4xl text-white/80"></i>
                        @endif

                        <div class="absolute top-2 right-2 z-10 flex items-center gap-1.5">
                            <x-ui.view-count :count="$article->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-white/90 dark:bg-(--bg-card) px-3 py-1.5 text-xs font-bold text-slate-500 dark:text-(--text-secondary)" />
                            <button type="button" wire:click="toggleLike({{ $article->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-white/90 dark:bg-(--bg-card) px-3 py-1.5 text-xs font-bold transition hover:bg-white dark:hover:bg-(--bg-hover) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}">
                                <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                <span>{{ $article->likedByUsers()->count() }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="ui-card relative z-10 -mt-8 grow hover:shadow-md transition p-4">
                        @php($sosCategory = $article->categories->firstWhere('slug', 'sos-dpemt'))
                        @if ($sosCategory)
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded mb-1.5" style="background-color: {{ $sosCategory->lightColor() }}; color: {{ $sosCategory->color }};">
                                {{ $sosCategory->name }}
                            </span>
                        @endif

                        <a href="{{ route('mural.show', $article) }}" class="font-bold text-sm text-slate-800 dark:text-(--text-primary) line-clamp-1 hover:text-(--brand-primary) transition block">
                            {{ $article->title }}
                        </a>

                        <p class="mt-2 grow text-sm text-slate-600 dark:text-(--text-secondary) line-clamp-3">{{ $article->excerpt }}</p>

                        <div class="mt-4 flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-xs text-slate-600 dark:text-(--text-secondary) min-w-0">
                                <x-ui.avatar :name="$article->author?->name" size="xs" />
                                <span class="truncate">{{ $article->author?->name ?? '—' }}</span>
                            </span>
                            <span class="shrink-0 flex items-center gap-1.5 text-xs text-slate-400 dark:text-(--text-muted)">
                                <i class="fa-regular fa-calendar"></i>
                                <span>{{ $article->published_at?->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="pt-4">
            {{ $articles->links() }}
        </div>
    @endif
</div>
