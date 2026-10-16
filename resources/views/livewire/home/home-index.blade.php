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
    <!-- EM DESTAQUE: 1 card grande (manchete) + 4 menores, numa grade -->
    <!-- assimétrica (2 à esquerda, 2 à direita) ao redor do grande. -->
    <!-- ========================================== -->
    <section class="space-y-4">
        @if ($headline || $highlighted->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-4 sm:grid-rows-2 gap-4">
                @if ($highlighted->get(0))
                    @php($item = $highlighted->get(0))
                    <x-site.highlight-card class="h-56 sm:col-start-1 sm:row-start-1" :url="$item['url']"
                        :image="$item['image']" :label="$item['label']" :title="$item['title']"
                        :date="$item['date']" :icon="$item['icon']" :color="$item['color']"
                        :views="$item['views']" :likes="$item['likes']" :is-liked="$item['isLiked']"
                        :like-action="($item['label'] === 'Fotos' ? 'toggleAlbumLike' : 'toggleLike').'('.$item['likeId'].')'" />
                @endif

                @if ($highlighted->get(1))
                    @php($item = $highlighted->get(1))
                    <x-site.highlight-card class="h-56 sm:col-start-1 sm:row-start-2" :url="$item['url']"
                        :image="$item['image']" :label="$item['label']" :title="$item['title']"
                        :date="$item['date']" :icon="$item['icon']" :color="$item['color']"
                        :views="$item['views']" :likes="$item['likes']" :is-liked="$item['isLiked']"
                        :like-action="($item['label'] === 'Fotos' ? 'toggleAlbumLike' : 'toggleLike').'('.$item['likeId'].')'" />
                @endif

                @if ($headline)
                    @php($headlineCategory = $headline->categories->first())
                    <x-site.highlight-card size="lg" class="h-72 sm:h-auto sm:col-start-2 sm:col-span-2 sm:row-start-1 sm:row-span-2"
                        :url="route('mural.show', $headline)" :image="$headline->featuredImageUrl()"
                        :label="$headlineCategory->name ?? 'Notícia'" :title="$headline->title"
                        :date="$headline->published_at" :icon="$headline->cover_icon"
                        :color="$headlineCategory->color ?? null" :views="$headline->views_count"
                        :likes="$headline->likedByUsers()->count()" :is-liked="$headline->isLikedBy(auth()->user())"
                        like-action="toggleLike({{ $headline->id }})" />
                @endif

                @if ($highlighted->get(2))
                    @php($item = $highlighted->get(2))
                    <x-site.highlight-card class="h-56 sm:col-start-4 sm:row-start-1" :url="$item['url']"
                        :image="$item['image']" :label="$item['label']" :title="$item['title']"
                        :date="$item['date']" :icon="$item['icon']" :color="$item['color']"
                        :views="$item['views']" :likes="$item['likes']" :is-liked="$item['isLiked']"
                        :like-action="($item['label'] === 'Fotos' ? 'toggleAlbumLike' : 'toggleLike').'('.$item['likeId'].')'" />
                @endif

                @if ($highlighted->get(3))
                    @php($item = $highlighted->get(3))
                    <x-site.highlight-card class="h-56 sm:col-start-4 sm:row-start-2" :url="$item['url']"
                        :image="$item['image']" :label="$item['label']" :title="$item['title']"
                        :date="$item['date']" :icon="$item['icon']" :color="$item['color']"
                        :views="$item['views']" :likes="$item['likes']" :is-liked="$item['isLiked']"
                        :like-action="($item['label'] === 'Fotos' ? 'toggleAlbumLike' : 'toggleLike').'('.$item['likeId'].')'" />
                @endif
            </div>
        @else
            <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                Nenhuma matéria publicada por enquanto.
            </div>
        @endif
    </section>

            <!-- ========================================== -->
            <!-- 2. ÚLTIMAS NOTÍCIAS DO PORTAL -->
            <!-- ========================================== -->
            @if ($latestNewsSlides->isNotEmpty())
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

                <!-- Carrossel (x-ui.carousel): 4 matérias por vez, com setas
                     quando houver mais de 4 — mesmo esquema da Galeria
                     Multimídia (ver HomeIndex::chunkIntoSlides()). -->
                <x-ui.carousel>
                    @foreach ($latestNewsSlides as $newsChunk)
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            @foreach ($newsChunk as $article)
                            @php($latestCategory = $article->categories->first())
                            <article class="ui-card overflow-hidden hover:shadow-md transition-shadow flex flex-col group"
                                wire:key="latest-{{ $article->id }}">
                                <a href="{{ route('mural.show', $article) }}"
                                    class="aspect-16/10 relative overflow-hidden flex items-center justify-center shrink-0"
                                    style="background-color: {{ $article->cover_color }};">
                                    @if ($article->featuredImageUrl())
                                        <img src="{{ $article->featuredImageUrl() }}" alt="{{ $article->title }}"
                                            class="w-full h-full object-cover" />
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
                                <div class="p-5 flex-1 flex flex-col gap-2">
                                    <a href="{{ route('mural.show', $article) }}"
                                        class="font-extrabold text-slate-800 dark:text-(--text-primary) text-base leading-snug group-hover:text-(--brand-primary) transition">
                                        {{ $article->title }}
                                    </a>
                                    <p class="text-sm text-slate-500 dark:text-(--text-secondary) line-clamp-2 leading-relaxed">
                                        {{ $article->excerpt }}
                                    </p>
                                    <div class="mt-auto pt-3 flex items-center justify-between gap-2 text-xs text-slate-400 dark:text-(--text-muted)">
                                        <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i>
                                            {{ $article->published_at->format('d/m/Y') }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <x-ui.view-count :count="$article->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-2.5 py-1 text-xs font-bold text-slate-500 dark:text-(--text-secondary)" />
                                            <button type="button" wire:click="toggleLike({{ $article->id }})"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-2.5 py-1 text-xs font-bold transition hover:bg-slate-100 dark:hover:bg-(--bg-elevated) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}"
                                                aria-label="Curtir matéria">
                                                <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                                {{ $article->likedByUsers()->count() }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                            @endforeach
                        </div>
                    @endforeach
                </x-ui.carousel>
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
                    <div class="relative overflow-hidden rounded-2xl p-6 flex flex-col md:flex-row gap-6 items-center"
                        style="background-color: var(--color-primary-500, #22b573);">
                        <div class="absolute rounded-full pointer-events-none"
                            style="width: 16rem; height: 16rem; top: -4rem; right: -4rem; border: 1px solid var(--color-primary-600, #138856);">
                        </div>
                        <div class="absolute rounded-full pointer-events-none"
                            style="width: 10rem; height: 10rem; bottom: -3rem; left: -3rem; border: 1px solid var(--color-primary-600, #138856);">
                        </div>

                        <div class="relative z-10 w-full md:w-5/12 text-white rounded-xl p-6 overflow-hidden flex flex-col justify-between min-h-50"
                            style="{{ $nextEvent->coverImageUrl() ? 'background-image: url(' . $nextEvent->coverImageUrl() . '); background-size: cover; background-position: center;' : 'background-color: #0f172a;' }}">
                            @if ($nextEvent->coverImageUrl())
                                <div class="absolute inset-0 bg-linear-to-t from-black/90 via-black/40 to-black/10"></div>
                            @endif

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

                            @unless ($nextEvent->coverImageUrl())
                                <i class="fa-solid fa-gavel text-slate-800 text-8xl absolute -right-4 -bottom-4 pointer-events-none"></i>
                            @endunless
                        </div>

                        <div class="relative z-10 w-full md:w-7/12 space-y-4">
                            <div>
                                <span class="text-white/80 text-xs font-bold uppercase tracking-wider">Evento
                                    Institucional</span>
                                <h4 class="text-lg font-black text-white uppercase leading-snug mt-1">
                                    {{ $nextEvent->title }}
                                </h4>
                                @if ($nextEvent->description)
                                    <p class="text-xs sm:text-sm text-white/80 mt-2 leading-relaxed">
                                        {{ $nextEvent->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                @if ($nextEvent->link)
                                    <a href="{{ $nextEvent->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-xs font-bold shadow-sm transition hover:brightness-95" style="color: var(--color-primary-700, #0f6d47);">
                                        <i class="fa-solid fa-pen-to-square"></i> Realizar Inscrição
                                    </a>
                                @endif
                                @if ($nextEvent->file_path)
                                    <a href="{{ $nextEvent->fileUrl() }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-5 py-2.5 text-xs font-bold text-white transition shadow-sm">
                                        <i class="fa-solid fa-{{ $nextEvent->fileIcon() }}"></i> Baixar Programação
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                        Nenhum evento programado por enquanto.
                    </div>
                @endif
    </section>

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

        @if ($gallerySlides->isNotEmpty())
            <!-- Componente nativo do kit de UI (x-ui.carousel): 4 itens por
                 vez (álbuns de fotos e vídeos misturados, mais recente
                 primeiro — ver HomeIndex::galleryItems()), com botão de
                 slide pra frente/trás quando houver mais de 4. Os slides já
                 vêm agrupados de HomeIndex::chunkIntoSlides(), que evita
                 deixar uma última página quase vazia ao avançar. -->
            <x-ui.carousel>
                @foreach ($gallerySlides as $itemsChunk)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($itemsChunk as $item)
                            @if ($item['type'] === 'video')
                                <button
                                    type="button"
                                    wire:key="gallery-video-{{ $item['id'] }}"
                                    @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: [{ url: @js($item['embedUrl']), name: @js($item['title']), type: 'video' }], index: 0 } }))"
                                    class="ui-card overflow-hidden group hover:shadow-md transition text-left"
                                >
                                    <div class="h-48 bg-slate-800 relative overflow-hidden flex items-center justify-center">
                                        @if ($item['cover'])
                                            <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                        @else
                                            <i class="fa-solid fa-video text-4xl text-slate-500"></i>
                                        @endif
                                        <div class="absolute inset-0 bg-slate-900/30 group-hover:bg-slate-900/10 transition flex items-center justify-center">
                                            <span class="w-11 h-11 rounded-full bg-white flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                                                <i class="fa-solid fa-play text-emerald-700"></i>
                                            </span>
                                        </div>
                                        <span class="absolute bottom-3 left-3 bg-white dark:bg-(--bg-surface) text-slate-800 dark:text-(--text-primary) font-bold text-[10px] px-2.5 py-1 rounded-md flex items-center gap-1.5">
                                            <i class="fa-solid fa-video text-(--brand-primary)"></i> {{ $item['countLabel'] }}
                                        </span>
                                    </div>
                                    <div class="p-4 bg-white dark:bg-(--bg-card)">
                                        <h4 class="font-extrabold text-xs uppercase text-slate-800 dark:text-(--text-primary) group-hover:text-(--brand-primary) transition">
                                            {{ $item['title'] }}
                                        </h4>
                                        @if ($item['description'])
                                            <p class="text-[11px] text-slate-500 dark:text-(--text-secondary) mt-1 line-clamp-2">
                                                {{ $item['description'] }}</p>
                                        @endif
                                    </div>
                                </button>
                            @else
                                <a href="{{ $item['url'] }}" wire:key="gallery-album-{{ $item['id'] }}" class="ui-card overflow-hidden group hover:shadow-md transition">
                                    <div class="h-48 bg-slate-200 dark:bg-(--bg-elevated) relative overflow-hidden flex items-center justify-center">
                                        @if ($item['cover'])
                                            <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                        @else
                                            <i class="fa-solid fa-images text-4xl text-slate-400 dark:text-(--text-muted)"></i>
                                        @endif
                                        <div class="absolute inset-0 bg-slate-900/30 group-hover:bg-slate-900/10 transition">
                                        </div>
                                        <span class="absolute bottom-3 left-3 bg-white dark:bg-(--bg-surface) text-slate-800 dark:text-(--text-primary) font-bold text-[10px] px-2.5 py-1 rounded-md flex items-center gap-1.5">
                                            <i class="fa-solid fa-images text-(--brand-primary)"></i> {{ $item['countLabel'] }}
                                        </span>
                                    </div>
                                    <div class="p-4 bg-white dark:bg-(--bg-card)">
                                        <h4 class="font-extrabold text-xs uppercase text-slate-800 dark:text-(--text-primary) group-hover:text-(--brand-primary) transition">
                                            {{ $item['title'] }}
                                        </h4>
                                        @if ($item['description'])
                                            <p class="text-[11px] text-slate-500 dark:text-(--text-secondary) mt-1 line-clamp-2">
                                                {{ $item['description'] }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </x-ui.carousel>
            <x-ui.lightbox />
        @else
            <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                Nenhum álbum ou vídeo disponível por enquanto.
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