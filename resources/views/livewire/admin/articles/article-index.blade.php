{{--
    Tela de gestão de Notícias (admin) — Notícias, Mural e SOS DPEMT são a
    mesma tabela de artigos, só filtrada por categoria no menu. Barra
    lateral com busca, categorias, arquivo por ano e posts populares;
    lista principal em cards com publicar/despublicar e excluir. Criar e
    editar abrem a página dedicada do formulário (não é modal).
--}}
<div class="space-y-4">
    <!-- Cabeçalho da Página -->
    <x-admin.page-header
        icon="comment-dots"
        title="Notícias"
        subtitle="Gerencie as notícias e artigos publicados no mural interno."
        :breadcrumbs="[['label' => 'Painel', 'href' => route('dashboard')], ['label' => 'Notícias']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.articles.create')">
                <i class="fa-solid fa-plus mr-1"></i> Nova notícia
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas: um cartão só, ocupando a largura toda, dividido por
         uma linha fina — em vez de 4 cards de dashboard inteiros com um
         vão vazio depois deles. -->
    <div class="ui-card flex flex-wrap items-stretch divide-x divide-(--border-color)">
        <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                <i class="fa-solid fa-newspaper text-sm" style="color: #2563EB;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Total de artigos</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalArticles }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #059669 14%, transparent);">
                <i class="fa-solid fa-check text-sm" style="color: #059669;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Publicados</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $publishedArticles }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #F59E0B 14%, transparent);">
                <i class="fa-solid fa-pen text-sm" style="color: #F59E0B;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Rascunhos</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $draftArticles }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 flex-1 min-w-48">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #EC4899 14%, transparent);">
                <i class="fa-solid fa-heart text-sm" style="color: #EC4899;"></i>
            </span>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-bold uppercase tracking-wide leading-none" style="color: var(--text-muted);">Curtidas</p>
                <p class="text-xl font-black leading-tight" style="color: var(--text-primary);">{{ $totalLikes }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Barra Lateral -->
        <aside class="lg:col-span-1 space-y-4">
            <!-- Busca -->
            <x-ui.card :padding="false" style="border-top: 2px solid #2563EB;">
                <div class="p-5">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #2563EB 14%, transparent);">
                            <i class="fa-solid fa-magnifying-glass text-xs" style="color: #2563EB;"></i>
                        </span>
                        <span class="font-bold text-sm" style="color: var(--text-primary);">Buscar</span>
                    </div>
                    <div class="space-y-2">
                        <x-ui.input wire:model.live.debounce.400ms="search" icon="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" placeholder="Buscar artigo..." />
                        <x-ui.button type="button" wire:click="$refresh" class="w-full justify-center">
                            <i class="fa-solid fa-magnifying-glass mr-1"></i> Buscar
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            <!-- Categorias -->
            <x-ui.card :padding="false" style="border-top: 2px solid #D97706;">
                <div class="p-5">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #D97706 14%, transparent);">
                        <i class="fa-solid fa-folder text-xs" style="color: #D97706;"></i>
                    </span>
                    <span class="font-bold text-sm" style="color: var(--text-primary);">Categorias</span>
                </div>
                <ul class="space-y-1 text-sm">
                    <li>
                        <button
                            type="button"
                            wire:click="$set('categoryId', '')"
                            class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg transition"
                            style="{{ $categoryId === '' ? 'background-color: var(--bg-hover); color: var(--text-primary); font-weight: 600;' : 'color: var(--text-secondary);' }}"
                        >
                            <span><i class="fa-solid fa-chevron-right text-[10px] mr-1.5 opacity-50"></i>Todas</span>
                            <span class="text-xs" style="color: var(--text-muted);">{{ $totalArticles }}</span>
                        </button>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <button
                                type="button"
                                wire:click="$set('categoryId', '{{ $category->id }}')"
                                class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg transition"
                                style="{{ (string) $categoryId === (string) $category->id ? 'background-color: var(--bg-hover); color: var(--text-primary); font-weight: 600;' : 'color: var(--text-secondary);' }}"
                            >
                                <span class="flex items-center gap-1.5 truncate">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $category->color }};"></span>
                                    <span class="truncate">{{ $category->name }}</span>
                                </span>
                                <span class="text-xs shrink-0" style="color: var(--text-muted);">{{ $category->articles_count }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                </div>
            </x-ui.card>

            <!-- Arquivo -->
            @if ($archiveYears->isNotEmpty())
                <x-ui.card :padding="false" style="border-top: 2px solid #9333EA;">
                    <div class="p-5">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #9333EA 14%, transparent);">
                            <i class="fa-solid fa-calendar-days text-xs" style="color: #9333EA;"></i>
                        </span>
                        <span class="font-bold text-sm" style="color: var(--text-primary);">Arquivo</span>
                    </div>
                    <ul class="space-y-1 text-sm">
                        @foreach ($archiveYears as $archiveYear => $count)
                            <li>
                                <button
                                    type="button"
                                    wire:click="$set('year', '{{ $year === (string) $archiveYear ? '' : $archiveYear }}')"
                                    class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg transition"
                                    style="{{ $year === (string) $archiveYear ? 'background-color: var(--bg-hover); color: var(--text-primary); font-weight: 600;' : 'color: var(--text-secondary);' }}"
                                >
                                    <span><i class="fa-solid fa-chevron-right text-[10px] mr-1.5 opacity-50"></i>{{ $archiveYear }}</span>
                                    <span class="text-xs" style="color: var(--text-muted);">{{ $count }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    </div>
                </x-ui.card>
            @endif

            <!-- Posts Populares -->
            @if ($popularPosts->isNotEmpty())
                <x-ui.card :padding="false" style="border-top: 2px solid #EC4899;">
                    <div class="p-5">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, #EC4899 14%, transparent);">
                            <i class="fa-solid fa-fire text-xs" style="color: #EC4899;"></i>
                        </span>
                        <span class="font-bold text-sm" style="color: var(--text-primary);">Posts Populares</span>
                    </div>
                    <ul class="space-y-3">
                        @foreach ($popularPosts as $post)
                            <li>
                                <a href="{{ route('admin.articles.edit', $post) }}" class="flex items-center gap-3 group">
                                    <div class="w-11 h-11 rounded-lg shrink-0 flex items-center justify-center overflow-hidden" style="background-color: {{ $post->cover_color }};">
                                        @if ($post->featuredImageUrl())
                                            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="w-full h-full object-cover" />
                                        @else
                                            <i class="fa-solid fa-{{ $post->cover_icon }} text-white text-sm"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold truncate group-hover:underline" style="color: var(--text-primary);">{{ $post->title }}</p>
                                        <p class="text-xs" style="color: var(--text-muted);">{{ $post->published_at?->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    </div>
                </x-ui.card>
            @endif

        </aside>

        <!-- Lista de Artigos — isolada num island: selecionar linhas,
             paginar e publicar/excluir só atualizam esta região, sem
             re-renderizar o cabeçalho/estatísticas/barra lateral da
             página. -->
        @island(name: 'articles-list', always: true)
        <div class="lg:col-span-3 space-y-4">
            @if (count($this->selected) > 0)
                <div class="flex items-center justify-end gap-2">
                    <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($this->selected) }} selecionado(s)</span>
                    @can('articles.update')
                        <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                            <i class="fa-solid fa-check mr-1"></i> Publicar
                        </x-ui.button>
                        <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                            <i class="fa-solid fa-ban mr-1"></i> Despublicar
                        </x-ui.button>
                    @endcan
                    @can('articles.delete')
                        <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($this->selected) }} artigo(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                            <i class="fa-solid fa-trash mr-1"></i> Excluir
                        </x-ui.button>
                    @endcan
                    <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
                </div>
            @endif

            @if ($this->search !== '' || $this->categoryId !== '' || $this->year !== '')
                <div class="flex items-center justify-between rounded-xl px-4 py-2.5 text-sm" style="background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary);">
                    <span>Filtros aplicados — {{ $this->articles->total() }} {{ $this->articles->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" wire:click="clearFilters" class="font-semibold hover:underline" style="color: var(--admin-primary);">Limpar filtros</button>
                </div>
            @endif

            @if ($this->articles->isEmpty())
                <x-ui.empty-state title="Nenhum artigo encontrado." icon="document" />
            @else
                <div class="flex items-center justify-between text-xs px-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none" style="color: var(--text-secondary);">
                        <x-ui.checkbox wire:click="toggleSelectAllOnPage" :checked="$this->allSelectedOnPage" />
                        Selecionar todos nesta página
                    </label>
                </div>

                @foreach ($this->articles as $article)
                    <div class="rounded-2xl p-4 flex flex-col sm:flex-row gap-4" style="background-color: var(--bg-card); border: 1px solid var(--border-color);" wire:key="article-{{ $article->id }}">
                        <div class="shrink-0 pt-1 sm:pt-2">
                            <x-ui.checkbox wire:model.live="selected" value="{{ $article->id }}" />
                        </div>

                        <a href="{{ route('admin.articles.edit', $article) }}" class="w-full sm:w-40 h-40 sm:h-28 rounded-xl shrink-0 overflow-hidden flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
                            @if ($article->featuredImageUrl())
                                <img src="{{ $article->featuredImageUrl() }}" alt="{{ $article->title }}" class="w-full h-full object-cover" />
                            @else
                                <i class="fa-solid fa-{{ $article->cover_icon }} text-3xl text-white/80"></i>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                @foreach ($article->categories as $category)
                                    <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded" style="background-color: {{ $category->lightColor() }}; color: {{ $category->color }};">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                                @if ($article->isScheduled())
                                    <x-ui.badge variant="info" size="xs">Agendado</x-ui.badge>
                                @else
                                    <x-ui.badge :variant="$article->is_published ? 'success' : 'warning'" size="xs">{{ $article->is_published ? 'Publicado' : 'Rascunho' }}</x-ui.badge>
                                @endif
                            </div>

                            <a href="{{ route('admin.articles.edit', $article) }}" class="font-bold hover:underline block" style="color: var(--text-primary);">
                                {{ $article->title }}
                            </a>

                            <div class="flex items-center gap-3 text-xs flex-wrap" style="color: var(--text-muted);">
                                <span><i class="fa-regular fa-calendar mr-1"></i>{{ $article->published_at?->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') ?? 'Sem data' }}</span>
                                <span>|</span>
                                <span><i class="fa-regular fa-eye mr-1"></i>{{ $article->views_count }}</span>
                                <span>|</span>
                                <span><i class="fa-regular fa-heart mr-1"></i>{{ $article->likedByUsers()->count() }}</span>
                                <span>|</span>
                                <span><i class="fa-regular fa-user mr-1"></i>{{ $article->author?->name ?? '—' }}</span>
                            </div>

                            <p class="text-sm line-clamp-2" style="color: var(--text-secondary);">{{ $article->excerpt }}</p>
                        </div>

                        <div class="flex sm:flex-col items-center justify-center gap-2 shrink-0 self-center">
                            <x-ui.tooltip text="Editar">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(37, 99, 235, 0.1); color: #2563EB;">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </x-ui.tooltip>
                            @can('articles.update')
                                <x-ui.tooltip :text="$article->is_published ? 'Despublicar' : 'Publicar'">
                                    <button wire:click="togglePublish({{ $article->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                        <i class="fa-solid fa-{{ $article->is_published ? 'toggle-off' : 'toggle-on' }}"></i>
                                    </button>
                                </x-ui.tooltip>
                            @endcan
                            @can('articles.delete')
                                <x-ui.tooltip text="Excluir">
                                    <button @click="confirmAction('Confirma a exclusão deste artigo?', () => $wire.delete({{ $article->id }}))" class="w-9 h-9 inline-flex items-center justify-center rounded transition hover:brightness-95" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </x-ui.tooltip>
                            @endcan
                        </div>
                    </div>
                @endforeach

                <div>{{ $this->articles->links() }}</div>
            @endif
        </div>
        @endisland
    </div>
</div>
