@props([
    'label' => null,
    'badge' => null,
    'for' => null,
    'error' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <div class="flex items-center justify-between gap-2">
            <label @if($for) for="{{ $for }}" @endif class="block text-sm font-medium" style="color: var(--text-secondary);">
                {{ $label }}
            </label>

            @if($badge)
                <span class="text-xs px-1.5 py-0.5 rounded-md" style="background-color: var(--bg-elevated); color: var(--text-muted);">
                    {{ $badge }}
                </span>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
