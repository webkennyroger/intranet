{{--
    Tela do Mural (feed de notícias).
    Lista as matérias publicadas, com busca por palavra-chave e
    filtros por categoria e período.
--}}
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Cabeçalho da página -->
    <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Mural</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Acompanhe as últimas notícias e informações.</p>
    </div>

    <!-- Filtros e busca -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <x-ui.input wire:model.live.debounce.400ms="search" label="Buscar por palavra chave" />
            </div>
            <div class="md:col-span-4">
                <x-ui.select wire:model.live="categoryId" label="Categoria">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="md:col-span-3">
                <x-ui.select wire:model.live="period" label="Período">
                    <option value="">Todos</option>
                    <option value="7d">Últimos 7 dias</option>
                    <option value="30d">Últimos 30 dias</option>
                    <option value="year">Este ano</option>
                </x-ui.select>
            </div>
        </div>
    </div>

    @if ($articles->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhuma notícia encontrada.
        </div>
    @else
        <!-- Grade de notícias -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($articles as $article)
                <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col">
                    <div class="relative h-52 flex items-center justify-center p-4 overflow-hidden" style="background-color: {{ $article->cover_color }};">
                        <div class="absolute top-3 right-3 z-10">
                            <span class="bg-emerald-800/90 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Notícia</span>
                        </div>
                        <div class="text-center text-white space-y-2 z-10 p-2">
                            <i class="fa-solid fa-{{ $article->cover_icon }} text-3xl"></i>
                            @if ($article->cover_label)
                                <p class="text-xs font-bold uppercase tracking-widest">{{ $article->cover_label }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <button wire:click="toggleLike({{ $article->id }})" class="flex items-center gap-1.5 {{ $article->isLikedBy(auth()->user()) ? 'text-emerald-700' : 'text-slate-500' }} hover:text-emerald-700 font-medium">
                                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-thumbs-up"></i>
                                    <span>{{ $article->likedByUsers()->count() }}</span>
                                </button>
                                <span class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar"></i>
                                    {{ $article->published_at?->format('d/m/Y') }}
                                </span>
                            </div>
                            <h2 class="font-extrabold text-slate-800 text-sm leading-snug">{{ $article->title }}</h2>
                            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">{{ $article->excerpt }}</p>
                        </div>

                        <div class="pt-2">
                            <a href="{{ Route::has('mural.show') ? route('mural.show', $article) : '#' }}" class="text-emerald-700 font-bold text-xs hover:underline inline-flex items-center gap-1">
                                Saiba mais <i class="fa-solid fa-arrow-right text-[10px]"></i>
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
