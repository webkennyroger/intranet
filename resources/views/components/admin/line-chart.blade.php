{{--
    Gráfico de linha renderizado em SVG puro (polyline + área preenchida),
    sem nenhuma biblioteca de gráficos de terceiro. As coordenadas dos
    pontos são calculadas em PHP a partir dos valores e do maior valor da
    lista. Usado nos painéis de estatísticas do admin.
--}}
@props([
    'labels' => [],
    'values' => [],
    'color' => null,
])

@php
    $color = $color ?? 'var(--admin-primary)';
    $max = max($values ?: [0]) ?: 1;
    $count = count($values);
    $width = 600;
    $height = 160;
    $stepX = $count > 1 ? $width / ($count - 1) : 0;

    $points = [];
    foreach ($values as $i => $value) {
        $x = $count > 1 ? $i * $stepX : $width / 2;
        $y = $height - ($value / $max) * ($height - 10);
        $points[] = [round($x, 1), round($y, 1)];
    }

    $polyline = implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $points));
    $areaPath = $points
        ? 'M0,'.$height.' L'.implode(' L', array_map(fn ($p) => $p[0].','.$p[1], $points)).' L'.$width.','.$height.' Z'
        : '';
@endphp

<div {{ $attributes }}>
    <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="w-full h-40">
        @if ($areaPath)
            <path d="{{ $areaPath }}" fill="{{ $color }}" opacity="0.12"></path>
        @endif
        <polyline points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
        @foreach ($points as $point)
            <circle cx="{{ $point[0] }}" cy="{{ $point[1] }}" r="3" fill="{{ $color }}"></circle>
        @endforeach
    </svg>
    <div class="flex justify-between mt-1 text-[10px]" style="color: var(--text-muted);">
        @foreach ($labels as $label)
            <span>{{ $label }}</span>
        @endforeach
    </div>
</div>
