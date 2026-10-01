@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'options' => [],
    'placeholder' => 'Selecione...',
])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <select
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm appearance-none ' .
                ($error ? 'border-red-500/50 focus:ring-red-500/40' : '')
        ]) }}
        style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }}; color: var(--text-primary);"
    >
        @if($placeholder)
            <option value="" style="color: var(--text-muted);">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
