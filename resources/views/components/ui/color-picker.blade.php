{{--
    Campo de cor: um color picker nativo do navegador ao lado de um input
    de texto (hex) sincronizados, mais uma paleta de swatches rápidos.
    Usado no formulário de artigos para escolher a cor da capa.
--}}
@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => '#00bc7d',
    'swatches' => null,
])

@php
    $modelName = $attributes->wire('model')->value();
    // Tailwind v4 (não v3): red-500, orange-500, amber-500, emerald-500,
    // cyan-500, blue-500, violet-500, pink-500, slate-500, gray-900.
    $defaultSwatches = ['#fb2c36', '#ff6900', '#fe9a00', '#00bc7d', '#00b8db', '#2b7fff', '#8e51ff', '#f6339a', '#62748e', '#101828'];
    $swatchList = $swatches ?? $defaultSwatches;
    $initial = $value ?: '#00bc7d';
@endphp

<div
    x-data="{
        hex: @if($modelName) $wire.entangle('{{ $modelName }}') @else '{{ $initial }}' @endif,
        normalize(val) {
            val = (val ?? '').trim();
            if (val && val[0] !== '#') val = '#' + val;
            return val;
        },
    }"
    class="space-y-1.5"
>
    @if($label)
        <label class="block text-sm font-medium" style="color: var(--text-secondary);">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center gap-2">
        <div
            class="relative shrink-0 w-11 h-11 rounded-xl overflow-hidden"
            style="border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }};"
        >
            <input
                type="color"
                x-model="hex"
                class="absolute -top-2 -left-2 w-[150%] h-[150%] cursor-pointer border-0 p-0 bg-transparent"
                tabindex="-1"
                aria-hidden="true"
            />
        </div>

        <input
            type="text"
            x-model="hex"
            @input="hex = normalize($event.target.value)"
            {{ $attributes->whereDoesntStartWith('wire:model')->merge([
                'class' => 'flex-1 rounded-xl focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40 focus:border-(--focus-ring) transition-all duration-200 px-4 py-2.5 text-sm font-mono ' .
                    ($error ? 'border-red-500/50 focus:ring-red-500/40' : ''),
                'placeholder' => '#10b981',
                'maxlength' => 9,
            ]) }}
            style="background-color: var(--bg-input); border: 1px solid {{ $error ? 'rgba(239, 68, 68, 0.5)' : 'var(--border-color)' }}; color: var(--text-primary);"
        />
    </div>

    @if(!empty($swatchList))
        <div class="flex flex-wrap items-center gap-1.5 pt-1">
            @foreach($swatchList as $swatch)
                <x-ui.tooltip :text="$swatch">
                    <button
                        type="button"
                        @click="hex = '{{ $swatch }}'"
                        class="w-6 h-6 rounded-full transition-transform duration-150 hover:scale-110 border-2"
                        :class="hex.toLowerCase() === '{{ strtolower($swatch) }}' ? 'border-emerald-500 scale-110' : 'border-[var(--border-color)]'"
                        style="background-color: {{ $swatch }};"
                        aria-label="{{ $swatch }}"
                    ></button>
                </x-ui.tooltip>
            @endforeach
        </div>
    @endif

    @if($error)
        <p class="text-xs text-red-400 mt-1.5">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs mt-1.5" style="color: var(--text-muted);">{{ $hint }}</p>
    @endif
</div>
