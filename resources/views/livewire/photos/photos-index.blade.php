{{--
    Tela de Mídias: aba Fotos lista os álbuns disponíveis, cada um levando à
    tela de detalhe do álbum; aba Vídeos lista os vídeos institucionais, cada
    um abrindo no lightbox (x-ui.lightbox), com setas pra navegar entre todos
    os vídeos, não só o clicado.
--}}
@php
    $lightboxItems = $videos->map(fn ($video) => [
        'url' => $video->embedUrl(),
        'name' => $video->title,
        'type' => 'video',
    ])->values();
@endphp
<div class="space-y-6" x-data="{ activeTab: 'fotos' }">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="photo-film" title="Mídias" subtitle="Fotos e vídeos de eventos e atividades." />

    <!-- Abas Fotos / Vídeos -->
    <div class="flex justify-center">
        <div class="inline-flex items-center gap-1.5 ui-card p-1.5">
        <button
            type="button"
            @click="activeTab = 'fotos'"
            class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition"
            :class="activeTab === 'fotos' ? 'bg-(--brand-primary) text-white shadow-sm' : 'text-slate-500 dark:text-(--text-secondary) hover:bg-slate-100 dark:hover:bg-(--bg-hover)'"
        >
            <i class="fa-solid fa-camera"></i>
            Fotos
            <span
                class="rounded-full px-2 py-0.5 text-[11px] font-black"
                :class="activeTab === 'fotos' ? 'bg-white/25 text-white' : 'bg-slate-100 dark:bg-(--bg-elevated) text-slate-500 dark:text-(--text-muted)'"
            >{{ $albums->count() }}</span>
        </button>
        <button
            type="button"
            @click="activeTab = 'videos'"
            class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition"
            :class="activeTab === 'videos' ? 'bg-(--brand-primary) text-white shadow-sm' : 'text-slate-500 dark:text-(--text-secondary) hover:bg-slate-100 dark:hover:bg-(--bg-hover)'"
        >
            <i class="fa-solid fa-video"></i>
            Vídeos
            <span
                class="rounded-full px-2 py-0.5 text-[11px] font-black"
                :class="activeTab === 'videos' ? 'bg-white/25 text-white' : 'bg-slate-100 dark:bg-(--bg-elevated) text-slate-500 dark:text-(--text-muted)'"
            >{{ $videos->count() }}</span>
        </button>
        </div>
    </div>

    <div
        x-show="activeTab === 'fotos'"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
    >
            @if ($albums->isEmpty())
                <!-- Estado vazio -->
                <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                    Nenhum álbum disponível por enquanto.
                </div>
            @else
                <!-- Grade de álbuns -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6 xl:grid-cols-4">
                    @foreach ($albums as $album)
                        <div class="flex flex-col group" wire:key="album-{{ $album->id }}">
                            <a href="{{ route('photos.show', $album) }}" class="block overflow-hidden rounded-2xl">
                                @if ($album->cover_image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($album->cover_image_path) }}" alt="{{ $album->title }}" class="h-44 w-full object-cover object-center group-hover:scale-105 transition duration-500">
                                @else
                                    <div class="h-44 w-full bg-emerald-50 flex items-center justify-center">
                                        <i class="fa-solid fa-image text-3xl text-emerald-200"></i>
                                    </div>
                                @endif
                            </a>
                            <div class="ui-card relative z-10 -mt-8 grow p-4 transition-shadow hover:shadow-lg">
                                <div>
                                    <a href="{{ route('photos.show', $album) }}" class="text-sm font-medium text-slate-700 dark:text-(--text-primary) line-clamp-1 hover:text-emerald-600">{{ $album->title }}</a>
                                </div>
                                <p class="mt-2 grow text-xs text-slate-500 dark:text-(--text-secondary) line-clamp-3">{{ $album->description ?: 'Sem descrição.' }}</p>
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="flex shrink-0 items-center space-x-1.5 text-xs text-slate-400 dark:text-(--text-muted)">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span>{{ $album->event_date?->format('d/m/Y') }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1.5">
                                        <x-ui.view-count :count="$album->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-2.5 py-1 text-xs font-bold text-slate-500 dark:text-(--text-secondary)" />
                                        <button
                                            type="button"
                                            wire:click="toggleAlbumLike({{ $album->id }})"
                                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-2.5 py-1 text-xs font-bold transition hover:bg-slate-100 dark:hover:bg-(--bg-elevated) {{ $album->isLikedBy(auth()->user()) ? 'text-emerald-600' : 'text-slate-500 dark:text-(--text-secondary)' }}"
                                        >
                                            <i class="fa-{{ $album->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                            <span>{{ $album->liked_by_users_count }}</span>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    <div
        x-show="activeTab === 'videos'"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
    >
            @if ($videos->isEmpty())
                <!-- Estado vazio -->
                <div class="ui-card p-12 text-center text-slate-500 dark:text-(--text-secondary)">
                    Nenhum vídeo disponível por enquanto.
                </div>
            @else
                <!-- Grade de vídeos -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6">
                    @foreach ($videos as $index => $video)
                        <div class="relative rounded-lg overflow-hidden group" wire:key="video-{{ $video->id }}">
                            @if ($video->thumbnailUrl())
                                <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}" class="h-72 w-full rounded-lg object-cover object-center group-hover:scale-105 transition duration-500">
                            @else
                                <div class="h-72 w-full rounded-lg bg-slate-800 flex items-center justify-center">
                                    <i class="fa-solid fa-video text-4xl text-slate-500"></i>
                                </div>
                            @endif

                            <!-- Cobre a capa inteira: clicar em qualquer ponto da imagem reproduz o vídeo -->
                            <button
                                type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: @js($lightboxItems), index: {{ $index }} } }))"
                                wire:click="incrementVideoViews({{ $video->id }})"
                                class="absolute inset-0 flex items-center justify-center"
                                aria-label="Reproduzir vídeo: {{ $video->title }}"
                            >
                                <span class="w-14 h-14 rounded-full bg-white flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                                    <i class="fa-solid fa-play text-emerald-700"></i>
                                </span>
                            </button>

                            <div class="absolute inset-x-0 bottom-0 space-y-1.5 rounded-lg bg-linear-to-t from-slate-900/90 via-slate-900/60 to-transparent px-4 pb-3 pt-12">
                                <div class="line-clamp-2">
                                    <button
                                        type="button"
                                        @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: @js($lightboxItems), index: {{ $index }} } }))"
                                        wire:click="incrementVideoViews({{ $video->id }})"
                                        class="text-left text-base font-medium text-white hover:underline"
                                    >
                                        {{ $video->title }}
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1 text-xs text-slate-200">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span>{{ $video->created_at->format('d/m/Y') }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1.5">
                                        <x-ui.view-count :count="$video->views_count" class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-xs font-bold text-slate-200" />
                                        <button
                                        type="button"
                                        wire:click="toggleVideoLike({{ $video->id }})"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-xs font-bold transition hover:bg-white/20 {{ $video->isLikedBy(auth()->user()) ? 'text-emerald-400' : 'text-slate-200' }}"
                                    >
                                        <i class="fa-{{ $video->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                                        <span>{{ $video->liked_by_users_count }}</span>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        <x-ui.lightbox />
    </div>
</div>
