{{--
    Botão padrão usado em todo o admin (vira link <a> se "href" for
    informado). Suporta variantes de cor, tamanhos, ícone à esquerda/
    direita e um estado "loading" que mostra um spinner e desabilita o
    botão automaticamente durante wire:loading.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $base = 'inline-flex items-center justify-center cursor-pointer font-semibold rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variantStyles = [
        'primary' => 'background-color: var(--admin-primary, var(--brand-primary, #0C4A34)); color: #fff;',
        'secondary' => 'background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border-color);',
        'danger' => 'background-color: #ef4444; color: #fff;',
        'warning' => 'background-color: #f97316; color: #fff;',
        'info' => 'background-color: #3b82f6; color: #fff;',
        'ghost' => 'color: var(--text-secondary);',
        'link' => 'color: var(--admin-primary, var(--brand-primary, #0C4A34));',
        'outline' => 'border: 2px solid var(--admin-primary, var(--brand-primary, #0C4A34)); color: var(--admin-primary, var(--brand-primary, #0C4A34));',
    ];

    $variants = [
        'primary' => 'hover:brightness-110 active:brightness-95 focus:ring-black/20 shadow-lg shadow-black/10',
        'secondary' => 'hover:brightness-95 active:brightness-90 focus:ring-(--focus-ring)/30',
        'danger' => 'hover:brightness-110 active:brightness-95 focus:ring-red-500/50 shadow-lg shadow-black/10',
        'warning' => 'hover:brightness-110 active:brightness-95 focus:ring-orange-500/50 shadow-lg shadow-black/10',
        'info' => 'hover:brightness-110 active:brightness-95 focus:ring-blue-500/50 shadow-lg shadow-black/10',
        'ghost' => 'hover:bg-slate-200 dark:hover:bg-white/10 focus:ring-(--focus-ring)/30',
        'link' => 'hover:brightness-110 underline-offset-4 hover:underline p-0 h-auto focus:ring-0',
        'outline' => 'hover:brightness-110 focus:ring-black/20',
    ];

    $sizes = [
        'xs' => 'px-2.5 py-1 text-xs gap-1',
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2.5 text-sm gap-2',
        'lg' => 'px-6 py-3 text-sm gap-2',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    $style = $variantStyles[$variant] ?? '';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if($disabled) disabled @endif
    @if($loading) wire:loading.attr="disabled" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($style) style="{{ $style }}" @endif
>
    @if($loading)
        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <x-ui.icon :name="$icon" class="w-4 h-4" />
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'right' && !$loading)
        <x-ui.icon :name="$icon" class="w-4 h-4" />
    @endif
</{{ $tag }}>
