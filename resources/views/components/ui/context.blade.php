@props([
    'width' => '56',
])

{{--
    Menu de contexto (clique com o botão direito). O slot `trigger` é o
    elemento que recebe o clique direito; o slot padrão são as opções do
    menu (normalmente <x-ui.context-item>). O menu aparece ancorado nas
    coordenadas exatas do clique ($event.clientX/clientY) e se ajusta pra
    não vazar da viewport.
--}}

@php
    $widthClass = match($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        '80' => 'w-80',
        default => 'w-56',
    };
@endphp

<div
    x-data="{ open: false, x: 0, y: 0 }"
    @contextmenu.prevent="
        open = true;
        x = $event.clientX;
        y = $event.clientY;
        $nextTick(() => {
            const menu = $refs.contextMenu;
            if (! menu) return;
            const rect = menu.getBoundingClientRect();
            if (x + rect.width > window.innerWidth) x = Math.max(8, window.innerWidth - rect.width - 8);
            if (y + rect.height > window.innerHeight) y = Math.max(8, window.innerHeight - rect.height - 8);
        });
    "
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
    {{ $attributes }}
>
    <!-- Área que recebe o clique direito -->
    <div>
        {{ $trigger ?? '' }}
    </div>

    <!-- Menu de contexto -->
    <div
        x-show="open"
        x-cloak
        x-ref="contextMenu"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-bind:style="`position: fixed; top: ${y}px; left: ${x}px; background-color: var(--bg-surface); border: 1px solid var(--border-color);`"
        class="z-50 {{ $widthClass }} rounded-xl shadow-xl py-1.5"
        role="menu"
        @click="open = false"
    >
        {{ $slot }}
    </div>
</div>
