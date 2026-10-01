@props([
    'label' => null,
    'placeholder' => 'Adicionar e pressionar Enter...',
    'error' => null,
    'hint' => null,
])

@php
    $modelName = $attributes->wire('model')->value();
@endphp

<div
    x-data="{
        pills: @if($modelName) $wire.entangle('{{ $modelName }}') @else [] @endif,
        draft: '',
        add() {
            const value = this.draft.trim().replace(/,$/, '');
            if (value && ! this.pills.includes(value)) {
                this.pills.push(value);
            }
            this.draft = '';
        },
        remove(index) {
            this.pills.splice(index, 1);
        },
    }"
    class="space-y-1.5"
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div
        class="w-full rounded-xl flex flex-wrap items-center gap-1.5 px-3 py-2 focus-within:ring-2 focus-within:ring-emerald-500/40 transition-all duration-200"
        style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }};"
    >
        <template x-for="(pill, index) in pills" :key="index">
            <span class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span x-text="pill"></span>
                <button type="button" @click="remove(index)" class="hover:opacity-70 focus:outline-none">
                    <x-ui.icon name="x-mark" class="w-3 h-3" />
                </button>
            </span>
        </template>

        <input
            type="text"
            x-model="draft"
            @keydown.enter.prevent="add()"
            @keydown.,.prevent="add()"
            @keydown.backspace="if (draft === '' && pills.length) remove(pills.length - 1)"
            @blur="add()"
            {{ $attributes->whereDoesntStartWith('wire:model')->merge([
                'class' => 'flex-1 min-w-[6rem] bg-transparent focus:outline-none text-sm',
                'placeholder' => $placeholder,
            ]) }}
            style="color: var(--text-primary);"
        />
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
