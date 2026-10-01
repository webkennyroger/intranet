@props([
    'text' => '',
    'position' => 'top',
])

@php
    $positions = [
        'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
        'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
    ];

    $arrowPositions = [
        'top' => 'top-full left-1/2 -translate-x-1/2 -mt-1',
        'bottom' => 'bottom-full left-1/2 -translate-x-1/2 -mb-1',
        'left' => 'left-full top-1/2 -translate-y-1/2 -ml-1',
        'right' => 'right-full top-1/2 -translate-y-1/2 -mr-1',
    ];

    $positionClass = $positions[$position] ?? $positions['top'];
    $arrowClass = $arrowPositions[$position] ?? $arrowPositions['top'];
@endphp

<div
    x-data="{ show: false }"
    @mouseenter="show = true"
    @mouseleave="show = false"
    @focusin="show = true"
    @focusout="show = false"
    class="relative inline-flex"
    {{ $attributes }}
>
    {{ $slot }}

    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-medium shadow-lg pointer-events-none {{ $positionClass }}"
        style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border-color);"
        role="tooltip"
    >
        {{ $text }}
        <div
            class="absolute w-2 h-2 rotate-45 {{ $arrowClass }}"
            style="background-color: var(--bg-elevated);"
        ></div>
    </div>
</div>
