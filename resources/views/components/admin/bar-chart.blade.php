{{--
    Gráfico de barras simples, renderizado só com divs e CSS (sem
    biblioteca de gráficos de terceiro). Cada valor vira uma barra cuja
    altura é proporcional ao maior valor da lista. Usado nos painéis de
    estatísticas do admin.
--}}
@props([
    'labels' => [],
    'values' => [],
    'color' => null,
])

@php
    $color = $color ?? 'var(--admin-primary)';
    $max = max($values ?: [0]) ?: 1;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-end gap-2 h-40']) }}>
    @foreach ($values as $i => $value)
        <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full">
            <span class="text-[10px] font-semibold" style="color: var(--text-secondary);">{{ $value }}</span>
            <div class="w-full rounded-t-md transition-all" style="height: {{ max(4, ($value / $max) * 110) }}px; background-color: {{ $color }};"></div>
            <span class="text-[10px]" style="color: var(--text-muted);">{{ $labels[$i] ?? '' }}</span>
        </div>
    @endforeach
</div>
