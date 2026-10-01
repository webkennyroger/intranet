@props([
    'options' => [],
    'value' => null,
])

@php
    $wireAttributes = $attributes->whereStartsWith('wire:model');
    $outerAttributes = $attributes->whereDoesntStartWith('wire:model');
@endphp

<div
    {{ $outerAttributes->merge(['class' => 'inline-flex items-center gap-1 rounded-xl p-1']) }}
    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color);"
    role="radiogroup"
>
    @foreach($options as $optionValue => $optionLabel)
        <label class="relative cursor-pointer select-none">
            <input
                type="radio"
                value="{{ $optionValue }}"
                @checked((string) $value === (string) $optionValue)
                {{ $wireAttributes->merge(['class' => 'peer sr-only']) }}
            />

            <span
                class="relative z-10 block whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium text-center transition-colors duration-150 peer-checked:bg-emerald-500 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40"
                style="color: var(--text-secondary);"
            >
                {{ $optionLabel }}
            </span>
        </label>
    @endforeach
</div>
