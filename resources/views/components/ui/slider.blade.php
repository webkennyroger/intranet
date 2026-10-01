@props([
    'label' => null,
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'value' => null,
    'suffix' => null,
    'error' => null,
    'hint' => null,
])

@php
    $initial = $value ?? $attributes->get('value') ?? $min;
@endphp

<div x-data="{ value: {{ (float) $initial }} }" class="space-y-1.5">
    @if($label)
        <div class="flex items-center justify-between gap-2">
            <label class="block text-sm font-medium" style="color: var(--text-secondary);">
                {{ $label }}
            </label>
            <span class="text-sm font-semibold tabular-nums" style="color: var(--text-primary);">
                <span x-text="value"></span>@if($suffix){{ $suffix }}@endif
            </span>
        </div>
    @endif

    <input
        type="range"
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        x-model.number="value"
        {{ $attributes->except('value')->merge([
            'class' => 'w-full h-2 rounded-full appearance-none cursor-pointer accent-emerald-500 ' .
                ($error ? 'ring-2 ring-red-500/40' : ''),
        ]) }}
        style="background-color: var(--bg-elevated);"
    />

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
