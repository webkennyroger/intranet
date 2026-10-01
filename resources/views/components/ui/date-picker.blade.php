@props([
    'label' => null,
    'placeholder' => 'Selecione uma data...',
    'error' => null,
    'hint' => null,
])

@php
    $modelName = $attributes->wire('model')->value();
@endphp

<div
    x-data="{
        open: false,
        selected: @if($modelName) $wire.entangle('{{ $modelName }}') @else null @endif,
        get display() {
            if (! this.selected) return '';
            const [y, m, d] = this.selected.split('-');
            return `${d}/${m}/${y}`;
        },
    }"
    x-init="$watch('selected', () => { open = false })"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative space-y-1.5"
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-ui.icon name="calendar" class="w-4 h-4" style="color: var(--text-muted);" />
        </div>

        <button
            type="button"
            @click="open = !open"
            {{ $attributes->whereDoesntStartWith('wire:model')->merge([
                'class' => 'w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 pl-10 pr-4 py-2.5 text-sm text-left ' .
                    ($error ? 'border-red-500/50 focus:ring-red-500/40' : '')
            ]) }}
            style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }}; color: var(--text-primary);"
        >
            <span x-text="display" x-show="selected" style="color: var(--text-primary);"></span>
            <span x-show="! selected" style="color: var(--text-muted);">{{ $placeholder }}</span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 origin-top-left left-0 rounded-xl shadow-xl"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
        >
            <x-ui.calendar x-model="selected" />
        </div>
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
