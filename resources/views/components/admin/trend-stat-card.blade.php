{{--
    Card de estatística com variação percentual e mini-gráfico (sparkline)
    da série acumulada dos últimos dias — usado no topo do dashboard.
--}}
@props([
    'label',
    'value',
    'changePercent' => 0,
    'period' => '7d',
    'icon' => null,
    'color' => 'var(--admin-primary)',
    'values' => [],
])

@php
    $isUp = $changePercent >= 0;
    $trendColor = $isUp ? '#059669' : '#DC2626';

    $max = max($values ?: [0]) ?: 1;
    $min = min($values ?: [0]);
    $range = max($max - $min, 1);
    $count = count($values);
    $width = 200;
    $height = 48;
    $stepX = $count > 1 ? $width / ($count - 1) : 0;

    $points = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? $i * $stepX : $width / 2;
        $y = $height - (($v - $min) / $range) * ($height - 6) - 3;
        $points[] = [round($x, 1), round($y, 1)];
    }

    $polyline = implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $points));
    $areaPath = $points
        ? 'M0,'.$height.' L'.implode(' L', array_map(fn ($p) => $p[0].','.$p[1], $points)).' L'.$width.','.$height.' Z'
        : '';
@endphp

<div
    {{ $attributes->merge(['class' => 'rounded-2xl p-4 overflow-hidden']) }}
    style="border: 1px solid var(--border-color); background: linear-gradient(to bottom right, color-mix(in srgb, {{ $color }} 10%, transparent), transparent);"
>
    <div class="flex items-center justify-between gap-2">
        <span class="text-[11px] font-bold uppercase tracking-wide truncate" style="color: var(--text-muted);">{{ $label }}</span>
        @if ($icon)
            <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background-color: color-mix(in srgb, {{ $color }} 18%, transparent); color: {{ $color }};">
                <i class="fa-solid fa-{{ $icon }} text-xs"></i>
            </span>
        @endif
    </div>

    <div class="mt-2 text-2xl font-bold tabular-nums" style="color: var(--text-primary);">{{ $value }}</div>

    <div class="mt-1.5 flex items-center gap-1.5 text-xs">
        <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 font-bold" style="background-color: color-mix(in srgb, {{ $trendColor }} 14%, transparent); color: {{ $trendColor }};">
            <i class="fa-solid fa-arrow-{{ $isUp ? 'up' : 'down' }} text-[9px]"></i>{{ number_format(abs($changePercent), 1) }}%
        </span>
        <span style="color: var(--text-muted);">{{ $period }}</span>
    </div>

    @if ($points)
        <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="w-full h-10 mt-2">
            <path class="chart-area" d="{{ $areaPath }}" fill="{{ $color }}" opacity="0.12"></path>
            <polyline class="chart-line" points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>
        </svg>
    @endif
</div>
