@props([
    'position' => 'bottom',
    'align' => 'start',
])

@php
    // Classes de posicionamento do painel em relação ao trigger.
    $positionClasses = match($position) {
        'top' => 'bottom-full mb-2',
        'left' => 'right-full mr-2',
        'right' => 'left-full ml-2',
        default => 'top-full mt-2', // bottom
    };

    // Para top/bottom o alinhamento é horizontal; para left/right é vertical.
    $isVertical = in_array($position, ['top', 'bottom']);

    $alignClasses = $isVertical
        ? match($align) {
            'center' => 'left-1/2 -translate-x-1/2 origin-top',
            'end' => 'right-0 origin-top-right',
            default => 'left-0 origin-top-left', // start
        }
        : match($align) {
            'center' => 'top-1/2 -translate-y-1/2 origin-left',
            'end' => 'bottom-0 origin-bottom-left',
            default => 'top-0 origin-top-left', // start
        };
@endphp

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative inline-block"
    {{ $attributes }}
>
    <!-- Trigger -->
    <div @click="open = !open">
        {{ $trigger ?? '' }}
    </div>

    <!-- Popover Panel -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 w-80 {{ $positionClasses }} {{ $alignClasses }} rounded-xl shadow-xl"
        style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
        role="dialog"
    >
        <!-- Botão de fechar explícito (o Popover não fecha ao clicar em conteúdo interno, só nisto ou fora) -->
        <button
            type="button"
            @click="open = false"
            class="absolute top-2.5 right-2.5 p-1 rounded-lg transition-colors"
            style="color: var(--text-secondary);"
            onmouseover="this.style.color='var(--text-primary)'"
            onmouseout="this.style.color='var(--text-secondary)'"
            aria-label="Fechar"
        >
            <x-ui.icon name="x-mark" class="w-4 h-4" />
        </button>

        <div class="px-4 py-4 pr-10">
            {{ $slot }}
        </div>
    </div>
</div>
