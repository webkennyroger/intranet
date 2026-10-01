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
    $base = 'inline-flex items-center justify-center font-semibold rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variantStyles = [
        'primary' => '',
        'secondary' => 'background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border-color);',
        'danger' => 'background-color: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);',
        'ghost' => 'color: var(--text-secondary);',
        'link' => 'color: #34d399;',
        'outline' => '',
    ];

    $variants = [
        'primary' => 'bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white focus:ring-emerald-500/50 shadow-lg shadow-emerald-500/20',
        'secondary' => 'hover:opacity-80 focus:ring-emerald-500/30',
        'danger' => 'hover:opacity-80 focus:ring-red-500/50',
        'ghost' => 'hover:bg-[var(--bg-hover)] focus:ring-emerald-500/30',
        'link' => 'hover:text-emerald-300 underline-offset-4 hover:underline p-0 h-auto focus:ring-0',
        'outline' => 'border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-500/30',
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
