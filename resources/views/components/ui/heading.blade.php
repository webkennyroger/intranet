@props([
    'size' => 'md',
    'level' => null,
])

@php
    $sizes = [
        'sm' => 'text-sm font-semibold',
        'md' => 'text-lg font-semibold',
        'lg' => 'text-xl font-bold',
        'xl' => 'text-2xl font-bold',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    $allowedLevels = [1, 2, 3, 4, 5, 6];
    $resolvedLevel = in_array((int) $level, $allowedLevels, true) ? (int) $level : 2;
    $tag = 'h' . $resolvedLevel;
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $sizeClasses]) }} style="color: var(--text-primary);">
    {{ $slot }}
</{{ $tag }}>
