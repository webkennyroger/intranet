@props([
    'title' => 'Nenhum resultado encontrado',
    'description' => null,
    'icon' => null,
    'action' => null,
])

<div class="text-center py-16 px-6">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-5" style="background-color: var(--bg-elevated);">
        @if($icon)
            <x-ui.icon :name="$icon" class="w-8 h-8" style="color: var(--text-muted);" />
        @else
            <svg class="w-8 h-8" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        @endif
    </div>

    <h3 class="text-lg font-semibold mb-1" style="color: var(--text-primary);">{{ $title }}</h3>

    <p class="text-sm max-w-sm mx-auto mb-6" style="color: var(--text-secondary);">
        {{ $description ?? 'Nenhum item foi encontrado para os filtros selecionados.' }}
    </p>

    @if($action)
        <div>{{ $action }}</div>
    @endif
</div>
