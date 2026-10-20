{{--
    Card de estatística usado nos grids de métricas do admin (ex.:
    totais, contadores). Fundo em gradiente sutil (da cor de destaque pra
    transparente) com uma bolinha colorida ao lado do rótulo, valor em
    destaque, sublabel opcional, ícone colorido e, se informado, uma barra
    de progresso de 0 a 100%.
--}}
@props([
    'label',
    'value',
    'sublabel' => null,
    'valueColor' => null,
    'icon' => null,
    'iconColor' => null,
    'progress' => null,
])

@php
    $accentColor = $iconColor ?? $valueColor ?? 'var(--admin-primary)';
@endphp

<div
    {{ $attributes->merge(['class' => 'rounded-2xl p-4 shadow-sm']) }}
    style="border: 1px solid var(--border-color); background: linear-gradient(to bottom right, color-mix(in srgb, {{ $accentColor }} 12%, transparent), transparent);"
>
    <div class="flex items-center gap-2 text-xs" style="color: var(--text-muted);">
        <span class="h-1.5 w-1.5 rounded-full shrink-0" style="background-color: {{ $accentColor }};"></span>
        <span class="truncate">{{ $label }}</span>
        @if ($icon)
            <i class="fa-solid fa-{{ $icon }} ml-auto text-sm shrink-0" style="color: {{ $accentColor }};"></i>
        @endif
    </div>
    <div class="mt-2 text-2xl font-semibold tabular-nums" style="color: {{ $valueColor ?? 'var(--text-primary)' }};">{{ $value }}</div>
    @if ($sublabel)
        <div class="mt-1 text-xs" style="color: var(--text-secondary);">{{ $sublabel }}</div>
    @endif
    @if (! is_null($progress))
        <div class="mt-3 h-1.5 rounded-full overflow-hidden" style="background-color: var(--bg-elevated);">
            <div class="h-full rounded-full" style="width: {{ max(0, min(100, $progress)) }}%; background-color: {{ $accentColor }};"></div>
        </div>
    @endif
</div>
