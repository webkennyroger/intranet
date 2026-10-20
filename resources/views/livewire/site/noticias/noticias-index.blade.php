{{--
    Tela pública de Notícias: mesmo design da tela admin de gestão de
    notícias (barra lateral com busca/arquivo/populares + lista de cards),
    só que somente leitura — sem nenhuma ação de criar/editar/excluir/
    publicar, e sempre restrita à categoria "Notícia".
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="comment-dots" title="Notícias" subtitle="Comunicados e informativos oficiais da Defensoria." />

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Barra Lateral -->
        <aside class="lg:col-span-1 space-y-4">
            <!-- Busca -->
            <div class="ui-card p-4 space-y-2">
                <div class="font-bold text-sm mb-2 text-slate-800 dark:text-(--text-primary)">Buscar</div>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 dark:text-(--text-muted)"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Buscar notícia..."
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-(--border-color) dark:bg-(--bg-hover) dark:text-(--text-primary) focus:outline-none focus:ring-2 focus:ring-(--brand-primary)/40 focus:border-(--brand-primary) transition"
                    />
                </div>
                <button type="button" wire:click="$refresh" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition" style="background-color: var(--brand-primary, #0C4A34);">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    <span>Buscar</span>
                </button>
            </div>

            <!-- Arquivo -->
            @if ($archiveYears->isNotEmpty())
                <div class="ui-card p-4">
                    <div class="font-bold text-sm mb-2 text-slate-800 dark:text-(--text-primary)">Arquivo</div>
                    <ul class="space-y-1 text-sm">
                        @foreach ($archiveYears as $archiveYear => $count)
                            <li>
                                <button
                                    type="button"
                                    wire:click="$set('year', '{{ $year === (string) $archiveYear ? '' : $archiveYear }}')"
                                    class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg transition {{ $year === (string) $archiveYear ? 'bg-slate-100 dark:bg-(--bg-elevated) text-slate-800 dark:text-(--text-primary) font-semibold' : 'text-slate-500 dark:text-(--text-secondary) hover:bg-slate-50 dark:hover:bg-(--bg-hover)' }}"
                                >
                                    <span><i class="fa-solid fa-chevron-right text-[10px] mr-1.5 opacity-50"></i>{{ $archiveYear }}</span>
                                    <span class="text-xs text-slate-400 dark:text-(--text-muted)">{{ $count }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Mais Lidas -->
            @if ($popularPosts->isNotEmpty())
                <div class="ui-card p-4">
                    <div class="font-bold text-sm mb-3 text-slate-800 dark:text-(--text-primary)">Mais Lidas</div>
                    <ul class="space-y-3">
                        @foreach ($popularPosts as $post)
                            <li>
                                <a href="{{ route('mural.show', $post) }}" class="flex items-center gap-3 group">
                                    <div class="w-11 h-11 rounded-lg shrink-0 flex items-center justify-center overflow-hidden" style="background-color: {{ $post->cover_color }};">
                                        @if ($post->featuredImageUrl())
                                            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                        @else
                                            <i class="fa-solid fa-{{ $post->cover_icon }} text-white text-sm"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-(--text-primary) truncate group-hover:underline">{{ $post->title }}</p>
                                        <p class="text-xs text-slate-400 dark:text-(--text-muted)">{{ $post->published_at?->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-site.popular-tags-card :tags="$popularTags" />
        </aside>

        <!-- Lista de Notícias -->
        <div class="lg:col-span-3 space-y-4">
            @if ($search !== '' || $year !== '')
                <div class="ui-card flex items-center justify-between px-4 py-2.5 text-sm text-slate-500 dark:text-(--text-secondary)">
                    <span>Filtros aplicados — {{ $this->articles->total() }} {{ $this->articles->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" wire:click="clearFilters" class="font-semibold hover:underline" style="color: var(--brand-primary, #0C4A34);">Limpar filtros</button>
                </div>
            @endif

            {{-- Lista de notícias — isolada num island: curtir, buscar,
                 filtrar por ano e paginar só atualizam esta região, sem
                 re-renderizar a barra lateral. --}}
            @island(name: 'noticias-articles', always: true)
            @if ($this->articles->isEmpty())
                <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                    Nenhuma notícia encontrada.
                </div>
            @else
                @foreach ($this->articles as $article)
                    <article class="ui-card hover:shadow-md transition p-4 flex flex-col sm:flex-row gap-4 group" wire:key="noticia-{{ $article->id }}">
                        <a href="{{ route('mural.show', $article) }}" class="w-full sm:w-40 h-40 sm:h-28 rounded-xl shrink-0 overflow-hidden flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
                            @if ($article->featuredImageUrl())
                                <img src="{{ $article->featuredImageUrl() }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                            @else
                                <i class="fa-solid fa-{{ $article->cover_icon }} text-3xl text-white/80"></i>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1 space-y-1.5">
                            @php($noticiaCategory = $article->categories->firstWhere('slug', 'noticia'))
                            @if ($noticiaCategory)
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded" style="background-color: {{ $noticiaCategory->lightColor() }}; color: {{ $noticiaCategory->color }};">
                                    {{ $noticiaCategory->name }}
                                </span>
                            @endif

                            <a href="{{ route('mural.show', $article) }}" class="font-bold text-slate-800 dark:text-(--text-primary) hover:text-(--brand-primary) transition block">
                                {{ $article->title }}
                            </a>

                            <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-(--text-muted) flex-wrap">
                                <span><i class="fa-regular fa-calendar mr-1"></i>{{ $article->published_at?->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                                <span>|</span>
                                <x-ui.view-count :count="$article->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold text-slate-500 dark:text-(--text-secondary)" />
                                <span>|</span>
                                <button type="button" wire:click="toggleLike({{ $article->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold transition hover:bg-slate-100 dark:hover:bg-(--bg-elevated) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}">
                                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                    <span>{{ $article->likedByUsers()->count() }}</span>
                                </button>
                                <span>|</span>
                                <span><i class="fa-regular fa-user mr-1"></i>{{ $article->author?->name ?? '—' }}</span>
                            </div>

                            <p class="text-sm text-slate-600 dark:text-(--text-secondary) line-clamp-2">{{ $article->excerpt }}</p>

                            <a href="{{ route('mural.show', $article) }}" class="text-sm font-semibold hover:underline inline-flex items-center gap-1" style="color: var(--brand-primary, #0C4A34);">
                                Ler notícia completa <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </article>
                @endforeach

                <div>{{ $this->articles->links() }}</div>
            @endif
            @endisland
        </div>
    </div>
</div>
