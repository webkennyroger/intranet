@props([
    'text' => 'Carregando...',
    'full' => false,
])

@if($full)
    <div class="flex items-center justify-center py-20">
        <div class="text-center">
            <svg class="w-8 h-8 animate-spin text-emerald-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <p class="text-sm" style="color: var(--text-secondary);">{{ $text }}</p>
        </div>
    </div>
@else
    <div class="flex items-center gap-3 py-4" {{ $attributes }}>
        <svg class="w-5 h-5 animate-spin text-emerald-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span class="text-sm" style="color: var(--text-secondary);">{{ $text }}</span>
    </div>
@endif
