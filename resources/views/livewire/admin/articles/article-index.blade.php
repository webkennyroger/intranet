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
            @if (count($selected) > 0)
                <span class="text-xs font-semibold" style="color: var(--text-muted);">{{ count($selected) }} selecionado(s)</span>
                @can('articles.update')
                    <x-ui.button type="button" wire:click="bulkPublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-check mr-1"></i> Publicar
                    </x-ui.button>
                    <x-ui.button type="button" wire:click="bulkUnpublish" variant="secondary" size="sm">
                        <i class="fa-solid fa-ban mr-1"></i> Despublicar
                    </x-ui.button>
                @endcan
                @can('articles.delete')
                    <x-ui.button type="button" @click="confirmAction('Confirma a exclusão de {{ count($selected) }} artigo(s) selecionado(s)?', () => $wire.bulkDelete())" variant="danger" size="sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                    </x-ui.button>
                @endcan
                <x-ui.button type="button" wire:click="deselectAll" variant="ghost" size="sm">Cancelar</x-ui.button>
            @else
                <x-ui.button :href="route('admin.articles.create')">
                    <i class="fa-solid fa-plus mr-1"></i> Nova notícia
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Estatísticas (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-admin.stat-card label="Total de artigos" :value="$totalArticles" sublabel="No mural" />
        <x-admin.stat-card label="Publicados" :value="$publishedArticles" sublabel="Visíveis para todos" valueColor="var(--admin-primary-dark)" />
        <x-admin.stat-card label="Rascunhos" :value="$draftArticles" sublabel="Ainda não publicados" />
        <x-admin.stat-card label="Curtidas" :value="$totalLikes" sublabel="No total" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Barra Lateral -->
        <aside class="lg:col-span-1 space-y-4">
            <!-- Busca -->
            <x-ui.card>
                <div class="font-semibold text-sm mb-2" style="color: var(--text-primary);">Buscar</div>
                <x-ui.input wire:model.live.debounce.400ms="search" icon="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" placeholder="Buscar artigo..." />
            </x-ui.card>

            <!-- Categorias -->
            <x-ui.card>
                <div class="font-semibold text-sm mb-2" style="color: var(--text-primary);">Categorias</div>
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
            </x-ui.card>

            <!-- Arquivo -->
            @if ($archiveYears->isNotEmpty())
                <x-ui.card>
                    <div class="font-semibold text-sm mb-2" style="color: var(--text-primary);">Arquivo</div>
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
                </x-ui.card>
            @endif

            <!-- Posts Populares -->
            @if ($popularPosts->isNotEmpty())
                <x-ui.card>
                    <div class="font-semibold text-sm mb-3" style="color: var(--text-primary);">Posts Populares</div>
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
                </x-ui.card>
            @endif

        </aside>

        <!-- Lista de Artigos -->
        <div class="lg:col-span-3 space-y-4">
            @if ($search !== '' || $categoryId !== '' || $year !== '')
                <div class="flex items-center justify-between rounded-xl px-4 py-2.5 text-sm" style="background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary);">
                    <span>Filtros aplicados — {{ $articles->total() }} {{ $articles->total() === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" wire:click="clearFilters" class="font-semibold hover:underline" style="color: var(--admin-primary);">Limpar filtros</button>
                </div>
            @endif

            @if ($articles->isEmpty())
                <x-ui.empty-state title="Nenhum artigo encontrado." icon="document" />
            @else
                <div class="flex items-center justify-between text-xs px-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none" style="color: var(--text-secondary);">
                        <x-ui.checkbox wire:click="toggleSelectAllOnPage" :checked="$allSelectedOnPage" />
                        Selecionar todos nesta página
                    </label>
                </div>

                @foreach ($articles as $article)
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

                <div>{{ $articles->links() }}</div>
            @endif
        </div>
    </div>
</div>
