{{--
    Grupo de opções exclusivas estilo "segmented control" (radios escondidos
    + pílula destacando a opção ativa). Componente genérico do kit de UI, ainda
    não usado em nenhuma tela deste projeto.
--}}
@props([
    'options' => [],
    'value' => null,
])

@php
    $wireAttributes = $attributes->whereStartsWith('wire:model');
    $outerAttributes = $attributes->whereDoesntStartWith('wire:model');
@endphp

<div
    {{ $outerAttributes->merge(['class' => 'inline-flex items-center gap-1 rounded-xl p-1']) }}
    style="background-color: var(--bg-elevated); border: 1px solid var(--border-color);"
    role="radiogroup"
>
    @foreach($options as $optionValue => $optionLabel)
        <label class="relative cursor-pointer select-none">
            <input
                type="radio"
                value="{{ $optionValue }}"
                @checked((string) $value === (string) $optionValue)
                {{ $wireAttributes->merge(['class' => 'peer sr-only']) }}
            />

            <span
                class="relative z-10 block whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium text-center transition-colors duration-150 text-(--text-secondary) peer-checked:bg-[var(--admin-primary,var(--brand-primary,#0C4A34))] peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-(--focus-ring)/40"
            >
                {{ $optionLabel }}
            </span>
        </label>
    @endforeach
</div>
