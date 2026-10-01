@props([
    'data' => [],
    'type' => 'bar',
    'color' => 'emerald',
])

@php
    $palette = [
        'emerald' => 'bg-emerald-500',
        'blue' => 'bg-blue-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-red-500',
        'violet' => 'bg-violet-500',
    ];

    $barColor = $palette[$color] ?? $palette['emerald'];

    $values = array_map(static fn ($point) => (float) ($point['value'] ?? 0), $data);
    $max = $values === [] ? 0 : max($values);

    $bars = array_map(function ($point) use ($max) {
        $value = (float) ($point['value'] ?? 0);
        $percentage = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;

        return [
            'label' => $point['label'] ?? '',
            'value' => $value,
            'percentage' => $percentage,
        ];
    }, $data);

    $isHorizontal = $type === 'horizontal-bar';
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($bars === [])
        <p class="text-sm" style="color: var(--text-muted);">Sem dados para exibir.</p>
    @elseif($isHorizontal)
        <div class="space-y-3">
            @foreach($bars as $bar)
                <div class="flex items-center gap-3">
                    <span class="w-24 flex-shrink-0 truncate text-xs" style="color: var(--text-secondary);" title="{{ $bar['label'] }}">
                        {{ $bar['label'] }}
                    </span>
                    <div class="h-3 flex-1 overflow-hidden rounded-full" style="background-color: var(--bg-elevated);">
                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $bar['percentage'] }}%;"></div>
                    </div>
                    <span class="w-12 flex-shrink-0 text-right text-xs font-medium" style="color: var(--text-primary);">
                        {{ $bar['value'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex h-48 items-end gap-3">
            @foreach($bars as $bar)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-xs font-medium" style="color: var(--text-primary);">{{ $bar['value'] }}</span>
                    <div class="flex h-full w-full items-end overflow-hidden rounded-t-lg" style="background-color: var(--bg-elevated);">
                        <div class="w-full rounded-t-lg {{ $barColor }}" style="height: {{ $bar['percentage'] }}%;"></div>
                    </div>
                    <span class="w-full truncate text-center text-xs" style="color: var(--text-secondary);" title="{{ $bar['label'] }}">
                        {{ $bar['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
