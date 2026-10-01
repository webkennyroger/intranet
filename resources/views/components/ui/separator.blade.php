@props([
    'orientation' => 'horizontal',
    'vertical' => false,
    'text' => null,
    'color' => null,
    'thickness' => null,
    'borderStyle' => 'solid',
    'width' => null,
])

@php
    $orientation = $vertical ? 'vertical' : $orientation;
    $lineStyle = 'border-top-style: ' . $borderStyle . ';'
        . ($color ? " border-top-color: {$color};" : ' background-color: var(--border-color);')
        . ($thickness ? " border-top-width: {$thickness}; background-color: transparent;" : '')
        . ($width ? " width: {$width}; margin-left: auto; margin-right: auto;" : '');
@endphp

@if($orientation === 'vertical')
    <div
        {{ $attributes->merge(['class' => 'inline-block w-px self-stretch flex-shrink-0']) }}
        style="background-color: {{ $color ?? 'var(--border-color)' }};"
        role="separator"
        aria-orientation="vertical"
    ></div>
@else
    @if($text)
        <div
            {{ $attributes->merge(['class' => 'flex items-center gap-3 w-full']) }}
            role="separator"
            aria-orientation="horizontal"
        >
            <span class="flex-1 h-px" style="{{ $lineStyle }}"></span>
            <span class="text-xs uppercase tracking-wide flex-shrink-0" style="color: var(--text-muted);">{{ $text }}</span>
            <span class="flex-1 h-px" style="{{ $lineStyle }}"></span>
        </div>
    @else
        <div
            {{ $attributes->merge(['class' => 'w-full h-px']) }}
            style="{{ $lineStyle }}"
            role="separator"
            aria-orientation="horizontal"
        ></div>
    @endif
@endif
