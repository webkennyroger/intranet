@props([
    'variant' => 'info',
    'icon' => null,
    'heading' => null,
])

@php
    $base = 'rounded-xl border p-4 flex items-start gap-3';

    $variants = [
        'info' => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
        'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
        'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
        'danger' => 'bg-red-500/10 border-red-500/30 text-red-400',
    ];

    $defaultIcons = [
        'info' => 'information-circle',
        'success' => 'check-circle',
        'warning' => 'exclamation-triangle',
        'danger' => 'x-circle',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['info']);
    $iconName = $icon ?? ($defaultIcons[$variant] ?? $defaultIcons['info']);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    <x-ui.icon :name="$iconName" class="w-5 h-5 shrink-0 mt-0.5" />
    <div class="flex-1 min-w-0">
        @if($heading)
            <p class="font-semibold text-sm">{{ $heading }}</p>
        @endif
        <div class="text-sm {{ $heading ? 'mt-1 opacity-80' : '' }}">
            {{ $slot }}
        </div>
    </div>
</div>
