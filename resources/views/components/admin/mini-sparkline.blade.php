{{--
    Linha fininha (sem área, sem card) pra mostrar uma tendência dentro de
    um painel maior — ex. o número de destaque do card "Notícias" do
    dashboard. Pra sparkline completa (com área e borda própria), ver
    x-admin.trend-stat-card.
--}}
@props([
    'values' => [],
    'color' => 'var(--admin-primary)',
])

@php
    $max = max($values ?: [0]) ?: 1;
    $min = min($values ?: [0]);
    $range = max($max - $min, 1);
    $count = count($values);
    $width = 120;
    $height = 36;
    $stepX = $count > 1 ? $width / ($count - 1) : 0;

    $points = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? $i * $stepX : $width / 2;
        $y = $height - (($v - $min) / $range) * ($height - 6) - 3;
        $points[] = round($x, 1).','.round($y, 1);
    }

    $polyline = implode(' ', $points);
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" {{ $attributes->merge(['class' => 'w-24 h-9']) }}>
    @if ($points)
        <polyline class="chart-line" points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
    @endif
</svg>
