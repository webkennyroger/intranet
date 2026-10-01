@props([
    'align' => 'right',
    'width' => '48',
])

@php
    $alignmentClasses = match($align) {
        'left' => 'origin-top-left left-0',
        'right' => 'origin-top-right right-0',
        default => 'origin-top-right right-0',
    };

    $widthClass = match($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        '80' => 'w-80',
        default => 'w-48',
    };
@endphp

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative inline-block text-left"
    {{ $attributes }}
>
    <!-- Trigger -->
    <div @click="open = !open">
        {{ $trigger ?? '' }}
    </div>

    <!-- Dropdown Content -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-2 {{ $widthClass }} {{ $alignmentClasses }} rounded-xl shadow-xl py-1.5"
        style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
    >
        {{ $slot }}
    </div>
</div>
