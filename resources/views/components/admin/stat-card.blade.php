{{--
    Card de estatística usado nos grids de métricas do admin (ex.:
    totais, contadores). Mostra um rótulo, um valor em destaque, um
    sublabel opcional, um ícone colorido e, se informado, uma barra de
    progresso de 0 a 100%.
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

<x-ui.card :padding="false" class="p-5">
    <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted);">{{ $label }}</div>
        @if ($icon)
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background-color: {{ $iconColor ?? 'var(--admin-primary)' }}1a;">
                <i class="fa-solid fa-{{ $icon }} text-sm" style="color: {{ $iconColor ?? 'var(--admin-primary)' }};"></i>
            </div>
        @endif
    </div>
    <div class="mt-2 text-3xl font-bold" style="color: {{ $valueColor ?? 'var(--text-primary)' }};">{{ $value }}</div>
    @if ($sublabel)
        <div class="mt-1 text-xs" style="color: var(--text-secondary);">{{ $sublabel }}</div>
    @endif
    @if (! is_null($progress))
        <div class="mt-3 h-1.5 rounded-full overflow-hidden" style="background-color: var(--bg-elevated);">
            <div class="h-full rounded-full" style="width: {{ max(0, min(100, $progress)) }}%; background-color: {{ $iconColor ?? 'var(--admin-primary)' }};"></div>
        </div>
    @endif
</x-ui.card>
