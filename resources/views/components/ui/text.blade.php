@props([
    'size' => 'md',
    'variant' => 'default',
    'inline' => false,
])

@php
    $sizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
        'xl' => 'text-lg',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    $variantStyles = [
        'default' => 'color: var(--text-secondary);',
        'muted' => 'color: var(--text-muted);',
        'strong' => 'color: var(--text-primary); font-weight: 600;',
    ];
    $variantStyle = $variantStyles[$variant] ?? $variantStyles['default'];

    $tag = $inline ? 'span' : 'p';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $sizeClasses]) }} style="{{ $variantStyle }}">
    {{ $slot }}
</{{ $tag }}>
