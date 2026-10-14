{{--
    Seletor de cor em formato de paleta de swatches, usado na página
    Aparência para customizar as cores do tema do admin/site. Cada clique
    chama o método Livewire "pick" passando o campo e a cor escolhida; o
    primeiro botão (hachurado) restaura o valor padrão.
--}}
@props([
    'field',
    'label',
    'value',
    'palette',
    'default' => '#0C4A34',
])

<div>
    <span class="block text-sm font-medium text-slate-700 dark:text-(--text-primary) mb-2">{{ $label }}</span>

    <div class="flex flex-wrap gap-2.5 mb-3">
        <button
            type="button"
            wire:click="pick('{{ $field }}', '{{ $default }}')"
            class="w-9 h-9 rounded-lg border-2 transition bg-white dark:bg-(--bg-elevated) {{ $value === $default ? 'border-slate-800 dark:border-(--text-primary) scale-110' : 'border-slate-300 dark:border-(--border-color)' }}"
            style="background-image: repeating-linear-gradient(45deg, #cbd5e1 0, #cbd5e1 1px, transparent 1px, transparent 6px);"
            title="Padrão (restaurar)"
        ></button>

        @foreach ($palette as $swatchLabel => $color)
            <button
                type="button"
                wire:click="pick('{{ $field }}', '{{ $color }}')"
                class="w-9 h-9 rounded-lg border-2 transition {{ $value === $color ? 'border-slate-800 dark:border-(--text-primary) scale-110' : ($color === '#FFFFFF' ? 'border-slate-300 dark:border-(--border-color)' : 'border-transparent') }}"
                style="background-color: {{ $color }};"
                title="{{ $swatchLabel }}"
            ></button>
        @endforeach
    </div>
    @error($field) <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
</div>
