@props([
    'heading' => null,
    'expanded' => false,
    'disabled' => false,
])

@php
    $itemId = $attributes->get('id') ?? 'accordion-item-' . \Illuminate\Support\Str::random(8);
@endphp

<div
    @if($expanded) x-init="toggle('{{ $itemId }}')" @endif
    {{ $attributes->except(['id'])->merge(['class' => '']) }}
    style="border-bottom: 1px solid var(--border-color);"
>
    <button
        type="button"
        @if(!$disabled) @click="toggle('{{ $itemId }}')" @endif
        :aria-expanded="isOpen('{{ $itemId }}').toString()"
        @if($disabled) disabled @endif
        class="flex w-full items-center justify-between gap-3 py-3.5 text-left text-sm font-medium transition-colors duration-200 {{ $disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}"
        style="color: var(--text-primary);"
    >
        <span>{{ $heading }}</span>
        <x-ui.icon
            name="chevron-down"
            class="w-4 h-4 shrink-0 transition-transform duration-200"
            x-bind:class="isOpen('{{ $itemId }}') ? '-rotate-180' : ''"
            style="color: var(--text-muted);"
        />
    </button>

    <div
        class="grid overflow-hidden transition-[grid-template-rows] duration-300 ease-in-out"
        :style="isOpen('{{ $itemId }}') ? 'grid-template-rows: 1fr;' : 'grid-template-rows: 0fr;'"
    >
        <div class="overflow-hidden">
            <div class="pb-4 text-sm" style="color: var(--text-secondary);">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
