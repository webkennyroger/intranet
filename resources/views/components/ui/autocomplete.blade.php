@props([
    'label' => null,
    'options' => [],
    'placeholder' => 'Digite para buscar...',
    'error' => null,
    'hint' => null,
])

<div
    x-data="{
        open: false,
        search: '',
        options: @js(array_values($options)),
        get filtered() {
            const term = this.search.trim().toLowerCase();
            if (! term) return this.options;
            return this.options.filter((option) => String(option).toLowerCase().includes(term));
        },
        select(value) {
            this.search = value;
            this.open = false;
            this.$refs.autocompleteInput.value = value;
            this.$refs.autocompleteInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.autocompleteInput.dispatchEvent(new Event('change', { bubbles: true }));
        },
    }"
    x-init="search = $refs.autocompleteInput.value ?? ''"
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
        <input
            type="text"
            x-ref="autocompleteInput"
            autocomplete="off"
            placeholder="{{ $placeholder }}"
            x-on:input="search = $event.target.value; open = true"
            x-on:focus="open = true"
            {{ $attributes->merge([
                'class' => 'w-full rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all duration-200 px-4 py-2.5 text-sm ' .
                    ($error ? 'border-red-500/50 focus:ring-red-500/40' : '')
            ]) }}
            style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }}; color: var(--text-primary);"
        />

        <div
            x-show="open && filtered.length"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto rounded-xl shadow-lg py-1"
            style="background-color: var(--bg-elevated); border: 1px solid var(--border-color);"
        >
            <template x-for="(option, index) in filtered" :key="index">
                <button
                    type="button"
                    @click="select(option)"
                    class="w-full text-left px-4 py-2 text-sm transition-colors duration-150 hover:bg-[var(--bg-hover)]"
                    style="color: var(--text-primary);"
                    x-text="option"
                ></button>
            </template>
        </div>
    </div>

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
