{{--
    Card de destaque com foto de fundo cheia (sem moldura em volta), gradiente
    escuro na base e selo/título/data por cima — usado na grade assimétrica
    "Em Destaque" da Início (1 card grande no centro + 4 menores ao redor).
--}}
@props([
    'url',
    'image',
    'label',
    'title',
    'date' => null,
    'icon' => 'newspaper',
    'size' => 'sm',
    'color' => null,
    'views' => null,
    'likes' => null,
    'isLiked' => false,
    'likeAction' => null,
    'variant' => 'photo',
])

@php($accent = $color ?? 'var(--brand-primary, #0C4A34)')

@if ($variant === 'green')
    {{-- Painel verde com anéis decorativos como moldura (igual ao antigo
        card de "Em Destaque"); por dentro, a foto ocupa o espaço cheio com
        selo/título/data sobrepostos — mesma organização da variante de foto
        cheia, só emoldurada em verde. --}}
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl p-2']) }}
        style="background-color: var(--color-primary-500, #22b573);">
        <div class="absolute rounded-full pointer-events-none"
            style="width: 11rem; height: 11rem; top: -3rem; right: -3rem; border: 1px solid var(--color-primary-600, #138856);">
        </div>
        <div class="absolute rounded-full pointer-events-none"
            style="width: 7rem; height: 7rem; bottom: -2.5rem; left: -2.5rem; border: 1px solid var(--color-primary-600, #138856);">
        </div>

        <a href="{{ $url }}" class="group relative z-10 block h-full w-full overflow-hidden rounded-lg">
            @if ($image)
                <img src="{{ $image }}" alt="{{ $title }}"
                    class="absolute inset-0 w-full h-full object-cover transition duration-500 group-hover:scale-105" />
            @else
                <div class="absolute inset-0 flex items-center justify-center bg-white/10">
                    <i class="fa-solid fa-{{ $icon }} text-3xl text-white/70"></i>
                </div>
            @endif

            <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>

            @if ($views !== null)
                <div class="absolute top-2 right-2 z-10 flex items-center gap-1.5">
                    <x-ui.view-count :count="$views" class="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-slate-700" />
                    <button type="button" wire:click.stop="{{ $likeAction }}"
                        class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[11px] font-bold transition hover:bg-slate-50 {{ $isLiked ? 'text-(--brand-primary)' : 'text-slate-700' }}"
                        aria-label="Curtir">
                        <i class="fa-{{ $isLiked ? 'solid' : 'regular' }} fa-heart"></i>
                        {{ $likes }}
                    </button>
                </div>
            @endif

            <div class="absolute inset-x-0 bottom-0 p-3 space-y-1">
                <span class="inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                    style="background-color: {{ $accent }};">
                    {{ $label }}
                </span>
                <p class="font-black text-sm text-white leading-snug line-clamp-2">
                    {{ $title }}
                </p>
                @if ($date)
                    <p class="text-xs text-white/70">{{ $date->format('d/m/Y') }}</p>
                @endif
            </div>
        </a>
    </div>
@else
    <a href="{{ $url }}" {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-xl block']) }}>
        @if ($image)
            <img src="{{ $image }}" alt="{{ $title }}"
                class="absolute inset-0 w-full h-full object-cover transition duration-500 group-hover:scale-105" />
        @else
            <div class="absolute inset-0 flex items-center justify-center" style="background-color: {{ $accent }};">
                <i class="fa-solid fa-{{ $icon }} text-4xl text-white/70"></i>
            </div>
        @endif

        <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>

        <span class="absolute top-3 left-3 z-10 inline-block rounded px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white"
            style="background-color: {{ $accent }};">
            {{ $label }}
        </span>

        @if ($views !== null)
            <div class="absolute top-3 right-3 z-10 flex items-center gap-1.5">
                <x-ui.view-count :count="$views" class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-700" />
                <button type="button" wire:click.stop="{{ $likeAction }}"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-bold transition hover:bg-slate-50 {{ $isLiked ? 'text-(--brand-primary)' : 'text-slate-700' }}"
                    aria-label="Curtir">
                    <i class="fa-{{ $isLiked ? 'solid' : 'regular' }} fa-heart"></i>
                    {{ $likes }}
                </button>
            </div>
        @endif

        <div class="absolute inset-x-0 bottom-0 p-4 {{ $size === 'lg' ? 'sm:p-6' : '' }} space-y-1.5">
            <p class="font-black text-white leading-snug line-clamp-2 {{ $size === 'lg' ? 'text-xl sm:text-2xl' : 'text-sm' }}">
                {{ $title }}
            </p>
            @if ($date)
                <p class="text-xs text-white/70">{{ $date->format('d/m/Y') }}</p>
            @endif
        </div>
    </a>
@endif
