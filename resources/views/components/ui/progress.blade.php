@props([
    'value' => 0,
    'max' => 100,
    'label' => null,
    'size' => 'md',
    'variant' => 'default',
    'color' => null,
])

@php
    $max = (float) ($max ?: 100);
    $value = max(0, min((float) $value, $max));
    $percentage = $max > 0 ? round(($value / $max) * 100, 2) : 0;

    $sizes = [
        'sm' => 'h-1.5',
        'md' => 'h-2.5',
        'lg' => 'h-4',
    ];

    $variants = [
        'default' => 'bg-gradient-to-r from-emerald-600 to-emerald-400',
        'success' => 'bg-gradient-to-r from-emerald-600 to-emerald-400',
        'warning' => 'bg-gradient-to-r from-amber-600 to-amber-400',
        'danger' => 'bg-gradient-to-r from-red-600 to-red-400',
    ];

    $trackClasses = trim(($sizes[$size] ?? $sizes['md']) . ' w-full rounded-full overflow-hidden');
    $fillClasses = trim(($variants[$variant] ?? $variants['default']) . ' h-full rounded-full transition-all duration-500 ease-out');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
        <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-sm font-medium" style="color: var(--text-secondary);">
                {{ $label }}
            </span>
            <span class="text-sm font-semibold tabular-nums" style="color: var(--text-primary);">
                {{ $percentage }}%
            </span>
        </div>
    @endif

    <div
        {{ $attributes->except('class') }}
        class="{{ $trackClasses }}"
        style="background-color: var(--bg-elevated);"
        role="progressbar"
        aria-valuenow="{{ $value }}"
        aria-valuemin="0"
        aria-valuemax="{{ $max }}"
    >
        <div class="{{ $color ? 'h-full rounded-full transition-all duration-500 ease-out' : $fillClasses }}" style="width: {{ $percentage }}%; {{ $color ? "background-color: {$color};" : '' }}"></div>
    </div>
</div>
