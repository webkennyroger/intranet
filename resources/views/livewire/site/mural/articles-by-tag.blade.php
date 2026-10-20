{{--
    Lista pública de matérias (de qualquer categoria) marcadas com a tag
    selecionada: mesmo card usado no Mural/Notícias/SOS, pra manter o
    design consistente em toda página que lista artigos, + barra lateral
    com "Explore por tags".
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="tag" :title="'Tag: #'.$tag" subtitle="Matérias marcadas com esta tag." :back="route('mural.index')" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna principal: matérias -->
        <div class="lg:col-span-2 space-y-6">
            {{-- Lista de matérias — isolada num island: curtir e paginar
                 só atualizam esta região. --}}
            @island(name: 'tagged-articles', always: true)
            @if ($this->articles->isEmpty())
                <!-- Estado vazio -->
                <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                    Nenhuma matéria encontrada com esta tag.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach ($this->articles as $article)
                        <article class="ui-card card hover:shadow-md transition flex flex-col" wire:key="tagged-{{ $article->id }}">
                            <!-- Capa (imagem ou cor + ícone) -->
                            <div class="p-2.5">
                                <div class="relative h-48 w-full rounded-lg flex items-center justify-center overflow-hidden" style="background-color: {{ $article->cover_color }};">
                                    @if ($article->featuredImageUrl())
                                        <img src="{{ $article->featuredImageUrl() }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $article->title }}" />
                                    @endif
                                    <div class="absolute top-2 right-2 z-10 flex items-center gap-1.5">
                                        <x-ui.view-count :count="$article->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-white dark:bg-(--bg-surface) px-3 py-1.5 text-xs font-bold text-slate-500 dark:text-(--text-secondary)" />
                                        <button wire:click="toggleLike({{ $article->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-white dark:bg-(--bg-surface) px-3 py-1.5 text-xs font-bold transition hover:bg-slate-50 dark:hover:bg-(--bg-hover) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}">
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
                                @php($primaryCategory = $article->categories->first())
                                <div>
                                    <span class="inline-block text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded" style="background-color: {{ $primaryCategory?->lightColor() ?? 'var(--brand-primary-light, #0C4A341a)' }}; color: {{ $primaryCategory?->color ?? 'var(--brand-primary, #0C4A34)' }};">{{ $primaryCategory?->name ?? 'Geral' }}</span>
                                </div>
                                <div class="mt-1">
                                    <a href="{{ route('mural.show', $article) }}" class="text-lg font-medium text-slate-700 dark:text-(--text-primary) hover:text-emerald-700 focus:text-emerald-700">
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
                                    <a href="{{ route('mural.show', $article) }}" class="inline-flex items-center justify-center rounded-full px-5 py-2 mt-4 text-sm font-bold text-white shadow-sm hover:brightness-110 active:brightness-95 transition" style="background-color: var(--brand-primary, #0C4A34);">
                                        Leia o artigo
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Paginação -->
                <div>
                    {{ $this->articles->links() }}
                </div>
            @endif
            @endisland
        </div>

        <!-- Coluna lateral: tags -->
        <aside class="space-y-4 self-start">
            <x-site.popular-tags-card :tags="$popularTags" />
        </aside>
    </div>
</div>
