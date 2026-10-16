{{--
    Linha de swatches de cor, usada na página Aparência para customizar as
    cores do tema do admin/site. Cada clique chama o método Livewire "pick"
    passando o campo e a cor escolhida — o botão hachurado restaura o valor
    padrão. A cor selecionada ganha uma marca de check em vez de só mudar de
    tamanho, pra ficar claro qual está ativa mesmo numa olhada rápida.
--}}
@props([
    'field',
    'value',
    'palette',
    'default' => '#0C4A34',
])

<div>
    <div class="flex flex-wrap gap-2.5">
        <button
            type="button"
            wire:click="pick('{{ $field }}', '{{ $default }}')"
            class="relative w-8 h-8 rounded-lg border-2 transition bg-white dark:bg-(--bg-elevated) {{ $value === $default ? 'border-slate-800 dark:border-(--text-primary)' : 'border-slate-200 dark:border-(--border-color) hover:border-slate-400 dark:hover:border-(--text-muted)' }}"
            style="background-image: repeating-linear-gradient(45deg, #cbd5e1 0, #cbd5e1 1px, transparent 1px, transparent 6px);"
            title="Padrão (restaurar)"
        >
            @if ($value === $default)
                <i class="fa-solid fa-check absolute inset-0 flex items-center justify-center text-xs text-slate-700 dark:text-emerald-400"></i>
            @endif
        </button>

        @foreach ($palette as $swatchLabel => $color)
            <button
                type="button"
                wire:click="pick('{{ $field }}', '{{ $color }}')"
                class="relative w-8 h-8 rounded-lg border-2 transition {{ $value === $color ? 'border-slate-800 dark:border-(--text-primary)' : ($color === '#FFFFFF' ? 'border-slate-200 dark:border-(--border-color) hover:border-slate-400' : 'border-transparent hover:brightness-110') }}"
                style="background-color: {{ $color }};"
                title="{{ $swatchLabel }}"
            >
                @if ($value === $color)
                    <i class="fa-solid fa-check absolute inset-0 flex items-center justify-center text-xs" style="color: {{ \App\Models\SiteSetting::readableTextFor($color) }};"></i>
                @endif
            </button>
        @endforeach
    </div>
    @error($field) <p class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
</div>
