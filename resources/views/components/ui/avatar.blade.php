{{--
    Avatar circular (ou quadrado) de usuário: mostra a imagem em "src" e,
    se ela falhar ao carregar (ou não houver "src"), cai para as iniciais
    calculadas a partir de "name" (ou um ícone genérico). Usado em várias
    telas do admin para representar usuários.
--}}
@props([
    'src' => null,
    'name' => null,
    'initials' => null,
    'alt' => null,
    'size' => 'md',
    'circle' => true,
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-lg',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $shapeClass = $circle ? 'rounded-full' : 'rounded-lg';

    $computedInitials = $initials;

    if (! $computedInitials && $name) {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name))));

        if (count($parts) === 1) {
            $computedInitials = mb_strtoupper(mb_substr($parts[0], 0, 2));
        } elseif (count($parts) > 1) {
            $first = mb_substr(reset($parts), 0, 1);
            $last = mb_substr(end($parts), 0, 1);
            $computedInitials = mb_strtoupper($first . $last);
        }
    }
@endphp

<div
    x-data="{ imgFailed: false }"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center shrink-0 font-semibold overflow-hidden select-none $sizeClasses $shapeClass"]) }}
    style="background-color: var(--bg-elevated); color: var(--text-secondary); border: 1px solid var(--border-color);"
>
    @if($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt ?? $name ?? '' }}"
            x-show="!imgFailed"
            x-on:error="imgFailed = true"
            class="w-full h-full object-cover {{ $shapeClass }}"
        >
    @endif

    <span
        @if($src)
            x-show="imgFailed"
            x-cloak
        @endif
        aria-hidden="true"
    >
        @if($computedInitials)
            {{ $computedInitials }}
        @elseif($slot->isNotEmpty())
            {{ $slot }}
        @else
            <svg class="w-1/2 h-1/2" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        @endif
    </span>
</div>
