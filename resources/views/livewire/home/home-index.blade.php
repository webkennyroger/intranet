{{--
Página Início: portal de notícias da intranet. Mesma estrutura visual do
mockup home.html (manchete + destaques, últimas notícias, galeria,
próximo evento e aniversariantes do mês), só que com dados reais vindos
do Mural, Fotos, Eventos e Usuários — o mesmo conteúdo gerido no admin.
--}}
<div class="mx-auto space-y-10">

    <!-- Barra superior do portal: data e aviso em destaque -->
    <div class="ui-card px-4 py-2.5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-500 dark:text-(--text-secondary) font-medium">
            <i class="fa-regular fa-calendar-check text-(--brand-primary)"></i>
            <span>{{ ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')) }}</span>
            <span class="text-slate-300 dark:text-(--text-muted)">|</span>
            <span class="text-(--brand-primary-dark) font-semibold"><i
                    class="fa-solid fa-circle text-[8px] text-(--brand-primary) mr-1"></i>Portal da intranet -
                Defensoria Pública do Estado de Mato Grosso</span>
        </div>
        @if ($headline)
            <div class="flex items-center gap-2 text-slate-600 dark:text-(--text-secondary) overflow-hidden">
                <span
                    class="bg-(--brand-primary)/10 text-(--brand-primary-dark) font-bold px-2 py-0.5 rounded text-[10px] uppercase tracking-wider shrink-0">Mural</span>
                <a href="{{ route('mural.show', $headline) }}"
                    class="truncate font-medium text-slate-700 dark:text-(--text-secondary) hover:text-(--brand-primary) transition">{{ $headline->title }}</a>
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- Em Destaque (coluna esquerda, alta) + Manchete, Últimas Notícias -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="font-extrabold text-2xl text-slate-800 dark:text-(--text-primary) uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-fire text-amber-500"></i> Em Destaque
                </h3>
            </div>

            @if ($highlighted->isNotEmpty())
                <div class="ui-card relative overflow-hidden flex-1 p-4 flex flex-col gap-4"
                    style="background-color: var(--color-primary-500, #22b573);">
                    <!-- Anéis decorativos concêntricos, "vazando" pela borda
                             direita no topo e na base do card (mesmo motivo da
                             demo uicolors.app: 3 raios por âncora, 2 âncoras). -->
                    @foreach ([0, 24] as $anchorTopRem)
                        @foreach ([22, 14, 6] as $ringSizeRem)
                            <div class="absolute rounded-full pointer-events-none"
                                style="width: {{ $ringSizeRem }}rem; height: {{ $ringSizeRem }}rem; top: {{ $anchorTopRem }}rem; right: 6rem; border: 1px solid var(--color-primary-600, #138856); transform: translate(50%, -50%);">
                            </div>
                        @endforeach
                    @endforeach
                    <!-- 1 de cada origem (Notícia, Mural, próximo Evento, álbum de
                             Fotos), sempre com foto — ver $highlighted em
                             HomeIndex::render(). Título da categoria em cima do
                             card (fora da foto), não dentro/sobre a imagem. -->
                    <div class="relative z-10 flex flex-col gap-4">
                        @foreach ($highlighted as $item)
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-white/80 mb-1.5">
                                    {{ $item['label'] }}
                                </p>
                                <a href="{{ $item['url'] }}"
                                    class="rounded-xl bg-black/10 hover:bg-black/20 transition overflow-hidden group flex flex-col shrink-0">
                                    <span class="block h-32 sm:h-40 relative overflow-hidden shrink-0">
                                        @if ($item['image'])
                                            <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                        @else
                                            <span class="w-full h-full flex items-center justify-center bg-black/15">
                                                <i class="fa-solid fa-{{ $item['icon'] }} text-3xl text-white/80"></i>
                                            </span>
                                        @endif
                                    </span>
                                    <span class="p-3">
                                        <span
                                            class="block text-sm font-bold text-white leading-snug line-clamp-2">{{ $item['title'] }}</span>
                                    </span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="ui-card p-6 text-sm text-center text-slate-500 dark:text-(--text-secondary)">
                    Nenhuma matéria em destaque.
                </div>
            @endif
        </div>

        <!-- Coluna direita: manchete, últimas notícias, galeria e agenda. -->
        <div class="lg:col-span-8 space-y-12">

            <!-- ========================================== -->
            <!-- 1. CAPA DO PORTAL (MANCHETE) -->
            <!-- ========================================== -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3
                        class="font-extrabold text-2xl text-slate-800 dark:text-(--text-primary) uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-regular fa-newspaper text-(--brand-primary-dark)"></i> Notícia
                    </h3>
                </div>

                @if ($headline)
                <!-- Manchete principal -->
                <article class="ui-card overflow-hidden hover:shadow-md transition flex flex-col group">
                    <a href="{{ route('mural.show', $headline) }}" class="flex flex-col flex-1 group">
                        <div class="relative overflow-hidden flex-1 min-h-55 sm:min-h-65">
                            <div class="absolute inset-0 group-hover:scale-105 transition duration-500"
                                style="{{ $headline->featuredImageUrl() ? 'background-image: url(' . $headline->featuredImageUrl() . '); background-size: cover; background-position: center;' : 'background-color: var(--brand-primary, #0C4A34);' }}">
                            </div>

                            <div class="absolute right-6 top-6 z-10 flex gap-2">
                                @php($headlineCategory = $headline->categories->first())
                                                    @if ($headlineCategory)
                                                        <span
                                                            class="font-black text-[11px] uppercase tracking-widest px-3 py-1 rounded-full shadow"
                                                            style="background-color: {{ $headlineCategory->color }}; color: {{ $headlineCategory->readableTextColor() }};">
                                                            {{ $headlineCategory->name }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @unless ($headline->featuredImageUrl())
                                                    <div class="absolute right-8 bottom-6 opacity-20 pointer-events-none">
                                                        <i class="fa-solid fa-{{ $headline->cover_icon }} text-9xl text-white"></i>
                                                    </div>
                                                @endunless
                                            </div>

                                            <div class="relative z-20 space-y-3 p-6 sm:p-8 pb-0 bg-white dark:bg-(--bg-card) shrink-0">
                                                <h2
                                                    class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-(--text-primary) leading-tight group-hover:text-(--brand-primary) transition">
                                                    {{ Str::upper($headline->title) }}
                                                </h2>
                                                <p
                                                    class="text-slate-500 dark:text-(--text-secondary) text-sm sm:text-base leading-relaxed line-clamp-2">
                                                    {{ $headline->excerpt }}
                                                </p>
                                            </div>
                                        </a>

                                        <div
                                            class="px-6 sm:px-8 pb-6 sm:pb-8 pt-2 bg-white dark:bg-(--bg-card) shrink-0 flex items-center justify-between text-xs text-slate-400 dark:text-(--text-muted) font-medium">
                                            <div class="flex items-center gap-4">
                                                <span class="flex items-center gap-1"><i class="fa-regular fa-clock"></i>
                                                    {{ $headline->published_at->diffForHumans() }}</span>
                                                @if ($headline->author)
                                                    <span>•</span>
                                                    <span class="flex items-center gap-1"><i class="fa-regular fa-user"></i>
                                                        {{ $headline->author->name }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" wire:click="toggleLike({{ $headline->id }})"
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold transition hover:bg-slate-100 dark:hover:bg-(--bg-elevated) {{ $headline->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}"
                                                    aria-label="Curtir matéria">
                                                    <i
                                                        class="fa-{{ $headline->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                                    <span>{{ $headline->likedByUsers()->count() }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                @else
                <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                    Nenhuma notícia publicada por enquanto.
                </div>
                @endif
            </div>

            <!-- ========================================== -->
            <!-- 2. ÚLTIMAS NOTÍCIAS DO PORTAL -->
            <!-- ========================================== -->
            @if ($latestNews->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between pb-5">
                    <h3
                        class="font-black text-lg text-slate-800 dark:text-(--text-primary) tracking-tight uppercase section-title-line flex items-center gap-2">
                        <i class="fa-regular fa-newspaper text-(--brand-primary-dark)"></i> Últimas Notícias do
                        Portal
                    </h3>
                    <a href="{{ route('mural.index') }}"
                        class="text-md text-(--brand-primary-dark) font-bold transition flex items-center gap-1 group">
                        Ver todo o acervo <i
                            class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1"></i>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($latestNews as $article)
                    @php($latestCategory = $article->categories->first())
                    <article class="ui-card overflow-hidden hover:shadow-md transition flex flex-col group"
                        wire:key="latest-{{ $article->id }}">
                        <a href="{{ route('mural.show', $article) }}"
                            class="h-44 relative overflow-hidden flex items-center justify-center"
                            style="background-color: {{ $article->cover_color }};">
                            @if ($article->featuredImageUrl())
                                <img src="{{ $article->featuredImageUrl() }}" alt="{{ $article->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                            @else
                                <i class="fa-solid fa-{{ $article->cover_icon }} text-4xl text-white/80"></i>
                            @endif
                            @if ($latestCategory)
                                <span
                                    class="absolute top-3 left-3 font-bold text-[10px] px-2.5 py-1 rounded-md uppercase shadow"
                                    style="background-color: {{ $latestCategory->color }}; color: {{ $latestCategory->readableTextColor() }};">
                                    {{ $latestCategory->name }}
                                </span>
                            @endif
                        </a>
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <div
                                    class="text-[11px] text-slate-400 dark:text-(--text-muted) font-medium mb-1.5 flex items-center gap-2">
                                    <span><i class="fa-regular fa-calendar"></i>
                                        {{ $article->published_at->format('d/m/Y') }}</span>
                                    <span>•</span>
                                    <span><i class="fa-regular fa-clock"></i> {{ $article->readingTimeMinutes() }}
                                        min
                                        leitura</span>
                                </div>
                                <a href="{{ route('mural.show', $article) }}"
                                    class="font-extrabold text-slate-800 dark:text-(--text-primary) text-base leading-snug group-hover:text-(--brand-primary) transition block">
                                    {{ $article->title }}
                                </a>
                                <p
                                    class="text-xs text-slate-600 dark:text-(--text-secondary) mt-2 line-clamp-3 leading-relaxed">
                                    {{ $article->excerpt }}
                                </p>
                            </div>
                            <a href="{{ route('mural.show', $article) }}"
                                class="pt-3 border-t border-slate-100 dark:border-(--border-color) flex items-center justify-between text-xs font-bold text-(--brand-primary-dark)">
                                <span>Leia mais</span>
                                <i
                                    class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i>
                            </a>
                        </div>
                    </article>
                    @endforeach
                </div>

            </section>
            @endif

            <!-- ========================================== -->
            <!-- 3. AGENDA & EVENTOS -->
            <!-- ========================================== -->
            <section class="space-y-4">
                <div class="flex items-center justify-between pb-5">
                    <h3 class="font-black text-lg text-slate-800 dark:text-(--text-primary) tracking-tight uppercase section-title-line flex items-center gap-2">
                        <i class="fa-regular fa-calendar-days text-(--brand-primary-dark)"></i> Agenda & Eventos
                        Institucionais
                    </h3>
                    <a href="#" class="text-md text-(--brand-primary-dark) font-bold transition flex items-center gap-1 group">
                        Ver agenda completa <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1"></i>
                    </a>
                </div>

                @if ($nextEvent)
                    <div class="ui-card p-6 flex flex-col md:flex-row gap-6 items-center">

                        <div class="w-full md:w-5/12 bg-slate-900 text-white rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between min-h-[200px]">
                            <div class="flex items-center justify-between z-10">
                                <span class="bg-red-600 text-white font-extrabold text-[10px] uppercase px-2.5 py-1 rounded">Próximo
                                    Evento</span>
                                @if ($nextEvent->location)
                                    <span class="text-xs text-slate-300 font-medium truncate max-w-[12rem]">{{ $nextEvent->location }}</span>
                                @endif
                            </div>

                            <div class="my-4 z-10">
                                <h4 class="text-xl font-black leading-tight text-white">
                                    {{ Str::upper($nextEvent->title) }}
                                </h4>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-slate-800 z-10">
                                <div class="flex items-center gap-2">
                                    <div class="bg-red-600 text-white px-3 py-1 rounded-lg text-center">
                                        <span class="block text-xs font-bold uppercase">{{ Str::upper($nextEvent->starts_at->translatedFormat('M')) }}</span>
                                        <span class="block text-lg font-black leading-none">{{ $nextEvent->starts_at->format('d') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-white">
                                            {{ $nextEvent->starts_at->format('H\hi') }}{{ $nextEvent->ends_at ? ' às ' . $nextEvent->ends_at->format('H\hi') : '' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <i
                                class="fa-solid fa-gavel text-slate-800 text-8xl absolute -right-4 -bottom-4 pointer-events-none"></i>
                        </div>

                        <div class="w-full md:w-7/12 space-y-4">
                            <div>
                                <span class="text-(--brand-primary) text-xs font-bold uppercase tracking-wider">Evento
                                    Institucional</span>
                                <h4 class="text-lg font-black text-slate-800 dark:text-(--text-primary) uppercase leading-snug mt-1">
                                    {{ $nextEvent->title }}
                                </h4>
                                @if ($nextEvent->description)
                                    <p class="text-xs sm:text-sm text-slate-600 dark:text-(--text-secondary) mt-2 leading-relaxed">
                                        {{ $nextEvent->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <x-ui.button :href="route('events.index')">
                                    <i class="fa-regular fa-calendar-days mr-1"></i> Ver todos os eventos
                                </x-ui.button>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <x-ui.button color="primary" wire:click="submit" class="px-5 py-2.5 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-2">
                                    <i class="fa-solid fa-pen-to-square"></i> Realizar Inscrição
                                </x-ui.button>
                                <button class="px-5 py-2.5 bg-slate-100 dark:bg-(--bg-elevated) hover:bg-slate-200 dark:hover:bg-(--bg-hover) text-slate-700 dark:text-(--text-secondary) font-bold text-xs rounded-xl transition flex items-center gap-2">
                                    <i class="fa-solid fa-download"></i> Baixar Programação (PDF)
                                </button>
                            </div>
                        </div>

                    </div>
                @else
                    <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                        Nenhum evento programado por enquanto.
                    </div>
                @endif
            </section>

        </div>
        <!-- /coluna direita -->
    </div>
    <!-- /Em Destaque + coluna direita -->

    <!-- ========================================== -->
    <!-- 4. GALERIA MULTIMÍDIA (largura toda, fora do grid) -->
    <!-- ========================================== -->
    <section class="space-y-4">
        <div class="flex items-center justify-between pb-5">
            <h3 class="font-black text-lg text-slate-800 dark:text-(--text-primary) tracking-tight uppercase section-title-line flex items-center gap-2">
                <i class="fa-solid fa-camera-retro text-(--brand-primary-dark)"></i> Galeria Multimídia
            </h3>
            <a href="{{ route('photos.index') }}" class="text-md text-(--brand-primary-dark) font-bold transition flex items-center gap-1 group">
                Ver todas as galerias <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1"></i>
            </a>
        </div>

        @if ($photoAlbumSlides->isNotEmpty())
            <!-- Componente nativo do kit de UI (x-ui.carousel): 4 álbuns por
                 vez, com botão de slide pra frente/trás quando houver mais
                 de 4 — cada "slide" é uma página de até 4 cards. Os slides
                 já vêm agrupados de HomeIndex::photoAlbumSlides(), que junta
                 um resto de 1-2 álbuns ao slide anterior em vez de deixar
                 uma última página quase vazia ao avançar. -->
            <x-ui.carousel>
                @foreach ($photoAlbumSlides as $albumsChunk)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($albumsChunk as $album)
                            <a href="{{ route('photos.show', $album) }}" wire:key="album-{{ $album->id }}" class="ui-card overflow-hidden group hover:shadow-md transition">
                                <div class="h-48 bg-slate-200 dark:bg-(--bg-elevated) relative overflow-hidden flex items-center justify-center">
                                    @if ($album->cover_image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($album->cover_image_path) }}" alt="{{ $album->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                    @else
                                        <i class="fa-solid fa-images text-4xl text-slate-400 dark:text-(--text-muted)"></i>
                                    @endif
                                    <div class="absolute inset-0 bg-slate-900/30 group-hover:bg-slate-900/10 transition">
                                    </div>
                                    <span class="absolute bottom-3 left-3 bg-white/90 dark:bg-(--bg-card) backdrop-blur text-slate-800 dark:text-(--text-primary) font-bold text-[10px] px-2.5 py-1 rounded-md flex items-center gap-1.5">
                                        <i class="fa-solid fa-images text-(--brand-primary)"></i> {{ $album->photos_count }} {{ $album->photos_count === 1 ? 'Foto' : 'Fotos' }}
                                    </span>
                                </div>
                                <div class="p-4 bg-white dark:bg-(--bg-card)">
                                    <h4 class="font-extrabold text-xs uppercase text-slate-800 dark:text-(--text-primary) group-hover:text-(--brand-primary) transition">
                                        {{ $album->title }}
                                    </h4>
                                    @if ($album->description)
                                        <p class="text-[11px] text-slate-500 dark:text-(--text-secondary) mt-1 line-clamp-2">
                                            {{ $album->description }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </x-ui.carousel>
        @else
            <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                Nenhum álbum disponível por enquanto.
            </div>
        @endif
    </section>

    <!-- ========================================== -->
    <!-- 5. ANIVERSARIANTES DO MÊS -->
    <!-- ========================================== -->
    @if ($birthdayBanner)
        <section class="space-y-4">
            <div class="flex items-center pb-5">
                <h3
                    class="font-black text-lg text-slate-800 dark:text-(--text-primary) tracking-tight uppercase section-title-line flex items-center gap-2">
                    <i class="fa-solid fa-cake-candles text-(--brand-primary-dark)"></i> Aniversariantes de
                    {{ now()->translatedFormat('F') }}
                </h3>
            </div>

            <div class="ui-card overflow-hidden">
                <img src="{{ $birthdayBanner->imageUrl() }}" alt="Banner de {{ $birthdayBanner->monthName() }}" class="w-full h-auto" />
            </div>
        </section>
    @endif

</div>