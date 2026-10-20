{{--
    Gráfico de barras agrupadas (duas ou mais séries por categoria),
    renderizado em HTML/CSS puro — sem SVG nem biblioteca de terceiros.
    Cada categoria é uma coluna com uma barra por série, altura
    proporcional ao maior valor entre todas as séries, e um tooltip com os
    valores exatos ao passar o mouse na coluna.
--}}
@props([
    'labels' => [],
    'series' => [], // [['label' => 'Visitas', 'color' => '#...', 'values' => [...]], ...]
])

@php
    $allValues = collect($series)->flatMap(fn (array $s) => $s['values'])->all();
    $max = max($allValues ?: [0]) ?: 1;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-end justify-between gap-1.5 h-full']) }}>
    @foreach ($labels as $i => $label)
        <div class="relative flex-1 flex flex-col items-center h-full" x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false">
            <div
                x-show="show" x-cloak x-transition.opacity.duration.150ms
                class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-10 rounded-xl p-3 text-xs shadow-lg whitespace-nowrap"
                style="background-color: var(--bg-card); border: 1px solid var(--border-color);"
            >
                <div class="font-bold mb-1.5 pb-1.5" style="color: var(--text-primary); border-bottom: 1px solid var(--border-color);">{{ $label }}</div>
                @foreach ($series as $s)
                    <div class="flex items-center justify-between gap-3 py-0.5">
                        <span class="flex items-center gap-1.5" style="color: var(--text-secondary);">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $s['color'] }};"></span>{{ $s['label'] }}
                        </span>
                        <span class="font-bold" style="color: var(--text-primary);">{{ $s['values'][$i] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-end gap-1 flex-1">
                @foreach ($series as $s)
                    @php $heightPct = max((($s['values'][$i] ?? 0) / $max) * 100, 2); @endphp
                    <div class="chart-bar w-2.5 rounded-full" style="height: {{ $heightPct }}%; background-color: {{ $s['color'] }}; --chart-bar-index: {{ $i }};"></div>
                @endforeach
            </div>

            <span class="mt-2 text-[11px] truncate" style="color: var(--text-muted);">{{ $label }}</span>
        </div>
    @endforeach
</div>
