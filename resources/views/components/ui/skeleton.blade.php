@props([
    'variant' => 'text',
    'width' => null,
    'height' => null,
    'lines' => 1,
])

@php
    $shapeClasses = match($variant) {
        'circle' => 'rounded-full',
        'rect' => 'rounded-lg',
        default => 'rounded-md',
    };

    $resolvedWidth = $width ?? match($variant) {
        'circle' => '3rem',
        'rect' => '100%',
        default => '100%',
    };

    $resolvedHeight = $height ?? match($variant) {
        'circle' => $resolvedWidth,
        'rect' => '8rem',
        default => '0.875rem',
    };

    $lineCount = $variant === 'text' ? max(1, (int) $lines) : 1;
@endphp

@if($lineCount > 1)
    <div class="flex flex-col gap-2" {{ $attributes }}>
        @for ($i = 0; $i < $lineCount; $i++)
            <div
                class="animate-pulse {{ $shapeClasses }}"
                style="background-color: var(--bg-elevated); width: {{ $i === $lineCount - 1 ? '75%' : $resolvedWidth }}; height: {{ $resolvedHeight }};"
            ></div>
        @endfor
    </div>
@else
    <div
        {{ $attributes->merge(['class' => "animate-pulse {$shapeClasses}"]) }}
        style="background-color: var(--bg-elevated); width: {{ $resolvedWidth }}; height: {{ $resolvedHeight }};"
    ></div>
@endif
